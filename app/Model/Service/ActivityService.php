<?php
/**
 * description UserService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/18 16:59
 */

namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityDotStockDao;
use App\Model\Dao\ActivityMemberDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\CardRecordDao;
use App\Model\Dao\DotDao;
use App\Model\Dao\UserDao;
use App\Utils\Check;
use Firebase\JWT\JWT;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;

/**
 * 活动逻辑
 * Class ActivityService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class ActivityService
{

    /**
     * @Inject()
     * @var ActivityDao
     */
    private $activityDao;

    /**
     * @Inject()
     * @var ActivityProductDao
     */
    private $activityProductDao;

    /**
     * @Inject()
     * @var ActivityDotStockDao
     */
    private $activityDotStockDao;

    /**
     * @Inject()
     * @var ActivityMemberDao
     */
    private $activityMemberDao;


    /**
     * 获取活动列表
     */
    public function getList($params){

       return $this->activityDao->getList($params);

    }

    /**
     * 会员端
     * @param $params
     * @return array
     */
    public function getList1($params){

        return $this->activityDao->getList1($params);

    }

    /**
     * 获取商品详情
     * @param $params
     */
    public function getProductdetails($params){

        $activity_id = $params['activity_id'];
        $product_id =$params['product_id'];
        $grade_id =$params['grade_id'];

        if (empty($activity_id)) throw new ApiException('活动ID为空');
        if (empty($product_id)) throw new ApiException('商品ID为空');
        if (empty($grade_id)) throw new ApiException('档次ID不能为空');

        $data = $this->activityProductDao->getMemberActivityProductById($activity_id,$product_id,$grade_id);
       if (empty($data)) throw new ApiException('商品数据异常');

       return $data;

    }

    /**
     * 根据网点，区域，活动ID查询库存
     */
    public function getProductStock($params){
        $activity_id = $params['activity_id'];
        $region_id =$params['region_id'];
        $dot_id =$params['dot_id'];

        if (empty($activity_id)) throw new ApiException('活动ID为空');
        if (empty($dot_id)) throw new ApiException('网点ID为空');
        if (empty($region_id)) throw new ApiException('档次ID不能为空');

       $data =$this->activityDotStockDao->getDotProductStock($activity_id,$dot_id,$region_id);

        $stock=[];
       foreach ($data as $key=>$v){
           $stock[$v['product_id']]=$v['surplus_stock_num'];
       }

       return $stock;
    }

    /**
     * 根据活动ID获取活动下等级下所有商品
     */
    public function activityDetails($params){

        $activity_id = $params['activity_id'];
        $token =$params['dot_token'];
        if (empty($activity_id)) throw new ApiException('活动ID为空');
        if (empty($token)) throw new ApiException('获取登录信息失败:token');

        //1，获取活动基本信息
        $activity_data =  $this->activityDao->getOndByCode($activity_id);

        if ($activity_data['start_time']>time())  throw new ApiException('活动未开始');

        //if ($activity_data['end_time']<=time())  throw new ApiException('活动已结束');

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        //2，获取等级商品列表
        $activity_product_data =$this->activityProductDao->getList($activity_id,$dot_data['dot_id']);

        $activity_data=[
            'activity_data'=>$activity_data,
            'activity_product_data'=>$activity_product_data
            ];

        return $activity_data;
    }

    /**
     * 会员端获取活动详情商品列表（）
     */
    public function memberActivityDetails($params){
        $token=$params['member_token'];

        $activity_id = $params['activity_id'];

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');


        if (empty($activity_id)) throw new ApiException('活动ID为空');

        ///验证会员是否已经领取过当前等级商品
        $product_status = $this->activityMemberDao->getActivityProductStatus1($activity_id,$member_data['unique_code']);

        //if (empty($product_status)) throw new ApiException('当前活动您没有可领取的权益');

        //1，获取活动基本信息
        $activity_data =  $this->activityDao->getOndByCode($activity_id);
        if ($activity_data['start_time']>time())  throw new ApiException('活动未开始');

        if ($activity_data['end_time']<=time())  throw new ApiException('活动已结束');

        $activity_data['product_status']=$product_status;

        //2，获取等级商品列表
        $activity_product_data =$this->activityProductDao->getMemberList($activity_id);

        $activity_data_res=[
            'activity_data'=>$activity_data,
            'activity_product_data'=>$activity_product_data,
        ];

        return $activity_data_res;
    }

    /**
     * 我的权益
     */
    public function getMyEquity($params){

        $token=$params['member_token'];

        //$activity_id = $params['activity_id'];

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        //if (empty($activity_id)) throw new ApiException('活动ID为空');

        return $this->activityMemberDao->getMyEquity($member_data['unique_code']);
    }

    /**
     * 根据活动ID和商品ID生成二维码
     * @param $params
     */
    public function activityQrCodeUrl($params){

        $activity_id = $params['activity_id'];
        $product_id =$params['product_id'];
        $grade_id =$params['grade_id'];
        $token=$params['dot_token'];

        if (empty($activity_id)) throw new ApiException('活动ID为空');
        if (empty($product_id)) throw new ApiException('商品ID为空');
        if (empty($grade_id)) throw new ApiException('档次ID不能为空');

        //1，获取活动基本信息
        $activity_data =  $this->activityDao->getOndByCode($activity_id);

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);

        if (empty($dot_data)) throw new ApiException('登录已过期');

        $dot_data = json_decode($dot_data,true);

        //2,获取活动对应的商品
        if ($activity_data['activity_type']==1){//线上
            $activity_product_data=$this->activityProductDao->getProductById($activity_id,$product_id,$grade_id);

        }else{//线下

            $activity_product_data=$this->activityProductDao->getProductDotById($activity_id,$product_id,$dot_data['dot_id'],$grade_id);

           /* if ($activity_data['member_source']==2){
                //验证预算是否还有剩余
                if ($activity_data['use_activity_moeny']+$activity_product_data['settlement_price']>$activity_data['activity_moeny']-$activity_data['use_activity_moeny'])
                    throw new ApiException('活动预算不足，无法下单');
            }

            //验证库存是否足够
            if ($activity_product_data['surplus_stock_num']<=0) throw new ApiException('活动商品库存不足');*/

        }

        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for($i = 0; $i < 20; $i++)
        {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        //生成二维码缓存的唯一标识
        $activity_code =md5($activity_id.$product_id.time()).randNum(4).$str;

        //生成二维码随机标识
        $url=config('web_url').'?activity_code='.$activity_code;

        //缓存二维码信息
        $activity_data_info['activity_id']=$activity_id;
        $activity_data_info['product_id']=$product_id;
        $activity_data_info['grade_id']=$grade_id;//档次主表
        $activity_data_info['region_id']=$dot_data['region_id'];//所属区域ID
        $activity_data_info['dot_id']=$dot_data['dot_id'];//网点
        $exp=config('jwt.member_time_exp');

        //缓存授权码
        Redis::setex(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code,$exp,json_encode($activity_data_info));

       $url1 = $this->scerweima2($url);

        $data=[
            'activity_data'=>$activity_data,
            'activity_product_data'=>$activity_product_data,
            'url'=>$url1
        ];
        return $data;

    }

    /**
     * 生成二维码
     * @param string $url
     */
    function scerweima2($url='')
    {
        require_once dirname(__DIR__) . '/../Utils/phpqrcode.php';

        $value = $url;					//二维码内容

        $errorCorrectionLevel = 'L';	//容错级别
        $matrixPointSize = 5;			//生成图片大小

        $filename_txt=microtime().'.png';
        //生成二维码图片
        $filename = dirname(__DIR__) .'/../../qrcode/'.$filename_txt;
       \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);

        $QR = $filename;//已经生成的原始二维码图片文件

        $QR = \imagecreatefromstring(file_get_contents($QR));

        //输出图片
        imagepng($QR, 'qrcode.png');
        imagedestroy($QR);

        return config('images_url').'/'.$filename_txt;
    }
}
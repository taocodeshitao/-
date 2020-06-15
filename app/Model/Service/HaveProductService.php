<?php
namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityMemberDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\GradeApiConfigDao;
use App\Model\Dao\GradeDao;
use App\Model\Dao\HaveProductDao;
use App\Model\Dao\HaveProductLogDao;
use App\Model\Dao\MemberDao;
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
 * 网点自有商品主表
 * Class HaveProductService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class HaveProductService
{

    /**
     * @Inject()
     * @var HaveProductDao
     */
    private $haveProductDao;

    /**
     * @Inject()
     * @var HaveProductLogDao
     */
    private $haveProductLogDao;

    /**
     * 行内商品列表
     * @param $params
     */
    public function getHaveProductList($params){

        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];
        if (empty($params['page'])) throw new ApiException('分页参数异常');

        return $this->haveProductDao->getStockage($dot_id, $params['page'],$params);

    }

    /**
     * 行内商品导出
     * @param $params
     */
    public function getHaveProductExport($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];

        $data =  $this->haveProductDao->getStockExport($dot_id,$params);


       // $this->output_excel($data);
    }

    /**
     * 新商品添加
     */
    public function haveProductAdd($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];
        if (empty($params['name']))throw new ApiException('商品名称为空');
        if (empty($params['stock']))throw new ApiException('商品库存为空');
        //验证名称是重复
       $name = $this->haveProductDao->getProductName($params['name']);
        if (!empty($name)) throw new ApiException('商品名称重复');

        $product_data=[
            'name'=>$params['name'],
            'dot_id'=>$dot_id,
            'stock'=>$params['stock'],
            'surplus_stock'=>$params['stock'],
            'created_at'=>time()
        ];
        $have_product_id = $this->haveProductDao->addData($product_data);

        //添加日志
        $haveProductLogData=[
            'have_product_id'=>$have_product_id,
            'type'=>0,
            'num'=>$params['stock'],
            'user_id'=>$dot_data['id'],
            'member_id'=>0,
            'created_at'=>time(),
        ];
         $this->haveProductLogDao->addData($haveProductLogData);

         return [];
    }


    /**
     * 新增库存
     *
     */
    public function addProductStock($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];
        if (empty($params['id'])) throw new ApiException('ID参数为空');
        if (empty($params['stock']))throw new ApiException('商品库存为空');

        //验证数据是否正常
        $have_product = $this->haveProductDao->getProdictById($params['id']);
        if (empty($have_product)) throw new ApiException('数据异常');

        $updata_data=[
            'stock'=>$have_product['stock']+(int)$params['stock'],
            'surplus_stock'=>$have_product['surplus_stock']+(int)$params['stock'],
            'updated_at'=>time(),
        ];
        $this->haveProductDao->updateData($params['id'],$updata_data);


        //添加日志
        $haveProductLogData=[
            'have_product_id'=>$params['id'],
            'type'=>0,
            'num'=>(int)$params['stock'],
            'user_id'=>$dot_data['id'],
            'member_id'=>0,
            'created_at'=>time(),
        ];
        $this->haveProductLogDao->addData($haveProductLogData);
        return [];

    }

    /**
     * 删除商品
     */
    public function delProduct($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];
        if (empty($params['id'])) throw new ApiException('ID参数为空');

        $this->haveProductDao->updateData($params['id'],['is_del'=>1]);

        return [];
    }

    /**
     * 库存变动记录
     */
    public function stockLog($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $dot_id=$dot_data['dot_id'];
        if (empty($params['page'])) throw new ApiException('分页参数异常');

        return $this->haveProductLogDao->getStockage($dot_id, $params['page'],$params);

    }

    /**
     * 库存变动记录详情
     */
    public function stockLogDetails($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        $id =$params['id'];

        return $this->haveProductLogDao->getStockDetails($id);
    }
    /**
     * 商品出库生成二维码
     */
    public function haveProductOutbound($params){
        $token=$params['dot_token'];

        //获取登录信息
        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $user_id=$dot_data['id'];
        $id=$params['id'];

        $have_product = $this->haveProductDao->getProdictById($params['id']);
        if (empty($have_product)) throw new ApiException('数据异常');


        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for($i = 0; $i < 20; $i++)
        {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        //生成二维码缓存的唯一标识
        $activity_code =md5($user_id.$id.time()).randNum(4).$str;

        //生成二维码随机标识
        $url=config('web_url').'?have_code='.$activity_code;

        //缓存二维码信息
        $activity_data_info['user_id']=$user_id;
        $activity_data_info['id']=$id;
        $activity_data_info['dot_id']=$dot_data['dot_id'];//网点
        $exp=config('jwt.member_time_exp');

        //缓存授权码
        Redis::setex(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$activity_code,$exp,json_encode($activity_data_info));

        $url1 = $this->scerweima2($url);

        $data=[
            'have_product'=>$have_product,
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

    /**
     * 会员扫描领取
     */
    public function memberReceive($params){

        $have_code=$params['have_code'];
        $have_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$have_code);

        $have_data = json_decode($have_data,true);
        if (empty($have_data)) throw new ApiException('二维码已失效');

        $product_have_data = $this->haveProductDao->getProdictById($have_data['id']);
        if (empty($product_have_data)) throw new ApiException('数据异常');

        return $product_have_data;

    }

    /**
     * 会员扫描确认领取
     * @return HaveProductDao
     */
    public function memberConfirmReceive($params)
    {
        $have_code=$params['have_code'];
        $have_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$have_code);

        $have_data = json_decode($have_data,true);
        if (empty($have_data)) throw new ApiException('二维码已失效');

        $product_have_data = $this->haveProductDao->getProdictById($have_data['id']);
        if (empty($product_have_data)) throw new ApiException('数据异常');


        $token=$params['member_token'];

        //$activity_id = $params['activity_id'];

        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);

        if (empty($member_data)) throw new ApiException('会员信息查询失败');


        $product_data=[
            'surplus_stock'=>$product_have_data['surplus_stock']-1,
        ];

        $this->haveProductDao->updateData($have_data['id'],$product_data);

        //添加日志
        $haveProductLogData=[
            'have_product_id'=>$have_data['id'],
            'type'=>1,
            'num'=>1,
            'user_id'=>$have_data['id'],
            'member_id'=>$member_data['member_id'],
            'created_at'=>time(),
        ];
        $this->haveProductLogDao->addData($haveProductLogData);

        //删除缓存信息
        Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':ACTIVITY:INFO:'.$have_code);

        return [];

    }
}
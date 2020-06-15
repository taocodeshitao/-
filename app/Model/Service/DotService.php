<?php
/**
 * description UserService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/18 16:59
 */

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityDotStockDao;
use App\Model\Dao\ActivityDotStockLogDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\ActivityStockLogDao;
use App\Model\Dao\DotDao;
use App\Model\Dao\RegionDao;
use App\Model\Dao\UserDao;
use Firebase\JWT\JWT;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Db\DB;
use Swoft\Redis\Redis;

/**
 * 网点操作逻辑
 * Class DotService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class DotService
{

    /**
     * @Inject()
     * @var DotDao
     */
    private $dotDao;

    /**
     * @Inject()
     * @var UserDao
     */
    private $userDao;

    /**
     * @Inject()
     * @var ActivityDao
     */
    private $activityDao;

    /**
     * @Inject()
     * @var RegionDao
     */
    private $regionDao;

    /**
     * @Inject()
     * @var ActivityDotStockDao
     */
    private $activityDotStockDao;

    /**
     * @Inject()
     * @var ActivityProductDao
     */
    private $activityProductDao;

    /**
     * @Inject()
     * @var ActivityDotStockLogDao
     */
    private $activityDotStockLogDao;

    /**
     * @Inject()
     * @var ActivityStockLogDao
     */
    private $activityStockLogDao;

    /**
     * 网点客户登录
     * @param int $user_id
     * @param string $account_$password
     * @return  array
     * @throws ApiException
     */
    public  function login($params)
    {
        if (empty($params['account'])||empty($params['password'])) throw new ApiException('用户名或者密码为空');

        //验证用户信息是否存在
        $data =$this->dotDao->getAccountStatus($params['account'],$params['password']);
        if (empty($data)) throw new ApiException('用户名或者密码错误');

        $secret_key = config('jwt.secret_key');
        $exp = intval(config('jwt.exp'));
        $type = config('jwt.type');

        //生产网点token
        $payLoad = [
            'dot_id' =>$data['id'],
            'iat'  => time(),
            'exp'  => time() + $exp
        ];
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for($i = 0; $i < 20; $i++)
        {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        //生成加密token
        $token =JWT::encode($payLoad,$secret_key,$type).$str;

        //缓存授权码
        Redis::setex(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token,$exp,json_encode($data));

        return ['token'=>$token,'role_id'=>$data['role_id']];
    }

    /**
     * 获取客户经理基本信息
     * @param $params
     */
    public function getDotInfo($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户基本信息信息异常');

        $data =$this->dotDao->getDotInfo($dot_data['id']);

        return $data;
    }

    /**
     * 修改密码
     */
    public function dotEditPassword($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户基本信息信息异常');

        if (empty($params['password'])||empty($params['new_password'])) throw new ApiException('密码参数为空');

        //原密码
        $dot_data1 = $this->dotDao->getDotInfo($dot_data['id']);
        if ($dot_data1['password']!=md5(json_encode($params['password']))) throw new ApiException('原密码错误');

        //执行修改操作
         $this->dotDao->updatePassword($dot_data['id'],$params['new_password']);

         //删除登录信息
        Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);

        return [];
    }

    /**
     * 退出登录
     */
    public function dotOutLogin($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户基本信息信息异常');

        //删除登录信息
        Redis::del(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);

        return [];
    }


    /**
     * 获取选择过的所有区域
     */
    public function getDotList(){

        $getRegionId =$this->dotDao->getRegionId();
        $region_id_data = array_column($getRegionId,'region_id');

        $region_id_str='';
        foreach ($region_id_data as $key=>$v){
            $region_id_str=$region_id_str.$v.',';
        }
        $region_id_str =rtrim($region_id_str, ",");

        return $this->regionDao->getList($region_id_str);
    }

    /**
     * 根据区域ID查询网点
     */
    public function getRegionDotList($params){
       $region_id =  $params['region_id'];
       if (empty($region_id)) throw new ApiException('区域ID为空');

       return $this->dotDao->getRegionList($region_id);

    }

    /**
     * 库存管理员工作台
     */
    public function getStockInfo($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');

        //获取支行名称
        $data =$this->dotDao->getDotInfo($dot_data['id']);

        //获取支行下商品出库入库情况
        $stock_num_data = $this->activityDotStockDao->getDotStockNum($dot_data['dot_id']);

        $data['receive_stock_num']=$stock_num_data['receive_stock_num'];
        $data['recovery_stock_num']=$stock_num_data['recovery_stock_num'];

        return $data;
    }

    /**
     * 库存管理员对商品入库确认列表
     *
     */
    public function dotStockConfirmAddList($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');

        //根据商品分组，获取所有待确认入库商品
       return $this->activityDotStockDao->getDotStockConfirmAddLsit($dot_data['dot_id']);

    }

    /**
     * 库存管理员对商品出库确认列表
     */
    public function dotStockConfirmOutList($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');

        //根据商品分组，获取所有待确认入库商品
        return $this->activityDotStockDao->dotStockConfirmOutList($dot_data['dot_id']);

    }

    /**
     * 库存管理员对商品入库确认
     */
    public function dotStockConfirmAdd($params){

        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');
        if (empty($params['id'])) throw new ApiException('入库商品参数异常');
        if (empty($params['product_id'])) throw new ApiException('入库商品ID参数异常');

        $id =  $params['id'];
        $product_id =  $params['product_id'];

        $activity_dot_stock_log_id = $params['activity_dot_stock_log_id'];

        try {
            DB::beginTransaction();

            //获取需要入库的库存
            $DotStockData = $this->activityDotStockDao->getActivityDotStock($id);
            if ($DotStockData['receive_stock_num']<=0) throw new ApiException('需要入库的库存为0，入库失败');

            //将需要入库的商品库存入库
            $this->activityDotStockDao->updateReceiveStockNum($id,$DotStockData['receive_stock_num']);

            //记录日志
            $this->activityDotStockLogDao->update($activity_dot_stock_log_id,['receive_num'=>$DotStockData['receive_stock_num'],'warehousing_user_id'=>$dot_data['id'],'warehousing_time'=>time()]);

            //添加列表日志
           /* $activityStockLogdata=[
                'product_id'=>$product_id,
                'dot_id'=>$dot_data['dot_id'],
                'activity_id'=>$DotStockData['activity_id'],
                'type'=>1,
                'num'=>$DotStockData['recovery_stock_num'],
                'is_comfirmation'=>1,
                'confirmation_time'=>time(),
                'user_id'=>$dot_data['id'],
                'created_at'=>time()
            ];
            $this->activityStockLogDao->addData($activityStockLogdata);*/

            $this->activityStockLogDao->updateStatus($product_id,$dot_data['dot_id'],$DotStockData['activity_id'],$dot_data['id']);

            //支行库存出入库日志
            $this->activityDotStockLogDao->updateReceive($DotStockData['activity_id'],$dot_data['dot_id'],$product_id,['warehousing_user_id'=>$dot_data['id'],'warehousing_time'=>time()]);

            DB::commit();

        return [];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    public function test1(){}
    public function test2(){}
    public function test3(){}

    /**
     *库存管理员对商品出库确认
     */
    public function dotStockConfirmOut111($params){
        echo "11";
        return [];
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');
        if (empty($params['id'])) throw new ApiException('入库商品参数异常');
        if (empty($params['product_id'])) throw new ApiException('入库商品ID参数异常');

        $id =  $params['id'];
        $product_id =  $params['product_id'];
        $activity_dot_stock_log_id = $params['activity_dot_stock_log_id'];

        try {
            DB::beginTransaction();
            //获取需要出库的库存
            $DotStockData = $this->activityDotStockDao->getActivityDotStock($id);
            if ($DotStockData['recovery_stock_num']<=0||$DotStockData['recovery_stock_num']>$DotStockData['surplus_stock_num']) throw new ApiException('需要入库的库存为0，入库失败');

            //将需要入库的商品库存入库
            $this->activityDotStockDao->updateRecoveryStockNum($id,$DotStockData['recovery_stock_num']);

            //添加日志
            //var_dump($activity_dot_stock_log_id,['recovery_confirm_num'=>$DotStockData['recovery_stock_num'],'out_of_stock_user_id'=>$dot_data['id'],'out_of_stock_time'=>time()]);
            $this->activityDotStockLogDao->update($activity_dot_stock_log_id,['recovery_confirm_num'=>$DotStockData['recovery_stock_num'],'out_of_stock_user_id'=>$dot_data['id'],'out_of_stock_time'=>time()]);
            $activity_dot_stock_log_data =$this->activityDotStockLogDao->getById($activity_dot_stock_log_id);

            //添加列表日志
            //出库日志
            $activityStockLogdata=[
                'product_id'=>$product_id,
                'activity_id'=>$DotStockData['activity_id'],
                'type'=>4,
                'num'=>$DotStockData['recovery_stock_num'],
                'outofstock_dot_id'=>$dot_data['dot_id'],
                'is_comfirmation'=>1,
                'dot_id'=>1,//1$dot_data['dot_id'],
                'confirmation_time'=>time(),
                'sponsor'=>$activity_dot_stock_log_data['recovery_user_id'],
                'identify_people'=>$dot_data['id'],
                'created_at'=>time()
            ];
            $this->activityStockLogDao->addData($activityStockLogdata);

            //入库日志
            $activityStockLogdata1=[
                'product_id'=>$product_id,
                'dot_id'=>1,
                'activity_id'=>$DotStockData['activity_id'],
                'type'=>3,
                'num'=>$DotStockData['recovery_stock_num'],
                'is_comfirmation'=>0,
                'sponsor'=>$dot_data['id'],
                'outofstock_dot_id'=>$dot_data['dot_id'],
                'confirmation_time'=>time(),
                'created_at'=>time()
            ];
            $this->activityStockLogDao->addData($activityStockLogdata1);

            //支行库存出库日志
            //$this->activityDotStockLogDao->outReceive($DotStockData['activity_id'],$dot_data['dot_id'],$product_id,['out_of_stock_user_id'=>$dot_data['id'],'out_of_stock_time'=>time()]);


            DB::commit();

            return [];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 商品报损
     */
    public function dotConfirmLoss($params){

        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        $product_id=  $params['product_id'];
        $activity_id =  $params['activity_id'];
        $loss_num =  $params['loss_num'];
        $content =  $params['content'];

        if (empty($dot_data['id'])) throw new ApiException('获取用户登录基本信息异常');
        if (empty($activity_id)||empty($loss_num)) throw new ApiException('参数异常');

        //获取一条基本数据
        $dotStockData = $this->activityDotStockDao->getActivityDotStockItem($product_id,$activity_id);

        if ($dotStockData['surplus_stock_num']<$loss_num) throw new ApiException('剩余库存小于报损库存，操作失败');

        $id=$dotStockData['id'];

        try {
            DB::beginTransaction();

            //报损减去剩余库存
            $this->activityDotStockDao->lossStockNum($id,$activity_id,$loss_num);

            //添加日志
            $data_add=[
                'activity_dot_stock_id'=>$activity_id,
                'type'=>3,
                'content'=>$content,
                'loss_user_id'=>$dot_data['id'],
                'loss_time'=>time(),
                'created_at'=>time(),
                'loss_num'=>$loss_num,
            ];
            $this->activityDotStockLogDao->addData($data_add);

            //添加库存变动日志
            $activity_stock_log_data=[
                'activity_id'=>$activity_id,
                'product_id'=>$product_id,
                'dot_id'=>$dot_data['dot_id'],
                'type'=>6,
                'damage_reason'=>$content,
                'sponsor'=>$dot_data['id'],
                'num'=>$loss_num,
                'created_at'=>time(),
                'is_comfirmation'=>1,
                'identify_people'=>$dot_data['id'],
            ];
            $this->activityStockLogDao->addData($activity_stock_log_data);

            //$this->activityDotStockLogDao->update1($dotStockData['id'],['loss_user_id'=>$dot_data['id'],'loss_num'=>$loss_num,'loss_time'=>time()]);

            DB::commit();

            return [];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }

    /**
     * 商品库存列表
     */
    public function dotProductStockList($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

        $params['dot_id'] =$dot_data['dot_id'];

        return $this->activityProductDao->getProductStockList($params);
    }

    /**
     * 商品库存详情
     */
    public function dotProductStockDetails($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);
        if (empty($params['product_id'])) throw new ApiException('商品ID异常');

        $params['dot_id'] =$dot_data['dot_id'];
        $data = $this->activityProductDao->getProductStockDetails($params);

        //查询所有的活动信息和剩余库存
        $activity_data_list=[];
        foreach ($data['activity_data'] as $key=>$v){
           $activity_data_info= $this->activityDao->getOndByCode($v['activity_id']);
           //根据活动ID和商品ID查询活动下该商品剩余的库存
           $num = $this->activityProductDao->getActivityProductCount($v['activity_id'],$params['product_id']);

            $activity_data_info['stokc_num']=$num;
            $activity_data_list[]=$activity_data_info;
        }
        $data['activity_list']=$activity_data_list;

        return $data;
    }


    /**
     *库存变动记录列表
     */
    public function getStockChangeList($params){
        $token = $params['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);
        $dot_data = json_decode($dot_data,true);

       return $this->activityStockLogDao->getAllPage($dot_data['dot_id'],$params['page'],$params);

    }

    /**
     *变动明细（入库）
     */
    public function getStockChangeDetails($params){

        if (empty($params['id'])) throw new ApiException('参数异常');

        $data = $this->activityStockLogDao->getStockChangeDetails($params['id']);

        //查询发起人
        if ($data['recovery_user_id']>0){
            $data['recovery_user_name']=$this->userDao->findById($data['recovery_user_id'])['name'];
        }

        //库存分配人
        if ($data['receive_user_id']>0){
            $data['receive_user_name']=$this->userDao->findById($data['receive_user_id'])['name'];
        }

        //入库确认管理员
        if ($data['warehousing_user_id']>0){
            $data['warehousing_user_name']=$this->userDao->findById($data['warehousing_user_id'])['name'];
        }

        //回收确认人
        if ($data['recovery_confirm_user_id']>0){

            $data['recovery_confirm_user_name']=$this->userDao->findById($data['recovery_confirm_user_id'])['name'];

        }

        //出库确认管理员关联表u
        if ($data['out_of_stock_user_id']>0){
            $data['out_of_stock_user_name']=$this->userDao->findById($data['out_of_stock_user_id'])['name'];
        }

        //报损管理员
         if ($data['loss_user_id']>0){
             $data['loss_user_id']=$this->userDao->findById($data['loss_user_id'])['name'];
         }

        return $data;

    }

    /**
     * 变动明细（订单出库）
     */
    public function getStockChangeOrderDetails($params){
        if (empty($params['id'])) throw new ApiException('参数异常');

        $data = $this->activityStockLogDao->getStockChangeOrderDetails($params['id']);

        $data['confirm_order_user_name']=$this->userDao->findById($data['confirm_order_user_id'])['name'];
        return $data;
    }
}
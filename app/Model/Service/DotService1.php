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
class DotService1
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
     *库存管理员对商品出库确认
     */
    public function dotStockConfirmOut111($params){

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
            //$this->activityStockLogDao->addData($activityStockLogdata);

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
           // $this->activityStockLogDao->addData($activityStockLogdata1);
        //修改回收确认
            $this->activityStockLogDao->updateRecoveryStatus($product_id,$dot_data['dot_id'],$DotStockData['activity_id'],$dot_data['id']);
            //支行库存出库日志
            //$this->activityDotStockLogDao->outReceive($DotStockData['activity_id'],$dot_data['dot_id'],$product_id,['out_of_stock_user_id'=>$dot_data['id'],'out_of_stock_time'=>time()]);


            DB::commit();

            return [];
        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }
    }
}
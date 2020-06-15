<?php declare(strict_types=1);


namespace App\Crontab;

use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityDotStockDao;
use App\Model\Dao\ActivityWaresDao;
use App\Model\Dao\OrderDao;
use App\Model\Dao\OrderLogDao;
use App\Model\Dao\OrderLogisticDao;
use App\Model\Dao\OrderWaresDao;
use App\Model\Data\SystemCache;
use App\Model\Service\OrderService;
use function Couchbase\defaultDecoder;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Crontab\Annotaion\Mapping\Cron;
use Swoft\Crontab\Annotaion\Mapping\Scheduled;
use Swoft\Db\DB;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Collection;

/**
 * Class CronTask
 *
 * @since 2.0
 *
 * @Scheduled()
 */
class CronTask
{

    /**
     * @Inject()
     * @var SystemCache
     */
    private  $systemCache;

    /**
     * @Cron("* * 23 * * *")
     * 删除三天前生成的二维码图片(每秒检测一次)
     */
    public function delQrcode(){
        $folder='/zhlyapi/qrcode/';
        $time=3600*24*3;
        $ext=array('php','htm','html'); //带有这些扩展名的文件不会被删除.
        $o=opendir($folder);
        while($file=readdir($o)){
            if($file !='.' && $file !='..' && !in_array(substr($file,strrpos($file,'.')+1),$ext)){
                $fullPath=$folder.'/'.$file;
                if(is_dir($fullPath)){
                    trash($fullPath);
                    @rmdir($fullPath);
                } else {
                    if(time()-filemtime($fullPath) > $time){
                        unlink($fullPath);
                    }
                }
            }
        }
        closedir($o);
    }

    /**
     * @Cron("0/10 * * * * *")
     * 没10秒检测线下订单超24小时没有领取商品，自动返还库存
     */
    public function cancelOrderStock(){

        /** @var OrderDao $orderDao */
        $orderDao = \Swoft::getBean(OrderDao::class);

        $order_data = $orderDao->getOrderLimit();
        if (empty($order_data)) return true;

        /** @var ActivityDotStockDao  $activityDotStockDao*/
        $activityDotStockDao = \Swoft::getBean(ActivityDotStockDao::class);

        /** @var OrderLogDao  $orderLogDao */
        $orderLogDao = \Swoft::getBean(OrderLogDao::class);


        foreach ($order_data as $v){

            //修改订单状态
            $orderDao->updateData(['id'=>$v['id']],['status'=>3]);

            //返还库存
            $activityDotStockDao->incrementOrderStock($v['activity_id'],$v['dot_id'],$v['product_id']);

            //记录订单日志
            $orderLogData=[
                'order_id'=>$v['id'],
                'content'=>'订单超24小时未发放，自动取消订单'.$v['sn'],
                'type'=>4,
                'member_id'=>0,
                'admin_user_id'=>0,
                'created_at'=>time(),
            ];
            $orderLogDao->addData($orderLogData);
        }
        return true;
    }
}

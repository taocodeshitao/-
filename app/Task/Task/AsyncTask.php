<?php declare(strict_types=1);

namespace App\Task\Task;

use App\Common\Cache;
use App\Model\Dao\ActivityDotStockDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\ActivityProductStockDao;
use App\Model\Service\CardService;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;
use Swoft\Task\Annotation\Mapping\Task;
use Swoft\Task\Annotation\Mapping\TaskMapping;

/**
 * 异步投递任务
 * Class AsyncTask
 *
 * @Task(name="asyn")
 */
class AsyncTask
{
    /**
     * 线下扣减库存
     * @TaskMapping(name="reduce")
     * @param string
     * @return bool
     */
    public  function reduce($activity_id,$product_id,$dot_id,$region_id)
    {
        if(empty($activity_id)||empty($product_id)) return true;


        /** @var ActivityDotStockDao $activityDotStockDao */
        $activityDotStockDao  = \Swoft::getBean(ActivityDotStockDao::class);
        $activityDotStockDao =  $activityDotStockDao->getDotStock($activity_id,$dot_id,$region_id,$product_id);

        if (!empty($activityDotStockDao)){

            $activityDotStockDao->getReduceStock($activityDotStockDao['id']);
        }

        return true;
    }

    /**
     * 线上会员修改领取状态
     *
     * @TaskMapping(name="updateMemberStatus")
     */

    public function updateMemberStatus($unique_code,$order_id){

    }
}

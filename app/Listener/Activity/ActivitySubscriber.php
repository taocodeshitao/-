<?php declare(strict_types=1);


namespace App\Listener\Activity;


use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityWaresDao;
use App\Model\Data\ActivityCache;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 活动
 * Class ActivitySubscriber
 *
 * @Subscriber()
 */
class ActivitySubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var ActivityWaresDao
     */
    protected  $activityWaresDao;

    /**
     * @Inject()
     * @var ActivityCache
     */
    private  $activityCache;

    public static function getSubscribedEvents():array
    {

        return [
                  Event::ACTIVITY_INVENTORY_REMOVE =>'handleInventoryEvent',
               ];
    }

    public  function handleInventoryEvent(EventInterface $event)
    {

        $code = $event->getParam('code');

        //获取活动信息
        $activity_info = $this->activityCache->getBaseCache($code);

        //更新活动库存
        $result = $this->activityWaresDao->updateInventoryByDecrement($activity_info['id'],$event->getParam('wares_id'), $event->getParam('num'));

        if($result===false) throw new ApiException('下单失败');
    }

}
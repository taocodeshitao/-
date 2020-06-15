<?php declare(strict_types=1);


namespace App\Listener\User;


use App\Listener\Event;
use App\Model\Data\CartCache;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;

/**
 * 兑换袋
 * Class CartSubscriber
 *
 * @Subscriber()
 */
class CartSubscriber implements EventSubscriberInterface
{

    /**
     * @Inject()
     * @var CartCache
     */
    protected  $cartCache;

    public static function getSubscribedEvents():array
    {

        return [
                  Event::CART_PRODUCT_ADD=>'handleAddEvent',

                  Event::CART_PRODUCT_REMOVE=>'handleRemoveEvent',

                  Event::CART_PRODUCT_UPDATE_NUM=>'handleNumEvent'
               ];
    }

    public  function handleAddEvent(EventInterface $event)
    {

        $this->cartCache->addProductCache($event->getParam('user_id'),$event->getParam('sku'),

            $event->getParam('num'));
    }


    public  function handleRemoveEvent(EventInterface $event):void
    {

        $this->cartCache->removeProductCache($event->getParam('user_id'),$event->getParam('sku_list'));
    }


    /**
     * @param EventInterface $event
     */
    public  function handleNumEvent(EventInterface $event):void
    {

        $this->cartCache->updateNumCache($event->getParam('user_id'),$event->getParam('sku'),

            $event->getParam('num'));
    }
}
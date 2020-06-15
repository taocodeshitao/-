<?php declare(strict_types=1);

namespace App\Listener;

 class Event
{
    /******************************兑换袋********************************/
     // 新增商品
    const CART_PRODUCT_ADD = 'cart:product:add';

     // 移除商品
    const CART_PRODUCT_REMOVE = 'cart:product:remove';

     // 商品数量更新
    const CART_PRODUCT_UPDATE_NUM = 'cart:product:update:num';

    /******************************订单********************************/
     // vop订单创建
    const VOP_ORDER_CREATE = 'vop:order:create';

     // 主订单
    const ORDER_MAIN_STATUS = 'order:main:status';

     // 订单积分支付
    const ORDER_PAY_INTEGRAL = 'order:pay:integral';

     // 订单补差支付
    const ORDER_PAY_DIFFER = 'order:pay:differ';

     // 订单退款
    const ORDER_REFUND_UPATE = 'order:refund:update';

    //添加订单流水
    const ORDER_ADD_RECORD = 'order:add:record';

    //支付
    const  PAY_MENT_CREATE = 'pay:ment:create';

    const  PAY_MENT_UPDATE ='pay:ment:update';



   /******************************活动商品库存********************************/

     //更新商品库存
    const ACTIVITY_INVENTORY_REMOVE = 'activity_inventory_remove';

   /******************************福卡********************************/
    //注册
    const CARD_REGISTER_ADD = 'card:register:add';

    //充值
    const CARD_EXCHANGE_ADD = 'card:exchange:add';

   /******************************用户流水********************************/
    //用户流水添加
    const USER_STREAM_ADD = 'user:stream:add';

 }
<?php
return [
    'debug' => env('SWOFT_DEBUG', 1),

     //积分比例
    'point_scale' =>10,

    //分页每页展示
    'page_size' => 10,

     //vop配置
    'vop_order_url' =>env('VOP_ORDER_URL'),

    //查询物流
    'express_order_url' =>env('EXPRESS_ORDER_URL'),

    //前端url
    'web_url' =>'http://192.168.1.96',

    'web_stock_url' =>'http://192.168.1.96/stock/confirm',

    //静态图片地址配置
    'images_url' =>'http://192.168.1.170',//'http://ly-h.li91.net/qrcode',

    //编号
    'CMB_CORP_NO'=>'020055',//'001120',
    //秘钥
    'CMB_KEY'=>'cmbtest1',//'cmbtest1',

];

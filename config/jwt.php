<?php

return [
     //机密秘钥
    'secret_key' =>env('JWT_SECRET'),
    //过期时间
    'exp' => env('JWT_EXP'),
    //加密类型
    'type'=>env('JWT_TYPE'),

    //会员登录过期时间
    'member_time_exp'=>3600*4,
    //网点登录缓存前缀
    'REDIS_DATEBASE_PREFIX' => 'ZHLY',

];
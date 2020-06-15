<?php
/**
 * description Wechat.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2020/1/13 19:59
 */

namespace App\Common;


class Wechat
{
    //支付配置
    public static function paymentConfig()
    {
        $config = [
            'app_id'             => config('wechat.app_id'),
            'mch_id'             => config('wechat.mch_id'),
            'key'                => config('wechat.app_key'),
            'notify_url'         => config('http_protocol').config('domain').'/api/wechat/callBack',

            //日志记录
            'log' => [
                'default' => 'dev',
                'channels' => [
                    // 测试环境
                    'dev' => [
                        'driver' => 'single',//的单一文件日志
                        'path' => '/tmp/easywechat.log',
                        'level' => 'debug',
                    ],
                    // 生产环境
                   'prod' => [
                        'driver' => 'daily',//按日期生成日志文件
                        'path' => '/tmp/easywechat.log',
                        'level' => 'info',
                    ],
                 ]
            ],
        ];
        return $config;
    }


    /**
     * 获取微信交易类型
     * @param int $pay_sign
     * @return mixed
     */
    public  static  function getTradeType(int $pay_sign=1)
    {
        $data = [
            1 =>'MWEB',
            2 =>'JSAPI'
        ];
        return $data[$pay_sign];
    }
}
<?php declare(strict_types=1);


namespace App\Rpc\Lib;

interface RpcSmsInterface
{

    /**
     * rpc短信发送接口
     *
     * @param string $mobile [手机号]
     * @param string $content【'短信内容,必须带签名'】
     * @return array
     *
     * 接口返回内容格式为数组:['code','message']
     * 发送成功:['code'=>200,'message'=>'success']
     * ['code'=>1001,'message'=>'参数不能为空'],
     * ['code'=>1002,'message'=>'手机格式错误'],
     * ['code'=>1003,'message'=>'短信内容不能为空'],
     * ['code'=>1004,'message'=>'发送短息失败'],
     */
    public  function send(string $mobile,string $content): array ;

}
<?php declare(strict_types=1);


namespace App\Rpc\Service;
use App\Rpc\Lib\RpcSmsInterface;
use Swoft\Rpc\Server\Annotation\Mapping\Service;


/**
 * 短信接口实现类
 * @Service()
 */
class RpcSmsService implements  RpcSmsInterface
{

    /**
     * 接收处理请求信息
     * @param String $mobile
     * @param String $content
     * @return array
     */
    public  function send(String $mobile,String $content):array
    {
        //测试可返回短信信息
        return ['code'=>200,'message'=>$content];
    }

}
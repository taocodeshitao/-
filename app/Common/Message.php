<?php   declare(strict_types=1);

namespace  App\Common;
/**
 * 返回响应类
 * Class Message
 */
class Message
{

    /**
     * 成功返回信息
     * @param String $message 返回描述
     * @param Int $code 返回码
     * @param array $data 返回数据
     * @return string
     */
    public  static  function success(array $data=[],String $message='操作成功',Int $code=StatusEnum::SuccessCode)
    {

          return ['code'=>$code,'message'=>$message,'data'=>$data];
    }

    /**
     * 失败返回信息
     * @param String $message 返回描述
     * @param Int $code 返回码
     * @param array $data 返回数据
     * @return string
     */
    public  static  function error(String $message='系统繁忙',Int $code=StatusEnum::FailCode,array $data=[])
    {

         return ['code'=>$code,'message'=>$message,'data'=>$data];
    }
}
<?php
namespace App\Utils;
use App\Exception\ApiException;
use Swoft\Bean\Annotation\Mapping\Bean;

/**
 * 验证类
 * Class Check
 * @Bean()
 */
class Check
{
    const MSG_DEFAULT =  '系统繁忙';
    /**
     * 验证null
     *
     * @param $value
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkNull($value, $msg = self::MSG_DEFAULT)
    {
        if($value === null) self::throw($msg);
    }

    /**
     * 验证非null
     *
     * @param $value
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkNotNull($value, $msg = self::MSG_DEFAULT)
    {
        if($value !== null) self::throw($msg);
    }

    /**
     * 验证存在性
     *
     * @param $vlaue
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkExists($vlaue, $msg = self::MSG_DEFAULT)
    {
        if(! isset($vlaue)) self::throw($msg);
    }

    /**
     *
     * @param $value
     * @param string $msg
     * @param int $code
     * @throws ApiException
     */
    public static function checkBoolean($value, $msg = self::MSG_DEFAULT)
    {
        if(! isset($value) || ! $value) self::throw($msg);
    }


    public static function checkTrue($value, $msg = self::MSG_DEFAULT)
    {
        if ($value) self::throw($msg);
    }

    /**
     * 验证非空数组
     *
     * @param $arr
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkEmptyArr($arr, $msg = self::MSG_DEFAULT)
    {
        if(! is_array($arr) || empty($arr)) self::throw($msg);
    }

    /**
     * 验证是否在数组中
     *
     * @param  $search
     * @param array $array
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkInArr($search,array $arr, $msg = self::MSG_DEFAULT)
    {
        if( ! $search || ! in_array($search,$arr)) self::throw($msg);
    }


    /**
     * 验证int相等
     *
     * @param int $current_val
     * @param int $check_val
     * @param string $msg
     *
     * @throws ApiException
     */
    public static function checkIntEqual(int $current_val, int $check_val, string $msg = self::MSG_DEFAULT)
    {
        if ($current_val !== $check_val) self::throw($msg);
    }



    private static function throw($msg)
    {
        throw new ApiException($msg);
    }

    /**
     * 验证手机号
     */
    public static function checkMobile($phone,string $msg = self::MSG_DEFAULT){
        $check = '/^(1(([3456789][0-9])|(47)))\d{8}$/';

        if (!preg_match($check, $phone)) throw new ApiException($msg);
    }

}
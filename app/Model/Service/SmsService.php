<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Common\Cache;
use App\Exception\ApiException;
use App\Model\Dao\UserDao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;

/**
 * 验证码业务处理类
 * Class SmsService
 * @Bean(scope=Bean::PROTOTYPE)
 */
class SmsService
{

    /**
     * 获取短信内容
     * @param string $mobile 手机号
     * @param int $type 短信类型
     * @return string
     * @throws ApiException
     */
    public  function  getSmsContent(string $mobile,int $type):string
    {
        $content ='';

        switch ($type)
        {
            case 1 ://注册
                    $code = $this->getCode($mobile,config('sms.register_key'));

                    $content = sprintf(config('sms.sms_template'),$code);break;

            case 2 ://登录
                    $code = $this->getCode($mobile,config('sms.plogin_kye'));

                    $content = sprintf(config('sms.sms_template'),$code);break;

            case 3 ://修改密码
                    $code = $this->getCode($mobile,config('sms.password_key'));

                    $content = sprintf(config('sms.sms_template'),$code);break;

            default: throw new ApiException('系统繁忙');
        }

        return $content;
    }


    /**
     * 获取验证码
     * @param String $mobile 手机号
     * @param String $prefix  缓存验证码前缀
     * @return string
     * @throws ApiException
     */
    public  function getCode(string $mobile,string $prefix):string
    {
        $key = sprintf(Cache::MOBILE_CODE,$prefix,$mobile);

        $expire_key = sprintf(Cache::MOBILE_CODE_EXPIRE,$prefix,$mobile);

        //验证60秒内是否已经发送过
        if(Redis::exists($expire_key)) throw new ApiException('操作太频繁,请稍后再试');

        //验证每天每个手机号发送短信的上限
        $this->_verifyPhoneNum($mobile);

        //获取随机码
        $code = randNum();

        //保存1分钟再次获取验证码
        Redis::setex($expire_key,60,$code);

        //保存5分钟有效期验证码
        Redis::setex($key,300,$code);

        return $code;
    }

    /**
     * 验证验证码
     * @param  string $mobile 手机号
     * @param  string $code 验证码
     * @param  string $prefix 验证码前缀
     * @return bool
     * @throws ApiException
     */
    public  function checkCode(string $mobile,string $code,string $prefix):bool
    {
        $key = sprintf(Cache::MOBILE_CODE,$prefix,$mobile);

        $expire_key = sprintf(Cache::MOBILE_CODE_EXPIRE,$prefix,$mobile);

        $old_code = Redis::get($key);

        if($old_code!=$code) throw new ApiException('验证码错误');

        Redis::del($key);

        Redis::del($expire_key);

        return true;
    }

    /**
     * 验证短信每天发送次数
     * @param string $mobile
     * @return bool
     * @throws ApiException
     */
    private  function _verifyPhoneNum(string  $mobile)
    {

        $count = intval(Redis::zIncrBy(Cache::MOBILE_CODE_DAY, 1, $mobile));

        if($count>=config('sms.send_day_num',100)) throw new ApiException('今日短信发送次数已达上线');

        if (Redis::ttl(Cache::MOBILE_CODE_DAY) == -1)
        {
            $timeout = (strtotime(date("Y-m-d")) + 86400) * 1000;

            Redis::pExpireAt(Cache::MOBILE_CODE_DAY, $timeout);
        }
        return true;
    }
}
<?php

namespace App\Model\Service;

use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\CardRecordDao;
use App\Model\Dao\UserDao;
use Firebase\JWT\JWT;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;

/**
 * Class AccountService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class AccountService
{

    /**
     * @Inject()
     * @var UserDao
     */
    private $userDao;

    /**
     * @Inject()
     * @var CardRecordDao
     */
    private $cardRecordDao;

    /**
     * 创建用户业务功能
     * @param array $data 接口数据
     * @return int
     * @throws ApiException
     */
    public  function doRegister(array  $data) :int
    {
        $mobile = $data['mobile'];
        $code = $data['code'];
        $card_password = $data['serialNumber'];
        $card_sn = $data['card_sn'];
        $accessToken = $data['accessToken'];

        //验证令牌
        $this->_verifyAccessToken($card_sn,$accessToken);

        /** @var SmsService $smsService */
        $smsService =  BeanFactory::getBean(SmsService::class);
        //验证验证码是否正确
        $smsService->checkCode($mobile,$code,config('sms.register_key'));

        //验证改手机是否已注册
        $userInfo = $this->userDao->findByPhone($mobile);

        if($userInfo) throw new ApiException('该手机号已注册,勿重复注册');

        //验证改福卡是否已注册过
        $card_info =$this->cardRecordDao->findByCardPassword($card_password);

        if($card_info) throw new ApiException('该兑换码已注册,勿重复注册');

        try {
            //注册该手机
            DB::beginTransaction();

            $user_id= $this->userDao->create($data);

            if(!$user_id) throw new ApiException('注册失败');

            //添加福卡使用记录
            \Swoft::trigger(Event::CARD_REGISTER_ADD,null,$user_id,$mobile,$card_sn,$card_password,1);

            DB::commit();

            return $user_id;

        } catch (ApiException $e) {

           DB::rollBack();

           throw new ApiException($e->getMessage());
        }

    }


    /**
     * 账号登录验证业务
     * @param string $mobile
     * @param string $password
     * @return int
     * @throws ApiException
     */
    public  function  doLogin(string $mobile,string $password):int
    {
        //验证手机号是否注册
        $userInfo = $this->userDao->findByPhone($mobile);

        if(!$userInfo) throw new ApiException('该手机号未注册,请先注册');

        //验证密码是否正确
        $old_password = $userInfo['password'];

        if(!password_verify(encryptPassword($password,$mobile),$old_password)){

            throw new ApiException('密码错误,请重新输入密码');
        }

        //更新用户登录记录
        //todo 缺失更新登录ip
        $data['last_login_ip'] = getClientIp();
        $data['last_login_time'] = time();
        $this->userDao->updateById($userInfo['id'],$userInfo['version'],$data);

        return $userInfo['id'];
    }


    /**
     * 账号登录验证业务
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public  function  doplogin(array $data):int
    {

        $mobile =  $data['mobile'];
        $code = $data['code'];

        //验证手机号是否注册
        $userInfo = $this->userDao->findByPhone($mobile);

        if(!$userInfo) throw new ApiException('该手机号未注册,请先注册');

        /** @var SmsService $smsService */
        $smsService =  BeanFactory::getBean(SmsService::class);
        //验证验证码
        $smsService->checkCode($mobile,$code,config('sms.plogin_kye'));

        //更新用户登录记录
        //todo 缺失更新登录ip
        $this->userDao->updateById($userInfo['id'],$userInfo['version'],['last_login_time'=>time()]);

        return $userInfo['id'];
    }

    /**
     * 修改或找回密码
     * @param array $data
     * @return int
     * @throws ApiException
     */
    public  function  doPassword(array $data):int
    {

        $mobile =  $data['mobile'];
        $code = $data['code'];
        $password = $data['password'];

        //验证手机号是否注册
        $userInfo = $this->userDao->findByPhone($mobile);

        if(!$userInfo) throw new ApiException('该手机号未注册,请先注册');

        //验证验证码
        /** @var SmsService $smsService */
        $smsService =  BeanFactory::getBean(SmsService::class);

        $smsService->checkCode($mobile,$code,config('sms.password_key'));

        $newPassword = password_hash(encryptPassword($password,$mobile),PASSWORD_DEFAULT);

        //更新密码
        $reusult =$this->userDao->updateById($userInfo['id'],$userInfo['version'],['password'=>$newPassword]);

        if(!$reusult) throw new ApiException('密码修改失败');

        return $userInfo['id'];
    }


    /**
     * 退出操作
     * @param int $user_id
     * @return bool
     * @throws ApiException
     */
    public  function doLogout(int $user_id)
    {
        //获取用户唯一标志
        $key = sprintf(Cache::AUTH_TOKEN,$user_id);

        $result = Redis::del($key);

        if(!$result)  throw new ApiException('操作失败');

        return true;
    }


    /**
     * 生成用户token
     * @param int $user_id 用户id
     * @return array
     */
    public  function  getToken(int $user_id)
    {
        //获取用户唯一标志
        $key = sprintf(Cache::AUTH_TOKEN,$user_id);

        $secret_key = config('jwt.secret_key');

        $exp = intval(config('jwt.exp'));

        $type = config('jwt.type');

        $payLoad = [
            'user_id' =>$user_id,
            'iat'  => time(),
            'exp'  => time() + $exp
        ];

        //生成加密token
        $token =JWT::encode($payLoad,$secret_key,$type);

        //缓存授权码
        Redis::setex($key,$exp,$token);

        return ['token'=>$token];
    }


    /**
     * 验证令牌
     * @param string $card_sn
     * @param string $accessToken
     * @return bool
     * @throws ApiException
     */
    private function _verifyAccessToken(string  $card_sn ,string $accessToken)
    {
        //获取该卡号的充值令牌
        $token = Redis::hGet(Cache::ACCESS_TOKEN,$card_sn);

        if($token!=$accessToken) throw new ApiException('非法操作');

        return true;
    }


}
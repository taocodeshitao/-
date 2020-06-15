<?php
/**
 * description UserService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/18 16:59
 */

namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\CardRecordDao;
use App\Model\Dao\UserDao;
use App\Utils\Check;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;

/**
 * 福卡操作逻辑
 * Class UserService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class CardService
{

    /**
     * @Inject()
     * @var CardRecordDao
     */
    private $cardRecordDao;


    /**
     * 福卡充值
     * @param int $user_id
     * @param string $card_password
     * @return  array
     * @throws ApiException
     */
    public  function exchange(int $user_id,string $card_password):array
    {
        //验证是否充值
        $this->_verifyRechange($card_password);

        //获取福卡验证信息
        $verify_result  = $this->getAccessToken($card_password,$user_id);

        //福卡充值
        $this->handleExchange($verify_result['data']['access_token']);

        $money  = $verify_result['data']['denomination'];
        $change_balance =$money * config('point_scale');
        $card_sn = $verify_result['data']['sn'];
        try {

            DB::beginTransaction();
            //更新用户积分信息
            /** @var UserService $userService */
            $userService = BeanFactory::getBean(UserService::class);

            $balance = $userService->updateUserIntegral($user_id,$change_balance,2);

            //获取用户信息
            /** @var UserDao $useDao */
            $useDao = \Swoft::getBean(UserDao::class);
            $user = $useDao->findById($user_id,['phone']);

            //添加用户流水日志
            \Swoft::trigger(Event::USER_STREAM_ADD,null,$user_id,$change_balance,$balance,1,'福卡充值',$card_sn);

            //添加福卡充值记录
            \Swoft::trigger(Event::CARD_EXCHANGE_ADD,null,$user_id,$user['phone'],$card_sn,$card_password,$change_balance,$money,2);

            DB::commit();

            return ['integral'=>$change_balance];

        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }

    }


    /**
     * 充值验证
     * @param string $card_password
     * @return bool
     * @throws ApiException
     */
    public  function _verifyRechange(string $card_password)
    {
        Check::checkBoolean($card_password,'兑换码缺失');

        //验证福卡是否已使用
        $card_info =$this->cardRecordDao->findByCardPassword($card_password,2);

        if($card_info) throw new ApiException('该兑换码已兑换');

        return true;
    }


    /**
     * 验证福卡是否已使用
     * @param string $card_password 兑换码
     * @return array
     * @throws ApiException
     */
    public  function _verifyEnable(string $card_password):array
    {

        Check::checkBoolean($card_password,'福卡参数缺失');

        //验证福卡是否已使用
        $card_info =$this->cardRecordDao->findByCardPassword($card_password);

        if($card_info) throw new ApiException('该兑换码已使用');

        //获取验证信息
        $result = $this->getAccessToken($card_password);

        $card_sn = $result['data']['sn'];

        //生成令牌信息
        $accessToken = _createAccessToken($card_sn);

        //保存令牌
        Redis::hSet(Cache::ACCESS_TOKEN,$card_sn,$accessToken);

        return ['card_sn'=>$card_sn,'accessToken'=>$accessToken];
    }


    /**
     * 获取福卡和权限信息
     * @param string $card_sn
     * @param int $user_id
     * @return mixed
     * @throws ApiException
     */
    private function getAccessToken(string $card_sn,int $user_id=0)
    {

        $data['app_id'] = config('app_id');
        $data['key'] = $card_sn;
        $data['user_id'] =$user_id;
        $data['timestamp'] =time();
        $data['signature'] = md5($data['timestamp'].$data['user_id'].$data['key'].$data['app_id'].config('app_key'));

        //验证福卡信息
        $result = get_curl_func(config('card_access_token_url'),$data);

        if(empty($result)) throw new ApiException('系统繁忙');

        if($result['code']!=200) throw new ApiException($result['msg']);

        if($result['data']['status']==3) throw new ApiException('该兑换码已兑换');

        return $result;

    }

    /**
     * 充值
     * @param string $access_token
     * @return bool
     * @throws ApiException
     */
    private  function handleExchange(string $access_token)
    {
        //福卡充值
        $result = curlFunc(config('card_exchange_url'),['access_token'=>$access_token]);

        if(empty($result) || $result['code']!=200) throw new ApiException($result['msg']);

        return true;
    }

}
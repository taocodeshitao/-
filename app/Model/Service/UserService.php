<?php
/**
 * description UserService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/18 16:59
 */

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\HistoryDao;
use App\Model\Dao\UserDao;
use App\Model\Dao\UserStreamDao;
use App\Model\Data\ProductCache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Co;
use Swoft\Db\DB;
use Swoft\Stdlib\Helper\ArrayHelper;

/**
 * 用户操作逻辑
 * Class UserService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class UserService
{

    /**
     * @Inject()
     * @var UserDao
     */
    private $userDao;

    /**
     * 获取用户信息
     * @param int $user_id
     * @return array
     */
    public function getUserInfo(int $user_id):array
    {
        return  $this->userDao->findUserInfoById($user_id);
    }

    /**
     * 获取消费列表
     * @param int $user_id
     * @param int $pageIndex
     * @param int $type
     * @param int $integral
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function getStreamList(int $user_id,int $integral,int $pageIndex,int $type)
    {
        /** @var UserStreamDao $userStreamDao */
        $userStreamDao = \Swoft::getBean(UserStreamDao::class);

        //获取用户累计已用积分信息和消费列表
        $request = [
               'total_integral' => function() use ($user_id,$userStreamDao) {return intval($userStreamDao->getTotalIntegral($user_id));},

               'list' => function()use($user_id,$pageIndex,$type,$userStreamDao) {

                       $list = $userStreamDao->getListByUid($user_id,$pageIndex,$type);

                       foreach ($list as $k=>&$v)
                       {
                           if(in_array($v['type'],[3,6,7]))
                           {
                               $v['type'] =1;
                           }else{
                               $v['type'] =2;
                           }
                           $v['created_at'] = date('Y-m-d H:i:s',$v['created_at']);
                       }

                       return $list;
                 }
        ];

        $data= Co::multi($request);

        $data['integral'] = $integral;

        //获取已用积分
        $data['used_integral'] = $data['total_integral']-$integral;

        return $data;
    }


    /**
     * 获取历史记录列表
     * @param int $user_id
     * @param int $pageIndex
     * @return array
     */
    public function getHistoryList(int $user_id,int $pageIndex)
    {

        $data = [];

        /** @var HistoryDao $historyDao */
        $historyDao = \Swoft::getBean(HistoryDao::class);

        $list = $historyDao->getList($user_id,$pageIndex);

        if(empty($list)) return $data;

        //组合商品
        $temps = [];

        foreach ($list as $v)  $temps[$v['date']][] =$v['code'];

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        $temp = [];$data['list'] =[];
        //获取商品信息
        foreach ($temps as $k=>$v)
        {
            $temp['data'] = $productService->_associateProduct($v);

            $temp['date'] = $k;

            array_push($data['list'],$temp);
        }

        return $data;
    }


    /**
     * 清除用户历史记录
     * @param int $user_id
     * @return bool
     * @throws ApiException
     */
    public  function clearHistory(int $user_id)
    {
        /** @var HistoryDao $historyDao */
        $historyDao = \Swoft::getBean(HistoryDao::class);

        //删除用户的历史记录
        $result = $historyDao->deleteByUid($user_id);

        if($result===false) throw new ApiException('系统繁忙');

        return true;
    }


    /**
     * 转账积分
     * @param int $user_id
     * @param int $integral
     * @param array $params
     * @return array
     * @throws ApiException
     */
    public  function transfer(int $user_id,int $integral,array $params)
    {
        //验证当前余额
        $this->_verifyIntegral($integral,$params['transfer_point']);

        //验证转账账号
        $transfer_user = $this->_verifyAccount($params['mobile']);

        //赠送积分
        try {
            DB::beginTransaction();

            //扣减用户积分
            $user = $this->userDao->findById($user_id);

            $balance = $this->updateUserIntegral($user_id,$params['transfer_point'],1);

            //添加用户流水日志
            \Swoft::trigger(Event::USER_STREAM_ADD,null,$user_id,$params['transfer_point'],$balance,6,'福豆赠出','赠出=》'.$params['mobile']);

            //添加转赠人积分
            $balance = $this->updateUserIntegral($transfer_user['id'],$params['transfer_point'],2);

            //添加用户流水日志
            \Swoft::trigger(Event::USER_STREAM_ADD,null,$transfer_user['id'],$params['transfer_point'],$balance,5,'福豆赠入','被赠送=>'.$user['phone']);

            DB::commit();

            return ['integral'=>$params['transfer_point']];

        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }

    }


    /**
     * 更新用户积分
     * @param int $user_id 用户id
     * @param int $balance 变动积分
     * @param int $type  1:扣减 2:充值
     * @return mixed
     * @throws ApiException
     */
    public  function updateUserIntegral(int $user_id,int $balance,int $type)
    {
        //获取用户信息
        $user = $this->userDao->findById($user_id,['integral','version']);

        if(empty($user)) throw new ApiException('系统繁忙');

        switch ($type) {

            case  1://消费积分
                    $data['integral'] = $user['integral']-$balance;
                    $data['updated_at'] = time();
                    $result = $this->userDao->updateById($user_id, $user['version'],$data);
                    break;

            case  2://充值积分
                    $data['integral'] = $user['integral']+$balance;
                    $data['updated_at'] = time();
                    $result = $this->userDao->updateById($user_id, $user['version'],$data);
                    break;
            default:
                throw new ApiException('系统繁忙');

        }
        if($result===false) throw new ApiException('系统繁忙');

        return $data['integral'];
    }

    /******************************************************************************************************/
    /**
     * 验证余额
     * @param int $integral
     * @param int $transfer_point
     * @throws ApiException
     */
    private function _verifyIntegral(int $integral,int $transfer_point):void
    {
        if($integral<$transfer_point) throw new ApiException('余额不足,无法转账');
    }

    /**
     *      * 验证账号
     * @param string $mobile
     * @param string $mobile
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     * @throws ApiException
     */
    private  function _verifyAccount(string $mobile)
    {
        $user = $this->userDao->findByPhone($mobile);

        if(!$user) throw new ApiException('该账号不存在');

        if($user['state']==0) throw new ApiException($mobile.'账号已禁用,无法赠送');

        return $user;
    }

}
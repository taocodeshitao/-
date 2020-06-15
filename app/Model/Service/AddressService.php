<?php declare(strict_types=1);

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Model\Dao\AddressDao;
use App\Model\Data\UserCache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Db\DB;
use Swoft\Stdlib\Helper\ArrayHelper;

/**
 * 用户地址逻辑
 * Class ArticleService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class AddressService
{

    /**
     * @Inject()
     * @var AddressDao
     */
    private $addressDao;
    /**
     * @Inject()
     * @var UserCache
     */
    private $userCache;

    /**
     * 获取地址列表信息
     * @param int $user_id
     * @return array
     */
    public  function  getList(int $user_id):array
    {

        $data = $this->userCache->getAddressCache($user_id);

        if(empty($data))
        {
            $data = $this->addressDao->getList($user_id);

            if($data) $this->userCache->saveAddressCache($user_id,$data);
        }

        return $data;
    }


    /**
     * 更新用户地址
     * @param array $data
     * @param int $user_id
     * @return bool
     * @throws ApiException
     */
    public  function  updateAddress(array $data,int $user_id):bool
    {
        try {

            //开启事务
            DB::beginTransaction();

            $address_id = $data['address_id'];

            $data['name'] =$data['consignee'];
            $data['phone'] =$data['mobile'];
            $data['updated_at'] =time();

            ArrayHelper::forget($data,['address_id','mobile','consignee']);

            //更新地址信息
            $result = $this->addressDao->updateByID($address_id, $data);

            if(!$result) throw new ApiException('修改地址失败');

            if($data['default']==1)
            {
                //更新默认地址信息
                $result = $this->addressDao->modifyByDefault($user_id,$address_id);

                if($result===false) throw new ApiException('修改地址失败');
            }
            //删除缓存地址
            $this->userCache->delAddressCache($user_id);

            DB::commit();

            return true;

        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }

    }

    /**
     * 添加地址信息
     * @param int $user_id 用户地址id
     * @param array $data 添加数据
     * @return bool
     * @throws ApiException
     */
    public  function  addAddress(int $user_id,array $data):bool
    {
        try {

            DB::beginTransaction();

            $data['name'] =$data['consignee'];
            $data['phone'] =$data['mobile'];
            $data['created_at'] =time();
            $data['uid'] = $user_id;

            ArrayHelper::forget($data,['address_id','mobile','consignee']);

            $address_id = $this->addressDao->addData($data);

            if(!$address_id) throw new ApiException('新增地址失败');

            if($data['default']==1)
            {
                //更新默认地址信息
                $result = $this->addressDao->modifyByDefault($user_id,intval($address_id));

                if($result===false) throw new ApiException('新增地址失败');
            }

            //删除缓存地址
            $this->userCache->delAddressCache($user_id);

            DB::commit();

            return true;

        } catch (ApiException $e) {

            DB::rollBack();

            throw new ApiException($e->getMessage());
        }

    }

    /**
     * 移除用户地址信息
     * @param int $user_id 用户id
     * @param int $address_id 地址id
     * @throws ApiException
     * @return bool
     */
    public  function delAddress(int $user_id,int $address_id):bool
    {
         //删除库中地址信息
         $result= $this->addressDao->deleteById($address_id);

         if(!$result) throw new ApiException('移除地址失败');

         //清除缓存库中地址信息
         $result = $this->userCache->delAddressCache($user_id);

         if(!$result) throw new ApiException('移除地址失败');

         return true;
    }

}
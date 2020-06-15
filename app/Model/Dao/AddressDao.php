<?php declare(strict_types=1);


namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 收货地址数据操作
 * Class AddressDao
 *
 * @Bean()
 */
class AddressDao
{

    /**
     * 未删除
     */
    const ADDRESS_STATUS_DELETE = 0;

    /**
     * 已删除
     */
    const ADDRESS_STATUS_ENBALE = 1;

    /**
     * 根据用户id获取收货地址列表
     * @param int $user_id 用户id
     * @return array
     */
    public  function getList(int $user_id):array
    {
        $where[] =['uid','=',$user_id];

        $where[] =['deleted_at','=',self::ADDRESS_STATUS_DELETE];

        $field =['id as address_id','phone as mobile','name as consignee','default','address','province_id','province_id','city_id','town_id','country_id','area'];

        $data=  DB::table('user_address')->where($where)->get($field)->toArray();

        return $data;
    }

    /**
     * 修改默认地址
     * @param int $user_id 用户id
     * @param int $address_id 地址id
     * @return int
     */
    public  function modifyByDefault(int $user_id,int $address_id):int
    {
        $where[] = ['id','<>',$address_id];

        $where[] = ['uid','=',$user_id];

        return DB::table('user_address')->where($where)->update(['default'=>0]);
    }

    /**
     * 获取一条地址信息
     * @param array $condition
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */

    public  function findOne(array $condition)
    {

        $field = ['province_id','city_id','town_id','country_id','area'];

        return DB::table('user_address')->where($condition)->first($field);
    }


    /**
     * 获取一条地址信息
     * @param int $address_id
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */

    public  function findById(int $address_id)
    {

        return DB::table('user_address')->where('id',$address_id)->first();
    }

    /**
     * 根据id更新一条记录信息
     * @param int $address_id
     * @param array $data
     * @return int
     */
    public  function updateByID(int $address_id,array $data):int
    {
        $where['id'] = $address_id;

        return DB::table('user_address')->where($where)->update($data);
    }

    /**
     * 删除用户地址
     * @param int $address_id
     * @return int
     */
    public  function deleteById(int $address_id):int
    {
        $where['id'] = $address_id;

        return DB::table('user_address')->where($where)->update(['deleted_at'=>time()]);
    }


    /**
     * 新增数据
     * @param array $data
     * @return string
     */
    public  function  addData(array $data):string
    {
        return DB::table('user_address')->insertGetId($data);
    }
}
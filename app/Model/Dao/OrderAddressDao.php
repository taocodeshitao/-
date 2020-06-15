<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 *订单收货数据操作类
 * @Bean()
 */
class OrderAddressDao
{


    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {
        return DB::table('order_receive')->insertGetId($data);
    }


    /**
     * 获取订单收货地址信息
     * @param string $order_id
     * @param array $field
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public  function findInfo(string $order_id,array $field=['id','phone','name','order_id','address','area']):array
    {
        return DB::table('order_receive')->where('order_id',$order_id)->get($field)->toArray();
    }


}
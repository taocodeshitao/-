<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 退款订单数据操作类
 * @Bean()
 */
class OrderRefundDao
{


    /**
     * 根据订单编号更新数据
     * @param array $condition
     * @param array $data
     * @return int
     */
    public  function updateData(array $condition,array $data)
    {

        return DB::table('order_refund')->where($condition)->update($data);
    }



    /**
     * 根据订单编号获取单个订单信息
     * @param string $order_sn
     * @param array $field
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findBySn(string $order_sn, $field=['*'])
    {

        $where['sn'] = $order_sn;

        return DB::table('order_refund')->where($where)->first($field);
    }


    /**
     * 获取列表信息
     * @param int $order_id
     * @param array $field
     * @return array
     */
    public  function getList(int $order_id, array $field=['*'])
    {
        $where['order_id'] = $order_id;

        return DB::table('order_refund')->where($where)->get($field)->toArray();
    }
}
<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 订单物流数据操作类
 * @Bean()
 */
class OrderLogisticDao
{

    /**
     * 获取订单商品物流
     * @param int $order_id
     * @return array
     */
    public function  getListByOrderId(int $order_id):array
    {
        return DB::table('order_express')->where('order_id',$order_id)->first();

    }

    public function find(array $option, array $field=['*'])
    {

        return DB::table('order_logistic')->where($option)->orderByDesc('id')->first($field);
    }
}
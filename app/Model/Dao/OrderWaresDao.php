<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 订单商品数据操作类
 * @Bean()
 */
class OrderWaresDao
{


    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {
        return DB::table('order_wares')->insert($data);
    }

    /**
     * 获取订单商品
     * @param int $order_id
     * @param array $filed
     * @return array
     */
    public function  getListByOrderId(int $order_id,array $filed=['*'])
    {
        return DB::table('order_wares')->where('order_id',$order_id)->get($filed)->toArray();
    }

    /**
     * 获取订单商品信息
     * @param int $order_id
     * @return array
     */
    public function  getWaresList(int $order_id)
    {
        return DB::table('order_wares')
                ->leftJoin('commodity_wares','commodity_wares.id','=','order_wares.wares_id')
                ->where('order_id',$order_id)
                ->get(['order_wares.code','order_wares.number','order_wares.wares_id','commodity_wares.code as sku'])
                ->toArray();
    }
}
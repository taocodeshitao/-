<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 支行库存出入库操作日志操作类
 * @Bean()
 */
class ActivityDotStockLogDao
{
    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {

        return DB::table('activity_dot_stock_log')->insertGetId($data);
    }

    /**
     * 修改数据
     */
    public function update($id,$update_data){
        $where = array();
        $where[] = ['id','=',$id];

        return DB::table('activity_dot_stock_log')->where($where)->update($update_data);
    }

    /**
     * 修改入库确认日志
     */
    public function updateReceive($activity_id,$dot_id,$product_id,$update_data){

        $where = array();
        $where[] = ['b.activity_id','=',$activity_id];
        $where[] = ['b.dot_id','=',$dot_id];
        $where[] = ['f.product_id','=',$product_id];
        $where[] = ['activity_dot_stock_log.type','=',1];

        return DB::table('activity_dot_stock_log')
            ->leftJoin('activity_dot_stock as b', 'b.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
            ->leftJoin('activity_product_stock as d', 'd.id', '=', 'b.activity_product_stock_id')
            ->leftJoin('activity_product as f', 'f.id', '=', 'd.activity_product_id')
            ->where($where)->update($update_data);
    }

    /**
     * 订单出库确认时间
     */
    public function updateOrderTime($order_id){
        $where = array();
        $where[] = ['order_id','=',$order_id];

        return DB::table('activity_dot_stock_log')->where($where)->update(['out_of_stock_time'=>time()]);
    }

    /**
     * 修改出库确认日志
     * @param $activity_id
     * @param $dot_id
     * @param $product_id
     * @param $update_data
     * @return int
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     * @throws \Swoft\Db\Exception\DbException
     */
    public function outReceive($activity_id,$dot_id,$product_id,$update_data){
        $where = array();
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['dot_id','=',$dot_id];
        $where[] = ['product_id','=',$product_id];
        $where[] = ['type','=',2];

        return DB::table('activity_dot_stock_log')->where($where)->update($update_data);
    }

    public function getById($id){
        $where = array();
        $where[] = ['id','=',$id];

        return DB::table('activity_dot_stock_log')->where($where)->first();
    }

}
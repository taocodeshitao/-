<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 网点自有商品日志
 * @Bean()
 */
class HaveProductLogDao
{

    /**
     *  活动启用
     */
    const  ACTIVITY_STATUS_ENABLE  = 1;

    /**
     * 活动禁用
     */
    const  ACTIVITY_STATUS_UNENABLE = 0;


    /**
     * 添加数据
     * @param $data
     */
    public function addData($data){
        return DB::table('have_product_log')->insertGetId($data);

    }

    /**
     * 库存列表
     */
    public function getStockage(int $dot_id, int $page = 1,$params){
        $where['have_product.dot_id'] = $dot_id;

        if (!empty($params['product_name']))  $where[] = ['have_product.name','like','%'.$params['product_name'].'%'];
        if(isset($params['type'])){
            if ($params['type']==0||$params['type']==1){
                $where[] = ["b.type", '=', $params['type']];
            }
        }

        return DB::table('have_product')
            ->where($where)
            ->leftJoin('have_product_log as b', 'b.have_product_id', '=', 'have_product.id')
            ->forPage($page,config('page_size'))
            ->select('have_product.name','b.created_at as log_created_at','b.num','b.type','b.id')
            ->orderBy('b.created_at','desc')
            ->get()
            ->toArray();
    }

    /**
     * 库存变动详情
     */
    public function getStockDetails($id){

        $where['have_product_log.id'] = $id;

        return DB::table('have_product_log')
            ->where($where)
            ->leftJoin('have_product as b', 'b.id', '=', 'have_product_log.have_product_id')
            ->leftJoin('member as c', 'c.id', '=', 'have_product_log.member_id')
            ->select('b.name','have_product_log.created_at as log_created_at','have_product_log.num','have_product_log.type','c.unique_code')
           ->first();
    }
}
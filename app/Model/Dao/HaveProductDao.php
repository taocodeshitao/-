<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 网点自有商品主表
 * @Bean()
 */
class HaveProductDao
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
        return DB::table('have_product')->insertGetId($data);

    }

    /**
     * 根据ID查询商品信息
     */
    public function getProdictById($id){
        $where['id'] = $id;

        return DB::table('have_product')
            ->where($where)
            ->first();
    }

    /**
     * 修改数据
     * @param $id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public function updateData($id,$update_data){
        $where['id'] = $id;

        return DB::table('have_product')
            ->where($where)
            ->update($update_data);
    }



    /**
     * 库存列表
     */
    public function getStockage(int $dot_id, int $page = 1,$params){
        $where['dot_id'] = $dot_id;
        $where['is_del'] = 0;
        return DB::table('have_product')
            ->where($where)
            ->forPage($page,config('page_size'))
            ->select('have_product.*')
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }


    /**
     * 库存商品导出
     */
    public function getStockExport(int $dot_id,$params){
        $where['dot_id'] = $dot_id;
        $where['is_del'] = 0;
        return DB::table('have_product')
            ->where($where)
            ->select('have_product.*')
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }
    /**
     * 查询商品名称
     */
    public function getProductName($name){
        $where['name'] = $name;

        return DB::table('have_product')
            ->where($where)
            ->first();
    }
}
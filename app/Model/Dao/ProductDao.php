<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 商品数据操作类
 * @Bean()
 */
class ProductDao
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
     * 根据商品ID查询一条商品信息
     * @param $product_id
     */
    public function getProductById($product_id){
        $field=['product.*','b.settlement_price'];

        return DB::table('product')
            ->leftJoin('product_price as b', 'b.product_id', '=', 'product.id')
            ->where('product.id',$product_id)->first($field);
    }

    /**
     * 获取商品基本信息
     */
    public function getProductInfo($product_id){

        $field=['product.*','b.settlement_price','supplier_product.itemid','supplier_product.supplier_id'];

        return DB::table('product')
            ->leftJoin('product_price as b', 'b.product_id', '=', 'product.id')
            ->leftJoin('product_supplier', 'product_supplier.product_id', '=', 'product.id')
            ->leftJoin('supplier_product', 'supplier_product.id', '=', 'product_supplier.supplier_product_id')
            ->where('product.id',$product_id)->first($field);

    }
}
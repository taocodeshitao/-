<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动商品库存操作表
 * @Bean()
 */
class ActivityProductStockDao
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
     * 获取活动信息
     * @return array
     */
    public  function getList():array
    {
        $where[] = ['status','=',self::ACTIVITY_STATUS_ENABLE];

        $data = DB::table('activity')
            ->where($where)
            ->select('*')
            ->get()
            ->toArray();

        return $data;
    }

    /**
     * 获取活动详
     * @param $activity_id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public  function getOndByCode( $activity_id)
    {
        return  DB::table('activity')->where('id',$activity_id)->first(['banner_img','name','introduction','activity_type','id','activity_code']);
    }


    /**
     * 获取活动详情
     * @param string $code
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function getDetailByCode($activity_product_id)
    {
        $where[] = ['activity_product_id','=',$activity_product_id];

        $data = DB::table('activity_product_stock')
            ->where($where)
            ->first();

        return  $data;
    }


}
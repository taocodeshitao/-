<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动商品数据操作类
 * @Bean()
 */
class ActivityWaresDao
{

    /**
     * 更新减活动商品库存
     * @param int $activity_id
     * @param int $wares_id
     * @param int $num
     * @return int
     */
    public  function updateInventoryByDecrement(int $activity_id,int $wares_id,int $num):int
    {

        $where['activity_id'] = $activity_id;

        $where['wares_id'] = $wares_id;

        return DB::table('activity_wares')->where($where)->decrement('stock_eable',$num,['updated_at'=>time()]);
    }

    /**
     * 更新加活动商品库存
     * @param int $activity_id
     * @param int $wares_id
     * @param int $num
     * @return int
     */
    public  function updateInventoryByIncrement(int $activity_id,int $wares_id,int $num):int
    {

        $where['activity_id'] = $activity_id;

        $where['wares_id'] = $wares_id;

        return DB::table('activity_wares')->where($where)->increment('stock_eable',$num,['updated_at'=>time()]);
    }
    /**
     * 获取活动商品信息
     * @param int $activity_id
     * @param string $sku
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findDetailBysku(int $activity_id,string $sku)
    {
        $a ='activity_wares';
        $b ='commodity_wares';

        $where[] =["{$a}.activity_id",'=',$activity_id];
        $where[] =["{$b}.code",'=',$sku];

        $data = DB::table($a)
                ->join($b,"{$b}.id","{$a}.wares_id")
                ->select("{$b}.code","{$a}.sort","{$a}.integral","{$a}.stock","{$a}.stock_eable","{$a}.limit")
                ->where($where)
                ->first();

        return $data;
    }


    /**
     * 获取商品列表
     * @param int $activity_id
     * @return array
     */
    public  function getSkuList(int $activity_id)
    {
        $a ='activity_wares';
        $b ='commodity_wares';

        $where[] =["{$a}.activity_id",'=',$activity_id];

        $data = DB::table($a)
            ->join($b,"{$b}.id","{$a}.wares_id")
            ->select("{$b}.code as sku","{$a}.sort")
            ->where("{$a}.activity_id",$activity_id)
            ->orderBy("{$a}.sort",'desc')
            ->get()
            ->toArray();

        return $data;
    }
    

}
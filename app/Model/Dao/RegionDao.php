<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 区域数据操作类
 * @Bean()
 */
class RegionDao
{
    /**
     * 获取活动信息
     * @return array
     */
    public  function getList($region_d):array
    {

        $data = DB::table('region')
            ->whereIn('id',explode(',',$region_d))
            ->select('*')
            ->get()
            ->toArray();

        return $data;
    }

}
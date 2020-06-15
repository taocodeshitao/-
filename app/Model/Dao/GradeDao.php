<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 档次数据操作类
 * @Bean()
 */
class GradeDao
{
    /**
     * 根据活动ID查询所有档次
     * @param $activity_id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public  function getOndByCode( $activity_id)
    {
        return  DB::table('grade')->where('activity_id',$activity_id)->get()->toArray();
    }

    public function getById($id){
        return  DB::table('grade')->where('id',$id)->first();
    }
}
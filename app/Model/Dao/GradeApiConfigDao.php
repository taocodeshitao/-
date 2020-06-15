<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 档次接口主表
 * @Bean()
 */
class GradeApiConfigDao
{


    /**
     * 获取活动详
     * @param $activity_id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public  function getOneGradeById( $grade_id)
    {
        return  DB::table('grade_api_config')->where('grade_id',$grade_id)->get()->toArray();
    }

}
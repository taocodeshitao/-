<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动会员导入方式（接口的方式）数据操作类
 * @Bean()
 */
class ActivityMemberApiDao
{
    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {

        return DB::table('activity_member_api')->insertGetId($data);
    }

}
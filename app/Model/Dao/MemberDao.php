<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 会员数据操作类
 * @Bean()
 */
class MemberDao
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
     * 根据用户标识获取用户信息
     * @param $member_code
     */
    public function getMemberCode($member_code){

        return DB::table('member')->where('unique_code',$member_code)->first();
    }

    /**
     * 新增会员信息
     * @param array $add_member_data
     */
    public function addMember(array $add_member_data){
       return DB::table('member')->insertGetId($add_member_data);
    }

}
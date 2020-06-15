<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 网点数据操作类
 * @Bean()
 */
class DotDao
{
    /**
     * 根据账号密码查询网点管理员信息
     * @param $account 账号
     * @param $password 密码
     */
    public function getAccountStatus($account,$password){

        $where=array();

        $where[] =['system_user.account','=',$account] ;
        $where[] =['system_user.status','=',1] ;

        $where[] =['system_user.password','=',md5(json_encode($password))] ;

        $data = DB::table('system_user')
            ->leftJoin('system_user_auth','system_user_auth.user_id','=','system_user.id')
            ->leftJoin('dot','dot.id','=','system_user.dot_id')
            ->where($where)
            ->whereIn('system_user_auth.role_id',[3,4])

            ->select('system_user.*','system_user_auth.role_id','dot.region_id')
            ->first();

        return $data;
    }

    /**
     * 根据管理员ID修改密码
     */
    public function updatePassword($user_id,$password){

        $update =[
            'password'=>md5(json_encode($password))
        ];

        return DB::table('system_user')->where('id',$user_id)->update($update);

    }

    /**
     * 获取用户基本信息
     * @param $user_id
     */
    public function getDotInfo($user_id){
        $where=array();

        $where[] =['system_user.id','=',$user_id];
        $data = DB::table('system_user')
            ->leftJoin('system_user_auth','system_user_auth.user_id','=','system_user.id')
            ->leftJoin('system_role','system_role.id','=','system_user_auth.role_id')
            ->leftJoin('dot','dot.id','=','system_user.dot_id')
            ->where($where)
            ->select('system_user.password','system_user.name','system_role.name as role_name','dot.name as dot_name')
            ->first();

        return $data;
    }

    /**
     * 查询用户是否禁用
     * @param $user_id]
     */
    public function getAccountById($user_id){
        $where=array();

        $where[] =['system_user.id','=',$user_id];
        $where[] =['system_user.status','=',1];

        return DB::table('system_user')->where($where)->first();

    }

    /**
     * 获取所有区域ID
     */
    public function getRegionId(){
        return DB::table('dot')->groupBy('region_id')->select('region_id')->get()->toArray();
    }

    /**
     * 根据区域ID查询网点
     */
    public function getRegionList($region_id){
        return DB::table('dot')->where('region_id',$region_id)->select('name','id')->get()->toArray();
    }
}
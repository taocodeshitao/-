<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动会员导入方式（导入的方式）数据操作类
 * @Bean()
 */
class ActivityMemberDao
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
     * 新增数据
     */
    public function addData($data){

        return DB::table('activity_member')->insertGetId($data);
    }

    /**
     * 修改领取状态
     *
     */
    public function updateReceiveStatus($activity_id,$grade_id,$unique_code,$order_id,$product_id){
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['unique_code','=',$unique_code];
        $where[] = ['grade_id','=',$grade_id];
        $where[] = ['status','=',0];

        $data1 = DB::table('activity_member')->where($where)->first();

        $data['status']=1;
        $data['order_id']=$order_id;
        $data['product_id']=$product_id;
        $data['receive_time']=time();

        return DB::table('activity_member')->where('id',$data1['id'])->update($data);
    }

    /**
     * @param $activity_id
     * @param $grade_id
     */
    public function getActivityGrade($activity_id,$grade_id,$uniqueuserid){
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['unique_code','=',$uniqueuserid];
        $where[] = ['status','=',0];
        if ($grade_id>0){

            $where[] = ['grade_id','=',$grade_id];
        }

        return DB::table('activity_member')->where($where)->get()->toArray();
    }


    /**
     * 根据活动activity_id，grade_id，unique_code查询会员没有领取的档次商品
     */
    public function getActivityProductStatus($activity_id,$grade_id,$unique_code){
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['unique_code','=',$unique_code];
        $where[] = ['grade_id','=',$grade_id];
        $where[] = ['status','=',0];

        return DB::table('activity_member')->where($where)->first();
    }


    /**
     * 根据活动activity_id，grade_id，unique_code查询会员没有领取的档次商品
     */
    public function getActivityProductStatus1($activity_id,$unique_code){
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['unique_code','=',$unique_code];

        return DB::table('activity_member')->where($where)->count();
    }
    /**
     * 查询会员状态
     */
    public function getStatus($activity_id,$unique_code){
        $where[] = ['unique_code','=',$unique_code];
        $where[] = ['activity_id','=',$activity_id];

        return DB::table('activity_member')->where($where)->first();
    }

    /**
     * 我的权益
     */
    public function getMyEquity($unique_code){
        //$where[] = ['activity_member.activity_id','=',$activity_id];
        $where[] = ['activity_member.unique_code','=',$unique_code];
        $where[] = ['activity.status','=',1];
        return DB::table('activity_member')
            ->leftJoin('activity','activity.id','=','activity_member.activity_id')
            ->select('activity.*')
            ->groupBy('activity.id')
            ->where($where)->get()->toArray();
    }

    /**
     * 获取会员还能领取该活动的次数
     */
    public function getReceiveNum($grade_id,$activity_id,$unique_code,$dot_id=0){
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['unique_code','=',$unique_code];
        $where[] = ['grade_id','=',$grade_id];
        if ($dot_id>0){
            $where[] = ['dot_id','=',$dot_id];
        }
        $where[] = ['status','=',0];

        return DB::table('activity_member')->where($where)->count();
    }
}
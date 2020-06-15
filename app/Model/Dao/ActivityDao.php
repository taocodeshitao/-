<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动数据操作类
 * @Bean()
 */
class ActivityDao
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
     * 获取活动信息(网点)
     * @return array
     */
    public  function getList($params):array
    {

        $where[] = ['status','=',self::ACTIVITY_STATUS_ENABLE];
        $where[] = ['start_time','<=',time()];
        //$where[] = ['end_time','<',time()-86400*30];
        if (!empty($params['activity_name']))  $where[] = ['name', 'like', '%' . $params['activity_name'] . '%'];

        $data = DB::table('activity')
            ->where($where)
            ->select('*')
            ->get()
            ->toArray();

        foreach ($data as $key=>&$v){
            if ($v['end_time']-86400*30>time()){
                usort($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 获取活动信息(会员)
     * @return array
     */
    public  function getList1():array
    {

        $where[] = ['activity.start_time','<=',time()];
        $where[] = ['activity.end_time','>=',time()];
        $where[] = ['activity.status','=',1];


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
        return  DB::table('activity')->where('id',$activity_id)->first();
    }

    /**
     * 修改预算
     */
    public function updateMoeny($activity_id,$moeny){
        return  DB::table('activity')->where('id',$activity_id)->update(['use_activity_moeny'=>$moeny]);
    }

    /**
     * 获取活动详情
     * @param string $code
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function getDetailByCode($activity_id)
    {
        $where[] = ['activity.activity_id','=',$activity_id];

        $data = DB::table('activity')
            ->leftJoin('attachment','activity.cover','=','attachment.id')
            ->where($where)
            ->select('activity.id','activity.sign as code','activity.status','activity.model','activity.title','attachment.url as image','activity.begin_time','activity.end_time')
            ->first();

        return  $data;
    }

}
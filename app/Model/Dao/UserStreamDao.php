<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 用户流水数据操作类
 * @Bean()
 */
class UserStreamDao
{

    /**x
     * 添加用户记录
     * @param $data
     * @return string
     */
    public  function addData(array $data):string
    {
        //添加用户记录
        return  DB::table('user_stream')->insertGetId($data);
    }


    /**
     * 获取列表
     * @param int $user_id
     * @param int $pageIndex
     * @param int $type 0 全部 1消费 2 收入
     * @param int $size
     * @return array
     */
    public  function getListByUid(int $user_id,int $pageIndex,int $type,int $size=10):array
    {
        $offset = $pageIndex==1 ? 0 : ($pageIndex-1)*$size;

        $where['uid'] = $user_id;

        if($type)
        {
            if($type==1)
            {
                $whereIn =[3,6,7];
            }else{
                 $whereIn = [1,2,4,5];
            }
            $data = DB::table('user_stream')->latest()
                    ->where($where)
                    ->whereIn('type',$whereIn)
                    ->offset($offset)
                    ->limit($size)
                    ->get(['type','name','integral','description','created_at'])
                    ->toArray();
        }else{
            $data = DB::table('user_stream')->latest()
                    ->where($where)
                    ->offset($offset)
                    ->limit($size)
                    ->get(['type','name','integral','description','created_at'])
                    ->toArray();
        }
        return $data;
    }

    /**
     * 获取累计积分
     * @param int $user_id
     * @return float|int
     */
    public  function getTotalIntegral(int $user_id)
    {
        $where['uid'] = $user_id;

        return DB::table('user_stream')->where($where)->whereIn('type',[1,2,5])->sum('integral');
    }


}
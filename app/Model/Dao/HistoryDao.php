<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 浏览历史数据库操作
 * Class HistoryDao
 *
 * @Bean()
 */
class HistoryDao
{

    /**
     * 获取历史记录列表
     * @param int $user_id
     * @param int $pageIndex
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getList(int $user_id,int $pageIndex = 1)
    {
        $a ='user_history';
        $b ='commodity_wares';

        $data = DB::table($a)
            ->join($b,"{$a}.wares_id","{$b}.id")
            ->where("{$a}.uid",$user_id)
            ->forPage($pageIndex, config('page_size'))
            ->select( "{$b}.code","{$a}.date")
            ->orderBy("{$a}.id",'desc')
            ->get()
            ->toArray();

        return $data;
    }


    /**
     * 获取单个历史记录
     * @param int $user_id
     * @param int $product_id
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findOne(int $user_id,int $product_id)
    {
        $where['uid'] = $user_id;

        $where['wares_id'] = $product_id;

        return DB::table('user_history')->where($where)->first();
    }

    /**
     * 新增历史数据
     * @param array $data
     * @return string
     */
    public  function  addData(array $data):string
    {
        return DB::table('user_history')->insertGetId($data);
    }



    /**
     * 删除历史记录
     * @param int $user_id
     * @return int
     */
    public  function deleteByUid(int $user_id):int
    {

        return DB::table('user_history')->where('uid',$user_id)->delete();
    }


    /**
     * 根据主键id更新数据
     * @param int $id
     * @param array $data 更新数据
     * @return bool
     */
    public  function updateById(int $id,array $data)
    {
        return DB::table('user_history')->where('id',$id)->update($data);
    }

}
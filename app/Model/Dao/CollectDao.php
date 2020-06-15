<?php declare(strict_types=1);


namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 用户收藏数据操作
 * Class AddressDao
 *
 * @Bean()
 */
class CollectDao
{

    /**
     * 根据用户id获取收藏列表
     * @param int $user_id 用户id
     * @return array
     */
    public  function getList(int $user_id):array
    {
        $a ='user_star';
        $b ='commodity_wares';

        $data = DB::table($a)
            ->leftJoin($b,"{$a}.wares_id",'=',"{$b}.id")
            ->where("{$a}.uid",$user_id)
            ->select("{$b}.code as sku")
            ->get()
            ->toArray();

        return $data;
    }


    /**
     * 获取单个收藏记录
     * @param int $user_id
     * @param int $product_id
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findById(int $user_id,int $product_id)
    {
        $where['uid'] = $user_id;

        $where['wares_id'] = $product_id;

        return DB::table('user_star')->where($where)->first();
    }

    /**
     * 获取用户收藏商品的数量
     * @param int $user_id
     * @return int
     */
    public  function getCount(int $user_id)
    {
        $where['uid'] = $user_id;

        return DB::table('user_star')->where($where)->count('id');
    }

    /**
     * 删除收藏
     * @param int $user_id
     * @param int $product_id
     * @return int
     */
    public  function deleteById(int $user_id,int $product_id):int
    {
        $where['uid'] = $user_id;

        $where['wares_id'] = $product_id;

        return DB::table('user_star')->where($where)->delete();
    }


    /**
     * 删除所有收藏
     * @param int $user_id
     * @return int
     */
    public  function deleteByUid(int $user_id):int
    {
        return DB::table('user_star')->where('uid',$user_id)->delete();
    }

    /**
     * 新增收藏数据
     * @param array $data
     * @return string
     */
    public  function  addData(array $data):string
    {
        return DB::table('user_star')->insertGetId($data);
    }
}
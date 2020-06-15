<?php declare(strict_types=1);


namespace App\Model\Dao;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 福卡记录dao
 * Class CardRecordDao
 * @Bean()
 */
class CardRecordDao
{

    /**
     * 根据卡号查询一条数据
     * @param String $card_password 卡密
     * @param int $type
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */

    public  function findByCardPassword(string $card_password,int $type =null)
    {
        $where['password'] = md5($card_password);

        if(!empty($type)) $where['type'] =$type;

        $data = DB::table('card_record')->where($where)->first();

        return $data;
    }

    /**
     * 添加福卡使用记录
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {

        return DB::table('card_record')->insertGetId($data);

    }
}
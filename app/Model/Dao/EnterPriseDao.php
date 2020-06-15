<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 企业数据操作
 * Class EnterPriseDao
 *
 * @Bean()
 */
class EnterPriseDao
{

    /**
     * 根据企业标志获取企业记录
     * @param string $sign 企业标志
     * @param array $fields 查询字段
     * @param string $sign
     * @param array $fields
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findBySign(string $sign,$fields=['*'])
    {
        $where['sign'] = $sign;

        return DB::table('enterprise')->where('sign',$sign)->first($fields);

    }
}
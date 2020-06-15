<?php declare(strict_types=1);


namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 分类数据操作
 * Class SubgroupDao
 *
 * @Bean()
 */
class SubgroupDao
{

    /**
     * 获取专题栏目
     * @return array
     */
    public  function getList():array
    {
        return DB::table('subgroup')->get(['id','pid','title'])->toArray();

    }

}
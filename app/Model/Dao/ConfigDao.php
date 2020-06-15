<?php declare(strict_types=1);

namespace App\Model\Dao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;


/**
 * 商城配置数据操作
 * Class ConfigDao
 *
 * @Bean()
 */
class ConfigDao
{

    /**
     * 获取配置信息
     * @param string $value
     * @return mixed
     */
    public  function getConfig(string  $value)
    {

        return  DB::table('config')->where('name',$value)->get(['name','value'])->first();

    }


}
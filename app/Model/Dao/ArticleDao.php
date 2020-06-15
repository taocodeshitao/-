<?php declare(strict_types=1);

namespace App\Model\Dao;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 文章数据操作
 * Class ArticleDao
 *
 * @Bean()
 */
class ArticleDao
{

    /**
     * 获取文章列表
     * @param array $field
     * @param array $condition
     * @return array
     */
    public  function getList(array $field=['*'],$condition=array()):array
    {
         return   DB::table('article')->where($condition)->get($field)->toArray();
    }


    /**
     * 获取文章详情
     * @param int $id
     * @param array $field
     * @return array
     */
    public  function getArticleById(int $id,$field=['*']):array
    {

        return DB::table('article')->where('id',$id)->get($field)->toArray();
    }

}
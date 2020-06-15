<?php declare(strict_types=1);

namespace App\Model\Data;


use App\Common\Cache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;

/**
 * 文章数据缓存操作
 * Class ArticleData
 *
 * @Bean()
 */
class ArticleCache
{

    /**
     *
     * 获取文章缓存数据
     * @param int $article_id
     * @return array
     */
    public  function getArticleCache(int $article_id):array
    {

        $key = sprintf(Cache::ARTICLE_DETAILS,$article_id);

        //获取文章详情缓存信息
        $data = Redis::get($key);

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }


    /**
     * 保存文章信息
     * @param int $article_id 文章id
     * @param array $data 缓存信息
     * @return bool
     */
    public  function saveArticleCache(int $article_id,array $data)
    {

        $key = sprintf(Cache::ARTICLE_DETAILS,$article_id);

        //获取文章缓存信息
        return Redis::set($key,json_encode($data));
    }

    /**
     * 获取文章列表缓存信息
     * @return array
     */
    public  function getArticleListCache():array
    {
        $data = Redis::hGet(Cache::ARTICLE_LIST,'article');

        $data =  $data ? json_decode($data,true) : [];

        return $data;
    }

    /**
     * 保存文章列表缓存信息
     * @param array $data
     * @return int
     */
    public  function saveArticleListCache(array $data):int
    {
        return  Redis::hSet(Cache::ARTICLE_LIST,'article',json_encode($data));
    }

}
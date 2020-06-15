<?php declare(strict_types=1);

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Model\Data\ArticleCache;
use App\Model\Dao\ArticleDao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;

/**
 * 文章业务逻辑
 * Class ArticleService
 *
 * @Bean()
 */
class ArticleService
{

    /**
     * @Inject()
     * @var ArticleDao
     */
    private $articleDao;
    /**
     * @Inject()
     * @var ArticleCache
     */
    private $articleCache;

    /**
     * 获取文章列表信息
     * @return array
     */
    public  function  getList():array
    {

        $data = $this->articleCache->getArticleListCache();

        if(empty($data))
        {
            $data = $this->articleDao->getList(['id','title']);

            if($data) $this->articleCache->saveArticleListCache($data);
        }

        return $data;
    }


    /**
     * 获取文章详情
     * @param int $id 文章id
     * @return array
     * @throws ApiException
     */
    public  function getDetails(int $id):array
    {

        $data  =$this->articleCache->getArticleCache($id);

        if(empty($data))
        {
            //存入缓存
            $data = $this->articleDao->getArticleById($id,['display','title','content']);

            if($data) $this->articleCache->saveArticleCache($id,$data);
        }

        return $data;
    }
}
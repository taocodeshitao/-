<?php declare(strict_types=1);

namespace App\Model\Service;


use App\Exception\ApiException;
use App\Model\Dao\CollectDao;
use App\Model\Dao\WaresDao;
use App\Model\Data\UserCache;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Stdlib\Helper\ArrayHelper;

/**
 * 用户收藏逻辑
 * Class CollectService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class CollectService
{

    /**
     * @Inject()
     * @var CollectDao
     */
    private $collectDao;

    /**
     * @Inject()
     * @var WaresDao
     */
    private $waresDao;

    /**
     * @Inject()
     * @var UserCache
     */
    private $userCache;

    /**
     * 获取收藏列表信息
     * @param int $user_id
     * @return array
     */
    public  function  getList(int $user_id):array
    {
        $sku_list = $this->userCache->getCollectCache($user_id);

        if(empty($sku_list))
        {
            //获取收藏数据
            $sku_list = $this->collectDao->getList($user_id);

            if(empty($sku_list)) return [];

            //保存收藏数据
            $this->userCache->saveCollectCache($user_id,$sku_list);
        }

        $sku_list = ArrayHelper::getColumn($sku_list,'sku');

        //获取商品的数据
        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        $data = $productService->_associateProduct($sku_list);

        return $data;
    }

    /**
     * 添加收藏信息
     * @param int $user_id 用户id
     * @param array $data 添加数据
     * @return bool
     * @throws ApiException
     */
    public  function  addCollect(int $user_id,array $data):bool
    {
        $sku = $data['sku'];

        //判断商品是否存在
        $product_info = $this->waresDao->findBySku($sku);

        if(empty($product_info)) throw new ApiException('系统繁忙');

        //商品是否已收藏
        $result =$this->collectDao->findById($user_id,$product_info['id']);

        if($result) throw new ApiException('该商品已收藏,请勿重复收藏');

        //获取收藏夹商品的数量
        $count = $this->collectDao->getCount($user_id);

        if($count>49) throw new ApiException('收藏已满,请清理后再加入');

        unset($data['sku']);
        $data['uid'] = $user_id;
        $data['wares_id'] = $product_info['id'];
        $data['created_at'] = time();

        //加入收藏
        $collect_id = $this->collectDao->addData($data);

        if(!$collect_id) throw new ApiException('收藏失败');

        //删除缓存
        $this->userCache->delCollectCache($user_id);

        return true;
    }

    /**
     * 移除用户收藏信息
     * @param int $user_id 用户id
     * @param array $data
     * @throws ApiException
     * @return bool
     */
    public  function delCollect(int $user_id,array $data):bool
    {

        $sku = $data['sku'];

        //判断商品是否存在
        $product_info = $this->waresDao->findBySku($sku);

        if(empty($product_info)) throw new ApiException('系统繁忙');

        //删除收藏商品
        $result = $this->collectDao->deleteById($user_id,$product_info['id']);

        if($result===false) throw new ApiException('取消收藏失败');

        //清除收藏缓存
        $this->userCache->delCollectCache($user_id);

        return true;

    }

    /**
     * 清空所有的缓存
     * @param int $user_id 用户id
     * @return bool
     * @throws ApiException
     */
    public  function deleteAllCollect(int $user_id)
    {
        //删除收藏商品
        $result = $this->collectDao->deleteByUid($user_id);

        if($result===false) throw new ApiException('清空收藏失败');

        //清除缓存
        $this->userCache->delCollectCache($user_id);

        return true;
    }

}
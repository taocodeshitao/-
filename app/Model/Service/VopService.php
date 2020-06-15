<?php
/**
 * description VopService.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2020/1/3 18:07
 */

namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Data\ProductCache;
use App\Rpc\Lib\JdInterface;
use App\Utils\Check;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Redis\Redis;
use Swoft\Rpc\Client\Annotation\Mapping\Reference;
use Swoft\Stdlib\Helper\ArrayHelper;
use Swoft\Stdlib\Helper\JsonHelper;

/**
 * vop逻辑
 * Class VopService
 * @package App\Model\Service
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class VopService
{

    /**
     * @Reference("vop.pool")
     * @var JdInterface
     */
    private $vopInterface;

    public function _verifyVopStock(string $sku_list, int $province, int $city, int $country)
    {
        Check::checkBoolean($sku_list, '商品编号缺失');

        $sku_list = explode(',', $sku_list);

        //获取商品来源编号信息
        /** @var ProductCache $productCache */
        $productCache = \Swoft::getBean(ProductCache::class);
        $product_base = $productCache->getDetailCache($sku_list);
        $products = [];
        $data = [];

        //组合商品编号
        foreach ($product_base as $k => $v)
        {
            $product_base[$k] = JsonHelper::decode($v, true);
            $product['code'] = $product_base[$k]['source_id'];
            $product['num'] = 1;
            array_push($products, $product);
        }
        //查询京东库存
        $result = $this->vopInterface->getJdStore($products, $province, $city, $country);

        if (empty($result) || $result['code'] != 1) throw new ApiException('系统繁忙');

        //重组商品sku
        $product_base = ArrayHelper::index($product_base, 'source_id');

        foreach ($result['data'] as $k => $v) {
            $temp['sku'] = $product_base[$v['code']]['code'];
            $temp['result'] = $v['result'] == '有货' ? 1 : 0;
            array_push($data, $temp);
        }

        return $data;
    }


    /**
     * 创建预占库存订单
     * @param string $order_sn
     * @param array $products
     * @param array $address
     * @return bool
     * @throws ApiException
     */
    public function preoccupyVopOrder(string $order_sn, array $products, array $address)
    {
        $data = [];$data['products']=[];
        //获取token
        $data['token'] = unserialize(Redis::get(Cache::PLAT_TOKEN));

        if (empty($data['token'])) throw new ApiException('系统繁忙');

        //组合定数据
        foreach ($products as $k => $v) {
            $temp['itemId'] = $v['source_id'];
            $temp['number'] = $v['num'];
            array_push($data['products'], $temp);
        }
        $data['isvirtual'] = 4;
        $data['orderId'] = $order_sn;
        $data['shouhuo_name'] = $address['name'];
	    $data['shouhuo_phone'] = $address['phone'];
        $data['provinceId'] = $address['province_id'];
        $data['cityId'] = $address['city_id'];
        $data['countyId'] = $address['country_id'];
        $data['townId'] = $address['town_id'];
        $data['shouhuo_addr'] = $address['address'];
        $data['addr_type'] = 1;
        $data['sendcms'] = 1;

        //处理返回信息
        $result =post_curl_func(config('vop_order_url'),JsonHelper::encode($data));

        if($result['errCode']!='0000') throw new ApiException('下单失败');

        return $result['data']['order_sn'];
    }
}
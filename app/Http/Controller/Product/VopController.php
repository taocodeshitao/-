<?php


namespace App\Http\Controller\Product;


use App\Common\Message;
use App\Model\Service\VopService;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * vop
 * Class VopController
 * @package App\Http\Controller\Product
 *
 * @Controller(prefix="/api/vop")
 */
class VopController
{

    /**
     * 查询京东商品库存
     * @RequestMapping(route="getStock",method={RequestMethod::POST})
     * @Validate(validator="addressValidator",fields={"sku","province_id","city_id","country_id"})
     * @param Request $request
     * @return string
     */
    public function getStock(Request $request)
    {
        $params = $request->getParsedBody();

        $vopService = BeanFactory::getBean(VopService::class);
        //验证京东库存
        $data = $vopService->_verifyVopStock($params['sku'],$params['province_id'],$params['city_id'],$params['country_id']);

        return Message::success($data);

    }

}
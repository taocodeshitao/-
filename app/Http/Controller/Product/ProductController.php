<?php declare(strict_types=1);


namespace App\Http\Controller\Product;


use App\Model\Service\ProductService;
use App\Common\Message;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Task\Task;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 商品控制器
 * Class ProductController
 * @Controller("/api/product")
 */
class ProductController
{

     /**
     * 获取商品的详情
     * @RequestMapping(route="details",method={RequestMethod::POST})
     * @Validate(validator="commonValidator",fields={"sku"})
     * @param Request $request
     * @return string
     */
    public  function  details(Request $request)
    {
        $params = $request->getParsedBody();

        $token = $request->getHeaderLine("token");

        $code = isset($params['code']) ? $params['code'] : null;

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        $data = $productService->getProductDetails($params['sku'],$code,$token);

        return Message::success($data);

    }


    /**
     * @RequestMapping(route="test1",method={RequestMethod::POST})
     */

    public function test1(Request $request){
        var_dump($request);
    }

}
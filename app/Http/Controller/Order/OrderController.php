<?php declare(strict_types=1);

namespace App\Http\Controller\Order;


use App\Common\Message;
use App\Http\Middleware\AuthMiddleware;
use App\Model\Service\OrderService;
use App\Http\Middleware\DotMiddleware;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;


/**
 * 订单
 * Class OrderController
 * @package App\Http\Controller
 * @Controller("/api/order")
 * @Middleware(DotMiddleware::class)
 */
class OrderController
{

    /**
     * 订单列表（网点端）
     * @RequestMapping(route="getList",method=RequestMethod::POST)
     * @param Request $request
     * @return string
     */
    public function ordersList(Request $request)
    {
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $orderinfo = $orderService->ordersList($params);

        $result['order_info']= $orderinfo;

        return Message::success($result);
    }

    /**
     * 订单详情
     * @RequestMapping(route="details",method=RequestMethod::POST)
     * @Validate(validator="orderValidator",fields={"order_id"})
     * @param Request $request
     * @return string
     */
    public function orderInfo(Request $request)
    {
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $orderinfo = $orderService->orderInfo($params);

        $result['order_info']    = $orderinfo;

        return Message::success($result);
    }

    /**
     * 订单发放商品
     * @RequestMapping(route="grantProduct",method=RequestMethod::POST)
     * @param Request $request
     * @return string
     */
    public function grantProduct(Request $request){
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->grantProduct($params);

        return Message::success($data);
    }
}
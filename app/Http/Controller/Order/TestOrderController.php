<?php declare(strict_types=1);

namespace App\Http\Controller\Order;


use App\Common\Message;
use App\Http\Middleware\AuthMiddleware;
use App\Model\Service\OrderService;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;


/**
 * 测试订单
 * Class TestOrderController
 * @package App\Http\Controller
 * @Controller("/api/testOrder")
 * @Middleware(AuthMiddleware::class)

 */
class TestOrderController
{
    /**
     * @RequestMapping(route="getOrderDetail",method={RequestMethod::POST})
     * 测试获取订单详情
     * @param Request $request
     */
    public function getOrderDetail(Request $request){
        echo "hello testOrderDetail";
    }
}
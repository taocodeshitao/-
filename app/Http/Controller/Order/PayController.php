<?php declare(strict_types=1);

namespace App\Http\Controller\Order;


use App\Common\Message;
use App\Http\Middleware\AuthMiddleware;
use App\Model\Service\PayService;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;


/**
 * 订单
 * Class PayController
 * @package App\Http\Controller
 * @Controller("api/order")
 * @Middleware(AuthMiddleware::class)

 */
class PayController
{
    /**
     * 生成订单商品信息
     * @RequestMapping(route="pay",method=RequestMethod::POST)
     * @Validate(validator="orderValidator",fields={"order_list","pay_sign"})
     * @param Request $request
     * @return  string
     */
    public function payOrder(Request $request)
    {
        $params = $request->getParsedBody();

        /** @var PayService $payService */
        $payService = BeanFactory::getBean(PayService::class);

        $data = $payService->pay($request->user_id,$request->integral,$params);

        return Message::success($data);
    }

}
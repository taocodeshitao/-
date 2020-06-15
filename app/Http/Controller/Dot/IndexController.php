<?php declare(strict_types=1);

namespace App\Http\Controller\Dot;

use App\Model\Service\ActivityService;
use App\Model\Service\DotService;
use App\Model\Service\OrderService;
use App\Model\Service\ProductService;
use App\Model\Service\SystemService;
use App\Http\Middleware\MemberMiddleware;
use App\Http\Middleware\DotMiddleware;
use App\Common\Message;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 网点端
 * Class IndexController
 *
 * @Controller(prefix="/api/dot")
 */
class IndexController{

    /**
     * 网点客户登录
     *
     * @RequestMapping(route="login",method={RequestMethod::POST})
     * @param Request $request
     * @return string
     */
    public  function login(Request $request)
    {

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $token = $dotService->login($params);

        return Message::success($token);
    }
    /**
     * 客户经理工作台
     *
     * @RequestMapping(route="info",method={RequestMethod::POST})
     * @Middleware(DotMiddleware::class)
     * @return array
     */
    public function dotInfo(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();
        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getDotInfo($params);

        return Message::success($data);
    }


    /**
     * 网点端修密码
     * @RequestMapping(route="editPassword",method={RequestMethod::POST})
     * @Middleware(DotMiddleware::class)
     * @return array
     */
    public function dotEditPassword(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();
        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotEditPassword($params);

        return Message::success($data);

    }

    /**
     * 退出登录
     * @RequestMapping(route="outLogin",method={RequestMethod::POST})
     * @Middleware(DotMiddleware::class)
     * @return array
     */
    public function dotOutLogin(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();
        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotOutLogin($params);

        return Message::success($data);

    }


    /**
     * 代客下单确认订单
     * @RequestMapping(route="confirmOrderInfo",method={RequestMethod::POST})
     * @Middleware(MemberMiddleware::class)
     * @return array
     */
    public function dotConfirmOrderInfo(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $OrderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->dotConfirmOrderInfo($params);

        return Message::success($data);
    }

    /**
     * 代客下单，确认提交订单
     * @RequestMapping(route="dotCreateOrder",method={RequestMethod::POST})
     * @Middleware(MemberMiddleware::class)
     * @return array
     */
    public function dotCreateOrder(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $OrderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->dotCreateOrder($params);

        return Message::success($data);
    }

    /**
     * 获取区域信息
     * @RequestMapping(route="dotRegionList",method={RequestMethod::POST})
     * @Middleware(MemberMiddleware::class)
     * @return array
     */
    public function dotRegionList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getDotList();

        return Message::success($data);
    }


    /**
     * 根据区域ID查询网点
     * @RequestMapping(route="dotRegionDotList",method={RequestMethod::POST})
     * @Middleware(MemberMiddleware::class)
     * @return array
     */
    public function getRegionDotList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getRegionDotList($params);

        return Message::success($data);

    }

    /**
     * 会员端商品详情
     * @RequestMapping(route="dotProductDetails",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(DotMiddleware::class)
     *
     */
    public function getDotProductDetails(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $activityService */
        $activityService = BeanFactory::getBean(ActivityService::class);

        $data = $activityService->getProductdetails($params);

        return Message::success($data);
    }
}
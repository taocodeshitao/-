<?php declare(strict_types=1);

namespace App\Http\Controller\Mmeber;

use App\Model\Dao\ActivityProductDao;
use App\Model\Service\ActivityService;
use App\Model\Service\DotService;
use App\Model\Service\HaveProductService;
use App\Model\Service\MemberService;
use App\Model\Service\OrderService;
use App\Model\Service\ProductService;
use App\Model\Service\SystemService;
use App\Http\Middleware\MemberMiddleware;
use App\Common\Message;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 会员端
 * Class MemberController
 *
 * @Controller(prefix="/api/member")
 *
 */
class MemberController{

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
        /** @var MemberService $memberService */
        $memberService = BeanFactory::getBean(MemberService::class);

        $data = $memberService->login($params);

        return Message::success($data);
    }

    /**
     * 确认订单（线下）
     * @RequestMapping(route="createOrderLower",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function createOrderLower(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->createOrderLower($params);

        return Message::success($data);
    }

    /**
     * 确认订单（线上）
     * @RequestMapping(route="createOrderUpper",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function createOrderUpper(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->createOrderUpper($params);

        return Message::success($data);
    }

    /**
     * 会员端活动详情
     * @RequestMapping(route="getActivityProductList",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function getActivityProductList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var MemberService $memberService */
        $memberService = BeanFactory::getBean(MemberService::class);

        $data = $memberService->getActivityProductList($params);

        return Message::success($data);
    }

    /**
     *会员端活动详情（商品等级列表）
     * @RequestMapping(route="getMemberProductList",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function memberActivityDetails(Request $request){

        //1，获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $ActivityService */
        $ActivityService = BeanFactory::getBean(ActivityService::class);


        $data = $ActivityService->memberActivityDetails($params);


        return Message::success($data);
    }

    /**
     * 会员活动列表
     * @RequestMapping(route="activityList",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function memberActivityList(Request $request){
        //获取请求参数
        $params =$request->getParsedBody();

        /** @var ActivityService $ActivityService */
        $ActivityService = BeanFactory::getBean(ActivityService::class);

        $data = $ActivityService->getList1($params);

        return Message::success($data);
    }

    /**
     * 会员端确认订单
     * @RequestMapping(route="confirmOrderInfo",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     * @return string
     */
    public function confirmOrderInfo(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var MemberService $memberService */
        $memberService = BeanFactory::getBean(MemberService::class);

        $data = $memberService->memberConfirmOrderInfo($params);

        return Message::success($data);
    }

    /**
     * 订单列表（会员端）
     * @RequestMapping(route="memberOrderList",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function memberOrderList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->memebrOrdersList($params);

        return Message::success($data);
    }


    /**
     * 会员端商品详情
     * @RequestMapping(route="productDetails",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     *
     */
    public function getProductdetails(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $activityService */
        $activityService = BeanFactory::getBean(ActivityService::class);

        $data = $activityService->getProductdetails($params);

        return Message::success($data);
    }

    /**
     * 会员订单详情
     * @RequestMapping(route="memberOrderInfo",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function getMemberOrderInfo(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var OrderService $orderService */
        $orderService = BeanFactory::getBean(OrderService::class);

        $data = $orderService->memberOrderInfo($params);

        return Message::success($data);
    }

    /**
     * 根据网点，区域，活动ID查询库存
     * @RequestMapping(route="getProductStock",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function getProductStock(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $activityService */
        $activityService = BeanFactory::getBean(ActivityService::class);

        $data = $activityService->getProductStock($params);

        return Message::success($data);

    }

    /**
     * 我的权益
     * @RequestMapping(route="getMyEquity",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function getMyEquity(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $activityService */
        $activityService = BeanFactory::getBean(ActivityService::class);

        $data = $activityService->getMyEquity($params);

        return Message::success($data);
    }

    /**
     *会员扫描领取
     * @RequestMapping(route="memberReceive",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function memberReceive(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $haveProductService */
        $activityService = BeanFactory::getBean(HaveProductService::class);

        $data = $activityService->memberReceive($params);

        return Message::success($data);

    }

    /**
     *会员扫描确认领取
     * @RequestMapping(route="memberConfirmReceive",method={RequestMethod::POST})
     * @param Request $request
     * @Middleware(MemberMiddleware::class)
     */
    public function memberConfirmReceive(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $haveProductService */
        $activityService = BeanFactory::getBean(HaveProductService::class);

        $data = $activityService->memberConfirmReceive($params);

        return Message::success($data);

    }


}
<?php declare(strict_types=1);

namespace App\Http\Controller\Activity;

use App\Model\Service\ActivityService;
use App\Model\Service\DotService;
use App\Model\Service\ProductService;
use App\Model\Service\SystemService;
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
 * 活动
 * Class ActivityController
 *
 * @Controller(prefix="/api/activity")
 * @Middleware(DotMiddleware::class)
 */
class ActivityController
{
    /**
     * 活动列表
     *
     * @RequestMapping(route="activityList",method={RequestMethod::POST})
     * @param Request $request
     *
     * @return string
     */
    public  function activityList(Request $request)
    {

        //获取请求参数
        $params= $request->getParsedBody();

        /** @var ActivityService $ActivityService */
        $ActivityService = BeanFactory::getBean(ActivityService::class);

        $data = $ActivityService->getList($params);

        return Message::success($data);
    }


    /**
     * 活动详情
     * @RequestMapping(route="details",method={RequestMethod::POST})
     *
     */
    public function activityDetails(Request $request){

        //1，获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $ActivityService */
        $ActivityService = BeanFactory::getBean(ActivityService::class);

        $data = $ActivityService->activityDetails($params);


        return Message::success($data);
    }

    /**
     * 代客下单生成二维码
     * @RequestMapping(route="getActivityCodeUrl",method={RequestMethod::POST})
     * @param Request $request
     */
    public function activityQrCodeUrl(Request $request){
        //1，获取请求参数
        $params = $request->getParsedBody();

        /** @var ActivityService $ActivityService */
        $ActivityService = BeanFactory::getBean(ActivityService::class);

        $data = $ActivityService->activityQrCodeUrl($params);

        return Message::success($data);
    }
}
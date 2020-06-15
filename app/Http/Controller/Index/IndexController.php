<?php declare(strict_types=1);

namespace App\Http\Controller\Index;

use App\Model\Service\ProductService;
use App\Model\Service\SystemService;
use App\Common\Message;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 商城首页
 * Class IndexController
 *
 * @Controller(prefix="/api/system")
 */
class IndexController
{

    /**
     * 获取商城配置信息
     *
     * @RequestMapping(route="getInfo",method={RequestMethod::POST})
     * @param Request $request
     * @Validate(validator="commonValidator",fields={"mark"})
     * @return string
     */
    public  function getMallInfo(Request $request)
    {
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getMallInfo($params['mark']);

        return Message::success($data);
    }

    /**
     * 获取商城栏目
     * @RequestMapping(route="getSubject",method={RequestMethod::POST})
     *
     * @return  string
     */
    public  function getSubjects()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getSubjectList();

        return Message::success($data);
    }

    /**
     * 获取商城限时活动
     * @RequestMapping(route="getLimit",method={RequestMethod::POST})
     * @return  string
     */
    public  function getLimitActivity()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getLimitInfo();

        return Message::success($data);
    }


    /**
     * 获取商城专区(热销榜和新品会)
     * @RequestMapping(route="getArrondy",method={RequestMethod::POST})
     * @return  string
     */
    public  function getArrondy()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getArrondy();

        return Message::success($data);
    }

    /**
     * 获取自定义商城专区(趣玩乐,优生活)
     * @RequestMapping(route="getCustomize",method={RequestMethod::POST})
     * @return  string
     */
    public  function getCustomizeArrondy()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getCustomize();

        return Message::success($data);
    }

    /**
     * 获取商城通知消息
     * @RequestMapping("getNotice",method={RequestMethod::POST})
     */
    public  function getNotice()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getNotice();

        return Message::success($data);
    }

    /**
     * 猜你喜欢
     * @RequestMapping("getLovely",method={RequestMethod::POST})
     * @param Request $request
     * @return  string
     */
    public  function getLovely(Request $request)
    {
        $user_token = $request->getHeaderLine("token");

        /** @var ProductService $productService */
        $productService = BeanFactory::getBean(ProductService::class);

        $data = $productService->getLovelyList($user_token);

        return Message::success($data);
    }


    /**
     * 获取热门搜索
     * @RequestMapping(route="getHotSearch",method={RequestMethod::POST})
     * @return string
     */
    public  function getHotSearch()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getHotSearch();

        return Message::success($data);
    }


    /**
     * 获取分类信息
     * @RequestMapping(route="getCategory",method={RequestMethod::POST})
     * @return string
     */
    public  function getCategory()
    {
        /** @var SystemService $systemService */
        $systemService = BeanFactory::getBean(SystemService::class);

        $data = $systemService->getCategory();

        return Message::success($data);
    }
}
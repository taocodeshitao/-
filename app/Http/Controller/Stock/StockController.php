<?php declare(strict_types=1);

namespace App\Http\Controller\Stock;

use App\Model\Service\DotService;
use App\Model\Service\DotService1;
use App\Model\Service\HaveProductService;
use App\Model\Service\OrderService;
use App\Model\Service\ProductService;
use App\Model\Service\SystemService;
use App\Http\Middleware\MemberMiddleware;
use App\Http\Middleware\DotMiddleware;
use App\Common\Message;
use Swoft\Bean\Annotation\Mapping\Inject;
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
 * @Controller(prefix="/api/stock")
 * @Middleware(DotMiddleware::class)
 */
class StockController{

    /**
     * @Inject()
     * @var DotService
     */
    protected  $dotService1;

    /**
     * 库存管理员工作台
     *
     * @RequestMapping(route="info",method={RequestMethod::POST})
     * @return array
     */
    public function dotInfo(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();
        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getStockInfo($params);

        return Message::success($data);

    }
    /**
     * 库存管理员入库确认列表
     * @RequestMapping(route="stockConfirmAddList",method={RequestMethod::POST})
     * @return array
     */
    public function dotStockConfirmAddList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();
        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotStockConfirmAddList($params);

        return Message::success($data);
    }
    /**
     * 库存管理员出库列表
     * @RequestMapping(route="stockConfirmOutList",method={RequestMethod::POST})
     * @return array
     */
    public function dotStockConfirmOutList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotStockConfirmOutList($params);

        return Message::success($data);

    }

    /**
     * 库存管理员入库确认
     * @RequestMapping(route="stockConfirmAdd",method={RequestMethod::POST})
     * @return array
     */
    public function dotStockConfirmAdd(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotStockConfirmAdd($params);

        return Message::success($data);
    }

    /**
     * 库存管理员出库确认
     * @RequestMapping(route="stockConfirmOut",method={RequestMethod::POST})
     * @return array
     */
    public function dotStockConfirmOut(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        //var_dump(BeanFactory::hasBean(DotService::class));
        $dotService = BeanFactory::getBean(DotService1::class);
        //var_dump(get_class_methods($dotService));
        $data = $dotService->dotStockConfirmOut111($params);

       return Message::success($data);
    }

    /**
     * 商品报损
     * @RequestMapping(route="confirmLoss",method={RequestMethod::POST})
     * @return array
     */
    public function dotConfirmLoss(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotConfirmLoss($params);

        return Message::success($data);

    }

    /**
     * 商品库存列表
     * @RequestMapping(route="productStockList",method={RequestMethod::POST})
     * @return array
     */
    public function dotProductStockList(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotProductStockList($params);

        return Message::success($data);
    }

    /**
     * 库存详情
     * @RequestMapping(route="productStockDetails",method={RequestMethod::POST})
     * @return array
     */
    public function dotProductStockDetails(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->dotProductStockDetails($params);

        return Message::success($data);

    }

    /**
     * 库存变动记录列表
     * @RequestMapping(route="getStockChangeList",method={RequestMethod::POST})
     * @return array
     */
    public function getStockChangeList(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getStockChangeList($params);

        return Message::success($data);

    }

    /**
     * @RequestMapping(route="getStockChangeDetails",method={RequestMethod::POST})
     * @param Request $request
     */
    public function getStockChangeDetails(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getStockChangeDetails($params);
        if ($data['type']==2&&$data['out_of_stock_user_id']>0){
            $data['receive_num']=$data['recovery_confirm_num'];
        }
        return Message::success($data);
    }

    /**
     * 变动明细（订单出库）
     * @RequestMapping(route="getStockChangeOrderDetails",method={RequestMethod::POST})
     * @param Request $request
     */
    public function getStockChangeOrderDetails(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var DotService $DotService */
        $dotService = BeanFactory::getBean(DotService::class);

        $data = $dotService->getStockChangeOrderDetails($params);

        return Message::success($data);
    }


    /**
     * 行内商品列表
     * @RequestMapping(route="haveProductList",method={RequestMethod::POST})
     * @param Request $request
     */
    public function getHaveProductList(Request $request){

        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->getHaveProductList($params);

        return Message::success($data);
    }

    /**
     * 新商品入库
     * @RequestMapping(route="haveProductAdd",method={RequestMethod::POST})
     * @param Request $request
     *
     */
    public function haveProductAdd(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->haveProductAdd($params);

        return Message::success($data);

    }

    /**
     *新增库存
     * @RequestMapping(route="addProductStock",method={RequestMethod::POST})
     * @param Request $request
     */
    public function addProductStock(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->addProductStock($params);

        return Message::success($data);
    }


    /**
     * 删除商品
     * @RequestMapping(route="delProduct",method={RequestMethod::POST})
     * @param Request $request
     */
    public function delProduct(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->delProduct($params);

        return Message::success($data);
    }

    /**
     * 库存变动记录
     * @RequestMapping(route="stockLog",method={RequestMethod::POST})
     * @param Request $request
     */
    public function stockLog(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->stockLog($params);

        return Message::success($data);
    }

    /**
     * 导出商品数据
     * @RequestMapping(route="haveProductExport",method={RequestMethod::POST})
     * @param Request $request
     *
     */
    public function haveProductExport(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->getHaveProductExport($params);

        return Message::success($data);
    }


    /**
     * 变动记录导出
     */


    /**
     * 商品出库
     * @RequestMapping(route="haveProductOutbound",method={RequestMethod::POST})
     * @param Request $request
     */

    public function haveProductOutbound(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->haveProductOutbound($params);

        return Message::success($data);
    }

    /**
     * 库存变动记录详情
     * @RequestMapping(route="stockLogDetails",method={RequestMethod::POST})
     * @param Request $request
     */
    public function stockLogDetails(Request $request){
        //获取请求参数
        $params = $request->getParsedBody();

        /** @var HaveProductService $HaveProductService */
        $haveService = BeanFactory::getBean(HaveProductService::class);

        $data = $haveService->stockLogDetails($params);

        return Message::success($data);

    }
}
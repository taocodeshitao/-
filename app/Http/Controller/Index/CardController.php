<?php


namespace App\Http\Controller\Index;


use App\Common\Message;
use App\Http\Middleware\AuthMiddleware;
use App\Model\Service\CardService;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\Middleware;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 福卡
 * Class CardController
 *
 * @Controller(prefix="api/card/")
 */
class CardController
{

    /**
     * 福卡验证
     * @RequestMapping(route="verify",method=RequestMethod::POST)
     * @Validate(validator="commonValidator",fields={"serialNumber"})
     * @param Request $request
     * @return string
     */
    public  function verifyCard(Request $request)
    {

        $params = $request->getParsedBody();

        /** @var CardService $cardService */
        $cardService = BeanFactory::getBean(CardService::class);

        $data = $cardService->_verifyEnable($params['serialNumber']);

        return Message::success($data);
    }

    /**
     * 福卡兑换
     * @RequestMapping(route="exchange",method=RequestMethod::POST)
     * @Validate(validator="commonValidator",fields={"serialNumber"})
     * @Middleware(AuthMiddleware::class)
     * @param Request $request
     * @return string
     */
    public  function exchangeCard(Request $request)
    {
        $params = $request->getParsedBody();

        /** @var CardService $cardService */
        $cardService = BeanFactory::getBean(CardService::class);

        $data = $cardService->exchange($request->user_id,$params['serialNumber']);

        return Message::success($data);
    }
}
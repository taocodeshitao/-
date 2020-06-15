<?php declare(strict_types=1);

namespace App\Tcp\Controller;

use App\Tcp\Middleware\DemoMiddleware;
use Swoft\Tcp\Server\Annotation\Mapping\TcpController;
use Swoft\Tcp\Server\Annotation\Mapping\TcpMapping;
use Swoft\Tcp\Server\Request;
use Swoft\Tcp\Server\Response;

/**
 *
 * Class DemoController
 *
 * @TcpController(middlewares={DemoMiddleware::class})
 */
class DemoController
{
    /**
     * @TcpMapping("list", root=true)
     * @param Response $response
     */
    public function list(Response $response): void
    {
        $response->setData('[list]allow command: list, echo, demo.echo');
    }

    /**
     * @TcpMapping("echo")
     * @param Request  $request
     * @param Response $response
     */
    public function index(Request $request, Response $response): void
    {
        $str = $request->getPackage()->getDataString();

        $response->setData('[demo.echo]hi, we received your message: ' . $str->data);
    }

    /**
     * @TcpMapping("strrev", root=true)
     * @param Request  $request
     * @param Response $response
     */
    public function strRev(Request $request, Response $response): void
    {
        $str = $request->getPackage()->getDataString();

        $response->setData(\strrev($str));
    }

    /**
     * @TcpMapping("echo", root=true)
     * @param Request  $request
     * @param Response $response
     */
    public function echo(Request $request, Response $response): void
    {
     //var_dump( $request->getPackage());
     //给客户ID为1的发送消息
        if ( $request->getFd()==1){
         //像客户端推送消息
            $response->setContent("你中奖了");
         }else{
            $response->setContent("再接再厉，加油~~~");
         }

        //$response->setContent("12121");
       /* if ($request->getReactorId()=="nihao"){

            $response->setContent("你也好！");;
        }*/
        //$str = $request->getPackage()->getDataString();
        //var_dump($request->getFd());

        /*
            $response->setContent("12121");
        $response->setData('[echo]hi, we received your message: ' .  $str->data);*/

    }
}
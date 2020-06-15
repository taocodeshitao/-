<?php declare(strict_types=1);
/**
 * This file is part of Swoft.
 *
 * @link     https://swoft.org
 * @document https://swoft.org/docs
 * @contact  group@swoft.org
 * @license  https://github.com/swoft-cloud/swoft/blob/master/LICENSE
 */

namespace App\WebSocket;

use Swoft\Http\Message\Request;
use Swoft\Redis\Redis;
use Swoft\Session\Session;
use Swoft\WebSocket\Server\Annotation\Mapping\OnClose;
use Swoft\WebSocket\Server\Annotation\Mapping\OnMessage;
use Swoft\WebSocket\Server\Annotation\Mapping\OnOpen;
use Swoft\WebSocket\Server\Annotation\Mapping\WsModule;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use function server;

/**
 * Class EchoModule
 *
 * @WsModule("echo")
 */
class EchoModule
{
    /**
     * 监听创建连接监听
     * @OnOpen()
     * @param Request $request
     * @param int     $fd
     */
    public function onOpen(Request $request, int $fd): void
    {
        Session::current()->push("Opened, welcome #{$fd}!");
    }

    /**
     * 监听接收消息
     * @OnMessage()
     * @param Server $server
     * @param Frame  $frame
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        $fd_data=[
            1,2,3
        ];
        foreach ($fd_data as $v){
            $server->push($v, "用户".$frame->fd."说：". $frame->data);
        }
    }

    /**
     * 连接关闭监听
     * @OnClose()
     * @param Server $server
     * @param int    $fd
     */
    public function OnClose(Server $server, int $fd): void
    {
        $fd_data=[
            1,2,3
        ];
        //有一个用户退出了群聊，通知所有用户
        foreach ($fd_data as $v) {
            $server->push($v, "用户{$fd}：我走了拜拜！");
        }
    }

}

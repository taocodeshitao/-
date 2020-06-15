<?php

namespace App\Http\Controller\Index;
use App\Model\Service\SmsService;
use App\Rpc\Lib\RpcSmsInterface;
use App\Common\Message;
use App\Rpc\Lib\RpcTestInterface;
use App\Validator\SmsValidator;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Rpc\Client\Annotation\Mapping\Reference;
use Swoft\Task\Task;


/**
 * 测试PRC服务控制器
 * @Controller(prefix="/testrpc")
 */
class TestRpcController
{

    /**
     * @Reference(pool="testrpc.pool")
     * @var RpcTestInterface
     */
    private $rpcTetsInterface;


    /**
     * @Reference(pool="testrpc.pool", version="1.2")
     *
     * @var RpcTestInterface
     */
    private $rpcTetsInterface1;

    /**
     * @RequestMapping("testGetList")
     *
     * @return array
     */
    public function getList(): array
    {
        $result  = $this->rpcTetsInterface->getList(12, 'type');
        $result1  = $this->rpcTetsInterface1->getList(50, 'type');
        return [$result,$result1];
    }
    /**
     * @RequestMapping("testDel")
     *
     * @return array
     */
    public function testDel(){
        $result  = $this->rpcTetsInterface->delete(12);

        return $result;
    }

}
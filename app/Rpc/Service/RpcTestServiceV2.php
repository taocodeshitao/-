<?php declare(strict_types=1);


namespace App\Rpc\Service;
use App\Rpc\Lib\RpcSmsInterface;
use App\Rpc\Lib\RpcTestInterface;
use Swoft\Rpc\Server\Annotation\Mapping\Service;
use Swoft\Task\Task;


/**
 * 测试接口实现类
 * @Service(version="1.2")
 */
class  RpcTestServiceV2 implements  RpcTestInterface
{

    public function getList(int $id, $type, int $count = 10): array
    {
        return [$id ,$type,$count,'111'];
    }

    public function delete(int $id): bool
    {
        sleep(3);

        Task::async('test','testadd',['shitao',$id]);
        return true;
        // TODO: Implement delete() method.
    }

    public function getBigContent(): string
    {
        // TODO: Implement getBigContent() method.
    }

}
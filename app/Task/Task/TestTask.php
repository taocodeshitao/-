<?php declare(strict_types=1);

namespace App\Task\Task;

use App\Common\Cache;
use App\Model\Dao\CardRecordDao;
use App\Model\Dao\HistoryDao;
use App\Model\Dao\UserDao;
use App\Model\Dao\WaresDao;
use App\Model\Service\CardService;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;
use Swoft\Task\Annotation\Mapping\Task;
use Swoft\Task\Annotation\Mapping\TaskMapping;

/**
 * 异步投递任务
 * Class TestTask
 *
 * @Task(name="test")
 */
class TestTask
{
    /**
     * 测试投递任务
     * @TaskMapping(name="testadd")
     *
     * @param string    $card_password
     * @param int       $id 用户id
     * @return array
     */
    public function testadd(string $name,int $id): array
    {
        sleep(3);

        $data =[
            'name'=>$name,
            'id'=>$id
        ];

        var_dump($data);
    }

    /**
     * 测试异步写日志
     * @TaskMapping(name="addLog")
     *
     * @param string $data
     * @return string
     */
    public function addLog(string $return_data,string $data):array
    {

        sleep(5);
        //写入文件
        $text = '--------'.date("Y-m-d H:i:s")."  测试标题"."-----------";
        $text .= "\r\n 发送的数据:".$return_data;
        $text .= "\r\n 返回的数据:".$data;
        $text .= "\r\n----------------------------------------------------------------------\r\n\r\n";
        $path = '/tao.swoft.com/runtime/test/'.date("Y")."/".date("m")."/";

        if(!is_dir($path)) {
            // 创建目录
            if(!mkdir($path,0777,true)){
                return false;
            }
        }
        $filename = $path.date("d").".log";
        $text = iconv("UTF-8","UTF-8//IGNORE",$text);

        $handler = fopen($filename ,'a');
        @fwrite($handler, $text);
        @fclose($handler);

        echo "成功";
       // return true;
    }
}

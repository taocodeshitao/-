<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 用户数据操作类
 * @Bean()
 */
class UserDao
{
    /**
     * 根据手机号查询一条记录
     * @param String $phone 手机号
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findByPhone(String $phone)
    {
        $where['phone'] = $phone;

        return DB::table('user')->where($where)->first();

    }


    /**
     * 根据主键id查询数据
     * @param int $user_id
     * @param array $field
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findById(int $user_id,$field=['*'])
    {

        return DB::table('system_user')->where('id',$user_id)->first($field);
    }


    /**
     * 根据用户id获取用户详情
     * @param int $user_id
     * @return array
     */
    public  function findUserInfoById(int $user_id)
    {
        $data = DB::table('user')
                ->leftJoin('attachment','user.avatar','=','attachment.id')
                ->select('user.id as user_id','user.username as account','user.nickname','user.phone as mobile','user.integral as peas','attachment.url as image')
                ->where('user.id',$user_id)
                ->first();

        return $data;
    }

    /**x
     * 添加用户记录
     * @param $data
     * @return int
     */
    public  function create(array $data)
    {
        $user['password'] = password_hash(encryptPassword($data['password'],$data['mobile']),PASSWORD_DEFAULT);
        $user['phone'] = $data['mobile'];
        $user['state'] =1;
        $user['last_login_time'] =time();
        $user['last_login_ip'] =getClientIp();
        $user['username'] = $data['mobile'];
        $user['created_at'] = time();
        //添加用户记录
        $user_id = DB::table('user')->insertGetId($user);

        return $user_id;
    }

    /**
     * 根据主键id更新用户数据
     * @param int $user_id 用户$user_id
     * @param int $version
     * @param array $data 更新数据
     * @return bool
     */
    public  function updateById(int $user_id,int $version,array $data)
    {
        $where['id'] = $user_id;

        $where['version'] = $version;

        return DB::table('user')->where($where)->increment('version',1,$data);
    }


}
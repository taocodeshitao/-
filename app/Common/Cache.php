<?php declare(strict_types=1);

namespace App\Common;

/**
 * Class Cache
 * @package App\Common
 */
 class Cache
 {

     Const CACHE_PREFIX = 'welfare:';

     ####################
     const PLAT_TOKEN ='flzht_cache:welfare:operation:token';

     const AUTH_TOKEN = self::CACHE_PREFIX.'user:token:%s';

     //充值令牌
     const ACCESS_TOKEN = self::CACHE_PREFIX.'card:token';

     //用户微信openid
     const USER_OPENID = self::CACHE_PREFIX.'user:openid:%s';

     //手机验证码发送次数每天统计条数
     const MOBILE_CODE_DAY = self::CACHE_PREFIX.'mobile:code:total';

     // 手机验证码
     const MOBILE_CODE = self::CACHE_PREFIX.'mobile:code:%s:%u';

     // 手机验证码过期时间
     const MOBILE_CODE_EXPIRE = self::CACHE_PREFIX.'mobile:expire:%s:%u';

     // 首页官方banner图
     const SYSTEM_BANNER = self::CACHE_PREFIX.'system:banner:data';

     // 首页企业banner图
     const SYSTEM_BANNER_ENTERPRISE = self::CACHE_PREFIX.'system:banner:enterprise';

     //首页商城配置 ##############
     const SYSTEM_CONFIG = self::CACHE_PREFIX.'system:config:data';

     //商城分类配置
     const SYSTEM_CATEGORY = self::CACHE_PREFIX.'system:categroy:data';

     //首页栏目配置信息
     const SYSTEM_SUBJECT = self::CACHE_PREFIX.'system:subject';

     //首页通知配置信息
     const SYSTEM_NOTICE = self::CACHE_PREFIX.'system:notice';

     //首页固定专区配置
     const SYSTEM_ARRONDY_DATA = self::CACHE_PREFIX.'system:arrondy:data';

     //首页自定义专区配置
     const SYSTEM_CUSTOMIZE_DATA = self::CACHE_PREFIX.'system:customize:data';

     //限时活动配置信息 ####################
     const ACTIVITY_LIMIT_BASE = self::CACHE_PREFIX.'activity:limit:base';

     //限时活动库存信息 ####################
     const ACTIVITY_LIMIT_INVENTORY = self::CACHE_PREFIX.'activity:limit:inventory:';

     //限时活动商品信息 ####################
     const ACTIVITY_LIMIT_PRODUCT = self::CACHE_PREFIX.'activity:limit:product:%s';

     //限时活动商品排序 ####################
     const ACTIVITY_LIMIT_SORT = self::CACHE_PREFIX.'activity:limit:sort:%s';

     //文章列表信息
     const ARTICLE_LIST = self::CACHE_PREFIX.'article:list';

     //文章详情
     const ARTICLE_DETAILS = self::CACHE_PREFIX.'article:details:%s';

     //商品详情信息 ####################
     const PRODUCT_DETAILS =  self::CACHE_PREFIX.'product:details:data';

     //用户地址
     const ADDRESS_LIST = self::CACHE_PREFIX.'user:address:%s';

     //用户收藏列表信息
     const COLLECT_LIST = self::CACHE_PREFIX.'user:collect:%s';

     //用户历史记录信息
     const HISTORY_LIST = self::CACHE_PREFIX.'user:history:%s';

     //用户购物车
     const CART_DATA   = self::CACHE_PREFIX.'user:cart:%s';

     //用户历史访问商品分类
     const HISTORY_CATEGORY   = self::CACHE_PREFIX.'user:history:%s';


     /****************************************************************************/


     const OPENID_TTL=7200;

     const COLLECT_TTL = 3600;

     const ADDRESS_TTL =3600;

     const HISTORY_TTL=7*24*3600;

     const ARRONDY_TTL=600;

     const CUSTOMIZE_TTL=800;

 }
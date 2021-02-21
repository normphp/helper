<?php
/**
 * 自定义函数
 */
declare (strict_types = 1);
use \normphp\helper\Helper;
use \normphp\staging\App;
use \container\app\AppContainer;

if (!function_exists('app')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @return  container\app\AppContainer
     */
    function app():App
    {
        return \normphp\staging\App::init();
    }
}
if (!function_exists('succeed')) {
    /**
     * @Author pizepei
     * @Created 2019/2/15 23:14
     * @Author pizepei
     * @Created 2019/2/15 23:02
     * @param     $data
     * @param     $msg 状态说明
     * @param     $code 状态码
     * @param int $count
     * @return array
     * @title  控制器成功返回(会结束当前业务)
     */
    function succeed($data,$msg='',$code='',$count=0)
    {
        return App()->Response()->succeed($data,$msg,$code,$count);
    }
}

if (!function_exists('error')) {
    /**
     * @Author pizepei
     * @Created 2019/2/15 23:09
     * @param $msg  错误说明
     * @param $code  错误代码
     * @param $data 错误详细信息
     * @return array
     * @title  控制器错误返回
     */
    function error($msg='',$code='',$data=[])
    {
        return App()->Response()->error($msg,$code,$data);
    }
}


if (!function_exists('Helper')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @param bool $new  是否强制实例化
     * @return  Helper
     */
    function Helper(bool $new = false):Helper
    {
        return Helper::init($new);
    }
}
if (!function_exists('HelperClass')) {
    /**
     * 快速获取容器中的实例 支持依赖注入
     * @param bool $new  是否强制实例化
     * @return  \app\HelperClass
     */
    function HelperClass(bool $new = false):Helper
    {
        /**
         * 使用容器返回需要的来的实例
         * 函数的:Helper用了强制控制返回的实例
         * 注解中的 @return  \app\HelperClass 用来适配IDE并不会实例化HelperClass类，和方便注册服务
         * 并不会实例化HelperClass类，Helper类会在实例化时获取到HelperClass的private $childContainer属性用来注册服务
         */
        return Helper::init($new);
    }
}
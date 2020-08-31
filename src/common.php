<?php
// +----------------------------------------------------------------------
// | LHSystem
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 青海云音信息技术有限公司 [ http://www.yyinfos.com ]
// +----------------------------------------------------------------------
// | 官方网站: https://www.yyinfos.com
// +----------------------------------------------------------------------
// | 作者：独角戏 <qhweb@foxmail.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

use think\facade\Event;
use think\facade\Route;
use think\helper\{
    Str, Arr
};

\think\Console::starting(function (\think\Console $console) {
    $console->addCommands([
        'addons:config' => '\\YYCms\\command\\SendConfig'
    ]);
});




// 插件类库自动载入
spl_autoload_register(function ($class) {

    $class = ltrim($class, '\\');

    $dir = app()->getRootPath();
    $namespace = 'addons';

    if (strpos($class, $namespace) === 0) {
        $class = substr($class, strlen($namespace));
        $path = '';
        if (($pos = strripos($class, '\\')) !== false) {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }
        $path .= str_replace('_', '/', $class) . '.php';
        $dir .= $namespace . $path;

        if (file_exists($dir)) {
            include $dir;
            return true;
        }

        return false;
    }

    return false;

});

if (!function_exists('hook')) {
    /**
     * 处理插件钩子
     * @param string $event 钩子名称
     * @param array|null $params 传入参数
     * @param bool $once 是否只返回一个结果
     * @return mixed
     */
    function hook($event, $params = null, bool $once = false)
    {
        $result = Event::trigger($event, $params, $once);

        return join('', $result);
    }
}

if (!function_exists('get_addons_info')) {
    /**
     * 读取插件的基础信息
     * @param string $name 插件名
     * @return array
     */
    function get_addons_info($name)
    {
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }

        return $addon->getInfo();
    }
}

if (!function_exists('get_addons_instance')) {
    /**
     * 获取插件的单例
     * @param string $name 插件名
     * @return mixed|null
     */
    function get_addons_instance($name)
    {
        static $_addons = [];
        if (isset($_addons[$name])) {
            return $_addons[$name];
        }
        $class = get_addons_class($name);
        if (class_exists($class)) {
            $_addons[$name] = new $class(app());

            return $_addons[$name];
        } else {
            return null;
        }
    }
}

if (!function_exists('get_addons_class')) {
    /**
     * 获取插件类的类名
     * @param string $name 插件名
     * @param string $type 返回命名空间类型
     * @param string $class 当前类名
     * @return string
     */
    function get_addons_class($name, $type = 'hook', $class = null)
    {
        $name = trim($name);
        // 处理多级控制器情况
        if (!is_null($class) && strpos($class, '.')) {
            $class = explode('.', $class);

            $class[count($class) - 1] = Str::studly(end($class));
            $class = implode('\\', $class);
        } else {
            $class = Str::studly(is_null($class) ? $name : $class);
        }
        switch ($type) {
            case 'controller':
                $namespace = '\\addons\\' . $name . '\\controller\\' . $class;
                break;
            default:
                $namespace = '\\addons\\' . $name . '\\Plugin';
        }

        return class_exists($namespace) ? $namespace : '';
    }
}

if (!function_exists('addons_url')) {
    /**
     * 插件显示内容里生成访问插件的url
     * @param $url
     * @param array $param
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return bool|string
     */
    function addons_url($url = '', $param = [], $suffix = true, $domain = false)
    {
        $request = app('request');
        if (empty($url)) {
            // 生成 url 模板变量
            $addons = $request->addon;
            $controller = $request->controller();
            $controller = str_replace('/', '.', $controller);
            $action = $request->action();
        } else {
            $url = Str::studly($url);
            $url = parse_url($url);
            if (isset($url['scheme'])) {
                $addons = strtolower($url['scheme']);
                $controller = $url['host'];
                $action = trim($url['path'], '/');
            } else {
                $route = explode('/', $url['path']);
                $addons = $request->addon;
                $action = array_pop($route);
                $controller = array_pop($route) ?: $request->controller();
            }
            $controller = Str::snake((string)$controller);

            /* 解析URL带的参数 */
            if (isset($url['query'])) {
                parse_str($url['query'], $query);
                $param = array_merge($query, $param);
            }
        }

        return Route::buildUrl("@addons/{$addons}/{$controller}/{$action}", $param)->suffix($suffix)->domain($domain);
    }
}

function pr($value='')
{
  print_r($value);exit;
}
/**
 * 获取拼音
 * @param $string
 * @param string $encoding
 */
function Pinyin($string, $encoding = 'utf-8'){
    return \YYCms\Service::Pinyin()->getPinyin($string, $encoding);
}

/**
 * 获取拼音缩写
 * @param $string
 * @param string $encoding
 */
function ShortPinyin($string, $encoding = 'utf-8'){
    return \YYCms\Service::Pinyin()->getShortPinyin($string, $encoding);
}

/**
 * 加密函数
 * @param $txt
 * @param string $key
 * @return string
 */
function lock_url($txt,$key='qhweb')
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $nh = rand(0,61);
    $ch = $chars[$nh];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = base64_encode($txt);
    $tmp = '';
    $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh+strpos($chars,$txt[$i])+ord($mdKey[$k++]))%65;
        $tmp .= $chars[$j];
    }
    return urlencode($ch.$tmp);
}

/**
 * 解密函数
 * @param $txt
 * @param string $key
 * @return bool|string
 */
function unlock_url($txt,$key='qhweb')
{
    $txt = urldecode($txt);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $ch = $txt[0];
    $nh = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $txt = substr($txt,1);
    $tmp = '';
    $k = 0;
    for ($i=0; $i<strlen($txt); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
        while ($j<0) $j+=65;
        $tmp .= $chars[$j];
    }
    return base64_decode($tmp);
}

/**
 * 缩略图生成
 * @param $srcPath 图片原地址
 * @param string $newWidth  缩略图图片的宽，默认200
 */
function ThumbSrc($srcPath,$newWidth='200'){
    if(empty($srcPath)) return '';
    $strInfo = parse_url($srcPath);
    if(isset($strInfo['host']) && $strInfo['host'] != $_SERVER['HTTP_HOST']) return $srcPath;
    $extension = pathinfo($strInfo['path'],PATHINFO_EXTENSION);
    $newSrc =  $strInfo['path'].'.w'.$newWidth.'.'.$extension;
    return (isset($strInfo['scheme']) ? $strInfo['scheme'] .'://' . $strInfo['host'] : '') . $newSrc;
}

/**
 * 404错误页面
 */
function _404($text=''){
    $text = !empty($text) ? $text : '对不起，您请求的页面不存在、或已被删除、或暂时不可用';
    $head404   = "data:image/png;base64," . base64_encode(file_get_contents(__DIR__ .'/../assets/image/head404.png'));
    $txtbg404   = "data:image/png;base64," . base64_encode(file_get_contents(__DIR__ .'/../assets/image/txtbg404.png'));
    $html='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <title>404-对不起！您访问的页面不存在</title>
            <style type="text/css">
            .head404{ width:580px; height:234px; margin:50px auto 0 auto; background:url('.$head404.') no-repeat; }
            .txtbg404{ width:499px; height:169px; margin:10px auto 0 auto; background:url('.$txtbg404.') no-repeat;}
            .txtbg404 .txtbox{ width:390px; position:relative; top:30px; left:60px;color:#eee; font-size:13px;}
            .txtbg404 .txtbox p {margin:5px 0; line-height:18px;}
            .txtbg404 .txtbox .paddingbox { padding-top:15px;}
            .txtbg404 .txtbox p a { color:#eee; text-decoration:none;}
            .txtbg404 .txtbox p a:hover { color:#FC9D1D; text-decoration:underline;}
            </style>
            </head>
            <body bgcolor="#494949">
                <div class="head404"></div>
                <div class="txtbg404">
              <div class="txtbox">
                  <p>'.$text.'</p>
                  <p class="paddingbox">请点击以下链接继续浏览网页</p>
                  <p>》<a style="cursor:pointer" onclick="history.back()">返回上一页面</a></p>
                  <p>》<a href="'.request()->domain().'">返回网站首页</a></p>
                </div>
              </div>
            </body>
            </html>';
    exit($html);
}




//删除目录（递归删除）
function delDir($dir){
  //传入文件的路径
  //遍历目录
  $arr = scandir($dir);
  foreach ($arr as $val) {
      if ($val != '.' && $val != '..') {
          //路径链接
          $file = $dir . '/' . $val;
          if (is_dir($file)) {
              delDir($file);
          } else {
              unlink($file);
          }
      }
  }
  rmdir($dir);
}


// 目录复制
function copyDir($dir1, $dir2){
    if(!file_exists($dir1)) return true;
    if (!file_exists($dir2)) {
        $cdir = mkdir($dir2,0777);
    }

    //遍历原目录
    $arr = scandir($dir1);
    foreach ($arr as $val) {
        if ($val != '.' && $val != '..') {
            //原目录拼接
            $sfile = $dir1 . '/' . $val;
            //目的目录拼接
            $dfile = $dir2 . '/' . $val;
            if (is_dir($sfile)) {
                copyDir($sfile, $dfile);
            } else {
                copy($sfile, $dfile);
            }
        }
    }
}


if (!function_exists('moveDir')) {
  // 移动目录
  function moveDir($dir1, $dir2){
      copyDir($dir1, $dir2);
      delDir($dir1);
  }
}
/**
* 创建文件夹
*/
function createDir($path, $mode = 0777){
  if (is_dir($path))
      return TRUE;
  $ftp_enable = 0;
  $path = format_dir_path($path);
  $temp = explode('/', $path);
  $cur_dir = '';
  $max = count($temp) - 1;
  for ($i = 0; $i < $max; $i++) {
      $cur_dir .= $temp[$i] . '/';
      if (@is_dir($cur_dir))
          continue;
      @mkdir($cur_dir, 0777, true);
      @chmod($cur_dir, 0777);
  }
  return is_dir($path);
}



/**
* 获取文件夹路径
*/
function format_dir_path($path=''){
  if(empty($path)) return '';
  $path = str_replace('\\', '/', $path);
  if (substr($path, -1) != '/')  $path = $path . '/';
  return $path;
}


/**
* 字节格式化
*/
function filesize_formatted($path){
  $size = filesize($path);
  $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $power = $size > 0 ? floor(log($size, 1024)) : 0;
  return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}
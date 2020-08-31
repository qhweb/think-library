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

namespace YYCms;

use think\App;
use think\Container;

/**
 * 自定义接口基类
 * Class Server
 * @package yycms
 */
class Server
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;
    /**
     * 定义当前版本
     * @var string
     */
    const VERSION = '1.2.23';

    /**
     * 静态配置
     * @var DataArray
     */
    private static $config;

    /**
     * 静态缓存
     * @var static
     */
    protected static $cache;

    /**
     * Api constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    /**
     * 初始化服务
     * @return $this
     */
    protected function initialize(): Server
    {
        return $this;
    }

    /**
     * 静态实例对象
     * @return static
     */
    public static function instance(): Server
    {
        return Container::getInstance()->make(static::class);
    }


    /**
     * 静态魔术加载方法
     * @param string $name 静态类名
     * @param array $arguments 参数集合
     * @return mixed
     * @throws InvalidInstanceException
     */
    public static function __callStatic($name, $arguments)
    {
        $class = 'YYCms\\library\\' . $name;
        if (!empty($class) && class_exists($class)) {
            $option = array_shift($arguments);
            $config = is_array($option) ? $option : [];
            $key = md5($class . serialize($config));
            if (isset(self::$cache[$key])) return self::$cache[$key];
            return self::$cache[$key] = new $class($config);
        }
        throw new Exception("class {$name} not found");
    }   
    // 获取版本
    public function getVersion()
    {
       return VERSION;
    }

    /**
     * 关键字分词自动获取
     * @param  string  $title    标题
     * @param  string  $content  内容
     * @param  boolean $loadInit 初始化类时是否直接加载词典
     * @return array            [description]
     */
    public function getKeywords($title = "", $content = "",$loadInit = false)
    {
        if (empty ( $title )) {return array ();}
        $class = 'YYCms\\library\\SplitWord';
        $data = $title . $title . $title . $content; // 为了增加title的权重，这里连接3次
        $class::$loadInit = $loadInit;  //初始化类时是否直接加载词典，选是载入速度较慢，但解析较快；选否载入较快，但解析较慢
        $pa = new $class( 'utf-8', 'utf-8', false );
        $pa->LoadDict ();  //载入词典
        $pa->SetSource ( $data );  //设置源字符串
        $pa->StartAnalysis ( true );  //是否对结果进行优化
        $tags = $pa->GetFinallyKeywords (4); // 获取文章中的五个关键字
        $tagsArr = explode(",",$tags);
        return $tagsArr;//返回关键字数组
    }
    
    /**
     * 设置模板目录
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function setTheme($template='./template/',$theme='default')
    {
        //自定义模板标签库
        $dirs = glob($this->app->getAppPath().'*');
        $files = [];
        foreach ($dirs as $key=>$val){
            $tags= glob($val. DIRECTORY_SEPARATOR . 'taglib'.DIRECTORY_SEPARATOR .'*.php');
            if($tags){
                $files[] =str_replace('.php','', str_replace($this->app->getAppPath(),basename($this->app->getAppPath()).DIRECTORY_SEPARATOR,implode(',',$tags)));
            }
        }
        $taglib = implode(',',$files);

        //前端模板配置
        $temp =  [
          // 模板路径
          'view_path'    => $template . $theme .'/',
          // 模板文件名分隔符
          // 'view_depr'    => '_',
          // 模板后缀
          'view_suffix'  => '.html',
          // 预先加载的标签库
          'taglib_pre_load'     =>    $taglib,
          'tpl_replace_string'  =>  [
            '__STATIC__'=>'/static',
            '__THEME__' => '/template/default',
          ]
        ];
        \think\facade\View::engine('Think')->config($temp);
    }
}
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

namespace YYCms\middleware;

use think\App;

class Addons
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app  = $app;
        
    }

    /**
     * 插件中间件
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        hook('addon_middleware', $request);

        return $next($request);
    }
}
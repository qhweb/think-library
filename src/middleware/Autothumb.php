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
use YYCms\Server;
use YYCms\Exception;

class Autothumb
{
    protected $app;
    protected $allowext = ['png','jpg','bmp','jpeg','gif'];

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
        $pathinfo = $request->pathinfo();
        $extension = pathinfo($pathinfo,PATHINFO_EXTENSION);
        if(!file_exists($pathinfo) && in_array($extension,$this->allowext)){
            return $this->compressImg($pathinfo);
        }
        return $next($request);
    }
    //图片压缩
    private function compressImg($path){
        $root_path = realpath($this->app->request->root());
        $desfile = $root_path . DIRECTORY_SEPARATOR . str_replace("/",DIRECTORY_SEPARATOR,$path); //目标目标路径 /var/www/http/file/abc.jpg.w320.jpg
        $dirname = dirname ( $desfile ) . "/";
        $filename = basename ( $desfile );
        $percent = 1;  #原图压缩，不缩放，但体积大大降低
        $noimg = __DIR__.'/../../assets/image.png';
        //正则获取需要放缩的图片大小，格式：/file/abc.jpg.w320.jpg
        if (!file_exists ( $desfile ) && preg_match ( "/([^\.]+\.(png|jpg|jpeg|gif))\.w([\d]+)\.(png|jpg|jpeg|gif)/i", $filename, $m )) {
            $srcfile = $dirname . $m [1];
            $width = $m [3];                    //匹配出输出文件宽度
            if ($width && file_exists ( $srcfile )) {  //而且文件存在
                return Server::Compress([$srcfile,$percent,$width])->compressImg();
            }else{
                $this->_404([$noimg,$percent,$width]);
            }
        }else{
            $this->_404([$noimg,1,'']);
        }
    }

    private function _404($options=[])
    {
        return Server::Compress($options)->compressImg();
    }
}
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

namespace YYCms\service;

use think\facade\Db;
use YYCms\Service;
use YYCms\Exception;

/**
 * 系统安装服务
 * Class InstallService
 * @package YYCms\service
 */
class InstallService extends Service
{

	public function install()
	{
		
		$root_path = $this->app->getRootPath();
		$install_path = $root_path . DIRECTORY_SEPARATOR . 'data';
		// 检测程序安装
		if (!is_dir($install_path)) {
		    createDir($install_path);
		}

		if (!file_exists($install_path . DIRECTORY_SEPARATOR."copyInstall.lock")) {
		    copyDir(__DIR__ . "/../install", $root_path . "/public/install");
		    $flag = @touch($$install_path . DIRECTORY_SEPARATOR . 'copyInstall.lock');
		}
		//删除安装包
		if(file_exists($install_path . DIRECTORY_SEPARATOR . "install.lock") && file_exists($root_path . "/public/install")){
		    delDir($root_path . "/public/install");
		}
		//跳转到安装界面
		if (!file_exists($install_path . DIRECTORY_SEPARATOR . "install.lock")) {
		    echo ('<script>location.href="/install";</script>');
		    exit;
		}else{
			if($this->app->request->pathinfo() == 'admin/login.html'){
				$this->checkCmsAuthorize();
			};
		}
	}

	/**
	 * 获取顶级域名
	 */
	function getTopDomainhuo(){
	    $host = $_SERVER['HTTP_HOST'];
		//查看是几级域名
		$data = explode('.', $host);
		$n = count($data);
		  //判断是否是双后缀
		$preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
		if(($n > 2) && preg_match($preg,$host)){
		   //双后缀取后3位
		   $host = $data[$n-3].'.'.$data[$n-2].'.'.$data[$n-1];
		}else{
		   //非双后缀取后两位
		   $host = $data[$n-2].'.'.$data[$n-1];
		}
		return $host;
	}
	/**
	 * 检测授权
	 */
	function checkCmsAuthorize()
	{
	  $topdomain = $this->getTopDomainhuo();
	  $client_check='http://domainauth.qhxckj.com/api?key=client_check&domain='.$_SERVER['HTTP_HOST'].'&topdomain='.$topdomain;
	  $check_info=file_get_contents($client_check);
	  $check_info = json_decode($check_info,true);
	  if(isset($check_info['message']) && $check_info['message']){
	    echo '<font color=red>' . $check_info['message'] . '</font>';
	    die;
	  }
	  if(isset($check_info['code']) && $check_info['code']!=0){
	      echo '<font color=red>' . $check_info['error'] . '</font>';
	      die;
	  }
	  unset($topdomain);
	}

}
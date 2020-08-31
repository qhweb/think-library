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
 * 插件生成服务
 * Class MakeService
 * @package app\addons\service
 */
class MakeService extends Service
{



  /**
   * 检查插件控制器是否存在某操作
   * @param string $name 插件名
   * @param string $controller 控制器
   * @param string $action 动作
   * @author 蔡伟明 <314013107@qq.com>
   * @return bool
   */
  	public function plugin_action_exists($name = '', $controller = '', $action = '')
  	{
      	if (strpos($name, '/')) {
          	list($name, $controller, $action) = explode('/', $name);
      	}
      	return method_exists("addons\\{$name}\\controller\\{$controller}", $action);
  	}


  /**
   * 插件配置文件生成
   * @param string $name 插件名
   * @param string $controller 控制器
   * @param string $addon_dir 插件地址
   * @author 蔡伟明 <314013107@qq.com>
   * @return bool
   */
	public function plugin_create_config($addon_dir='')
	{
	    $config = <<<str
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

// 插件配置
return [
  'title'     => [//配置在表单中的键名 ,这个会是config[title]
    'title' => '显示标题:',//表单的文字
    'type'  => 'text',		 //表单的类型：text、textarea、checkbox、radio、select等
    'value' => '系统信息',			 //表单的默认值
  ],
  'display'   => [
    'title' => '是否显示:',
    'type'  => 'radio',
    'options'   => [
      '1' => '显示',
      '0' => '不显示'
    ],
    'value' => '1'
  ]
];
str;
	    file_put_contents($addon_dir . 'config.php', $config);
	}


	  /**
	   * 插件控制器生成
	   * @param string $name 插件名
	   * @param string $controller 控制器
	   * @param string $addon_dir 插件地址
	   * @author 蔡伟明 <314013107@qq.com>
	   * @return bool
	*/
	public function plugin_create_file($name = '', $controller = '', $addon_dir = '',$title='')
	{
	    $namespace = 'addons\\' . $name.'\\controller';
	    $controller = ucfirst(strtolower($controller));
	    $title = $title ? $title : $controller;
	    $Content = <<<str
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

namespace {$namespace};
use think\admin\Controller;
use think\\facade\View;

/**
 * {$title}管理
 * Class {$controller}
 * @package {$namespace}
 */
class {$controller} extends Controller
{
  // 初始化
  protected function initialize()
  {
    \$this->name = \$this->request->param('addon');
    // 获取当前插件目录
    \$this->addon_path = \$this->app->addons->getAddonsPath() . \$this->name . DIRECTORY_SEPARATOR;
    \$this->view = View::engine('Think');
    \$this->view->config([
        'view_path' => \$this->addon_path . 'view' . DIRECTORY_SEPARATOR
    ]);
  }

  /**
   * {$title}管理
   * @auth true
   * @menu true
   */
  public function index()
  {
    return 'hello addons {$controller}';
  }
}
str;
	      file_put_contents("{$addon_dir}controller/{$controller}.php", $Content);
	}

	  /**
	   * 插件预览
	   * @param string $data 插件数据
	   * @param string $controller 控制器
	   * @param string $action 动作
	   * @author 蔡伟明 <314013107@qq.com>
	   * @return bool
	   */
public function plugin_create_preview($data = [])
{
	    $data['status'] = 1;
	    $data['hooks'] = isset($data['hooks']) ? $data['hooks'] : [];

	    $hook = '';
	    foreach ($data['hooks'] as $value) {
	        $hook .= <<<str
	    // 实现的 {$value} 钩子方法
	    public function {$value}(\$param){

	    }
str;
	    }
	    $classname = ucfirst($data['name']);
	    $namespace = 'addons\\' . $data['name'];
	    $tpl = <<<str
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

namespace {$namespace};
use think\Addons;

/**
* {$data['title']}插件
* @author {$data['author']}
*/
class Plugin extends Addons
{
  public \$info = array(
      'name'=>'{$data['name']}',
      'title'=>'{$data['title']}',
      'description'=>'{$data['description']}',
      'status'=>{$data['status']},
      'author'=>'{$data['author']}',
      'version'=>'{$data['version']}',
      'is_config'=>{$data['is_config']},
      'is_admin'=>{$data['is_admin']},
      'is_index'=>{$data['is_index']}
  );
  public function install(){
      \$prefix = \$this->app->config->get('database.connections.mysql.prefix');
      return true;
  }
  public function uninstall(){
      \$prefix = \$this->app->config->get('database.connections.mysql.prefix');
      return true;
  }
  {$hook}
}
str;
	    return $tpl;
	}
  public function execute()
    {
        //获取表前缀
        $prefix = \think\facade\Config::get('database.connections.mysql.prefix');
        $charset = \think\facade\Config::get('database.connections.mysql.charset');


        //判断插件安装表是否存在
        if ($this->checkTable('system_addons')) {
            return true;
        }else{
            try {
                $tableName = $prefix.'system_addons';
                Db::execute("CREATE TABLE `".$tableName."` (
                `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
                `title` varchar(255) DEFAULT NULL COMMENT '插件名称',
                `name` varchar(50) DEFAULT NULL COMMENT '插件标识',
                `description` mediumtext COMMENT '插件描述',
                `status` tinyint(3) unsigned DEFAULT '0' COMMENT '插件状态',
                `is_admin` tinyint(3) unsigned DEFAULT '0' COMMENT '后台管理',
                `author` varchar(50) DEFAULT NULL,
                `is_index` tinyint(3) unsigned DEFAULT NULL COMMENT '是否前台',
                `setting` text COMMENT '配置信息',
                `version` varchar(50) DEFAULT NULL COMMENT '版本',
                `is_config` tinyint(3) DEFAULT NULL,
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COMMENT='插件安装表'");
            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to create table "%s"', 'system_addons'));
            }
        }
        //判断插件钩子表是否存在
        if ($this->checkTable('system_hooks')) {
            return true;
        }else{
            try {
                $tableName = $prefix.'system_hooks';
                Db::execute("CREATE TABLE `".$tableName."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) DEFAULT NULL COMMENT '钩子标识',
                `title` varchar(255) DEFAULT NULL COMMENT '钩子名称',
                `addons` varchar(255) DEFAULT NULL COMMENT '所属插件',
                `status` tinyint(3) unsigned DEFAULT '1' COMMENT '状态',
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COMMENT='插件钩子表'");
                Db::execute("INSERT INTO `system_hooks` VALUES ('1', 'adminIndex', '后台管理首页', 'systeminfo', '1')");
                Db::execute("INSERT INTO `system_hooks` VALUES ('2', 'pageHeader', '页面顶部', '', '1')");
                Db::execute("INSERT INTO `system_hooks` VALUES ('3', 'AddonsInit', '直接执行的插件', '', '1')");
                Db::execute("INSERT INTO `system_hooks` VALUES ('4', 'pageFooter', '页面底部钩子', '', '1')");
                Db::execute("INSERT INTO `system_hooks` VALUES ('5', 'appInit', '应用加载', '', '1')");
                Db::execute("INSERT INTO `system_hooks` VALUES ('6', 'adminFooter', '管理页面底部钩子', '', '1')");
            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to create table "%s"', 'system_hooks'));
            }
        }
    }

      /**
      * 获取全部表
      * @param string $dbName
      * @return array
      */
     private function get_dbname($dbName = '*') {
         $sql = 'SHOW TABLE STATUS';
         $list = Db::query($sql);
         $tables = array();
         foreach ($list as $value)
         {
             $tables[] = $value['Name'];
         }
         return $tables;
     }
     /**
       * 检查数据表是否存在
       */
    private function checkTable($tableName,$prefix='')
    {
        //获取数据库所有表名
        $tables = $this->get_dbname();
        //组装表名
        $table = $prefix . $tableName;
        //判断表名是否已经存在
        return in_array($table,$tables);
    }
}
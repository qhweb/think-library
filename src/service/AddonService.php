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

use YYCms\Service;
use YYCms\service\MakeService;
/**
 * 插件管理
 * Class AddonService
 * @package app\addons\service
 */
class AddonService extends Service
{
  //插件安装数据表
  protected $table = 'SystemAddons';
  //钩子数据表
  protected $hookTable = 'SystemHooks';
  //错误信息
  protected $errMsg;
  //插件目录
  protected $addons_dir = 'addons';


  /**
   * 所有钩子
   * @return [type] [description]
   */
  public function getHooks()
  {
    return $this->app->db->name($this->hookTable)->field('name,title')->select();
  }

  /**
   * 所有已安装的插件
   * @return [type] [description]
   */
  public function getAddons($name='')
  {
    if($name){
      return $this->app->db->name($this->table)->where('name',$name)->find();
    }else{
      return $this->app->db->name($this->table)->column('*','name');
    }
  }

  /**
   * 所有已安装的插件管理菜单
   * @return [type] [description]
   */
  public function getAddonsMenu($pid=0)
  {
    $addons = $this->app->db->name($this->table)->where('is_admin',1)->column('*','name');
    $menus = [];
    foreach ($addons as $key => $val) {
      $menus[] = [
        'id' => $val['id'],
        'pid' => $pid,
        'title' => $val['title'],
        'icon' => '',
        'node'  =>  '',
        'url' => addons_url($val['name']."://admin/index"),
        'params'  =>  '',
        'target'=> '_self',
        'sort'  =>  0,
        'status'  =>  1,
      ];
    }
    return $menus;
  }

  /**
   * 检查插件是否存在
   * @return mixed
   */
  public function checkName($name)
  {
    //插件目录中的插件
    $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR;
    $files = scandir($addons_path);
    $addons = [];
    foreach ($files as $file) {
        if($file == $name){
          return true;
        }
    }
    return false;
  }

  /**
   * 未安装插件列表
   * @return mixed
   */
  public function uninstalled()
  {
    //已安装插件
    $this->inaddons = $this->getAddons();
    //插件目录中的插件
    $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR;
    $files = scandir($addons_path);
    $addons = [];
    foreach ($files as $file) {
        // 已安装的插件
        if (isset($this->inaddons[strtolower($file)])) {
          continue;
        }
        // 处理获取插件信息
        if ($file != '.' && $file != '..' && is_dir($addons_path . $file)) {
          if($object = $this->getInstance($file)){
            $addons[] = $object->getInfo($file);
          }
        }
    }
    return $addons;
  }
  /**
   * 检查插件是否安装
   * @param  string $name 插件目录名
   * @return [type]       [description]
   */
  public function checkAddons($name='')
  {
    $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR.$name;
    $inaddons = $this->getAddons($name);
    return $inaddons ? 2 : 0;
  }
  /**
   * 获取插件信息
   * @param  string $name 插件目录名
   */
  public function getInfo($name)
  {
    $info_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'info.php';
    if (file_exists($info_path)) {
      return require $info_path;
    }
    return [];
  }
  /**
   * 安装插件
   * @param  string $name 插件目录名
   */
  public function install($name='')
  {
      if (!$name) {
        $this->errMsg = '参数错误';
        return false;
      }

      if ($object = $this->getInstance($name)) {
          $data = $object->getInfo();
          $data['setting'] = $object->getConfig();
          if ($this->getAddons($data['name'])) {
            $this->errMsg = '当前插件已安装';return false;
          }
          // 读取插件目录及钩子列表
          $base = get_class_methods("\\YYCms\\Addons");
          // 读取出所有公共方法
          $methods = (array)get_class_methods($object);
          // 跟插件基类方法做比对，得到差异结果
          $hooks = array_diff($methods, $base);
          // 查询钩子信息
          if (!empty($hooks)) {
              $hooks = $this->app->db->name($this->hookTable)->whereIn('name', $hooks)->select();
              $hooklist = [];
              foreach ($hooks as $hook) {
                  $addons = explode(',', $hook['addons']);
                  array_push($addons, $name);
                  $addons = array_filter(array_unique($addons));
                  $hooklist[] = [
                      'id' => $hook['id'],
                      'addons' => implode(',', $addons)
                  ];
              }
          }

          $this->app->db->startTrans();
          try {
              $data['setting'] = json_encode($data['setting']);
              $this->app->db->name($this->table)->insert($data);
              if (isset($hooklist) && !empty($hooklist)) {
                foreach ($hooklist as $key => $val) {
                  $this->app->db->name($this->hookTable)->update($val);
                }
              }
              if (false !== $object->install()) {
                $this->app->db->commit();
              }
          } catch (\Exception $e) {
              $this->app->db->rollback();
              $this->errMsg = '安装异常:'.$e->getMessage();
              return false;
          }
          return true;
      }
      $this->errMsg = '安装失败';
      return false;
  }
  /**
   * 返回错误信息
   */
  public function getError()
  {
    return $this->errMsg;
  }
  /**
   * 卸载插件
   */
  public function uninstall($name ='')
  {
      if (!$name) {
          $this->errMsg = '参数错误';
          return false;
      }
      $info = $this->getAddons($name);
      
      if ($info) {
          // 获取所有相关钩子
          $hooks = $this->app->db->name($this->hookTable)->where('find_in_set(:name, addons)', ['name' => $name])->select();
          $hooklist = [];
          foreach ($hooks as $hook) {
              $addons = explode(',', $hook['addons']);
              $addons = array_diff($addons, [$name]);
              $addons = array_filter(array_unique($addons));
              $hooklist[] = [
                  'id' => $hook['id'],
                  'addons' => implode(',', $addons)
              ];
          }
          // 开启事务
          $this->app->db->startTrans();
          try {
              // 删除插件
              $this->app->db->name($this->table)->where(['name' => $name])->delete();
              // 删除钩子
              if (!empty($hooklist)) {
                foreach ($hooklist as $key => $val) {
                  $this->app->db->name($this->hookTable)->update($val);
                }
              }
              $object = $this->getInstance($name);
              if ($object && false !== $object->uninstall()) {
                  $this->app->db->commit();
              }
              
          } catch (\Exception $e) {
              $this->app->db->rollback();
              $this->errMsg = '卸载异常:'.$e->getMessage();
              return false;
          }
          return true;
      }
      $this->errMsg = '卸载失败';
      return false;
  }

  /**
   * 删除插件
   */
  public function delete($name='')
  {
      $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR;
      if ($name) {
        rm_dirs($addons_path . $name);
        return true;
      }
      $this->errMsg = '参数错误';
      return false;
  }

  /**
   * 更新插件配置
   */
  public function setConfig($name='',$config=[])
  {
      $addConfig = $this->getInstance($name)->getConfig(true);
      $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'config.php';
      foreach ($addConfig as $key => $val) {
        $val['value'] = $config[$key];
        $addConfig[$key] = $val;
      }
      //打开输出缓冲区
      ob_start();
      //返回数组生成的php代码
      var_export($addConfig);
      //返回内部缓冲区的内容
      $arrStr = ob_get_contents();
      //删除内部缓冲区的内容，并且关闭内部缓冲区
      ob_end_clean();
      $content = '<?php' . PHP_EOL. '//插件配置' . PHP_EOL. 'return ' . $arrStr.';';
      file_put_contents($addons_path, $content);
      return true;
  }

  /**
   * 获取插件实例
   * @param $file
   * @return bool|object
   */
  public function getInstance($file)
  {
      $class = "\\addons\\{$file}\\Plugin";
      if (class_exists($class)) {
        return new $class($this->app);
      }
      return false;
  }
  /**
     * 预览插件
     * @param array $data
     * @return bool|string
     */
    public function preview($data = [])
    {
        $data['status'] = 1;
        $data['hooks'] = isset($data['hooks']) ? $data['hooks'] : [];
        $data['is_config'] = isset($data['is_config']) ? $data['is_config'] : 0;
        $data['is_index'] = isset($data['is_index']) ? $data['is_index'] : 0;
        $data['is_admin'] = isset($data['is_admin']) ? $data['is_admin'] : 0;
        return MakeService::instance()->plugin_create_preview($data);
    }


    public function build($data = [])
    {
        $addonFile = $this->preview($data);
        //插件目录中的插件目录
        $addons_path = $this->app->getRootPath().$this->addons_dir.DIRECTORY_SEPARATOR;

        // 创建目录结构
        $files = array ();
        $addon_dir = "$addons_path{$data['name']}".DIRECTORY_SEPARATOR;
        $files [] = $addon_dir;
        $addon_name = "Plugin.php";
        // 如果有前后台入口,创建目录
        if (isset($data['is_admin']) || isset($data['is_index'])) {
            $files[] = "{$addon_dir}controller/";
            $files[] = "{$addon_dir}view/";
        }

        foreach ($files as $dir) {
            if (!mk_dirs($dir)) {
                $this->error = '插件' . $data['name'] . '目录存在';
                return false;
            }
        }

        // 写插件入口文件
        file_put_contents( "{$addon_dir}{$addon_name}", $addonFile);

        // 如果有配置文件
        if (isset($data['is_config'] ) && $data['is_config'] == 1) {
            MakeService::instance()->plugin_create_config($addon_dir);
        }
        // 如果存在后台
        if (isset($data['is_admin']) && $data['is_admin'] == 1) {
            MakeService::instance()->plugin_create_file($data['name'],'admin',$addon_dir,$data['title']);
        }
        // 如果存在前台
        if (isset($data['is_index']) && $data['is_index'] == 1) {
          MakeService::instance()->plugin_create_file($data['name'],'index',$addon_dir,$data['title']);
        }
        return true;
    }
}

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
class ModuleService extends Service
{

  public function execute()
    {
        //获取表前缀
        $prefix = \think\facade\Config::get('database.connections.mysql.prefix');
        $charset = \think\facade\Config::get('database.connections.mysql.charset');


        //判断插件安装表是否存在
        if ($this->checkTable('system_field')) {
            return true;
        }else{
            try {
                $tableName = $prefix.'system_field';
                Db::execute("CREATE TABLE `".$tableName."` (
                `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
                `mid` int(11) NOT NULL DEFAULT '0' COMMENT '模型ID',
                `field` varchar(20) NOT NULL DEFAULT '' COMMENT '字段名',
                `name` varchar(30) NOT NULL DEFAULT '' COMMENT '字段别名',
                `tips` varchar(150) DEFAULT '' COMMENT '字段说明',
                `required` tinyint(1) DEFAULT '0' COMMENT '是否必填',
                `pattern` mediumtext COMMENT '验证规则',
                `type` varchar(20) NOT NULL DEFAULT '' COMMENT '表单类型',
                `setup` mediumtext COMMENT '字段配置',
                `sort` int(11) DEFAULT '0' COMMENT '排序',
                `status` tinyint(1) DEFAULT '1' COMMENT '状态',
                `islist` tinyint(1) DEFAULT '0' COMMENT '列表',
                `width` int(11) DEFAULT '0' COMMENT '列表宽',
                `group` varchar(255) DEFAULT NULL COMMENT '列表分组',
                `align` varchar(10) DEFAULT 'center' COMMENT '位置',
                `hidden` tinyint(3) DEFAULT '0' COMMENT '隐藏域',
                `position` varchar(255) DEFAULT '' COMMENT '表单分组',
                `issort` tinyint(4) DEFAULT '0' COMMENT '列表排序',
                `column` tinyint(4) DEFAULT '0' COMMENT '多列表单',
                `minlength` int(11) DEFAULT NULL,
                `maxlength` int(11) DEFAULT NULL,
                `fwidth` varchar(11) DEFAULT '' COMMENT '表单宽',
                `search` tinyint(4) DEFAULT '0',
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COMMENT='自定义模型-字段表'");
            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to create table "%s"', 'system_field'));
            }
        }
        //判断插件钩子表是否存在
        if ($this->checkTable('system_module')) {
            return true;
        }else{
            try {
                $tableName = $prefix.'system_module';
                Db::execute("CREATE TABLE `".$tableName."` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID主键',
                `title` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '模型名称',
                `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '模型标示',
                `description` varchar(200) CHARACTER SET utf8mb4 DEFAULT '' COMMENT '模型说明',
                `issystem` tinyint(1) unsigned DEFAULT '0' COMMENT '0独立模块，1内容扩展模块',
                `setup` mediumtext CHARACTER SET utf8mb4 COMMENT '模型配置',
                `sort` bigint(11) unsigned DEFAULT '0' COMMENT '排序',
                `status` tinyint(1) unsigned DEFAULT '1' COMMENT '状态',
                `number` int(11) unsigned DEFAULT '20' COMMENT '每页条数',
                `model` mediumtext CHARACTER SET utf8mb4 COMMENT '关联模型',
                `sort_field` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '可排序字段',
                `search_field` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL COMMENT '可搜索字段',
                `tree_table` tinyint(3) unsigned DEFAULT '0' COMMENT '是否树形列表',
                PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COMMENT='自定义模型表'");

            } catch (Exception $e) {
                throw new Exception(sprintf('Failed to create table "%s"', 'system_module'));
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
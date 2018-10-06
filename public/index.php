<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:45:35 EDT 2018
 *
 * @description 项目入口
 */

$now = microtime(true);

require_once('../vendor/autoload.php');

use Helpers\DB;
use Helpers\App;
use Helpers\Request;
use Helpers\Response;

// 获得配置
$config = [];
$config_dir = '../config/';
foreach(scandir($config_dir, 0) as $value) {
  if (in_array($value, ['.', '..'])) continue;
  $key = str_replace(['_local.ini', '.ini'], '', $value);
  $config[$key] = parse_ini_file($config_dir . $value);
}

// 调试
if ($config['app']['debug']) {
  ini_set('display_errors',1);
  ini_set('display_startup_errors',1);
  error_reporting(-1);
}

// 载入配置
$app = new App(['config' => $config]);

// 注入
$app->db = new DB($config['db']);
$app->request = new Request;
$app->response = new Response;

// 读取路由
$app->router(require_once('../router.php'));

// 运行程序
$app->run();

// 输出执行时间
$app->response->withJson(['used' => (string)((microtime(true) - $now) * 1000).'ms']);

<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:47:36 EDT 2018
 *
 * @description 框架主体
 */
namespace Helpers;

use Exception;

class App
{
  /**
   * 路由队列
   */
  private $_router_queue = [
    'get'    => [],
    'post'   => [],
    'put'    => [],
    'delete' => [],
  ];

  /**
   * 容器
   */
  private $_container = [];

  /**
   * 写入容器
   * @param   $params   array   需要写入容器的变量
   * @return  null
   */
  public function __construct(array $params)
  {
    foreach ($params as $key => $value) {
      $this->_container[$key] = $value;
    }
  }

  /**
   * 写入容器
   * @param   $key    string
   * @param   $value  mixed
   * @return  null
   */
  public function __set($key, $value)
  {
    $this->_container[$key] = $value;
  }

  /**
   * 从容器中取值
   * @param   $key    string
   * @return  mixed
   */
  public function __get($key)
  {
    $func = $this->_container[$key];
    if (is_callable($func)) {
      return $func($this);
    }
    return $func;
  }

  /**
   * 注意：递归调用，注意性能，避免太深
   * TODO 缓存路由
   * 添加路由
   * @param $rules  array 参数
   */
  public function router(array $rules, array $extra=[])
  {
    /**
     * 开启调试模式
     */
    if ($this->_container['config']['app']['debug']) {
      ini_set('display_errors',1);
      ini_set('display_startup_errors',1);
      error_reporting(-1);
    }

    /**
     * 将 $rules 中的规则导入 $extra
     */
    // 替换
    foreach (['method' => 'get', 'action' => ''] as $key => $value) {
      if(!isset($extra[$key])) {
        $extra[$key] = $value;
      }
      if (isset($rules[$key])) {
        $extra[$key] = $rules[$key];
        unset($rules[$key]);
      }
    }

    // 合并
    if (!isset($extra['middlewares'])) {
      $extra['middlewares'] = [];
    }
    if (isset($rules['middlewares'])) {
      $extra['middlewares'] = array_merge($extra['middlewares'], $rules['middlewares']);
      unset($rules['middlewares']);
    }

    // 初始化变量
    if (!isset($extra['uri'])) {
      $extra['uri'] = '';
    }

    foreach ($rules as $uri => $rule) {
      // 跳过非预定参数
      if ('/' === substr($uri, 0, 1)) {
        // 拼接，并递归查找
        $this->router($rule, array_merge($extra, ['uri' => $extra['uri'].$uri]));
      } else {
        throw new Exception('路由参数非法！');
      }
    }

    // 最深一层
    if (count($rules) <= 0) {
      $uri = $extra['uri'];
      $method = $extra['method'];
      unset($extra['uri']);
      unset($extra['method']);

      $this->_router_queue[$method][$uri] = $extra;
    }
  }

  /**
   * 运行程序
   * @param   null
   * @return  bool  是否执行成功
   */
  public function run()
  {
    if (!isset($_SERVER['REQUEST_METHOD'])) {
      throw new Exception('未知请求方法');
    }

    $method = strtolower($_SERVER['REQUEST_METHOD']);

    $uri = strtolower(explode('?', $_SERVER['REQUEST_URI'])[0]);

    if (!isset($this->_router_queue[$method][$uri])) {
      http_response_code(404);die;
    }

    $item = $this->_router_queue[$method][$uri];
    $func = $item['action'];
    if (is_callable($func)) {
      // 闭包函数
      $func->bindTo($this)();
      return true;
    } else if (is_string($func)) {
      // 调用用户方法
      // 字符串函数
      $next = function() use ($func){
        $temp = explode('/', $func);
        $func = array_pop($temp);
        $class = '\\' . implode('\\', $temp);
        // 依赖注入
        $c = new $class();
        foreach ($this->_container as $key=>$value) {
          $c->$key = $value;
        }
        $c->$func();
      };
      $this->run_middleware($item['middlewares'], $next);
      return true;
    }
    return false;
  }

  /**
   * 调用中间件
   * @param   $middlewares  array   中间件数组
   * @param   $next         Closure 用户方法
   * @return  null
   */
  private function run_middleware(array $middlewares, \Closure $next)
  {
    foreach($middlewares as $middleware) {
      if (is_string($middleware)) {
        $md = function ($next) use ($middleware){
          $temp = explode('/', $middleware);
          $func = array_pop($temp);
          $class = '\\' . implode('\\', $temp);
          // 依赖注入
          $c = new $class();
          foreach ($this->_container as $key=>$value) {
            $c->$key = $value;
          }
          $c->$func($next);
        };
      } else if (is_callable($middleware)) {
        // 闭包函数
        $md = $middleware->bindTo($this);
      }
      $next = function() use($next, $md) {
        return $md($next);
      };
    }
    $next();
  }
}

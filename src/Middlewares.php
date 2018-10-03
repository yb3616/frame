<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:58:47 EDT 2018
 *
 * @description 路由中间件
 */

namespace Apps;

class Middlewares
{
  public function timmer(\Closure $next)
  {
    $now = microtime(true);
    $next();
    echo '<hr/>Used '. (string)((microtime(true) - $now) * 1000) . ' ms';
  }
}

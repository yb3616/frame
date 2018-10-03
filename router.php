<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:48:53 EDT 2018
 *
 * @description 路由
 */

return [
  '/' => [
    'action' => 'Apps/Index/index',
    'method' => 'get',
    'middlewares' => ['Apps/Middlewares/timmer'],
  ]
];

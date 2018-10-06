<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:48:53 EDT 2018
 *
 * @description 路由
 */

return [
  'middlewares' => 'Apps/Middlewares/test',
  '/' => [
    'action' => 'Apps/Index/index',
    'method' => 'get',
    'middlewares' => function($next) {
      $next();
      $this->response->withJson(['232a' => '222']);
    }
  ],
  '/test' => function() {
    $this->response->withJson(['foo'=>'bar'], 200);
    // var_dump($this->request->get(['foo' , 'bar' ]));
  }
];

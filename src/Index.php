<?php

namespace Apps;

class Index
{
  public function index()
  {
    $this->response->withJson(['foo'=>'bar'], 200);
  }
}

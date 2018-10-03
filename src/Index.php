<?php

namespace Apps;

class Index
{
  public function index()
  {
    echo '<pre>';
    print_r($this->config);
    echo '</pre>';
  }
}

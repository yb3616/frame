<?PHP
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Fri Oct  5 21:21:37 EDT 2018
 *
 * @description 请求参数
 */

namespace Helpers;

class Request
{
  private $_params = [
    'get'  => [],
    'post' => [],
    'json' => [],
    'xml'  => [], // TODO
    'file' => [], // TODO
    'all'  => [],
  ];

  public function __construct()
  {
    foreach(['get'=>$_GET, 'post'=>$_POST, 'json'=>json_decode(file_get_contents('php://input'), true)] as $type => $params) {
      $params = isset($params) ? $params : [];
      foreach($params as $key => $value) {
        $this->_params[$type][trim($key)] = trim($value);
      }
    }

    $this->_params['all'] = array_merge(
      $this->_params['get'],
      $this->_params['post'],
      $this->_params['xml'],
      $this->_params['json']
    );
  }

  public function get($name = null)
  {
    return $this->getParam($name, 'get');
  }

  public function post($name = null)
  {
    return $this->getParam($name, 'post');
  }

  public function json($name = null)
  {
    return $this->getParam($name, 'json');
  }

  public function xml($name = null)
  {
    return $this->getParam($name, 'xml');
  }

  public function param($name = null)
  {
    return $this->getParam($name, 'all');
  }

  private function getParam($name,string $type) {
    if (null === $name) {
      // 返回指定类型下所有数据
      return $this->_params[$type];

    } else if (is_string($name)) {
      // 字符串型
      $name = array_map('trim', explode(',', $name));

    } else if (!is_array($name)) {
      throw new Exception('参数类型不支持');
    }

    $ret = [];
    foreach($name as $param) {
      if (isset($this->_params[$type][$param])) {
        // 返回指定数据
        $ret[$param] = $this->_params[$type][$param];
      }
    }
    return $ret;
  }
}

<?PHP
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Fri Oct  5 22:18:33 EDT 2018
 *
 * @description 响应
 * 参考 PSR-7 https://www.php-fig.org/psr/psr-7/
 */

namespace Helpers;

class Response
{
  private $_json = [];

  public function withHeader(string $type, string $value)
  {
    header($type . ':' . $value);
    return $this;
  }

  public function withAddedHeader(string $type, string $value)
  {
    header($type . ':' . $value, false);
    return $this;
  }

  public function withStatus(int $code, string $reasonPhrase='')
  {
    http_response_code($code);
    return $this;
  }

  /**
   * 返回 json 数据
   * @param   $data   array   返回数据
   * @param   $code   int     响应代码
   * @return  $this
   */
  public function withJson(array $data, int $code=0)
  {
    $this->_json = array_merge($this->_json, $data);
    if (0 !== $code) {
      $this->withStatus($code);
    }
    return $this;
  }

  public function __destruct()
  {
    if (!empty($this->_json)) {
      $this->withHeader('Content-Type', 'application/json;charset=utf-8');
      echo json_encode($this->_json);
    }
  }
}

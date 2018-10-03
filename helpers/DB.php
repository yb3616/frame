<?php
/**
 * @author  姚斌 <yb3616@126.com>
 * @since   Tue Oct  2 00:47:36 EDT 2018
 *
 * @description ORM
 */

namespace Helpers;

use PDO;
use exception;

class DB
{
  // 数据库连接句柄
  private $_conn = null;

  // 参数
  private $_alias = [
    'field' => '',
    'pre'   => '',
    'table' => '',
    'limit' => '',
    'order' => '',
    'group' => '',
    'join'  => '',
    'where' => '',
    'debug' => false,
  ];

  /**
   * 使用 PDO 连接数据库
   * @param   $config   array   连接配置
   * @return  null
   */
  public function __construct(array $config)
  {
    if (is_null ($this->_conn)) {
      $this->_alias['pre'] = $config['pre'];
      $dsn = $config['type'] . ':host=' . $config['host'] . ';dbname=' . $config['dbname'];
      $this->_conn = new PDO($dsn, $config['user'], $config['pass']);
      $this->_conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
    }
  }

  /**
   * 设置字段
   * @param   $field    string | array
   * @return  $this
   */
  public function field($field)
  {
    if (is_array($field)) {
      // 数组参数
      $this->_alias['field'] = implode(',', array_map(function($v){
        $r = $this->convert($v);
        if ($v === $r) {
          $r = '`'.trim($v).'`';
        }
        return $r;
      }, $field));
    } else if(is_string($field)) {
      // 字符串参数
      // 以逗号分割字符串，去除首位空格并添加 '`' 符号
      $temp = array_map(function($v) {
        $r = $this->convert($v);
        if ($v === $r) {
          $r = '`'.trim($v).'`';
        }
        return $r;
      }, explode(',', $field));
      $this->_alias['field'] = implode(',', $temp);
    } else {
      throw new exception('不支持的参数类型: DB->field');
    }
    return $this;
  }

  /**
   * 设置别名
   * @param   $alias    string  别名
   * @return  $this
   */
  public function alias(string $alias)
  {
    $temp = $this->convert($alias);
    if ($temp === $alias) {
      $temp = '`'.$alias.'`';
    }

    $this->_alias['alias'] = $temp;
    return $this;
  }

  /**
   * 设置表名，不带前缀
   * @param   $table    string    数据库表名
   * @return  $this
   */
  public function table(string $table)
  {
    $temp = $this->convert($table);
    if ($temp === $table) {
      $temp = '`'.$table.'`';
    }

    $this->_alias['table'] = $temp;
    return $this;
  }

  /**
   * 设置表名，带前缀
   * @param   $name     string    数据库表名
   * @return  $this
   */
  public function name(string $name)
  {
    $table = $this->_alias['pre'] . $name;
    $temp = $this->convert($table);
    if ($temp === $table) {
      $temp = '`'.$table.'`';
    }

    $this->_alias['table'] = $temp;
    return $this;
  }

  /**
   * 设置查询条件
   * 保存字符串
   * @param   $where  条件
   * @return  $this
   */
  public function where($where)
  {
    if (is_array($where)) {
      // 数组参数
      $temp = [];
      foreach($where as $key => $value) {
        $tmp_key = $this->convert($key);
        if ($key === $tmp_key) {
          $tmp_key = '`'.$key.'`';
        }
        array_push($temp, $tmp_key.'='.(is_string($value)?'\''.$value.'\'':$value));
      }
      if (strlen($this->_alias['where']) > 0) {
        $this->_alias['where'] .= ' AND ';
      }
      $this->_alias['where'] .= implode(' AND ', $temp);
    } else if(is_string($where)) {
      // 字符串参数
      $this->_alias['where'] .= $this->convert($where);
    } else {
      throw new exception('不支持的参数类型: DB->where');
    }
    return $this;
  }

  /**
   * 设置limit条件
   * @param   $limit
   * @return  $this
   */
  public function limit(string $limit)
  {
    $this->_alias['limit'] = $limit;
    return $this;
  }

  /**
   * 排序
   * @param   $order
   * @return  $this
   */
  public function order(string $order)
  {
    $temp = explode(' ', $order);
    $field = $this->convert($temp[0]);
    if ($field === $temp[0]) {
      $field = '`'.$temp[0].'`';
    }
    $this->_alias['order'] = $field.' '.$temp[1];
    return $this;
  }

  /**
   * group
   * @param   $group
   * @return  $this
   */
  public function group(string $group)
  {
    $tmp = $this->convert($group);
    if ($tmp === $group) {
      $tmp = '`'.$group.'`';
    }
    $this->_alias['group'] = $tmp;
    return $this;
  }

  /**
   * join
   * @param   $join   array
   *    ['tb01 as t1' => 't1.uid = user.id']
   *    ['tb01' => 'tb01.uid = user.id']
   * @param   $options  array
   * @return  $this
   */
  public function join(array $join, array $options=[])
  {
    $type = 'JOIN';
    if (isset($options['type']) && $options['type']) {
      $type = strtoupper($options['type']).' JOIN';
    }
    $temp = [];
    foreach ($join as $key => $value) {
      $value = $this->convert($value);
      $temp_key = array_map('trim', explode(' ', trim($key)));
      if (isset($options['addPre']) && $options['addPre']) {
        $temp_key[0] = $this->_alias['pre'].$temp_key[0];
      }
      if (count($temp_key) === 3) {
        // database.table
        $tbname = $this->convert($temp_key[0]);
        if ($tbname === $temp_key[0]) {
          $tbname = '`'.$temp_key[0].'`';
        }
        $temp[] = $type.$tbname.' '.strtoupper($temp_key[1]).' `'.$temp_key[2].'` ON '.$value;
      } else if (count($temp_key) === 0){
        $tbname = $this->convert($temp_key[0]);
        if ($tbname === $temp_key[0]) {
          $tbname = '`'.$temp_key[0].'`';
        }
        $temp[] = $type.$tbname.' ON '.$value;
      }
    }
    $this->_alias['join'] = implode(' ', $temp);
    return $this;
  }

  /**
   * 增
   * @param   $values   array
   * @return
   */
  public function add(array $values)
  {
    if (empty($this->_alias['table'])) {
      throw new exception('请设置表名');
    }

    $sql = 'INSERT INTO '.$this->_alias['table'].' ('.implode(',', array_map(function($v) {
      return '`'. $v .'`';
    },array_keys($values))).') VALUES('.implode(',', array_map(function($v) {
      return is_string($v) ? '\'' . $v . '\'' : $v;
    }, array_values($values))).');';

    // $sql = $this->convert($sql);
    if ($this->_alias['debug']) {
      echo $sql;
    } else {
      return $this->_conn->exec($sql);
    }
  }

  /**
   * 删
   * @param   null
   * @return
   */
  public function delete()
  {
    if (empty($this->_alias['table'])) {
      throw new exception('请设置表名');
    }
    // 禁止全删
    if (empty($this->_alias['where'])) {
      throw new exception('请设置查询条件');
    }

    $sql = 'DELETE FROM '.$this->_alias['table'].' WHERE '.$this->_alias['where'].';';

    // $sql = $this->convert($sql);

    if ($this->_alias['debug']) {
      echo $sql;
    } else {
      return $this->_conn->exec($sql);
    }
  }

  /**
   * 改
   * @param   $data   array
   * @return
   */
  public function update(array $data)
  {
    if (empty($this->_alias['table'])) {
      throw new exception('请设置表名');
    }
    // 禁止全删
    if (empty($this->_alias['where'])) {
      throw new exception('请设置查询条件');
    }

    $temp = [];
    foreach($data as $key => $value) {
      array_push($temp, '`'.$key.'`='.(is_string($value)?'\''.$value.'\'':$value));
    }
    $sql = 'UPDATE '.$this->_alias['table'].' SET '.implode(',', $temp).' WHERE '.$this->_alias['where'].';';

    // $sql = $this->convert($sql);

    if ($this->_alias['debug']) {
      echo $sql;
    } else {
      return $this->_conn->exec($sql);
    }
  }

  /**
   * 查
   * @param   null
   * @return
   */
  public function select()
  {
    if (empty($this->_alias['table'])) {
      throw new exception('请设置表名');
    }
    $sql = 'SELECT ';
    if (!empty($this->_alias['field'])) {
      $sql .= $this->_alias['field'];
    } else {
      $sql .= '*';
    }
    $sql .= ' FROM '.$this->_alias['table'];
    if (!empty($this->_alias['alias'])) {
      $sql .= ' AS ' . $this->_alias['alias'];
    }
    if (!empty($this->_alias['join'])) {
      $sql .= ' ' . $this->_alias['join'];
    }
    if (!empty($this->_alias['where'])) {
      $sql .= ' WHERE ' . $this->_alias['where'];
    }
    if (!empty($this->_alias['group'])) {
      $sql .= ' GROUP BY ' . $this->_alias['group'];
    }
    if (!empty($this->_alias['order'])) {
      $sql .= ' ORDER BY ' . $this->_alias['order'];
    }
    // 须放最后
    if (!empty($this->_alias['limit'])) {
      $sql .= ' LIMIT ' . $this->_alias['limit'];
    }

    // $sql = $this->convert($sql);
    $sql .= ';';

    if ($this->_alias['debug']) {
      echo $sql;
    } else {
      // 仅返回关联数组
      return $this->_conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
  }

  /**
   * 是否输出 sql 语句
   * @param   $debug    bool
   * @return  $this
   */
  public function debug(bool $debug)
  {
    $this->_alias['debug'] = $debug;
    return $this;
  }

  private function convert(string $str)
  {
    return preg_replace('/(^|[^`])(\w+)\.(\w+)/', "$1`$2`.`$3`", $str);
  }
}

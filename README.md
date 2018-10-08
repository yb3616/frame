# 1. 简介
自制简易框架，集合路由，中间件，容器技术，依赖注入，ORM等功能。旨在帮助开发人员理解现代框架的原理。
> 不支持模板渲染，仅支持API开发，前端请使用`reactjs`、`vuejs`、`angular`等框架。

# 2. 配置
有两种配置文件，一种以`.ini`结尾，另一种以`_local.ini`结尾，后一种被设计为本地配置，不会传入git仓库中去，且优先级高。

若一个配置文件的文件名为`app_local.ini`，则可以在程序中使用`$this->config->app`来获得相应的配置。

# 3. 路由
路由使用数组的形式，因支持闭包函数（由于性能等原因，后期可能会去除，请尽量避免使用闭包函数）。不支持写入JSON等文件格式。

## 3.1. 文件路径
```
project/router.php
```

## 3.2. 请求方法

### 3.2.1 一般请求
> 默认匹配“GET”请求。
> 请求 URI 大小写敏感。
> 凡数组键名以“/”开头的，皆为请求路径，即 URI。

```php
return [
    '/uri' => 'namespace/class/method',
];

// OR
return [
    '/uri' => ['action' => 'namespace/class/method'],
];
```

### 3.2.2 指定请求类型
> 请求类型的大小写不敏感。

```php
return [
    '/uri' => ['action' => 'n/c/m', 'method' => 'post'],
];
```

### 3.2.3 路由中间件
> 中间件按写入顺序执行。

```php
return [
    '/uri' => ['action' => 'n/c/m', 'middlewares' => 'n/c/m'],
];

// OR
return [
    '/uri' => ['action' => 'n/c/m', 'middlewares' => ['n/c1/m1', 'n/c2/m2']],
];
```

### 3.2.4 路由分组

```php
return [
    '/api' => [
        '/foo' => 'n/c1/m',
        '/bar' => 'n/c2/m',
    ],
];
```

### 3.2.5 设置默认参数
> 路由数组越深的参数优先级越高。

```php
return [
    '/user' => [
        'middlewares' => 'isLoggedIn',
        'method' => 'post',
        '/getUserName' => 'n/c1/m',
        '/logOut' => 'n/c2/m',
    ],
    '/guest' => [
        'middlewares' => 'isGuest',
        '/login' => 'n/c3/m',
        '/register' => 'n/c4/m',
    ],
];
```

### 3.2.6 路由闭包（不建议使用）
> 请尽量使用完整的“命名空间/类名/方法名”代替闭包函数；

```php
return [
    '/uri' => 'action' => function() {
        echo 'Hello, There!';
    },
];
```

### 3.2.7 中间件闭包（不建议使用）
> 请尽量使用完整的“命名空间/类名/方法名”代替闭包函数；

```php
return [
    '/uri' => ['action' => 'n/c/m', 'middlewares' => function($next) {
        echo 'before';
        $next();
        echo 'after';
    }],
];
```

# 4. 中间件

## 4.1 中间件路径
```
project/src/Middlewares.php
```
> 一下目录仅本人建议，只要`namespace/class/method`路径正确，随便你写哪里。
> 目前暂不支持函数级别的中间件。

## 4.2 中间件语法
```php
namespace Apps;
use Closure;
class Middlewares
{
    public function isGuest(Closure $next)
    {
        // 仅为游客时执行程序
        if (empty($_SESSION['user']['id])) {
            next();
        }
    }
}
```

# 5. ORM
## 5.1 增
> 暂无批量添加
> 返回影响行数
```php
$rows = $this->db
    ->table('pre_tbname')
    ->add([
        'username' => 'admin',
        'password' => password_hash('123456', PASSWORD_BCRYPT),
    ]);
```

## 5.2 删
> 绝无批量删除
> 返回影响行数
```php
$rows = $this->db
    ->name('tbname')
    ->where(['id' => 2])
    ->delete();
```

## 5.3 改
> 返回影响行数
```php
$rows = $this->db
    ->name('tbname')
    ->where('id=2')
    ->delete();
```

## 5.4 查
> 返回二维关联数组
```php
// 非关联查询（如有关联需求，强烈建议自行创建视图）
$result0 = $this->db
    ->name('tbname')
    ->field('uid,uname,pass')
    ->order('uname asc')
    ->limit('2')
    ->select();

// 关联查询（理论上支持关联多表）
$result1 = $this->db
    ->table('pre_tbname')
    ->alias('a')
    // 'addPre'：实际关联的表会自动转化成'pre_bankcard'
    ->join(
        ['bankcard as b' => 'a.uid=b.uid'],
        ['addPre' => true, 'type' => 'inline'],
    )
    ->field(['a.uid', 'a.uname', 'b.bankcard']
    ->order('b.bankcard desc')
    ->select();
```

## 5.5 调试
```php
$rows = $this->db
    ->name('tbname')
    ->where('id = 1')
    ->debug(true)
    ->delete();
```

# 6. Resquest
获得请求参数

## 6.1 get
```php
$foo = $this->request->get('foo');

// OR
$allGet = $this->request->get();

// OR
$array = $this->request->get('foo, bar');

// OR
$array = $this->request->get(['foo', 'bar']);
```

## 6.2 post
```php
$foo = $this->request->post('foo');

// OR
$allGet = $this->request->post();

// OR
$array = $this->request->post('foo, bar');

// OR
$array = $this->request->post(['foo', 'bar']);
```

### 6.3 json
```php
$foo = $this->request->json('foo');

// OR
$allGet = $this->request->json();

// OR
$array = $this->request->json('foo, bar');

// OR
$array = $this->request->json(['foo', 'bar']);
```

# 7. Response
响应体

## 7.1 写入状态码
```php
$this->response->withStatus(301);
```

# 7.2 写入响应头
```php
$this->response->withHeader('Content-Type', 'application/json; charset=utf-8');
```

# 7.3 输出 JSON
可多次调用 `withJson` 以合并多个数组，最终将一起输出
```php
$this->response->withJson(['foo' => 'bar']);

// OR
$this->response->withJson(['foo' => 'bar'], 201);
```

# 8. 应用程序
请开发人员，根据`PSR-4`自由组织代码结构，尽量保证一个方法不超过100行代码。
> 命名空间为：namespace Apps;

# 9. 容器与依赖注入
将第三方库注入`$app`变量中，就可以在应用程序或者闭包（包括中间件）中调用被注入的方法。

比如，我写了一个DB类（`project/helpers/DB.php`），现在我想全局使用改类方法，为防止全局变量命名污染，将其注入`$app`类变量中（`$app->db = new \Helpers\DB()`），然后就可以在中间件以及应用程序中使用`$this->db`变量来操作数据库。

<?php
/**
 * 框架启动文件
 *
 * User: Lin
 * Date: 2016/3/16
 * Time: 20:20
 */

// 装载自动载入程序
require __DIR__ . '/../bootstrap/autoload.php';

// 装载框架初始化程序
$app = require __DIR__ . '/../bootstrap/app.php';

use Qsmf\Route\Route;
$route = new Route();

$route->add('/blog/to/:year/:month', ['HelloController','helloAction']);

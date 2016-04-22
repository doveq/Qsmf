<?php
/**
 * 访问路由方法实现
 *
 * User: 20160309
 * Date: 2016/4/15
 * Time: 15:31
 */

namespace Qsmf\Route;

class Route
{
    // 访问请求方式
    const REQUEST_METHOD_GET = 1;
    const REQUEST_METHOD_POST = 2;
    const REQUEST_METHOD_PUT = 3;
    const REQUEST_METHOD_DELETE = 4;
    const REQUEST_METHOD_PATCH = 5;
    const REQUEST_METHOD_HEAD = 6;
    const REQUEST_METHOD_OPTIONS = 7;


    const TOKEN_TYPE_OPTIONAL = 1;
    const TOKEN_TYPE_VARIABLE = 2;
    const TOKEN_TYPE_TEXT = 3;

    // 保存路由数据
    public $routes = [];


    public function __construct()
    {

    }

    /**
     * 设置匹配所有路由
     */
    public function any($pattern, $callback, array $options = array())
    {
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置获取路由
     */
    public function get($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_GET;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置添加路由
     */
    public function post($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_POST;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置更新完整信息路由
     */
    public function put($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_PUT;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置更新部分信息路由
     */
    public function patch($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_PATCH;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置删除路由
     */
    public function delete($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_DELETE;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置获取资源的元数据路由
     */
    public function head($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_HEAD;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 设置获取信息，关于资源的哪些属性是客户端可以改变的路由
     */
    public function options($pattern, $callback, array $options = array())
    {
        $options['method'] = self::REQUEST_METHOD_OPTIONS;
        $this->add($pattern, $callback, $options);
    }

    /**
     * 添加规则
     *
     * @param string $pattern 访问路由地址，可以是正则
     *  例如：
     *      /post/{id}          => matches /post/33
     *     /post/{id}(/{title})   => matches /post/33, /post/33/wahhh
     *
     * @param array $callback 路由访问回调方法，第一个元素是类名，第二元素是方法名
     * @param array $options 可选附加信息
     *      $options['method'] 路由访问模式，POST、GET 等等
     *      $options['as']  设置路由别名，可以方便根据别名查找路由
     *      $options['domain'] 设置能访问的域名
     *      $options['secure'] 设置是否是安全访问 HTTPS
     *
     * @return void
     */
    public function add($pattern, $callback, array $options = array())
    {
        // 判断是路由地址是否设置了表达,列如：/path/:year
        $pcre = strpos($pattern, '{') !== false;
        if ($pcre) {
            // 如果设置了表示式则解析
            $routeArgs = $this->compilePattern($pattern, $options);

            $route = [true, $routeArgs['regex'], $callback, $options];
        } else {
            // 如果只是普通的路由地址
            $route = [false, $pattern, $callback, $options];
        }

        // 如果设置了路由别名
        if (isset($options['as']) && $options['as'])
            $this->routes[$options['as']] = $route;
        else
            $this->routes[] = $route;

    }

    public function dispatch($route)
    {

    }

    public function run($path)
    {

    }

    /**
     * 查找匹配的路由
     */
    public function match($path)
    {
        // 判断请求类型
        $requestMethod = null;

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))
            $requestMethod = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        else if (isset($_SERVER['REQUEST_METHOD']))
            $requestMethod = $_SERVER['REQUEST_METHOD'];


        switch (strtoupper($requestMethod)) {
            case "POST":
                $requestMethod = self::REQUEST_METHOD_POST;
            case "GET":
                $requestMethod = self::REQUEST_METHOD_GET;
            case "PUT":
                $requestMethod = self::REQUEST_METHOD_PUT;
            case "DELETE":
                $requestMethod = self::REQUEST_METHOD_DELETE;
            case "PATCH":
                $requestMethod = self::REQUEST_METHOD_PATCH;
            case "HEAD":
                $requestMethod = self::REQUEST_METHOD_HEAD;
            case "OPTIONS":
                $requestMethod = self::REQUEST_METHOD_OPTIONS;
            default:
                $requestMethod = 0;
        }

        foreach ($this->routes as $route) {
            if ($route[0]) {
                if (!preg_match($route[1], $path, $regs)) {
                    continue;
                }
                $route[3]['vars'] = $regs;

                // validate request method
                if (isset($route[3]['method']) && $route[3]['method'] != $requestMethod)
                    continue;
                if (isset($route[3]['domain']) && $route[3]['domain'] != $_SERVER["HTTP_HOST"])
                    continue;
                if (isset($route[3]['secure']) && $route[3]['secure'] && (!isset($_SERVER["HTTPS"]) || !$_SERVER["HTTPS"]))
                    continue;

                return $route;
            } else {
                // prefix match is used when expanding is not enabled.
                if ((is_int($route[2]) && strncmp($route[1], $path, strlen($route[1])) === 0) || $route[1] == $path) {
                    // validate request method
                    if (isset($route[3]['method']) && $route[3]['method'] != $requestMethod)
                        continue;
                    if (isset($route[3]['domain']) && $route[3]['domain'] != $_SERVER["HTTP_HOST"])
                        continue;
                    if (isset($route[3]['secure']) && $route[3]['secure'] && (!isset($_SERVER["HTTPS"]) || !$_SERVER["HTTPS"]))
                        continue;

                    return $route;
                } else {
                    continue;
                }
            }
        }

    }


    /**
     * 解析设置的路由地址
     */
    public function compilePattern($pattern, array $options = array())
    {
        $tokens = array();
        $variables = array();
        $pos = 0;

        /*
          正则匹配出路由地址里的变量

          例如：
          /post/{id}(/{title})
         *
         * 匹配：
         * Array
            (
                [0] => Array
                    (
                        [0] => Array
                            (
                                [0] => {id}
                                [1] => 6
                            )
                        [1] => Array
                            (
                                [0] => id
                                [1] => 7
                            )
                    )
                [1] => Array
                    (
                        [0] => Array
                            (
                                [0] => (/{title})
                                [1] => 10
                            )
                        [1] => Array
                            (
                                [0] =>
                                [1] => -1
                            )
                        [2] => Array
                            (
                                [0] => /{title}
                                [1] => 11
                            )
                    )
            )
         */
        preg_match_all('/(?:\{([\w\d_]+)\}|\((.*)\))/x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {

//            if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
//                $tokens[] = array(self::TOKEN_TYPE_TEXT, $text);
//            }
//
//            // the first char from pattern (which is the seperater)
//            $seps = array($pattern[$pos]);
//            $pos = $match[0][1] + strlen($match[0][0]);

            // 如果是设置了可选的变量
            if ($match[0][0][0] == '(') {

            } else {
                // 如果是设置了必须的变量
                $varName = $match[1][0];

                $regexp = '[^\/]+?';

                $tokens[] = array(self::TOKEN_TYPE_VARIABLE,
                    $match[0][0],
                    $regexp,
                    $varName);

                // append variable name
                $variables[] = $varName;
            }
        }

        print_r($tokens);
        //print_r($variables);

        // 路由地址生成正则表达式
        $regex = '';
        foreach($tokens as $token) {
            $regex .= str_replace($token[1], '(' .$token[2]. ')', $pattern);
        }

        $res['regex'] = $regex;
        $res['variables'] = $variables;

        print_r($res);

        return $res;
    }

}
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
     */
    public function add($pattern, $callback, array $options = array())
    {
        // 判断是路由地址是否设置了正则表达
        $pcre = strpos($pattern, ':') !== false;
        if ($pcre) {
            $routeArgs = $this->compilePattern($pattern, $options);

            $this->routes[] = [true, $pattern, $callback, $options];
        } else {
            $this->routes[] = [false, $pattern, $callback, $options];
        }

        return $this->routes;
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
                $requestMethod =self::REQUEST_METHOD_OPTIONS;
            default:
                $requestMethod = 0;
        }


    }


    /**
     * 解析路由正则地址
     */
    public function compilePattern($pattern, array $options = array())
    {
        $len = strlen($pattern);
        $pos = 0;

        /**
         * contains:
         *
         *   array( 'text', $text ),
         *   array( 'variable', $match[0][0][0], $regexp, $var);
         *
         */
        $tokens = array();
        $variables = array();

        /**
         * 正则解析路由地址
         * /blog/to/:year/:month
         *
         * 结果：
         * Array (
         *    [0] => Array (
         *          [0] => Array (
         *              [0] => /:year
         *              [1] => 8
         *          ),
         *          [1] => Array (
         *              [0] => year
         *              [1] => 10
         *          )
         *    ),
         *
         *    [1] => Array (
         *          [0] => Array (
         *              [0] => /:month
         *              [1] => 14
         *          ),
         *          [1] => Array (
         *              [0] => month
         *              [1] => 16
         *          )
         *     )
         * );
         */
        preg_match_all('/(?:.:([\w\d_]+)|\((.*)\))/x', $pattern, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {

            if ($text = substr($pattern, $pos, $match[0][1] - $pos)) {
                // Array ( [0] => Array ( [0] => 3 [1] => /blog/to ) )
                $tokens[] = array( self::TOKEN_TYPE_TEXT, $text);
            }

            $seps = array($pattern[$pos]);
            $pos = $match[0][1] + strlen($match[0][0]);

            if ($pos !== $len) {
                $seps[] = $pattern[$pos];
            }

            $varName = $match[1][0];

            // use the default pattern (which is based on the separater charactors we got)
            $regexp = sprintf('[^%s]+?', preg_quote(implode('', array_unique($seps)), '#'));

            // append token item
            $tokens[] = array(self::TOKEN_TYPE_VARIABLE,
                $match[0][0][0],
                $regexp,
                $varName);

            // append variable name
            $variables[] = $varName;
        }

        print_r($tokens);
        exit;

    }

}
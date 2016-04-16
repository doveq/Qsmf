<?php
/**
 * Created by IntelliJ IDEA.
 * User: 20160309
 * Date: 2016/4/16
 * Time: 13:54
 */

$string = '/blog/to/:year/:month';
preg_match_all('/(?:.:([\w\d_]+)|\((.*)\))/x', $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
print_r($matches);

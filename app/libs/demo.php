<?php

/**
 * magento_sdk demo
 *
 * @author  Jiankang.Wang
 * @Date    2014-6-19
 * @Time    10:03:37 AM
 */
require 'MagentoApi.php';
//$server = 'http://localhost/magento/';           #magento server address or ip
 $server = 'http://192.168.6.13/deal8/';
$user = 'api';                             #api user name, created by magento
$key = '123456';                               #api user key, created by magento
// $user = 'api';
// $key = '123456';
$api = new MagentoApi($server);
$api->timeout = 3600;                       #same as magento's setting in System > Configuration > Magento Core API > Client Session Timeout (sec.)
$api->init($user, $key);


//require('demo/category.php');
//require('demo/article.php');
//require('demo/customer.php');
//require('demo/order.php');
//require('demo/partner.php');
//require('demo/return.php');
require('demo/attribute.php');

if ($data) {
    print_r($data);
} else {
    echo $api->error;
}
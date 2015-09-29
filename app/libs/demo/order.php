<?php

/**
 * magento_sdk demo
 *
 * @author  Jiankang.Wang
 * @Date    2014-6-19
 * @Time    10:03:37 AM
 */
/**
 * get order list
 */
#get all orders, don't use this in product environment, it's slow
//$data = $api->getOrders();


#get page 1 of the newest orders, 10 orders per page
//$data = $api->getOrders(array(
//    'limit' => 20,
//    'page' => 1,
//    'orderby'=>'updated_at',
//    'filters' => array(
//        'status' => MagentoApi::ORDER_STATUS_PENDING,
//        'updated_at' => array('gt' => '2014-07-09 01:24:17'))
//        ));

#use increment_id to get order's info
$data = $api->getOrder('100000111');




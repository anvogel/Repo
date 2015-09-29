<?php
/**
 * depends on RMA Module
 */

//$data = $api->getReturns();
//filters 'rma_id', 'customer_id', 'order_id', 'email', 'tracking_no', 'return_type', 'package', 'increment_id', 'adminstatus', 'product_id'
$data = $api->getReturns(array('limit' => 100, 'page' => 1, 'filters' => array('order_id' => '3')));

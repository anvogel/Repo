<?php
/**
 * depends on Partner Module
 */

//$data = $api->getPartners();
$data = $api->getPartners(array('limit' => 1, 'page' => 2, 'filers' => array('partner_id' => '222')));

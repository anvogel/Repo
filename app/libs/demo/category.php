<?php

/**
 * magento_sdk demo
 *
 * @author  Jiankang.Wang
 * @Date    2014-6-19
 * @Time    10:03:37 AM
 */
#Get
//$data = $api->getCatagories();
#Set
// catagory data see http://www.magentocommerce.com/api/soap/catalog/catalogCategory/catalog_category.create.html for mode optional fieleds
$category = array(
    'name' => 'Category name 2',
    'is_active' => 1,
    'available_sort_by' => array('position'),
    'default_sort_by' => 'position',
    'description' => 'Category description',
    'is_anchor' => 0,
    'meta_description' => 'Category meta description',
    'meta_keywords' => 'Category meta keywords',
    'meta_title' => 'Category meta title',
    'page_layout' => 'two_columns_left',
    'url_key' => 'url-key',
    'include_in_menu' => 1,
);
#add a category
$data = $api->setCategory(1, $category);

<?php

/**
 * magento_sdk demo
 *
 * @author  Jiankang.Wang
 * @Date    2014-6-19
 * @Time    10:03:37 AM
 */
$products = array(
    array(
        'type' => 'simple',
        'set' => 4,
        'sku' => 'simple_product_sku',
        'productData' => array(
            'categories' => array(2),
            'websites' => array(1),
            'is_deal' => 1, #deal8 only
            'deal_start_time' => '2014-07-07', #deal8 only
            'special_price' => '10',
            'name' => 'Product name',
            'description' => 'Product description',
            'short_description' => 'Product short description',
            'weight' => '10',
            'status' => '1',
            'url_key' => 'simple-product-sku-url-key',
            'url_path' => 'simple-product-sku-url-path',
            'visibility' => '4',
            'price' => '100',
            'tax_class_id' => 1, #for deal8, use 4
            'meta_title' => 'Product meta title',
            'meta_keyword' => 'Product meta keyword',
            'meta_description' => 'Product meta description'
        ),
        'store' => '1',
        'images' => array(
            MagentoApi::image('C:\Users\lujie\Pictures\Koala.jpg', array('deal_top_image_1'), null, true), #deal8 only
            MagentoApi::image('C:\Users\lujie\Pictures\Penguins.jpg', array('deal_top_image_2'), null, true), #deal8 only
            MagentoApi::image('http://img12.360buyimg.com/n0/g14/M04/04/0C/rBEhVlKcWD8IAAAAAAFrOf88o0MAAGWdgHvBOoAAWtR189.jpg', array('image')),
        ),
    ),

    array(
        'type' => 'simple',
        'set' => 'Shoes', //attribute set id
        'sku' => 'simple1 for configurable',
        'storeView' => '1',
        'productData' => array(
            'categories' => array(2),
            'websites' => array(1),
            'is_deal' => 1, #deal8 only
            'deal_start_time' => '2014-08-17', #deal8 only
            'price' => '100',
            'special_price' => '10',
            'name' => 'Product name',
            'description' => 'Product description',
            'short_description' => 'Product short description',
            'weight' => '10',
            'status' => '1',
            'url_key' => 'simple1-key',
            'url_path' => 'simple1-path',
            'visibility' => '4',
            'tax_class_id' => 0, #none tax, for deal8, use 4
            'meta_title' => 'Product meta title',
            'meta_keyword' => 'Product meta keyword',
            'meta_description' => 'Product meta description',
            //the attributes of the attribute set we add, if use the shoes attribute set, it will has color and size attributes
            'color' => 'Blue', //attribute value id or attribute value label
            'size' => 'XL',
            'stock_data' => array(
                'manage_stock' => 1,
                'qty' => 100,
                'is_in_stock' => 1,
            ),
        ),
    ),

    array(
        'type' => 'simple',
        'set' => 'Shoes', //attribute set code or id
        'store' => '1',
        'sku' => 'simple2 for configurable',
        'productData' => array(
            'categories' => array(2),
            'websites' => array(1),
            'is_deal' => 1, #deal8 only
            'deal_start_time' => '2014-08-17', #deal8 only
            'price' => '200',
            'special_price' => '20',
            'name' => 'Product name',
            'description' => 'Product description',
            'short_description' => 'Product short description',
            'weight' => '10',
            'status' => '1',
            'url_key' => 'simple2-key',
            'url_path' => 'simple2-path',
            'visibility' => '4',
            'tax_class_id' => 0, #none tax, for deal8, use 4
            'meta_title' => 'Product meta title',
            'meta_keyword' => 'Product meta keyword',
            'meta_description' => 'Product meta description',
            //the attributes of the attribute set we add, if use the shoes attribute set, it will has color and size attributes
            'color' => 'Red', //attribute value id or attribute value label
            'size' => 'L',
            'stock_data' => array(
                'manage_stock' => 1,
                'qty' => 200,
                'is_in_stock' => 1,
            ),
        ),
    ),

    array(
        'type' => 'configurable',
        'set' => 'Shoes',
        'sku' => 'configurable_product_sku',
        'productData' => array(
            'categories' => array(2),
            'websites' => array(1),
            'is_deal' => 1, #deal8 only
            'deal_start_time' => '2014-08-17', #deal8 only
            'price' => '100',
            'special_price' => '20',
            'name' => 'Configurable Product name',
            'description' => 'Configurable Product description',
            'short_description' => 'Configurable Product short description',
            'weight' => '10',
            'status' => '1',
            'url_key' => 'configurable-product-url-key',
            'url_path' => 'configurable-product-url-path',
            'visibility' => '4',
            'tax_class_id' => 0, #for deal8, use 4
            'meta_title' => 'Configurable Product meta title',
            'meta_keyword' => 'Configurable Product meta keyword',
            'meta_description' => 'Configurable Product meta description',
            //for configurable product, set the configurable attribute value, the attribute value must be created first by attribute api
            'configurable_attributes_data' => array(
                array(
                    'attribute_code' => 'color',
                    //configurable product has own price, the child product's price is not used,
                    //the final price is the sum of the configurable product price and attributes price
                    //for example, this product, if customer choice Red and L, the price is 100 + (100 * 10%) + (-3) = 107
                    'pricing' => array('Blue' => '3', 'Red' => '10%'),
                ),
                array(
                    'attribute_code' => 'size',
                    'pricing' => array('L' => '-3', 'XL' => '-10%'),
                ),
            ),
            //link to the child products, it must be in the same attribute set
            'configurable_products_data' => array(
                'simple1 for configurable', 'simple2 for configurable'
            ),
        ),
    ),
);

#improt articles
//$data = $products;
$data = $api->improtArticles($products);

#get article data
//$data = $api->getArticle('asd');

#get productId,name,description only
//$data = $api->getArticle(12, null, array('product_id', 'name', 'description'));

$products = array(
    'simple1 for configurable' => array(
        'stock_data' => array(
            'manage_stock' => 1,
            'qty' => 100,
            'is_in_stock' => 1,
        ),
    ),
    'simple2 for configurable' => array(
        'stock_data' => array(
            'manage_stock' => 1,
            'qty' => 200,
            'is_in_stock' => 1,
        ),
    ),
);

$data = $api->batchUpdateArticles($products);

<?php
/**
 * magento_sdk demo for attribute and attribute set
 * it used for configurable article
 *
 * @author Lujie.Zhou(lujie.zhou@jago-ag.cn)
 * @Date 8/19/14
 * @Time 10:09 AM
 */

/**
 * if a product has attributes, it may need to create a attribute set to manage attributes
 * attribute set like category, for example:
 * shoes has two attributes: color and size,
 * you need to create a attribute set named shoes based on default attribute set,
 * and add color and size attributes to shoes attribute set.
 * So if you want to create a shoes product, select the sample product type and shoes attribute set, the product will contain color and size attributes.
 * Then you need to create shoes sample products and create a shoes configurable product link to the shoes sample products
 */

$attributeToAdd = array(
    'attribute_code' => 'attributetoadd',
    "scope" => "global",
    "default_value" => "100",
    "frontend_input" => "select",
    "is_unique" => 0,
    "is_required" => 0,
    "is_configurable" => 1,
    "is_searchable" => 0,
    "is_visible_in_advanced_search" => 0,
    "used_in_product_listing" => 0,
    "additional_fields" => array(
        "is_filterable" => 1,
        "is_filterable_in_search" => 1,
        "position" => 1,
        "used_for_sort_by" => 1
    ),
    "frontend_label" => array(
        array(
            "store_id" => 0,
            "label" => "Add attribute"
        )
    )
);

$attrId = $api->createAttribute($attributeToAdd);

$optionToAdd = array(
    "label" => array(
        array(
            "store_id" => 0,
            "value" => "New Option"
        ),
    ),
    "order" => 0,
    "is_default" => 0
);

$optionId = $api->addOptionToAttribute('attributetoadd', $optionToAdd);

//4 is the default attribute set id
$setId = $api->createAttributeSet('NewSet', 4);
$groupId = $api->addAttributeGroup($setId, 'variations');
$data = $api->addAttributeToSet($attrId, $setId, $groupId);

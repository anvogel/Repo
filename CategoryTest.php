<?php
/*
 * Copyright 2011 Daniel Sloof
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

class Test
{

    /**
     * Stores array of imported simple products used for configurable, bundle and grouped product creation.
     *
     * @var array
     */
    protected $_linkedProducts = null;

    /**
     * Default attributes that are used for every product entity.
     *
     * @var array
     */
    protected $_defaultProductAttributes = array(
        'description'       => 'Some description',
        '_attribute_set'    => 'Default',
        'short_description' => 'Some short description',
        '_product_websites' => 'base',
        'status'            => 1, //Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
        'visibility'        => 4, //Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
        'tax_class_id'      => 0,
        'is_in_stock'       => 1
    );

    /**
     * Default attributes that are used for customers.
     *
     * @var array
     */
    protected $_defaultCustomerAttributes = array(
        '_website'          => 'base',
        '_store'            => 'default',
        'group_id'          => 1
    );

    /**
     * Default attributes that are used for categories. 
     *
     * @var array
     */
    protected $_defaultCategoryAttributes = array(
        '_root'             => 'Default Category',
        'is_active'         => 'yes',
        'include_in_menu'   => 'yes',
        'description'       => 'Category description',
        'meta_description'  => 'Category meta description',
        'available_sort_by' => 'position',
        'default_sort_by'   => 'position'
    );

    /**
     * Creates and stores 3 simple products with different values for the color attribute.
     * These products are used for configurable, bundle and grouped product generation.
     *
     * @return array
     */
    protected function _getLinkedProducts()
    {
        // We create 3 simple products so we can test configurable/bundle links.
        if ($this->_linkedProducts === null) {
            $this->_linkedProducts = $this->generateRandomSimpleProduct(3);

            // Use the color option for configurables. Note that this attribute must be added to the specified attribute set!
            foreach (array('red', 'yellow', 'green') as $key => $color) {
                $this->_linkedProducts[$key + 1]['color'] = $color;
            }
//            Mage::getModel('api_import/import_api')->importEntities($this->_linkedProducts);
        }
        return $this->_linkedProducts;
    }

    /**
     * Generates random simple products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomSimpleProduct($numProducts)
    {
        $products = array();

        for ($i = 1; $i <= $numProducts; $i++) {
            $products[$i] = array_merge(
                $this->_defaultProductAttributes,
                array(
                    'sku'    => 'some_sku_' . $i . rand(1, 1000),
                    '_type'  => 'simple', //Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
                    'name'   => 'Some product ( ' . $i . ' )',
                    'price'  => rand(1, 1000),
                    'weight' => rand(1, 1000),
                    'qty'    => rand(1, 30)
                )
            );
        }

        return $products;
    }

    /**
     * Generates random configurable products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomConfigurableProduct($numProducts)
    {
        $products = array();

        for ($i = 1, $counter = 1; $i <= $numProducts; $i++) {
            // Generate configurable product.
            $products[$counter] = array_merge(
                $this->_defaultProductAttributes,
                array(
                    'sku'    => 'some_configurable_' . $i . rand(1, 1000),
                    '_type'  => 'configurable', //Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
                    'name'   => 'Some configurable ( ' . $i . ' )',
                    'price'  => rand(1, 1000),
                    'weight' => rand(1, 1000)
                )
            );

            // Associate child products.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
                $products[$counter] = array_merge(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
                        '_super_products_sku'     => $linkedProduct['sku'],
                        '_super_attribute_code'   => 'color',
                        '_super_attribute_option' => $linkedProduct['color']
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random bundle products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomBundleProduct($numProducts)
    {
        $products = array();

        for ($i = 1, $counter = 1; $i <= $numProducts; $i++) {
            // Generate bundle product.
            $products[$counter] = array_merge(
                $this->_defaultProductAttributes, array(
                    'sku'        => 'some_bundle_' . $i . rand(1, 1000),
                    '_type'      => 'bundle', //Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
                    'name'       => 'Some bundle ( ' . $i . ' )',
                    'price'      => rand(1, 1000),
                    'weight'     => rand(1, 1000),
                    'price_view' => 'price range',
                    'price_type' => 1, //Mage_Bundle_Model_Product_Price::PRICE_TYPE_FIXED
                )
            );

            // Create an option.
            $optionTitle = 'Select a bundle item!';
            $products[$counter]['_bundle_option_title'] = $optionTitle;
            $products[$counter]['_bundle_option_type']  = 'select'; //Danslo_ApiImport_Model_Import_Entity_Product_Type_Bundle::DEFAULT_OPTION_TYPE;

            // Associate option selections.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
                $products[$counter] = array_merge(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
                        '_bundle_option_title'        => $optionTitle,
                        '_bundle_product_sku'         => $linkedProduct['sku'],
                        '_bundle_product_price_value' => rand(1, 1000)
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random grouped products.
     *
     * @param int $numProducts
     * @return array
     */
    public function generateRandomGroupedProduct($numProducts)
    {
        $products = array();

        // Generate grouped product.
        for ($i = 1, $counter = 1; $i <= $numProducts; $i++) {
            $products[$counter] = array_merge(
                $this->_defaultProductAttributes,
                array(
                    'sku'   => 'some_grouped_' . $i . rand(1, 1000),
                    '_type' => 'grouped', //Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
                    'name'  => 'Some grouped ( ' . $i . ' )'
                )
            );

            // Associated child products.
            foreach ($this->_getLinkedProducts() as $linkedProduct) {
                $products[$counter] = array_merge(
                    (isset($products[$counter]) ? $products[$counter] : array()),
                    array(
                        '_associated_sku'         => $linkedProduct['sku'],
                        '_associated_default_qty' => '1', // optional
                        '_associated_position'    => '0'  // optional
                    )
                );
                $counter++;
            }
        }

        return $products;
    }

    /**
     * Generates random customers.
     *
     * @param int $numCustomers
     * @return array
     */
    public function generateRandomStandardCustomer($numCustomers)
    {
        $customers = array();

        for ($i = 0; $i < $numCustomers; $i++) {
            $customers[$i] = array_merge(
                $this->_defaultCustomerAttributes,
                array(
                    'email'     => sprintf('%s@%s.com', uniqid(), uniqid()),
                    'firstname' => uniqid(),
                    'lastname'  => uniqid()
                )
            );
        }

        return $customers;
    }

    /**
     * Generates random categories.
     *
     * @param int $numCategories
     * @return array
     */
    public function generateRandomStandardCategory($numCategories)
    {   
        $categories = array();
        
        for ($i = 1; $i <= $numCategories; $i++) {
            $categories[$i - 1] = array_merge(
                $this->_defaultCategoryAttributes,
                array(
                    'name'          => sprintf('Test Category %d', $i),
                    '_category'     => sprintf('Test Category %d', $i),
                    'url_key'       => sprintf('test%d', $i),
                )
            );
            if ($i % 5 > 1) {
                $categories[$i - 1]['_category'] = $categories[$i - 2]['_category'] . '/' . $categories[$i - 1]['_category'];
            }
        } 

        return $categories;
    }

}

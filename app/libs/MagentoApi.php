<?php

/**
 * magento_sdk MagentoApi
 *
 * @author  Jiankang.Wang
 * @Date    2014-6-19
 * @Time    9:44:11 AM
 */

/**
 * @property-read string $error error message
 * @property-read int $errorCode errorCode
 * @property int $timeout timeout of cache
 * @method array getArticle(int|string $productId, int|string $store $store = null, array $attributes = null, string $identifierType = null) get article infos
 * @method boolean updateArticle(int|string $productId,array $productData,int|string $store = null, string $identifierType = null)
 * @method array getCustomer(int $customerId, array $attributes = null) get customer info
 * @method array getCustomers(array $filters)  retrieve the list of customers.
 * @method array getOrders(null|array $condition) get orders
 * @method array getOrder(string $orderIncrementId Increment Id) get order info
 * @method array getCategories(int $parentId = null, int|string $store = null) Allows you to retrieve the hierarchical tree of categories.
 * @method int setCategory(int $parentId,array $categoryData, int|string $store = null) Create a new category and return its ID.
 * @method boolean updateOrder(array $params) update order
 * @method int createAttribute(array $attributeToAdd) Create a new attribute to attribute set and return its ID.
 * @method int updateAttribute(string $attributeCode, array $attributeToUpdate) Update a attribute
 * @method int addOptionToAttribute(string $attributeCode, array $optionToAdd) Create a new attribute option to attribute and return its ID.
 * @method int createAttributeSet(string $setName, int $baseSetId) Create a new attribute set and return its ID.
 * @method int addAttributeGroup(int $attributeSetId, string $groupName) Add group to attribute set and return its ID.
 * @method int addAttributeToSet(int $attributeId, int $setId, int $groupId = null) Add attribute to attribute set and return its ID.  If the groupId parameter is not passed, the attribute is added to the General group by default.
 */
class MagentoApi
{

    public $timeout = 3600;
    protected $client;
    protected $session;
    protected $sessionKey;
    protected $_error;
    protected $_errorCode;
    protected $_cacheFolder = 'cache';
    protected $_wsdl;
    protected $apis = array(
        'getCustomer' => 'customer.info',
        'getCustomers' => 'customer.list',
        'getOrders' => 'order.list',
        'getOrder' => 'order.info',
        'updateOrder' => 'order.addComment',
        'cancelOrder' => 'order.cancel',
        'updateOrderShipment' => 'order_shipment.addComment',
        'addShipmentTrack' => 'order_shipment.addTrack',
        'getArticle' => 'catalog_product.info',
        'updateArticle' => 'catalog_product.update',
        'updateMedia' => 'catalog_product_attribute_media.update',
        'getCatagories' => 'catalog_category.tree',
        'updateCatagories' => 'catalog_category.update',
        'setCategory' => 'catalog_category.create',
        'createAttribute' => 'product_attribute.create',
        'updateAttribute' => 'product_attribute.update',
        'addOptionToAttribute' => 'product_attribute.addOption',
        'createAttributeSet' => 'product_attribute_set.create',
        'addAttributeGroup' => 'product_attribute_set.groupAdd',
        'addAttributeToSet' => 'product_attribute_set.attributeAdd',
    );

    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_ClOSED = 'closed';

    public function __construct($Server)
    {
        $folder = __DIR__ . '/' . $this->_cacheFolder;
        if (!is_writable($folder)) {
            die($this->_cacheFolder . ' folder should be writable');
        }
        $wsdl = $this->loadWsdl($Server . '/api/soap/?wsdl');
        $this->client = new SoapClient($wsdl);
    }

    public function __call($name, $arguments = null)
    {
        if (method_exists($this, $name . 'Api')) {
            $method = $name . 'Api';
            return $this->$method($arguments);
        } elseif (key_exists($name, $this->apis)) {
            $method = $this->apis[$name];
            return $this->call($method, $arguments);
        } else {
            return false;
        }
    }

    public function __get($name)
    {
        $var = '_' . $name;
        if (isset($this->$var)) {
            return $this->$var;
        } else {
            return null;
        }
    }

    /**
     * login
     * @param string $user
     * @param string $key
     */
    public function init($user, $key)
    {
        if (!$this->getSession($user, $key)) {
            $this->session = $this->client->login($user, $key);
            $this->setSession($user, $key);
        }
    }

    /**
     * unset the session
     */
    public function destruct()
    {
        $this->client->endSession($this->session);
        $this->unsetSession();
    }

    /**
     * 
     * @param string $name api name
     * @param mixed $arguments arguments
     */
    public function call($name, $arguments = null)
    {
        if (is_null($this->session)) {
            return false;
        }
        try {
            $data = $this->client->call($this->session, $name, $arguments);
        } catch (SoapFault $e) {
            $this->error = $e->getMessage();
            $this->errorCode = $e->getCode();
            return false;
        }
        return $data;
    }

    public static function toJson($data)
    {
        return json_encode($data);
    }

    public static function toXml($data, $root = 'root')
    {
        $xml = new SimpleXMLElement("<$root></$root>");
        self::iteratechildren($data, $xml);
        return $xml->asXML();
    }

    public static function iteratechildren($array, $xml)
    {
        foreach ($array as $name => $value) {
            if (!is_array($value)) {
                $xml->$name = $value;
            } else {
                $xml->$name = null;
                self::iteratechildren($value, $xml->$name);
            }
        }
    }

    protected function getSession($user, $key)
    {
        $cache = md5($this->_wsdl . '/' . $user . '/' . $key);
        $session = $this->getCache($cache);
        if ($session) {
            $this->session = $session;
            $this->sessionKey = $cache;
        }
        return $session;
    }

    protected function setSession($user, $key)
    {
        $this->setCache(md5($this->_wsdl . '/' . $user . '/' . $key), $this->session, $this->timeout);
    }

    protected function unsetSession()
    {
        $file = __DIR__ . '/' . $this->_cacheFolder . '/' . $this->sessionKey . '.cache';
        @unlink($file);
    }

    protected function getCache($name, $default = null)
    {
        $file = __DIR__ . '/' . $this->_cacheFolder . '/' . $name . '.cache';
        if (is_readable($file)) {
            $data = file_get_contents($file);
            if ($data) {
                $_data = unserialize($data);
                if ($_data['create_time'] + $this->timeout >= time()) {
                    return $_data['value'];
                }
            }
            return $default;
        } else {
            return $default;
        }
    }

    protected function setCache($name, $value, $expire = 0)
    {
        $folder = __DIR__ . '/' . $this->_cacheFolder;
        $file = $folder . '/' . $name . '.cache';
        file_put_contents($file, serialize(array('create_time' => time(), 'value' => $value)));
    }

    protected function loadWsdl($wsdl)
    {
        $folder = __DIR__ . '/' . $this->_cacheFolder;
        $file = $folder . '/' . md5($wsdl) . '.wsdl';
        $this->_wsdl = $wsdl;
        if (file_exists($file)) {
            return $file;
        }
        $ch = curl_init($wsdl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        if (!$data) {
            die('Couldn\'t load WSDL at ' . $wsdl);
        } else {
            file_put_contents($file, $data);
        }
        return $file;
    }

    ######################

    /**
     * @param $articles
     * @return bool
     */
    public function improtArticles($articles)
    {
//        return $this->call('catalog_product.api_import', array('products' => $articles));
//        print_r($articles);exit;
        return $this->call('import.importEntities', array($articles));


    }

    public function batchUpdateArticles($articles)
    {
        return $this->call('catalog_product.update', $articles);
    }

    public function updateCatagories($categoryies)
    {
        return $this->call('catalog_category.update', $categoryies);
    }
    
    public function updateArticleMedia($articles)
    {
        return $this->call('catalog_product_attribute_media.update', $articles);
    }
    
    public function createArticleMedia($articles)
    {
        return $this->call('catalog_product_attribute_media.create', $articles);
    }
    
    
     public function deleteArticleMedia($articles)
    {
        
        return $this->call('product_media.remove', $articles);
    }
    
    public function getAllArticles($articles)
    {
        return $this->call('catalog_product.info', $articles);
    }

    /**
     * get partner id and names array
     * @param array $data array('limit' => 1, 'page' => 2, 'filers' => array('partner_id' => '222'))
     * @return array
     */
    public function getPartners($data = null)
    {
        return $this->call('partner.list', $data);
    }

    public function getReturns($data = null)
    {
        return $this->call('rma.list', $data);
    }

    /**
     * 
     * @param string $filename image file path
     * @param array $types  Array of types 
     * @param string $label  Image label 
     * @param boolean $exclude  Defines whether the image will associate only to one of three image types 
     * @return boolean|array
     */
    public static function image($filename, $types = array(), $label = null, $exclude = false)
    {
        if (FALSE !== strpos($filename, 'http://') || FALSE !== strpos($filename, 'https//') || FALSE !== strpos($filename, 'ftp://')) {
            $data = $filename;
        } else {
            if (!is_readable($filename)) {
                return false;
            }
            $data = base64_encode(file_get_contents($filename));
        }

        return array(
            'file' => array(
                'content' => $data,
                'mime' => self::getMine($filename),
            ),
            'label' => $label,
            'types' => $types,
            'exclude' => (int) $exclude
        );
    }

    protected static function getMine($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }
        $info = pathinfo($filename);
        switch ($info['extension']) {
            case 'jpg':
            case 'jpeg':
            default:
                $mine = 'image/jpeg';
                break;
            case 'png':
                $mine = 'image/png';
                break;
            case 'gif':
                $mine = 'image/gif';
                break;
        }
        return $mine;
    }

}

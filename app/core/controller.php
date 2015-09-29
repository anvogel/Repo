<?php

require_once __DIR__ .'/../config/config.php';
include_once __DIR__ .('/../db/mysql.php');
include_once __DIR__ .('/../libs/MagentoApi.php');

/**
 * This is the "base controller class". All other "real" controllers extend this class.
 */
class Controller
{
    /**
     * @var null Database Connection
     * @var null API Connection
     */
    protected   $oMySQL;
    /** @var MagentoApi */
    protected   $oAPI;

    /**
     * Whenever a controller is created, open a database connection too. The idea behind is to have ONE connection
     * that can be used by multiple models (there are frameworks that open one connection per model).
     */
    function __construct()
    {
        $this->openDatabaseConnection();
        $this->openMagentoConnection();
    }

    /**
     * Open the database connection with the credentials from application/config/config.php
     */
    private function openDatabaseConnection()
    {
        $this->oMySQL   = new MySQL(DB_NAME,DB_USER,DB_PASSWORD,DB_SERVER);
    }
    
    /**
     * Open the database connection with the credentials from application/config/config.php
     */
    private function openMagentoConnection()
    {
        $this->oAPI     = new MagentoApi(MAGENTO_SERVER_NAME);
        $this->oAPI->init(MAGENTO_SERVER_USER, MAGENTO_SERVER_KEY);
    }

    /**
     * Load the model with the given name.
     * loadModel("SongModel") would include models/songmodel.php and create the object in the controller, like this:
     * $songs_model = $this->loadModel('SongsModel');
     * Note that the model class name is written in "CamelCase", the model's filename is the same in lowercase letters
     * @param string $model_name The name of the model
     * @return object model
     */
    public function loadModel($model_name)
    {
        require 'app/models/' . strtolower($model_name) . '.php';
        // return new model (and pass the database connection to the model)
        return new $model_name($this->oMySQL);
    }
    /**
     * Load the view with the given name.
     * view("viewName") would include view/viewname.php, like this:
     * $this->view('index', $data);
     * Note that the second parameter is the data inside the view, you can set it to empty or don't pass it
     * @param string $view_name The name of the model
     * @param string | array $data, data to be shown in view
     */
    public function view($view_name, $data)
    {
        require 'app/view/' . strtolower($view_name) . '.php';
        
    }

    /**
     *
     * @param string $str
     * @param array $options
     * @return string
     */
    public function seoUrl($str, $options = array())
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string )$str, 'UTF-8', mb_list_encodings());
        $defaults = array('delimiter' => '-', 'limit' => null, 'lowercase' => true,
            'replacements' => array(), 'transliterate' => true,);
        // Merge options
        $options = array_merge($defaults, $options);
        $char_map = array(
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'AE', 'Å' => 'A', 'Æ' =>
                'AE', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I',
            'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' =>
                'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'OE', 'Ő' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' =>
                'U', 'Û' => 'U', 'Ü' => 'UE', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH', 'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'æ' =>
                'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' =>
                'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ő' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' =>
                'u', 'û' => 'u', 'ü' => 'ue', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y',
            // Latin symbols
            '©' => '(c)'

        );
        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'],
            $str);
        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }
        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/',
            '$1', $str);
        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str,
            'UTF-8')), 'UTF-8');
        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);
        return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
    }
}

<?php

/**
 * Configuration
 *
 * For more info about constants please @see http://php.net/manual/en/function.define.php
 * If you want to know why we use "define" instead of "const" @see http://stackoverflow.com/q/2447791/1114320
 */

/**
 * Configuration for: Error reporting
 * Useful to show every little problem during development, but only show hard errors in production
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);

/**
 * Configuration for: Project URL
 * Put your URL here, for local development "127.0.0.1" or "localhost" (plus sub-folder) is fine
 */
//define('URL', "https://www.jago24.fr/index.php/");

define('URL', "http://623047-db3.jago-ag.de/index.php/");

/**
 * Configuration for: Database
 * This is the place where you define your database credentials, database type etc.
 */


define('DB_SERVER', '192.168.178.115:3306');
define('DB_NAME', 'shop');
define('DB_USER', 'magentointerface');
define('DB_PASSWORD', 'mage01!');


/*
define('DB_SERVER', '10.10.100.11:3306');
define('DB_NAME', 'shop');
define('DB_USER', 'root');
define('DB_PASSWORD', 'jago');*/

define('HTACCESS_USER', 'jago');
define('HTACCESS_PASS', 'jago91');
//define('MAGENTO_SERVER_NAME', 'https://www.jago24.fr'); #magento server address or ip
define('MAGENTO_SERVER_NAME', 'http://623047-db3.jago-ag.de'); #magento server address or ip
#define('MAGENTO_SERVER_NAME_LOCAL', 'http://old.yincms.com'); #magento server address or ip
define('MAGENTO_SERVER_USER', 'wawi');#api user name, created by magento
define('MAGENTO_SERVER_KEY', 'c04bbefee2fe4fec592c49ce5327b5d4'); // db3 Shop
//define('MAGENTO_SERVER_KEY', '2434c76ed88f4d1572e9c5448a88916b'); // jago24.fr Shop

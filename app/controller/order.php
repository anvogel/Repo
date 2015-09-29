<?php
require_once __DIR__ .'/customer.php';

/**
 * Order
 * 
 * @package MagentoAPI
 * @author JAGO Warenhandels GmbH & Co. KG
 * @copyright 2014
 * @version $Id$
 * @access public
 */
class Order extends Controller {
    
    private $debug = false;
    private $mwstFR = 1.20; # depends on shipping country
    private $paymentMethods_arr = array(1 => "banktransfer",
                                        2 => "paymentnetwork_pnsofortueberweisung",
                                        5 => "paypal_express",
                                        7 => "paymentoperator_cc",
                                        0 => "checkmo");
    private $orderStatus_arr = array(0 => 'processing',
                                     1 => 'partially_delivered',
                                     2 => 'delivered',
                                     3 => 'canceled');
    
    /**
     * Order::__construct()
     * 
     * @return void
     */
    function __construct() {
        parent::__construct();
    }
    /**
     * Important: Please uncomment the part where is the query to check if the order is already imported, only when you will 
     * use the interface in production enviroment.
     * To do:
     *      * Get the payment method IDs and compare with ours.
     *      * Please use the Magento API 
     *      * use this for get list of orders: http://www.magentocommerce.com/api/soap/sales/salesOrder/sales_order.list.html
     *      * and this for get order details: http://www.magentocommerce.com/api/soap/sales/salesOrder/sales_order.info.html
     * 
     * Order::importOrders()
     * 
     * @return void
     */
    function importOrders() {

        $o_customer     = new Customer();
        #$orders         = $this->oAPI->getOrders(array('increment_id' => 'FR24-200001146'));
        $orders         = $this->oAPI->getOrders(array('created_at' => array('from' => date('Y-m-d H:i:s', time() - 2*60*60))));

		#var_dump ($orders); die();
		
        if (!$orders) {
            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
        } else {
            $a_result   = "";
            $i_num_rows = false;
            $orderModel  = $this->loadModel('OrderModel');
            foreach($orders as $order)
            {
			if ($order["status"] != "canceled" AND $order["status"] != "waiting_auth_poperator_note"){
                //get Order info
                $orderInfo              = $this->oAPI->getOrder($order['increment_id']);
                if($this->debug){
                    #echo "pos:".$orderInfo['items'][0]["sku"]."<br />";
                    var_dump($orderInfo);continue;
                }
                $a_newOrder         = array();
                $a_orderPosition    = array();
                $a_customer         = array();
                $i_customer_id      = null;
                $i_auftragId        = null;
                $a_result = $this->oMySQL->executeSQL("SELECT * FROM tbl_auftrag where auftrag_foreign_id='".$order['increment_id']."'");
                #$a_result = mysql_fetch_assoc($this->oMySQL->result);# $this->oMySQL->arrayResults();
                echo "<br />check duplicates.<br />";
                var_dump ($a_result);
                $i_num_rows = $this->oMySQL->records;
                if ($i_num_rows>=1) {
                    continue;
                }
                $a_newOrder['auftrag_foreign_id'] = $order['increment_id'];
                
                $a_country = $this->oMySQL->executeSQL("SELECT * FROM wawi.tbl_Laender where Laender_Kuerzel='".$orderInfo['billing_address']['country_id']."' and Laender_shop_sichtbar = -1");

                if (!empty($order['customer_email'])) {
                    //check if customer exists or add new customer
                    $a_result = $this->oMySQL->executeSQL("SELECT * FROM tbl_kunden where kunden_email='".$order['customer_email']."'");
                    #echo "<br />check customer:<br />";
                    #var_dump ($a_result);
                    $i_num_rows = $this->oMySQL->records;
                    if ($i_num_rows>0) {
                        $i_customer_id = $a_result[0]['kunden_id'];
                    } else {
                       //add new customer which has no ID. Guest Customer
                        $a_newCustomer = array();
                        $a_newCustomer['kunden_email']      = $order['customer_email'];
                        if ($a_customerResult['gender'] == 1 ) {
                            $iGender = 3;
                        } else if ($a_customerResult['gender'] == 2) {
                            $iGender = 2;
                            } else {
                                $iGender = 0;
                                }
                        $a_newCustomer['kunden_geschlecht'] = $iGender;
                        $a_newCustomer['kunden_name']       = mysql_real_escape_string($orderInfo['billing_address']['lastname']);
                        $a_newCustomer['kunden_Vorname']    = mysql_real_escape_string($orderInfo['billing_address']['firstname']);
                        $a_newCustomer['kunden_pwd']        = null;
                        $a_newCustomer['kunden_ref']        = 'jago24fr'; # ???
                        $a_newCustomer['kunden_sprache']    = $a_country[0]['Laender_sprache'];
                        $a_newCustomer['kunden_land']       = $a_country[0]['Laender_ID'];
                        $this->oMySQL->insert('tbl_kunden',$a_newCustomer); 
                        $i_customer_id = $this->oMySQL->lastInsertID();   

                    }
                } else {
                    //error handling
                    //die('there is no email address!');
                    echo "emtpy email address";
                }
                
                #echo "<br />check country:<br />";
                #var_dump ($a_country);
                $a_newOrder['auftrag_ref']              = $this->generateRandomStrings();
                $a_newOrder['auftrag_k_id']             = $i_customer_id;
                $a_newOrder['auftrag_datum']            = $order['created_at'];
                #$a_newOrder['auftrag_zahlung_datum']    = $order['created_at'];
                //billing address
                $a_newOrder['auftrag_r_firma']          = mysql_real_escape_string($orderInfo['billing_address']['company']);
                $a_newOrder['auftrag_r_name']           = mysql_real_escape_string($orderInfo['billing_address']['firstname'].' '.$orderInfo['billing_address']['lastname']);
                $a_newOrder['auftrag_r_strasse']        = mysql_real_escape_string($orderInfo['billing_address']['street']);
                $a_newOrder['auftrag_r_ort']            = mysql_real_escape_string($orderInfo['billing_address']['city']);
                $a_newOrder['auftrag_r_land']           = mysql_real_escape_string($a_country[0]['Laender_ID']);
                $a_newOrder['auftrag_r_plz']            = mysql_real_escape_string($orderInfo['billing_address']['postcode']);
                $a_newOrder['auftrag_r_geschlecht']     = mysql_real_escape_string($orderInfo['customer_gender']);
                
                if ($orderInfo['shipping_address']['street']!='') {
                    $a_country = $this->oMySQL->executeSQL("SELECT * FROM wawi.tbl_Laender where Laender_Kuerzel='".$orderInfo['shipping_address']['country_id']."'");
                    //shipping address
                    $a_newOrder['auftrag_l_firma']      = mysql_real_escape_string($orderInfo['shipping_address']['company']);
                    $a_newOrder['auftrag_l_name']       = mysql_real_escape_string($orderInfo['shipping_address']['firstname'].' '.$orderInfo['shipping_address']['lastname']);
                    $a_newOrder['auftrag_l_strasse']    = mysql_real_escape_string($orderInfo['shipping_address']['street']);
                    $a_newOrder['auftrag_l_ort']        = mysql_real_escape_string($orderInfo['shipping_address']['city']);
                    $a_newOrder['auftrag_l_land']       = mysql_real_escape_string($a_country[0]['Laender_ID']);
                    $a_newOrder['auftrag_l_plz']        = mysql_real_escape_string($orderInfo['shipping_address']['postcode']);
                    $a_newOrder['auftrag_l_geschlecht'] = mysql_real_escape_string($orderInfo['customer_gender']);
                } else {
                    //copy the billing address to shipping
                    $a_newOrder['auftrag_l_firma']      = mysql_real_escape_string($orderInfo['billing_address']['company']);
                    $a_newOrder['auftrag_l_name']       = mysql_real_escape_string($orderInfo['billing_address']['firstname'].' '.$orderInfo['billing_address']['lastname']);
                    $a_newOrder['auftrag_l_strasse']    = mysql_real_escape_string($orderInfo['billing_address']['street']);
                    $a_newOrder['auftrag_l_ort']        = mysql_real_escape_string($orderInfo['billing_address']['city']);
                    $a_newOrder['auftrag_l_land']       = mysql_real_escape_string($a_country[0]['Laender_ID']);
                    $a_newOrder['auftrag_l_plz']        = mysql_real_escape_string($orderInfo['billing_address']['postcode']);
                    $a_newOrder['auftrag_l_geschlecht'] = mysql_real_escape_string($orderInfo['customer_gender']);
                }
                
        //        $a_newOrder['auftrag_zahlart']          = $this->paymentMethods_arr[$orderInfo['payment']['method']];
                $a_newOrder['auftrag_wert']             = $order['grand_total'];
                $a_newOrder['auftrag_mwst']             = $this->mwstFR;
                $a_newOrder['auftrag_versandkosten']    = doubleval($order['shipping_amount'] / $this->mwstFR);
                $a_newOrder['auftrag_waehrung']         = $order['order_currency_code'];
                $a_newOrder['auftrag_shop']             = 8;
                $a_newOrder['auftrag_za_aufschlag']     = 0; # no use of cash on delivery
                $a_newOrder['auftrag_partner']          = 0;
                $a_newOrder['auftrag_newsletteraktion'] = 0;
                
				
				if ($orderInfo['payment']['method'] == "banktransfer"){
						$a_newOrder['auftrag_zahlart']      = 1;
                    }
					
					if ($orderInfo['payment']['method'] == "paymentnetwork_pnsofortueberweisung"){
						$a_newOrder['auftrag_zahlart']      = 2;
                    }
					
					if ($orderInfo['payment']['method'] == "paypal_express"){
						$a_newOrder['auftrag_zahlart']      = 5;
                    }
					
					if ($orderInfo['payment']['method'] == "paymentoperator_cc"){
						$a_newOrder['auftrag_zahlart']      = 7;
                    }
					
					if ($orderInfo['payment']['method'] == "checkmo"){
						$a_newOrder['auftrag_zahlart']      = 0;
                    }
				
				#echo 'test';
                $i_auftragId = $orderModel->addNewOrder($a_newOrder);
                if($i_auftragId==false) {
                    //error handling
                    //die('the order couldnt\'t be imported');
                    continue;
                }
                $this->oMySQL->executeSQL("INSERT INTO tbl_magento (magento_orderid, wawi_orderid, order_date, order_status, shop_id) values ('".$order['increment_id']."',".$i_auftragId.",'".$order['created_at']."',0,8)");
                foreach ($orderInfo['items'] as $position) {
                    $productData_arr = $this->oMySQL->executeSQL("SELECT * FROM wawi.tbl_Artikel where A_Artikelnummer='".mysql_real_escape_string($position['sku'])."'");
                    #echo "<br />check sku:"."SELECT * FROM wawi.tbl_Artikel where A_Artikelnummer='".mysql_real_escape_string($position['sku'])."'"."<br />";
                    #var_dump ($productData_arr);
                    
                    if($position['product_type']==="configurable"){continue;}//Magento is generating one more position and I don't know why??
                    $a_orderPosition['ver_auf_a_auftrag']   = $i_auftragId;
                    $a_orderPosition['ver_auf_a_artikel']   = $productData_arr[0]["A_ID"];#$position['product_id'];//check if we should use item or product ID???
                    $a_orderPosition['ver_auf_a_preis']     = doubleval($position['price'] / $this->mwstFR);
                    $a_orderPosition['ver_auf_a_mwst']      = $this->mwstFR;
                    $a_orderPosition['ver_auf_a_menge']     = $position['qty_ordered'];
                    $a_orderPosition['ver_auf_a_Ref']       = $order['increment_id'].'-'.$position['product_id'];
                    
                    if($this->debug){
                        var_dump($a_orderPosition);
                    }
					#echo 'test';
                    $orderModel->addPositionToOrder($a_orderPosition);
                }
                if ($order['discount_amount']<0) {
                    $a_orderPosition = array();
                    $a_orderPosition['ver_auf_a_auftrag']   = $i_auftragId;
                    $a_orderPosition['ver_auf_a_artikel']   = 4981;//check if we should use item or product ID???
                    $a_orderPosition['ver_auf_a_preis']     = doubleval($order['discount_amount'] / $this->mwstFR);
                    $a_orderPosition['ver_auf_a_mwst']      = ($orderInfo['tax_percent']/100)+1;
                    $a_orderPosition['ver_auf_a_menge']     = 1;
                    $a_orderPosition['ver_auf_a_Ref']       = $order['increment_id'].'-'.$order['coupon_code'];
                    
                    $orderModel->addPositionToOrder($a_orderPosition);
					
                }
				
                $this->oAPI->call('sales_order.addComment', array($order['increment_id'], 'processing','Order Status has changed to : processing', false));
				#echo 'test extern Zahlungen';
				#echo '<br><br>';
				#var_dump ($orderInfo['payment']['method']);
				#echo '<br><br>';
                // 1 = Banküberweisung, 2 = Sofortüberweisung, 5 = Paypal, 7 = Kreditkarte 
                if (($orderInfo['payment']['method'] == $this->paymentMethods_arr[5] OR ($orderInfo['payment']['method'] == $this->paymentMethods_arr[2] and $order["status"] != "pending") OR 
				$orderInfo['payment']['method'] == $this->paymentMethods_arr[7])) {
				
				#echo 'test intern Zahlungen';
				
                    $a_orderPayment = array();
                    $a_orderPayment['zahlungen_zweck']          = $i_auftragId . $a_newOrder['auftrag_ref'];
                    $a_orderPayment['zahlungen_summe']          = $order['grand_total'];
                    $a_orderPayment['zahlungen_transaction_id'] = $orderInfo['payment']['last_trans_id'] == '' ? null : $orderInfo['payment']['last_trans_id'];
					#$a_orderPayment['zahlungen_transaction_id'] = 'jagofr'.$i_auftragId.$a_newOrder['auftrag_ref'];
					 
					 if ($orderInfo['payment']['method'] == "banktransfer"){
						$a_orderPayment['zahlungen_herkunft']       = 1;
                    }
					
					if ($orderInfo['payment']['method'] == "paymentnetwork_pnsofortueberweisung"){
						$a_orderPayment['zahlungen_herkunft']       = 2;
                    }
					
					if ($orderInfo['payment']['method'] == "paypal_express"){
						$a_orderPayment['zahlungen_herkunft']       = 5;
                    }
					
					if ($orderInfo['payment']['method'] == "paymentoperator_cc"){
						$a_orderPayment['zahlungen_herkunft']       = 7;
                    }
					
					if ($orderInfo['payment']['method'] == "checkmo"){
						$a_orderPayment['zahlungen_herkunft']       = 0;
                    }
					/*
                    if($orderInfo['payment']['method']=="banktransfer")
                    {
                    //    $aImportPaymentToDB['zahlungen_bestaetigt'] = 0;    
						$a_orderPayment['zahlungen_bestaetigt'] = 0;
                    }
                    if($orderInfo['payment']['method']!="banktransfer")
					{ 
                      //  $aImportPaymentToDB['zahlungen_bestaetigt'] = -1;
						$a_orderPayment['zahlungen_bestaetigt'] = -1;
                    }
                    */
                    $a_orderPayment['zahlungen_datum']          = $order['created_at'];
                    $a_orderPayment['zahlungen_status']         = 1;
					$a_orderPayment['zahlungen_bestaetigt']     = -1;
                    
                    if($this->debug){
					
					echo 'test';
                        var_dump($a_orderPayment);
					echo 'test';
                    }
                    $orderModel->addOrderPayment($a_orderPayment);
                }
                
                if($this->debug){
                    echo $i_auftragId;               
                    echo '<hr><br>';
                }
            }
		}
        }
        
    }
    /*
    * Update order status on Magento and Wawi table called Magento
    *
    */
    function updateOrderStatus() {
        
        $orderModel = $this->loadModel('ordermodel');

        $a_Orders = $orderModel->getOrderStatuses();
        foreach ($a_Orders as $order) {
            if($order['wawiShippingStatus']!=0) {
                $result = $this->oAPI->call('sales_order.addComment', array($order['auftrag_foreign_id'], $this->orderStatus_arr[$order['wawiShippingStatus']],'Order Status has changed to :'.$this->orderStatus_arr[$order['wawiShippingStatus']], false));
                if($result==true) {
                    $orderModel->updateOrderStatus($order['auftrag_id'], $order['wawiShippingStatus']);
                }
            }
        }
    }
    /**
     * use it for testing
     */
    function debug() {
        
        $orders  = $this->oAPI->getOrder(array('increment_id' => 'FR24-200000999'));
        echo "<pre>";
        print_r($orders);
        echo "</pre>";
    }
    
    function generateRandomStrings($length = 2) {
        $characters = 'abcdefghijklmnpqrtuvwxyz';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
        
    function clean_up_db()
    {
        global $link;
        if( $link != false )
            mysql_close($link);
        $link = false;
    }
}

?>

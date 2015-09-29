<?php


class Customer extends Controller {
    
    function __construct() {
        parent::__construct();
    }

    function get_customer($i_customerId) {
        $a_customer = $this->oAPI->getCustomer($i_customerId);
        if (!$a_customer) {
            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
        } else {
            return $a_customer;
        }
    }
    
    function addNewCustomer($i_customerId) {
        
        $a_customerResult = $this->get_customer($i_customerId);
        $a_newCustomer = array();
        $a_newCustomer['kunden_email'] = $a_customerResult['email'];
        if ($a_customerResult['gender'] == 1) {
            $iGender = 3;
        } else if ($a_customerResult['gender'] == 2) {
            $iGender = 2;
            } else {
                $iGender = 0;
                }
        $a_newCustomer['kunden_geschlecht'] = $iGender;
        $a_newCustomer['kunden_name'] = $a_customerResult['lastname'];
        $a_newCustomer['kunden_Vorname'] = $a_customerResult['firstname'];
        $a_newCustomer['kunden_pwd'] = $a_customerResult['password_hash'];
        $a_newCustomer['kunden_ref'] = 'deal8';
        
        $this->oMySQL->insert('tbl_kunden',$a_newCustomer); 
        $i_newCustomerId = $this->oMySQL->lastInsertID();
        if($i_newCustomerId!=0 && $i_newCustomerId>0) {
            return $i_newCustomerId;
        }
        return false;
    }
    
}

?>

<?php

class OrderModel
{
    protected $oMySQL;
    
    function __construct($db) {
        try {
            $this->oMySQL = $db;
        } catch (Exception $e) {
            exit('Database connection could not be established.');
        }
    }
    function addNewOrder($a_order)
    {
        #var_dump($a_order);
        if (is_array($a_order)) {
            $this->oMySQL->insert('tbl_auftrag',$a_order); 
            $i_auftragId = $this->oMySQL->lastInsertID();
            if($i_auftragId!=0 && $i_auftragId>0) {
                return $i_auftragId;
            }    
        }
        return false;
    }
    function addPositionToOrder($a_orderPosition)
    {
        if (is_array($a_orderPosition)) {
            $this->oMySQL->insert('tbl_verbindung_auftrag_artikel',$a_orderPosition); 
            $i_orderPositionId = $this->oMySQL->lastInsertID();
            if($i_orderPositionId!=0 && $i_orderPositionId>0) {
                return $i_orderPositionId;
            }    
        }
        return false;
    }
    function addOrderPayment($a_orderPaymet)
    {
	#var_dump ($a_orderPayment);
        if (is_array($a_orderPaymet)) {
            $this->oMySQL->insert('tbl_zahlungen',$a_orderPaymet); 
            $i_orderPaymetId = $this->oMySQL->lastInsertID();
            if($i_orderPaymetId!=0 && $i_orderPaymetId>0) {
                return $i_orderPaymetId;
            }    
        }
        return false;
    }
    function getOrderStatuses()
    {
        $s_sql = "SELECT auftrag_foreign_id, auftrag_id, order_status as magentoShippingStatus, IF(auftrag_storniert = -1, 3, IF(amountOfShippedParcels = 0, 0, IF(amountOfParcels > amountOfShippedParcels, 1, 2))) wawiShippingStatus, auftrag_shop FROM (
                  SELECT *, COUNT(*) amountOfParcels, SUM(IF(cc.Versand_Paket_status = 2, 1, 0)) amountOfShippedParcels FROM tbl_auftrag aa
                        INNER JOIN tbl_verbindung_auftrag_artikel bb ON aa.auftrag_id = bb.ver_auf_a_auftrag
                        LEFT JOIN lagerverwaltung.tbl_Versand_Paket cc ON bb.ver_auf_a_id = cc.Versand_Paket_position_id
                        LEFT JOIN tbl_magento mm on aa.auftrag_id = mm.wawi_orderid 
                        WHERE aa.auftrag_shop = 8
                          and (mm.order_status = 0  or mm.id is null) and auftrag_foreign_id is not null
                        GROUP By auftrag_foreign_id
                        ORDER BY auftrag_datum
                ) qq";

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }
    function updateOrderStatus($i_auftragId, $i_orderStatus){
        $s_sql = "update tbl_magento set order_status = $i_orderStatus where wawi_orderid =".$i_auftragId;

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;
    }
}

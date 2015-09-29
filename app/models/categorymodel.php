<?php


/**
 * CategoryModel
 * 
 * @package MagentoAPI
 * @author JAGO Warenhandels GmbH & Co. KG
 * @copyright 2014
 * @version $Id$
 * @access public
 */
class CategoryModel
{
   
    /**
     * CategoryModel::__construct()
     * 
     * @param mixed $db
     * @return void
     */
    function __construct($db) {
        try {
            $this->oMySQL = $db;
        } catch (Exception $e) {
            exit('Database connection could not be established.');
        }
    }
    
    /**
     * CategoryModel::getCategories()
     * 
     * @param intiger $i_languageId
     * @param bool $b_mainCategory
     * @return array | false, returns result into array or if the result is zero it returns false.
     */
    function getCategories($i_languageId, $i_shop, $b_mainCategory)
    {
	
		$sql = "SELECT aa.id as ID, aa.kategorie as Kat_id, dd.magentoCatId as Magento_id, cc.magentoCatId as Parent_id, cc.wawiCatId as ParentOfParent, aa.lft as Left_id, aa.rgt as Right_id,
                bb.kat_beschriftung_beschriftung as Name, bb.kat_beschriftung_titletag as Title_Tag, bb.kat_beschriftung_keys as Keywords, 
                bb.kat_beschriftung_beschreibungoben as Short_Description, bb.kat_beschriftung_description as Meta_Description,
                bb.kat_beschriftung_beschreibung as Long_Description, bb.kat_beschriftung_url as Url
                FROM tbl_katneu aa 
                LEFT JOIN tbl_kategorie_beschriftung bb on bb.kat_beschriftung_katneuid = aa.kategorie
                LEFT JOIN test.tbl_magentoCategory cc on aa.parent = cc.wawiCatId and cc.shop=$i_shop
                LEFT JOIN test.tbl_magentoCategory dd on aa.kategorie = dd.wawiCatId and dd.shop=$i_shop
                WHERE aa.kategorie != 1069 and ".($b_mainCategory==true?'aa.parent is null and aa.id != 304':'aa.parent is not null and dd.magentoCatId is not null')." and bb.kat_beschriftung_sprache=$i_languageId
                GROUP BY bb.kat_beschriftung_beschriftung
                ORDER BY bb.kat_beschriftung_pfad ASC"; 			
				
        $a_result = $this->oMySQL->executeSQL($sql);
        
        if (count($a_result)) {
            return $a_result;
        }
        return false;
    }
    
    /**
     * CategoryModel::getCategories()
     * 
     * @param intiger $i_languageId
     * @param bool $b_mainCategory
     * @return array | false, returns result into array or if the result is zero it returns false.
     */
    function getNewCategories($i_languageId, $i_shop, $b_mainCategory)
    {
    
        $sql = "SELECT aa.id as ID, aa.kategorie as Kat_id, dd.magentoCatId as Magento_id, cc.magentoCatId as Parent_id, cc.wawiCatId as ParentOfParent, aa.lft as Left_id, aa.rgt as Right_id,
                bb.kat_beschriftung_beschriftung as Name, bb.kat_beschriftung_titletag as Title_Tag, bb.kat_beschriftung_keys as Keywords, 
                bb.kat_beschriftung_beschreibungoben as Short_Description, bb.kat_beschriftung_description as Meta_Description,
                bb.kat_beschriftung_beschreibung as Long_Description, bb.kat_beschriftung_url as Url
                FROM tbl_katneu aa 
                LEFT JOIN tbl_kategorie_beschriftung bb on bb.kat_beschriftung_katneuid = aa.kategorie
                LEFT JOIN test.tbl_magentoCategory cc on aa.parent = cc.wawiCatId and cc.shop=$i_shop
                LEFT JOIN test.tbl_magentoCategory dd on aa.kategorie = dd.wawiCatId and dd.shop=$i_shop
                WHERE aa.kategorie != 1069 and ".($b_mainCategory==true?'aa.parent is null and aa.id != 304':'aa.parent is not null and cc.magentoCatId is not null and dd.magentoCatId is null')." and bb.kat_beschriftung_sprache=$i_languageId
                GROUP BY bb.kat_beschriftung_beschriftung
                ORDER BY bb.kat_beschriftung_pfad ASC";             
                
        $a_result = $this->oMySQL->executeSQL($sql);
        
        if (count($a_result)) {
            return $a_result;
        }
        return false;
    }
    
    /**
     * CategoryModel::getParentOfParentId()
     * We need this parent id for each sub category, because we have three levels categories.
     * 
     * @param intiger $i_ParentOfParent
     * @return array | false, returns result into array or if the result is zero it returns false.
     */
    function getParentOfParentId($i_ParentOfParent, $i_shop)
    {
        $i_parentOfParent = $this->oMySQL->executeSQL("select dd.magentoCatId from tbl_katneu  aa
                                                    left join test.tbl_magentoCategory bb on bb.wawiCatId = aa.kategorie and bb.shop = $i_shop
                                                    left join test.tbl_magentoCategory dd on dd.wawiCatId = aa.parent and dd.shop = $i_shop
                                                    where aa.kategorie in( ".$i_ParentOfParent." ) limit 1");
        if ($i_parentOfParent) {
            return $i_parentOfParent;
        }
        return false;
    }
    function getBrands($i_languageId)
    {
        $a_brands = $this->oMySQL->executeSQL("select * from tbl_markenneu aa 
                                               where aa.marke_sprache = $i_languageId and aa.marke_name != 'ALLE&reg;'
                                               and aa.marke_name != 'Unsere Marken'");
        if ($a_brands) {
            return $a_brands;
        }
        return false;
    }
    function debug()
    {
        $sql = "SELECT aa.id as ID, aa.kategorie as Kat_id, dd.magentoCatId as Magento_id, cc.magentoCatId as Parent_id, cc.wawiCatId as ParentOfParent, aa.lft as Left_id, aa.rgt as Right_id,
                bb.kat_beschriftung_beschriftung as Name, bb.kat_beschriftung_titletag as Title_Tag, bb.kat_beschriftung_keys as Keywords, bb.kat_beschriftung_description as Short_Description,
                bb.kat_beschriftung_beschreibung as Long_Description, bb.kat_beschriftung_url as Url
                FROM tbl_katneu aa 
                LEFT JOIN tbl_kategorie_beschriftung bb on bb.kat_beschriftung_katneuid = aa.kategorie
                LEFT JOIN test.tbl_magentoCategory cc on aa.parent = cc.wawiCatId
                LEFT JOIN test.tbl_magentoCategory dd on aa.kategorie = dd.wawiCatId 
                WHERE bb.kat_beschriftung_sprache=4 and dd.magentoCatId is null
                ORDER BY bb.kat_beschriftung_pfad ASC
                ";
    
        $a_result = $this->oMySQL->executeSQL($sql);
        
        if (count($a_result)) {
            return $a_result;
        }
        return false;
    }
}

<?php

/**
 * ArticleModel
 * 
 * @package MagentoAPI
 * @author JAGO Warenhandels GmbH & Co. KG
 * @copyright 2014
 * @version $Id$
 * @access public
 */
class ArticleModel
{
    /**
     * ArticleModel::__construct()
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
     * ArticleModel::getArticles()
     * 
     * @param intiger $i_languageId, language id of the articles
     * @return array | false, returns result into array or if the result is zero it returns false.
     */
    function getArticles($i_languageId, $i_countryId, $s_countryCode, $i_productId)
    {
        $s_sql = "SELECT SQL_NO_CACHE
                qry3.var_articles,
                qry3.skus,
                qry3.prices,
                qry3.variations,
                qry3.Auslauf_status,
                shop_artikel_id AS Shop_Id,
                A_ID AS Article_Id,
                A_Artikelnummer AS Article_Number,
                trim(artben_bezeichnungLW) AS Titel,
                a_beschreibung_beschreibung AS Short_Desc,                                    
                shop_artikel_bezeichnung_intern AS Internal_SKU,
                artben_top1, artben_beschreibung, artben_top2, artben_techdaten, artben_top3, artben_top4, artben_top5, artben_setbeaus,
                artben_lieferumfang,artben_textilien,artben_sicherheitshinweis,ArtikelGSsiegel_institut, ArtikelGSsiegel_zertifikat,
                ztexte_text,ztexte_imagegr,ArtikelGSsiegel_gueltigBis,
                A_Bestand, A_Gewicht, A_Auslaufvermerk AS Auslauf,
                round(A_VK,4) AS First_Price,
                round(spn_preis,4) AS Low_Price,
                ver.vkShop_versandkosten as Shipping_Costs,
                A_herstellkosten AS Production_Costs,
                bilder_url AS Image_Url,
                bilder_variation_url,
                kategorie AS Category_Number,
                tbl_kategorie_beschriftung.kat_beschriftung_beschriftung AS Category_Title,
                CASE ver_sartikel_artikel_bestand WHEN 1 THEN '1-3 Tage' WHEN 2 THEN 'wenig auf Lager' END AS Delivery_Time,
                marke_descriptions AS Brand_Desc, marke_id as Brand_ID,
                marke_name AS Brand_Name, 
                MarkeLogoOhneBgSrc AS Brand_Logo_Url,
                'EUR' AS Currency,
                IF(aa.A_Bestand > 100, 100, IF(aa.A_Bestand <= 10, 0, aa.A_Bestand -10)) AS stock_quantity,
                A_reichweite,
                A_EAN AS EAN,
                ShopArtikelSuchwoerter_Suchwort AS Search_Keywords,
                A_Gewicht AS weight,
                IF(aa.A_Bestand <= 10, 0, 1) AS Active_Status,
                shop_artikel_Versandklasse,
                attribute_beschriftung_beschriftung AS Variation
                
                FROM shop.tbl_shop_artikel
                LEFT JOIN tbl_markenneu ON marke_orgid = shop_artikel_marke and marke_sprache = $i_languageId
                INNER JOIN tbl_artikel_beschreibung ON a_beschreibung_artikel = shop_artikel_id
                INNER JOIN tbl_artikelbeschreibung_neu as Artikel_sprache ON artben_artikelid = shop_artikel_id and Artikel_sprache.artben_sprache = $i_languageId
                INNER JOIN tbl_verbindung_shop_artikel_artikel ON shop_artikel_id = ver_sartikel_artikel_said
                INNER JOIN wawi.tbl_Artikel aa ON A_ID = ver_sartikel_artikel_aid
                LEFT JOIN tbl_verbindung_artikel_katneu ON ver_a_k_artikel = shop_artikel_id
                LEFT JOIN tbl_katneu ON kategorie = ver_a_k_kategorie
                LEFT JOIN test.tbl_magentoCategory ON wawiCatId = ver_a_k_kategorie
                LEFT JOIN tbl_kategorie_beschriftung on kat_beschriftung_katneuid = ver_a_k_kategorie and tbl_kategorie_beschriftung.kat_beschriftung_sprache = $i_languageId
                LEFT JOIN tbl_attribute_beschriftung ON attribute_beschriftung_attribut = ver_sartikel_artikel_attribut AND attribute_beschriftung_sprache = $i_languageId
                LEFT JOIN (Select bilder_id, GROUP_CONCAT(bilder_src order by bilder_sort ASC SEPARATOR '||') as bilder_url , bilder_src, bilder_s_artikel
                                from shop.tbl_bilder where bilder_art=1 GROUP by bilder_s_artikel) AS qry ON bilder_s_artikel = shop_artikel_id
                LEFT JOIN (Select bilder_id, GROUP_CONCAT(bilder_src order by bilder_sort ASC SEPARATOR '||') as bilder_variation_url , bilder_variation
                                from shop.tbl_bilder where bilder_art=1 GROUP by bilder_variation) AS qry5 ON bilder_variation = A_ID
                LEFT JOIN (SELECT * FROM tbl_bilder where bilder_art=1 order by bilder_sort ASC) AS qry2 ON qry2.bilder_variation = A_ID
                LEFT JOIN tbl_shop_artikel_suchwoerter ON ShopArtikelSuchwoerter_Artikel = shop_artikel_id and ShopArtikelSuchwoerter_Sprache= $i_languageId 
                LEFT JOIN wawi.tbl_ArtikelGSsiegel on ArtikelGSsiegel_artikelid  =  A_ID
                LEFT JOIN tbl_zertifikatstexte ON wawi.tbl_ArtikelGSsiegel.ArtikelGSsiegel_zertifikat = shop.tbl_zertifikatstexte.ztexte_zertifikat and ztexte_sprache= $i_languageId
                LEFT JOIN tbl_shoppreise ss on ss.spn_artikelid = A_ID and ss.spn_TLD='$s_countryCode'
                LEFT JOIN test.tbl_versandkostenShops ver ON aa.A_ID = ver.vkShop_aid and ver.vkShop_land = $i_countryId
                LEFT join (SELECT shop_artikel_id as ArtikleNummber, GROUP_CONCAT(A_Artikelnummer order by if(spn_preis is null, A_VK, spn_preis) asc SEPARATOR '||') as skus, 
                            GROUP_CONCAT(attribute_beschriftung_beschriftung order by if(spn_preis is null, A_VK, spn_preis) asc SEPARATOR '||') as variations, 
                            GROUP_CONCAT(round(if(spn_preis is null, A_VK, spn_preis),4) order by if(spn_preis is null, A_VK, spn_preis) asc SEPARATOR '||') AS prices, count(aa.shop_artikel_id) as var_articles,
                            GROUP_CONCAT(IF(A_Bestand <= 10, if(A_Auslaufvermerk = -1, -1, 0), if(A_Auslaufvermerk = -1, 0, 0)) order by if(spn_preis is null, A_VK, spn_preis) asc SEPARATOR '||') Auslauf_status
                            FROM shop.tbl_shop_artikel aa
                            INNER JOIN tbl_verbindung_shop_artikel_artikel bb ON shop_artikel_id = ver_sartikel_artikel_said
                            INNER JOIN wawi.tbl_Artikel cc ON A_ID = ver_sartikel_artikel_aid
                            LEFT JOIN tbl_attribute_beschriftung ON attribute_beschriftung_attribut = ver_sartikel_artikel_attribut AND attribute_beschriftung_sprache = $i_languageId
                            LEFT JOIN tbl_shoppreise  on spn_artikelid = A_ID and spn_TLD='$s_countryCode' and spn_shopid = shop_artikel_id
                            where A_Dummy = 0 and A_Aktuell=0 and A_VK > 2 and A_reichweite >=0 and A_Artikelnummer not like '%-ext' AND ".($i_languageId==4?'A_Bestand >= 10':'(A_Bestand > 0 or A_Auslaufvermerk =0)')."
                            GROUP BY aa.shop_artikel_id order by spn_preis asc) AS qry3 on qry3.ArtikleNummber = shop_artikel_id
                
                WHERE a_beschreibung_sprache = $i_languageId and ver_a_k_kategorie !=''
                AND A_Dummy = 0 and A_Aktuell=0 and A_VK > 2 and A_reichweite >=0 and artben_bezeichnungLW!='' and A_Artikelnummer not like '%BBQ___DE%' and A_Artikelnummer not like '%-ext' AND ".($i_languageId==4?'A_Bestand >= 10':'(A_Bestand > 0 or A_Auslaufvermerk =0)')."
                ".($i_productId==null?"":" and shop_artikel_id = $i_productId ")."
                GROUP BY Article_Id Order by shop_artikel_id, A_VK";
                #echo "sql: $s_sql"; die;
        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;
    }

    function getCategoryRoot($i_categoryId, $i_languageId)
    {
        $s_sql = "select ff.kat_beschriftung_beschriftung as root, gg.kat_beschriftung_beschriftung as sub, kk.kat_beschriftung_beschriftung as subsub  from tbl_katneu  aa
                    left join tbl_katneu bb on aa.parent = bb.kategorie
                    left join tbl_katneu cc on bb.parent = cc.kategorie
                    left join tbl_kategorie_beschriftung kk on aa.kategorie = kk.kat_beschriftung_katneuid and kk.kat_beschriftung_sprache=$i_languageId
                    left join tbl_kategorie_beschriftung gg on aa.parent = gg.kat_beschriftung_katneuid and gg.kat_beschriftung_sprache=$i_languageId
                    left join tbl_kategorie_beschriftung ff on bb.parent = ff.kat_beschriftung_katneuid and ff.kat_beschriftung_sprache=$i_languageId
                    where aa.kategorie in( $i_categoryId ) limit 1";

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }
    function getImages($s_skus, $i_isParent, $i_languageId)
    {
        $s_sql = "SELECT SQL_NO_CACHE
                    qry3.var_articles,
                    qry3.skus,
                    qry3.prices,
                    qry3.variations,
                    shop_artikel_id AS Shop_Id,
                    A_ID AS Article_Id,
                    A_Artikelnummer AS Article_Number,
                    trim(artben_bezeichnungLW) AS Titel,
                    bilder_url AS Image_Url,
                    bilder_variation_url
                    
                    FROM shop.tbl_shop_artikel
                    LEFT JOIN tbl_markenneu ON marke_id = shop_artikel_marke and marke_sprache = $i_languageId
                    INNER JOIN tbl_artikel_beschreibung ON a_beschreibung_artikel = shop_artikel_id
                    INNER JOIN tbl_artikelbeschreibung_neu as Artikel_sprache ON artben_artikelid = shop_artikel_id and Artikel_sprache.artben_sprache = $i_languageId
                    INNER JOIN tbl_verbindung_shop_artikel_artikel ON shop_artikel_id = ver_sartikel_artikel_said
                    INNER JOIN wawi.tbl_Artikel aa ON A_ID = ver_sartikel_artikel_aid
                    	 LEFT JOIN (Select bilder_id, GROUP_CONCAT(bilder_src order by bilder_sort ASC SEPARATOR '||') as bilder_url , bilder_src, bilder_s_artikel
                                    from shop.tbl_bilder where bilder_art=1 GROUP by bilder_s_artikel) AS qry ON bilder_s_artikel = shop_artikel_id
                    LEFT JOIN (Select bilder_id, GROUP_CONCAT(bilder_src order by bilder_sort ASC SEPARATOR '||') as bilder_variation_url , bilder_variation
                                    from shop.tbl_bilder where bilder_art=1 GROUP by bilder_variation) AS qry5 ON bilder_variation = A_ID
                    LEFT JOIN (SELECT * FROM tbl_bilder where bilder_art=1 order by bilder_sort ASC) AS qry2 ON qry2.bilder_variation = A_ID
                    LEFT join (SELECT shop_artikel_id as ArtikleNummber, GROUP_CONCAT(A_Artikelnummer SEPARATOR '||') as skus, 
                                GROUP_CONCAT(attribute_beschriftung_beschriftung SEPARATOR '||') as variations, 
                                GROUP_CONCAT(round(A_VK,2) SEPARATOR '||') AS prices, count(aa.shop_artikel_id) as var_articles
                                FROM shop.tbl_shop_artikel aa
                                INNER JOIN tbl_verbindung_shop_artikel_artikel bb ON shop_artikel_id = ver_sartikel_artikel_said
                                INNER JOIN wawi.tbl_Artikel cc ON A_ID = ver_sartikel_artikel_aid
                                LEFT JOIN tbl_attribute_beschriftung ON attribute_beschriftung_attribut = ver_sartikel_artikel_attribut AND attribute_beschriftung_sprache = $i_languageId
                                where A_Dummy = 0 and A_Aktuell=0 and A_VK > 2 and A_reichweite >=0 and A_Artikelnummer not like '%-ext' AND A_Bestand >= 10 
                                GROUP BY aa.shop_artikel_id order by shop_artikel_id, A_VK) AS qry3 on qry3.ArtikleNummber = shop_artikel_id
                    
                    WHERE a_beschreibung_sprache = $i_languageId 
                    AND A_Dummy = 0 and A_Aktuell=0 and A_VK > 2 and A_reichweite >=0
                    AND ".($i_isParent==false?"A_Artikelnummer='$s_skus'":"shop_artikel_id=$s_skus")."
                    GROUP BY Article_Id Order by shop_artikel_id, A_VK";
                    #echo "sql".$s_sql;die;
        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }/*    
    function getImages($s_sku, $i_isParent, $i_languageId)
    {
        $s_sql = "Select A_Artikelnummer, GROUP_CONCAT(bilder_src order by bilder_sort ASC SEPARATOR '||') as bilder_urls , trim(artben_bezeichnungLW) AS Titel
                  from shop.tbl_bilder aa
                  left join shop.tbl_shop_artikel bb on aa.bilder_s_artikel = bb.shop_artikel_id
                  INNER JOIN tbl_verbindung_shop_artikel_artikel ON shop_artikel_id = ver_sartikel_artikel_said
                  INNER JOIN tbl_artikelbeschreibung_neu cc ON artben_artikelid = shop_artikel_id and artben_sprache = $i_languageId
                  INNER JOIN wawi.tbl_Artikel  ON A_ID = ver_sartikel_artikel_aid
                  where bilder_art=1 and ".($i_isParent==false?"A_Artikelnummer='$s_sku'":"shop_artikel_id=$s_sku")." GROUP by bilder_s_artikel";

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }*/
    function getCertificates()
    {
        $s_sql = "Select A_Artikelnummer, concat('https://623047-db3.jago-ag.de/media/gs_logos/', zz.ztexte_imagegr) crt_url, ss.ArtikelGSsiegel_zertifikat crt_id
                  from shop.tbl_bilder aa
                  left join shop.tbl_shop_artikel bb on aa.bilder_s_artikel = bb.shop_artikel_id
                  INNER JOIN tbl_verbindung_shop_artikel_artikel ON shop_artikel_id = ver_sartikel_artikel_said
                  INNER JOIN wawi.tbl_Artikel  ON A_ID = ver_sartikel_artikel_aid
                  LEFT JOIN wawi.tbl_ArtikelGSsiegel ss on ArtikelGSsiegel_artikelid  =  A_ID
                  LEFT JOIN tbl_zertifikatstexte zz ON ss.ArtikelGSsiegel_zertifikat = zz.ztexte_zertifikat and ztexte_sprache= 99
                  where zz.ztexte_imagegr is not null or zz.ztexte_imagegr!=''
                  GROUP by bilder_s_artikel";

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }
    
    function getStockAndPrice()
    {
        $s_sql = "SELECT SQL_NO_CACHE
                        shop_artikel_id as shopid,
                        A_ID as Artikelnummer,
                        A_Artikelnummer as SKU,
                        round(A_VK*1.19,2) AS normal_preis,
                        IF(aa.A_Bestand > 100, 100, IF(aa.A_Bestand <= 10, 0, aa.A_Bestand -10)) as stock_quantity

                    FROM shop.tbl_shop_artikel

                        INNER JOIN tbl_artikel_beschreibung ON a_beschreibung_artikel = shop_artikel_id
                        INNER JOIN tbl_artikelbeschreibung_neu as Artikel_sprache ON artben_artikelid = shop_artikel_id and Artikel_sprache.artben_sprache =4
                        INNER JOIN tbl_verbindung_shop_artikel_artikel ON shop_artikel_id = ver_sartikel_artikel_said
                        INNER JOIN wawi.tbl_Artikel aa ON A_ID = ver_sartikel_artikel_aid
                        LEFT JOIN tbl_verbindung_artikel_katneu ON ver_a_k_artikel = shop_artikel_id
                        LEFT JOIN tbl_attribute_beschriftung ON attribute_beschriftung_attribut = ver_sartikel_artikel_attribut AND attribute_beschriftung_sprache = 4

                    WHERE a_beschreibung_sprache = 4 and ver_a_k_kategorie !=''
                        AND A_Dummy = 0 and A_Aktuell=0 and A_VK > 2 and A_reichweite >=1 and A_Artikelnummer not like '%ext%'
                        GROUP BY Artikelnummer Order by shop_artikel_id, Artikelnummer, normal_preis limit 10, 10";

        $a_result = $this->oMySQL->executeSQL($s_sql);

        if (count($a_result)) {
            return $a_result;
        }
        return false;

    }

}

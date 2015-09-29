<?php
error_reporting(E_ALL^E_NOTICE);
header('Content-Type: text/html; charset=utf-8');
/**
 * Article
 *
 * @package MagentoAPI
 * @author Remzi Zaimi
 * @copyright 2014
 * @version $Id$
 * @access public
 */
class Article extends Controller
{
    private $a_shops = array(4=>array('store'=>'french', 
                                      'website'=>'base', 
                                      'ext'=>'-fr',
                                      'root_category'=>'Default Category',
                                      'country_iso_code'=> 'FR',
                                      'tech_title'=>'Caractéristiques techniques',
                                      'content_title'=>'Contenu de la livraison',
                                      'mwst'=>1.20,
                                      'countryCode'=>'FR'),
                            99=>array('store'=>'uk_english', 
                                      'website'=>'uk_website', 
                                      'ext'=>'-uk',
                                      'root_category'=>'UK Root Category',
                                      'country_iso_code'=> 'GB',
                                      'tech_title'=>'Technical Information',
                                      'content_title'=>'Delivery Contents',
                                      'mwst'=>1.20,
                                      'countryCode'=>'UK'),
                    ); 
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Desc: We use this function to import Articles into Magento. Thing to remember, please import first
     * categories and get the Category IDs. In the query, I have already joined the table test.tbl_magentoKategorie.
     * The function seo url is generating friendly urls.
     *
     * To do:
     *      *Variation
     *      *if we need addition information to import into Magento use the url: http://www.magentocommerce.com/api/soap/introduction.html
     *
     *      *clean the cache
     *      cd /var/www/html
     *      rm -rf var/cache/* 
     *
     *      *use this function to reindex all
     *      cd /var/www/html
     *      php shell/indexer.php --reindexall 
     *
     *  Article::exportArticles()
     *
     * @param integer $i_languageId , 99 stands for English Language, 4 for french
     * @param integer $i_productId  optional, shop product id.
     * @return void
     */
    function exportArticles($i_languageId = null, $i_productId=null)
    {
        if(!isset($i_languageId)&&!is_numeric($i_languageId)||!is_numeric($i_productId)) {die('please set and use integer for the language id or product id.');}

        $articleModel = $this->loadModel('ArticleModel');

        $a_countryInfo = $this->oMySQL->executeSQL("select aa.Laender_ID c_id, Laender_Kuerzel iso, aa.Laender_waehrung currency, 
                                                     aa.Laender_waehrung_umrechnung exchangerate_eur_multi
                                                     from wawi.tbl_Laender aa
                                                     where aa.Laender_Kuerzel = '".($this->a_shops[$i_languageId]['country_iso_code'])."' and Laender_shop_sichtbar=-1 limit 1");
        $i_countryId = $a_countryInfo[0]['c_id'];
        $a_Articles = $articleModel->getArticles($i_languageId, $i_countryId, $this->a_shops[$i_languageId]['country_iso_code'], $i_productId);
        $time_start = $this->microtime_float();

        $products = array();
        $a_parentProduct = array();
        $a_prices = array();
        $a_variations = array();
        $a_skus = array();
        
        $i_countProducts=1;
        foreach ($a_Articles as $article) {

            if($i_languageId != 4) { if($article['stock_quantity'] == 0 && $article['Auslauf']==-1) { continue; } }

            $a_categoryRoot = $articleModel->getCategoryRoot($article['Category_Number'], $i_languageId);
            $a_images = explode("||", $article['Image_Url']);
    //      var_dump ($article['Image_Url']);
            $a_MainImage = array();
            $s_MainImage = "noimage.jpg";
            if(!empty($article['bilder_variation_url']) && $article['bilder_variation_url'] !='') {
                $a_images = explode("||", $article['bilder_variation_url']);
                $a_MainImage = explode('/',$a_images[0]);
                if(count($a_MainImage)==8) {
                    $s_MainImage = $a_MainImage[count($a_MainImage)-3].'/'.$a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                }else{
                    $s_MainImage = $a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                }
            }else if(!empty($a_images)){
                $a_MainImage = explode('/',$a_images[0]);
                if(count($a_MainImage)==8) {
                    $s_MainImage = $a_MainImage[count($a_MainImage)-3].'/'.$a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                }else{
                    $s_MainImage = $a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                }
            }

            $s_description          = $article['artben_beschreibung'] . '<br>'.
                                            '<h2>'.$this->a_shops[$i_languageId]['tech_title'].'</h2>'    . '<br>' . $article['artben_techdaten'] .
                                            '<h2>'.$this->a_shops[$i_languageId]['content_title'].'</h2>'        . '<br>' . $article['artben_lieferumfang'] ;
            $s_bullets_listpage     = '<ul>'.(isset($article['artben_top1'])?'<li>'.$article['artben_top1'].'</li>':'').
                                            (isset($article['artben_top2'])?'<li>'.$article['artben_top2'].'</li>':'').
                                            (isset($article['artben_top3'])?'<li>'.$article['artben_top3'].'</li>':'')
                                      .'</ul>';
            $s_bullets_detailspage  = '<ul>'.(isset($article['artben_top1'])?'<li>'.$article['artben_top1'].'</li>':'').
                                            (isset($article['artben_top2'])?'<li>'.$article['artben_top2'].'</li>':'').
                                            (isset($article['artben_top3'])?'<li>'.$article['artben_top3'].'</li>':'').
                                            (isset($article['artben_top4'])?'<li>'.$article['artben_top4'].'</li>':'').
                                            (isset($article['artben_top5'])?'<li>'.$article['artben_top5'].'</li>':'')
                                      .'</ul>';

            $products = array();
            $sku = $article['Article_Number'].$this->a_shops[$i_languageId]['ext'];
            if($i_languageId==99)
            {

                $price = $article['Low_Price']!=null ? ($article['Low_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi'] 
                : ($article['First_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi'];
                $price = $price / $this->a_shops[$i_languageId]['mwst'];
            } else {
                $price = $article['Low_Price']!=null ? ($article['Low_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $this->a_shops[$i_languageId]['mwst'] : 
                $article['First_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9);
            }
            #$price = (($i_languageId==99 ? $article['First_Price'] / $a_countryInfo[0]['exchangerate_eur_multi'] :  ($article['Low_Price']!=null ? $article['Low_Price']/$this->a_shops[$i_languageId]['mwst'] : $article['First_Price'])) + ($article['Low_Price']!=null ?'':(isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9)));
            $products[] = array(
                'sku' => $sku,
                'store' => $this->a_shops[$i_languageId]['store'],
                '_attribute_set' => 'Configurable Product',
                '_type' => 'simple',
                '_product_websites' => $this->a_shops[$i_languageId]['website'],
                'description' => $s_description,
                'short_description' => ($article['Short_Desc']!=null ? $article['Short_Desc'] : '&nbsp;'),
                '_category' => ($a_categoryRoot[0]['root']==null?'':trim($a_categoryRoot[0]['root'])."/").
                               ($a_categoryRoot[0]['sub']==null?'':trim($a_categoryRoot[0]['sub'])."/").
                               ($a_categoryRoot[0]['subsub']==null?'':trim($a_categoryRoot[0]['subsub'])),
                '_root_category' => $this->a_shops[$i_languageId]['root_category'],
                'status' => ($article['Auslauf']==-1 && $article['stock_quantity']==0 || intval($article['Shipping_Costs'])>500 ? 2 : 1),
                'visibility' => $article['var_articles']==1?4:1,// if the var_articles==1 you set the visibility to 4. 4 means the product is visible and 1 is invisible
                'tax_class_id' => 2,
                'is_in_stock' => ($article['stock_quantity']==0?0:1),
                'name' => $article['Titel'],
                //for UK shop we need to calculate the price
                'price' => $price,
                'weight' => ($article['weight'] === null ? 0.1 : $article['weight']),
                'qty' => $article['stock_quantity'],
                'variation' => $article['Variation'],
                'small_image' => $s_MainImage,
                'thumbnail' => $s_MainImage,
                'image' => $s_MainImage,
                '_media_attribute_id' => 88,
                '_media_image' => $s_MainImage,
                '_media_is_disabled' => 0,
                '_media_lable' => $article['Titel'].' - 1',
                '_media_position' => 1,
                'bullets_listpage' => $s_bullets_listpage,
                'bullets_detailspage' => $s_bullets_detailspage,
                'manufacturer' => $article['Brand_Name'],
                'manufacturer_description' => $article['Brand_Desc'],
                'certificate_img' => (isset($article['ztexte_imagegr'])?'logos/'.$article['ztexte_imagegr']:''),
                'certificate_number' => $article['ArtikelGSsiegel_zertifikat'],
                'certificate_valid_date' => $article['ArtikelGSsiegel_gueltigBis'],
                'certificate_text' => $article['ztexte_text'],
                'raw_material_text' => $article['artben_textilien'],
                'safety_info_text' => (isset($article['artben_sicherheitshinweis'])?$article['artben_sicherheitshinweis']:''),
                'ean' => $article['EAN'],

            );
            /*if(isset($article['Brand_Name'])) {
                $products[] = array(
                '_category' => $article['Brand_Name'],
                '_root_category' => $this->a_shops[$i_languageId]['root_category']
                );
            }*/
            if(empty($article['bilder_variation_url']) && $article['bilder_variation_url'] == '') {
                for ($i=0; $i < count($a_images); $i++) {
                    if($i==0) {continue;} 
                    $a_MainImage = explode('/',$a_images[$i]);
                    if(count($a_MainImage)==8) {
                        $s_MainImage = $a_MainImage[count($a_MainImage)-3].'/'.$a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                    }else{
                        $s_MainImage = $a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                    }
                    //if you need multi pictures, add the data and not set the sku!!!
                    $products[] = array(
                        '_media_attribute_id' => 88,
                        '_media_image' => $s_MainImage,
                        '_media_is_disabled' => 0,
                        '_media_lable' => $article['Titel'].' - '.($i+1),
                        '_media_position' => ($i+1),
                    );
                }
            }else{
                $a_images = explode("||", $article['bilder_variation_url']);
                for ($i=0; $i < count($a_images); $i++) {
                    if($i==0) {continue;} 
                    $a_MainImage = explode('/',$a_images[$i]);
                    if(count($a_MainImage)==8) {
                        $s_MainImage = $a_MainImage[count($a_MainImage)-3].'/'.$a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                    }else{
                        $s_MainImage = $a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                    }
                    //if you need multi pictures, add the data and not set the sku!!!
                    $products[] = array(
                        '_media_attribute_id' => 88,
                        '_media_image' => $s_MainImage,
                        '_media_is_disabled' => 0,
                        '_media_lable' => $article['Titel'].' - '.($i+1),
                        '_media_position' => ($i+1),
                    );
                }
            }
            #echo "<pre>";
            #print_r($products);
            #echo "</pre>";
            $data = $this->oAPI->improtArticles($products);
            if (!$data) {
                echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            }

            $a_skus = explode("||", $article['skus']);
            $a_Auslauf_status = explode("||", $article['Auslauf_status']);
            
            if(count($a_skus) > 1 && $i_countProducts==count($a_skus)) {
                $s_parentTempSku = $article['Shop_Id'].$this->a_shops[$i_languageId]['ext'];
                for ($j=0; $j < count($a_skus); $j++) {
                    if(intval($a_Auslauf_status[$j])==-1){continue;}
                    if(($j+1)==count($a_skus)) {
                        $a_prices = explode("||", $article['prices']);
                        $a_variations = explode("||", $article['variations']);
                        
                        $products[] = $products[$e];
                        $products[$e+1]['sku'] = $s_parentTempSku;
                        $products[$e+1]['_type'] = 'configurable';
                        $products[$e+1]['_attribute_set'] = 'Configurable Product';
                        $products[$e+1]['visibility'] = 4;
                        $products[$e+1]['is_in_stock'] = 1;
                        $products[$e+1]['qty'] = 100;
                        if($i_languageId==99)
                        {
                            $price=0;
                            $price = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi']; 
                            $products[$e+1]['price'] = $price / $this->a_shops[$i_languageId]['mwst'];
                        } else {
                            $products[$e+1]['price'] = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $this->a_shops[$i_languageId]['mwst'] ; 
                        }
                        
                        for ($i=0; $i < count($a_skus); $i++) { 
                            $a_parentProduct[] = array(
                            '_super_products_sku' => $a_skus[$i].$this->a_shops[$i_languageId]['ext'],
                            '_super_attribute_code' => 'variation',
                            '_super_attribute_option' => $a_variations[$i],
                            '_super_attribute_price_corr' => $a_prices[$i]!=0 ? (($i_languageId==99 ?  (($a_prices[$i])/$this->a_shops[$i_languageId]['mwst']) / $a_countryInfo[0]['exchangerate_eur_multi'] :   $a_prices[$i]/$this->a_shops[$i_languageId]['mwst'])) -  ($i_languageId==99 ?  ($a_prices[0]/$this->a_shops[$i_languageId]['mwst']) / $a_countryInfo[0]['exchangerate_eur_multi'] :   $a_prices[0]/$this->a_shops[$i_languageId]['mwst']) : 0
                            );
                        }
                        $a_images = explode("||", $article['Image_Url']);
                        for ($i=0; $i < count($a_images); $i++) { 
                            $a_MainImage = explode('/',$a_images[$i]);
                            if(count($a_MainImage)==8) {
                                $s_MainImage = $a_MainImage[count($a_MainImage)-3].'/'.$a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                            }else{
                                $s_MainImage = $a_MainImage[count($a_MainImage)-2].'/'.end($a_MainImage);
                            }
                            if($i==0) {
                                $products[$e+1]['small_image'] =  $s_MainImage;
                                $products[$e+1]['thumbnail'] =  $s_MainImage;
                                $products[$e+1]['image'] = $s_MainImage;
                                $products[$e+1]['_media_attribute_id'] = 88;
                                $products[$e+1]['_media_image'] = $s_MainImage;
                                $products[$e+1]['_media_is_disabled'] = 0;
                                $products[$e+1]['_media_lable'] = $article['Titel'].' - 1';
                                $products[$e+1]['_media_position'] = 1;
                            } else {
                                $a_parentProduct[] = array(
                                    '_media_attribute_id' => 88,
                                    '_media_image' => $s_MainImage,
                                    '_media_is_disabled' => 0,
                                    '_media_lable' => $article['Titel'].' - '.($i+1),
                                    '_media_position' => ($i+1),
                                );
                            }    
                        }
                        #print_r($a_parentProduct);
                        $data = $this->oAPI->improtArticles($a_parentProduct);
                        if (!$data) {
                            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
                        }
                        $a_parentProduct = array();
                        $i_countProducts=1;
                    }
                }
            }elseif(count($a_skus) > 1){
                $i_countProducts++;
            }
        }
       
        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        echo "Response time: $time ";

    }

    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     *
     */
    public function updateProduct($i_languageId = null, $i_productId=null)
    {
        if(!isset($i_languageId)) {die('please set the language id.');}

        $articleModel = $this->loadModel('ArticleModel');

        $a_countryInfo = $this->oMySQL->executeSQL("select aa.Laender_ID c_id, Laender_Kuerzel iso, aa.Laender_waehrung currency, 
                                                     aa.Laender_waehrung_umrechnung exchangerate_eur_multi
                                                     from wawi.tbl_Laender aa
                                                     where aa.Laender_Kuerzel = '".($this->a_shops[$i_languageId]['country_iso_code'])."' limit 1");
        $i_countryId = $a_countryInfo[0]['c_id'];
        $a_Articles = $articleModel->getArticles($i_languageId, $i_countryId, $this->a_shops[$i_languageId]['country_iso_code'], $i_productId);
        $time_start = $this->microtime_float();

        $products = array();
        $a_parentProduct = array();
        $a_prices = array();
        $a_variations = array();
        $a_skus = array();
        
        $i_countProducts=1; $e=0;
        foreach ($a_Articles as $article) {

            #if($i_languageId != 4) { if($article['stock_quantity'] == 0 && $article['Auslauf']==-1) { continue; } }

            $a_categoryRoot = $articleModel->getCategoryRoot($article['Category_Number'], $i_languageId);
            

            $s_description          = $article['artben_beschreibung'] . '<br>'.
                                            '<h2>'.$this->a_shops[$i_languageId]['tech_title'].'</h2>'    . '<br>' . $article['artben_techdaten'] .
                                            '<h2>'.$this->a_shops[$i_languageId]['content_title'].'</h2>'        . '<br>' . $article['artben_lieferumfang'] ;
            $s_bullets_listpage     = '<ul>'.(isset($article['artben_top1'])?'<li>'.$article['artben_top1'].'</li>':'').
                                            (isset($article['artben_top2'])?'<li>'.$article['artben_top2'].'</li>':'').
                                            (isset($article['artben_top3'])?'<li>'.$article['artben_top3'].'</li>':'')
                                      .'</ul>';
            $s_bullets_detailspage  = '<ul>'.(isset($article['artben_top1'])?'<li>'.$article['artben_top1'].'</li>':'').
                                            (isset($article['artben_top2'])?'<li>'.$article['artben_top2'].'</li>':'').
                                            (isset($article['artben_top3'])?'<li>'.$article['artben_top3'].'</li>':'').
                                            (isset($article['artben_top4'])?'<li>'.$article['artben_top4'].'</li>':'').
                                            (isset($article['artben_top5'])?'<li>'.$article['artben_top5'].'</li>':'')
                                      .'</ul>';

            #$products = array();
            $sku = $article['Article_Number'].$this->a_shops[$i_languageId]['ext'];
            if($i_languageId==99)
            {
                $price = $article['Low_Price']!=null ? ($article['Low_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi'] 
                : ($article['First_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi'];
                $price = (number_format(round(($price),0)-0.05,2))/$this->a_shops[$i_languageId]['mwst'];
            } else {
                $price = $article['Low_Price']!=null ? ($article['Low_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $this->a_shops[$i_languageId]['mwst'] : 
                $article['First_Price'] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9);
            }
            #$price = (($i_languageId==99 ? $article['First_Price'] / $a_countryInfo[0]['exchangerate_eur_multi'] :  ($article['Low_Price']!=null ? $article['Low_Price']/$this->a_shops[$i_languageId]['mwst'] : $article['First_Price'])) + ($article['Low_Price']!=null ?'':(isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9)));
            $products[] = array(
                'sku' => $sku,
                'store' => $this->a_shops[$i_languageId]['store'],
                '_attribute_set' => 'Configurable Product',
                '_type' => 'simple',
                '_product_websites' => $this->a_shops[$i_languageId]['website'],
                'description' => (isset($s_description)? htmlspecialchars_decode($s_description) : '&nbsp;'),
                'short_description' => (isset($article['Short_Desc'])? htmlspecialchars_decode($article['Short_Desc']) : '&nbsp;'),
                '_category' => ($a_categoryRoot[0]['root']==null?'':trim($a_categoryRoot[0]['root'])."/").
                               ($a_categoryRoot[0]['sub']==null?'':trim($a_categoryRoot[0]['sub'])."/").
                               ($a_categoryRoot[0]['subsub']==null?'':trim($a_categoryRoot[0]['subsub'])),
                '_root_category' => $this->a_shops[$i_languageId]['root_category'],
                'status' => ($article['Auslauf']==-1 && $article['stock_quantity']==0 ? 2 : ($article['Shipping_Costs']>500 ? 2 : 1)),
                'visibility' => $article['var_articles']==1?4:1,// if the var_articles==1 you set the visibility to 4. 4 means the product is visible and 1 is invisible
                'tax_class_id' => 2,
                'is_in_stock' => ($article['stock_quantity']==0?0:1),
                'name' => (isset($article['Titel'])? $article['Titel'] : '&nbsp;'),
                //for UK shop we need to calculate the price
                'price' => $price,
                'weight' => ($article['weight'] === null ? 0.1 : $article['weight']),
                'qty' => $article['stock_quantity'],
                'variation' => $article['Variation'],
                'small_image' => $s_MainImage,
                'thumbnail' => $s_MainImage,
                'image' => $s_MainImage,
                '_media_attribute_id' => 88,
                '_media_image' => $s_MainImage,
                '_media_is_disabled' => 0,
                '_media_lable' => $article['Titel'].' - 1',
                '_media_position' => 1,
                'bullets_listpage' => $s_bullets_listpage,
                'bullets_detailspage' => $s_bullets_detailspage,
                'manufacturer' => $article['Brand_Name'],
                'manufacturer_description' => $article['Brand_Desc'],
                'certificate_img' => (isset($article['ztexte_imagegr'])?'logos/'.$article['ztexte_imagegr']:''),
                'certificate_number' => $article['ArtikelGSsiegel_zertifikat'],
                'certificate_valid_date' => $article['ArtikelGSsiegel_gueltigBis'],
                'certificate_text' => $article['ztexte_text'],
                'raw_material_text' => $article['artben_textilien'],
                'safety_info_text' => (isset($article['artben_sicherheitshinweis'])?(strlen($article['artben_sicherheitshinweis'])>250?'':$article['artben_sicherheitshinweis']):''),
                'ean' => $article['EAN'],

            );
            /*if(isset($article['Brand_Name'])) {
                $products[] = array(
                '_category' => $article['Brand_Name'],
                '_root_category' => $this->a_shops[$i_languageId]['root_category']
                );
            }*/
           
            #$data = $this->oAPI->improtArticles($products);
            #if (!$data) {
            #    echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            #}
            #echo "<pre>";
            #print_r($products['sku']);
            #echo "</pre>";

            $a_skus = explode("||", $article['skus']);
            $a_Auslauf_status = explode("||", $article['Auslauf_status']);
            
            if(count($a_skus) > 1 && $i_countProducts==count($a_skus)) {
                $s_parentTempSku = $article['Shop_Id'].$this->a_shops[$i_languageId]['ext'];
                for ($j=1; $j <= count($a_skus); $j++) {
                    
                    if($j==count($a_skus)) {
                        $a_prices = explode("||", $article['prices']);
                        $a_variations = explode("||", $article['variations']);
                        
                        $products[] = $products[$e];
                        $products[$e+1]['sku'] = $s_parentTempSku;
                        $products[$e+1]['_type'] = 'configurable';
                        $products[$e+1]['_attribute_set'] = 'Configurable Product';
                        $products[$e+1]['visibility'] = 4;
                        $products[$e+1]['is_in_stock'] = 1;
                        $products[$e+1]['qty'] = 100;
                        // set the default value to disabled, incase that one of the child's auslauf status is zero(0), set it enable 
                        $products[$e+1]['status'] = 2;
                        foreach ($a_Auslauf_status as $key => $value) {

                            if($value==0){$products[$e+1]['status'] = 1;}
                        }
                        
                        if($i_languageId==99)
                        {
                            $price=0;
                            $price = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi']; 
                            $products[$e+1]['price'] = (number_format(round(($price),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'];
                        } else {
                            $products[$e+1]['price'] = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $this->a_shops[$i_languageId]['mwst'] ; 
                        }
                        
                        for ($i=0; $i < count($a_skus); $i++) {
                            if(intval($a_Auslauf_status[$i])==-1){continue;}
                            $price=0;
                            if($i_languageId==99)
                            {
                                $price_x = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi']; 
                                $price_x = (number_format(round(($price_x),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'];
                                $price_y = ($a_prices[$i] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ) / $a_countryInfo[0]['exchangerate_eur_multi']; 
                                $price_y = (number_format(round(($price_y),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'];
                                $price = $price_y - $price_x;
                            } else {
                                $price_x = ($a_prices[0] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ); 
                                $price_x = (number_format(round(($price_x),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'];
                                $price_y = ($a_prices[$i] + (isset($article['Shipping_Costs'])?$article['Shipping_Costs']:9) ); 
                                $price_y = (number_format(round(($price_y),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'];
                                $price = $price_y - $price_x; 
                            }
                            #$price = $a_prices[$i]!=0 ? (($i_languageId==99 ? ((number_format(round(($a_prices[$i]) / $a_countryInfo[0]['exchangerate_eur_multi']),0)-0.05,2)) / $this->a_shops[$i_languageId]['mwst'] : (number_format(round(($a_prices[$i]),0)-0.05,2))/$this->a_shops[$i_languageId]['mwst'])) -  ($i_languageId==99 ?  (number_format(round(($a_prices[0]/$this->a_shops[$i_languageId]['mwst']),0)-0.05,2)) / $a_countryInfo[0]['exchangerate_eur_multi'] :  (number_format(round(($a_prices[0]),0)-0.05,2)) /$this->a_shops[$i_languageId]['mwst']) : 0; 
                            $products[$e+1+$i+1] = array(
                            '_super_products_sku' => $a_skus[$i].$this->a_shops[$i_languageId]['ext'],
                            '_super_attribute_code' => 'variation',
                            '_super_attribute_option' => $a_variations[$i],
                            '_super_attribute_price_corr' => $price
                            );
                        }
                        
                        #array_merge($products, $a_parentProduct);
                        #$data = $this->oAPI->improtArticles($a_parentProduct);
                        #echo "<pre>";
                        #print_r($a_parentProduct);
                        #echo "</pre>";
                        #if (!$data) {
                        #    echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
                        #}
                        #$a_parentProduct = array();
                        $i_countProducts=1;
                    }
                }
            }elseif(count($a_skus) > 1){
                $i_countProducts++;
            }elseif(count($a_skus) == 1){
                $i_countProducts=1;
            }
            $e++;
        }
        #echo "<pre>";
        #print_r($products);
        #echo "</pre>";#die;
        $data = $this->oAPI->improtArticles($products);
        if (!$data) {
            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
        }
        
        $time_end = $this->microtime_float();
        $time = $time_end - $time_start;

        echo "Response time: $time ";
    }
    function updateImagesNew($i_languageId,$i_isParent=false) 
    {
        if(!isset($i_languageId)) {die('please set the language id.');}

        $articleModel = $this->loadModel('ArticleModel');
        $a_skus = array();
        foreach($a_skus as $sku) {
            $a_Articles = $articleModel->getImages(substr($sku,0,-3), $i_isParent, $i_languageId);
           
            foreach ($a_Articles as $article) {
                #echo "<pre>";
            #print_r($article);
            #echo "<pre>";
                $a_images = explode("||", $article['Image_Url']);
                if(empty($article['bilder_variation_url']) && $article['bilder_variation_url'] == '') {
                    
                    for ($i=0; $i < count($a_images); $i++) {
                       $newImage = array(
                            'file' => array(
                                'name' => basename($a_images[$i]),
                                'content' => base64_encode(file_get_contents($a_images[$i])),
                                'mime'    => 'image/jpeg'
                            ),
                            'label'    => $article['Titel']." - ".($i+1),
                            'position' => $i+1,
                            'types'    => ($i==0 ? array('small_image','thumbnail','image','_media_image') : null),
                            'exclude'  => 0
                        );
                        $products = array(
                            'sku' => $sku,
                            'data' => $newImage
                            );
    
                        $data = $this->oAPI->createArticleMedia( $products );
                        if (!$data) {
                            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
                        }
                    }
                }else{
                    $a_images = explode("||", $article['bilder_variation_url']);
                    for ($i=0; $i < count($a_images); $i++) {
                        $newImage = array(
                            'file' => array(
                                'name' => basename($a_images[$i]),
                                'content' => base64_encode(file_get_contents($a_images[$i])),
                                'mime'    => 'image/jpeg'
                            ),
                            'label'    => $article['Titel']." - ".($i+1),
                            'position' => $i+1,
                            'types'    => ($i==0 ? array('small_image','thumbnail','image','_media_image') : null),
                            'exclude'  => 0
                        );
                        $products = array(
                            'sku' => $sku,
                            'data' => $newImage
                            );
    
                        $data = $this->oAPI->createArticleMedia( $products );
                        if (!$data) {
                            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
                        }
                    }
                }
            }            
        }
        
    }
    function updateImages($i_languageId,$i_isParent=false) 
    {
        $articleModel = $this->loadModel('ArticleModel');
        $arrayName = array('SUGS01-fr','KÜAR015-fr','LSGF01-fr','BHTN03sch-fr','GLST01-fr','MATA05-fr','FRT02-fr');
        
        foreach ($arrayName as $sku) {
            $a_Articles = $articleModel->getImages($sku, $i_isParent,$i_languageId);
            if(empty($a_Articles)) {continue;}
            $a_imageUlrs = explode('||', $a_Articles[0]['bilder_urls']);
            for ($i=0; $i < count($a_imageUlrs); $i++) {
                $newImage = array(
                    'file' => array(
                        'name' => basename($a_imageUlrs[$i]),
                        'content' => base64_encode(file_get_contents($a_imageUlrs[$i])),
                        'mime'    => 'image/jpeg'
                    ),
                    'label'    => $a_Articles[0]['Titel']." - ".($i+1),
                    'position' => $i+1,
                    'types'    => ($i==0 ? array('small_image','thumbnail','image','_media_image') : null),
                    'exclude'  => 0
                );
                $products = array(
                    'sku' => $sku,
                    'data' => $newImage
                    );

                $data = $this->oAPI->createArticleMedia( $products );
                if (!$data) {
                    echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
                }
            }
        }
    }
    function updateCertificates($i_isParent=false) 
    {
        $articleModel = $this->loadModel('ArticleModel');
        $a_Articles = $articleModel->getCertificates();
        foreach ($a_Articles as $crt) {
            $newImage = array(
                'file' => array(
                    'name' => basename($crt['crt_url']),
                    'content' => base64_encode(file_get_contents($crt['crt_url'])),
                    'mime'    => 'image/jpeg'
                ),
                'label'    => $crt['crt_id']." - ".($i+1),
                'position' => 0,
                'types'    => array('certificate_img'),
                'exclude'  => 0
            );
            $products = array(
                'sku' => $crt['A_Artikelnummer'].'-fr',
                'data' => $newImage
                );
            $data = $this->oAPI->createArticleMedia( $products );
            if (!$data) {
                echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            }
            echo "sku: ".$crt['A_Artikelnummer'];
            
        }
    }
    
    /**
     * not working: Requested image not exists in product images' gallery
     */
    public function updatePictures()
    {
        #$s_MainImage = 'http://s519814402.online.de/img/produkte/auto/2105/steckschluesselsatz_stsl01_detail01_gr.jpg';
        $s_MainImage = '/auto/kbd05_2_1_part.jpg';
        #$s_MainImage = '/media/import/auto/2105/steckschluesselsatz_stsl01_detail01_gr.jpg';
        #$articleModel = $this->loadModel('ArticleModel');

        #$a_Articles = $articleModel->getArticles(4);
        $time_start = microtime(true);
        #foreach ($a_Articles as $article) {
            $products = array(
                #'sku' => $article['SKU'],
                'sku' => "STSL01",
                'data' => array(
                    'productData' => array(
                        'small_image' => $s_MainImage,
                        'thumbnail' => $s_MainImage,
                        'image' => $s_MainImage,
                        '_media_attribute_id' => 88,
                        '_media_image' => $s_MainImage,
                        '_media_is_disabled' => 0,
                        '_media_lable' => 'testimage - 1',
                        '_media_position' => 1
                    )
                )
            );

            $data = $this->oAPI->updateArticleMedia( $products );
            if (!$data) {
                echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            }
            print_r($products);
        #}
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "<br> Process Time: {$time}";
    }
    
    /**
     * Internal Server Error
     */
    public function createArticleImage($i_languageId=4){
        $s_MainImage = 'http://s519814402.online.de/img/produkte/auto/sitzgruppe_stzg33_farbwahl_shop_gr.jpg';
        #$s_MainImage = '/auto/kbd05_2_1_part.jpg';
        #$s_MainImage = '/media/import/auto/2105/steckschluesselsatz_stsl01_detail01_gr.jpg';
        #$s_MainImage = '/var/www/html/media/import/auto/2105/steckschluesselsatz_stsl01_detail01_gr.jpg';
        #$articleModel = $this->loadModel('ArticleModel');
        
        $articleModel = $this->loadModel('ArticleModel');

        $a_Articles = $articleModel->getArticles($i_languageId);
        print_r($a_Articles);
        foreach ($a_Articles as $Article){
            $aPictures = explode(",", $Article['Image_Url']);
            echo '<br>test';
            echo $Article["Article_Number"].' - '.$aPictures[0];
            echo '<br>';
        
        $newImage = array(
            'file' => array(
                'name' => 'file_name',
                'content' => base64_encode(file_get_contents($aPictures[0])),
                'mime'    => 'image/jpeg'
            ),
            'label'    => 'Cool Image Through Soap',
            'position' => 2,
            'types'    => array('small_image','thumbnail','image','_media_image'),
            'exclude'  => 0
        );
        
        #$a_Articles = $articleModel->getArticles(4);
        $time_start = microtime(true);
        #foreach ($a_Articles as $article) {
            $products = array(
                #'sku' => $article['SKU'],
                'sku' => $Article["Article_Number"],
                'data' => $newImage
            );

            $data = $this->oAPI->createArticleMedia( $products );
            if (!$data) {
                echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            }
        }
        //        print_r($products);
    
        #}
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "<br> Process Time: {$time}";
    }
    
    
     public function deleteArticleImage(){
       
        
        #$a_Articles = $articleModel->getArticles(4);
        $time_start = microtime(true);
        #foreach ($a_Articles as $article) {
            $products = array(
                #'sku' => $article['SKU'],
                'sku' => "STZG33",
                'data' => "/f/i/file_name_1.jpg"
            );

            $data = $this->oAPI->deleteArticleMedia( $products );
            if (!$data) {
                echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
            }
            print_r($products);
        #}
        $time_end = microtime(true);
        $time = $time_end - $time_start;
        echo "<br> Process Time: {$time}";
    }

    /**
     * not working
     */
    public function getArticle($article_id)
    {
        $data = $this->oAPI->getArticle(9984, null, array('product_id', 'name', 'description'));
        #$data = $this->oAPI->getArticle(9984, array('product_id', 'name', 'description'), "sku");
        echo "data:";
        print_r($data);
    }
    /**
     * 
     */
    public function getAllArticles2($articleid)
    {
        $data = $this->oAPI->getAllArticles(array("product"=>"sku"));
        echo "data:";
        print_r($data);
    }
    
    public function importConfigurable()
    {
        $products = array();
        $products[] = array(
            'description' => 'description',
            '_attribute_set' => 'Configurable Product',
            'short_description' => 'short_description',
            '_product_websites' => 'base',
            'status' => 1,
            '_category' => 'Garden',
            '_root_category' => 'Default Category',
            'visibility' => 4,
            'tax_class_id' => 0,
            'is_in_stock' => 1,
            'sku' => 'csku-1',
            '_type' => 'simple',
            'name' => 'name',
            'price' => 10,
            'weight' => rand(1, 1000),
            'qty' => rand(1, 30),
            '_media_attribute_id' => 88,
            '_media_image' => 'VOL12/VOL12.jpg',
            'small_image' => 'VOL12/VOL12.jpg',
            'thumbnail' => 'VOL12/VOL12.jpg',
            'image' => 'VOL12/VOL12.jpg',
            '_media_is_disabled' => 0,
            '_media_lable' => 'first',
            '_media_position' => 1,
            'variation' => 'Blue',
            'bullets_listpage' => 'test list',
            'bullets_detailspage' => 'Test details',
            'manufacturer' => 'Jago',
            'manufacturer_description' => 'Jag description',
            'certificate_img' => 'jago.jpg',
            'certificate_number' => '1234567asdfgh',
            'certificate_valid_date' => '01.01.2015',
            'certificate_text' => 'certificate description',
            'raw_material_text' => 'raw material',
            'safety_info_text' => '100% safty',
        );
        $products[] = array(
            'description' => 'description',
            '_attribute_set' => 'Configurable Product',
            'short_description' => 'short_description',
            '_product_websites' => 'base',
            'status' => 1,
            '_category' => 'Garden',
            '_root_category' => 'Default Category',
            'visibility' => 1,
            'tax_class_id' => 0,
            'is_in_stock' => 1,
            'sku' => 'csku-2',
            '_type' => 'simple',
            'name' => 'name',
            'price' => 20,
            'weight' => rand(1, 1000),
            'qty' => rand(1, 30),
            '_media_attribute_id' => 88,
            '_media_image' => 'VOL12/VOL12.jpg',
            'small_image' => 'VOL12/VOL12.jpg',
            'thumbnail' => 'VOL12/VOL12.jpg',
            'image' => 'VOL12/VOL12.jpg',
            '_media_is_disabled' => 0,
            '_media_lable' => 'first',
            '_media_position' => 1,
            'variation' => 'green',
            'bullets_listpage' => 'test list',
            'bullets_detailspage' => 'Test details',
            'manufacturer' => 'Jago',
            'manufacturer_description' => 'Jag description',
            'certificate_img' => 'jago.jpg',
            'certificate_number' => '1234567asdfgh',
            'certificate_valid_date' => '01.01.2015',
            'certificate_text' => 'certificate description',
            'raw_material_text' => 'raw material',
            'safety_info_text' => '100% safty',
        );
        $products[] = array(
            'description' => 'description',
            '_attribute_set' => 'Configurable Product',
            'short_description' => 'short_description',
            '_product_websites' => 'base',
            'status' => 1,
            '_category' => 'Garden',
            '_root_category' => 'Default Category',
            'visibility' => 4,
            'tax_class_id' => 0,
            'is_in_stock' => 1,
            'sku' => 'csku',
            '_type' => 'configurable',
            'name' => 'name',
            'price' => 10,
            'weight' => rand(1, 1000),
            'qty' => rand(1, 30),
            '_media_attribute_id' => 88,
            '_media_image' => 'VOL12/VOL12.jpg',
            'small_image' => 'VOL12/VOL12.jpg',
            'thumbnail' => 'VOL12/VOL12.jpg',
            'image' => 'VOL12/VOL12.jpg',
            '_media_is_disabled' => 0,
            '_media_lable' => 'first',
            '_media_position' => 1,
            'variation' => 'green',
            'bullets_listpage' => 'test list',
            'bullets_detailspage' => 'Test details',
            'manufacturer' => 'Jago',
            'manufacturer_description' => 'Jag description',
            'certificate_img' => 'jago.jpg',
            'certificate_number' => '1234567asdfgh',
            'certificate_valid_date' => '01.01.2015',
            'certificate_text' => 'certificate description',
            'raw_material_text' => 'raw material',
            'safety_info_text' => '100% safty',
        );
        //if you need multi pictures, add the data and not set the sku!!!
        $products[] = array(
            '_media_attribute_id' => 88,
            '_media_image' => 'kastz01dr_03_gr.jpg',
            '_media_is_disabled' => 0,
            '_media_lable' => 'second',
            '_media_position' => 2,
            '_super_products_sku' => 'csku-1',
            '_super_attribute_code' => 'variation',
            '_super_attribute_option' => 'Blue',
            '_super_attribute_price_corr' => '0',
        );
        $products[] = array(
            '_media_attribute_id' => 88,
            '_media_image' => 'kastz01dr_03_gr.jpg',
            '_media_is_disabled' => 0,
            '_media_lable' => 'second',
            '_media_position' => 2,
            '_super_products_sku' => 'csku-2',
            '_super_attribute_code' => 'variation',
            '_super_attribute_option' => 'Green',
            '_super_attribute_price_corr' => '10',
        );

        $data = $this->oAPI->improtArticles($products);
        if (!$data) {
            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
        }
    }
    function importVariation()
    {

        $products = array(
            array(
                'type' => 'configurable',
                'set' => 4,
                'sku' => 'configurable_product_sku',
                'productData' => array(
                    'categories' => array(2),
                    'websites' => array(1),
                    'name' => 'Configurable Product name',
                    'description' => 'Configurable Product description',
                    'short_description' => 'Configurable Product short description',
                    'weight' => '10',
                    'status' => '1',
                    'url_key' => 'configurable-product-url-key',
                    'url_path' => 'configurable-product-url-path',
                    'visibility' => '4',
                    'price' => '100',
                    'tax_class_id' => 1, #for deal8, use 4
                    'meta_title' => 'Configurable Product meta title',
                    'meta_keyword' => 'Configurable Product meta keyword',
                    'meta_description' => 'Configurable Product meta description',
                    'configurable_attributes_data' => array(    //for configurable product,
                        array('attribute_code' => 'size'),
                        array('attribute_code' => 'color'),
                    ),
                    'configurable_products_data' => array(
                        'BELE03' => array(),
                        'BELE02' => array(),
                    ),
                ),
            ),
            array(
                'type' => 'configurable',
                'set' => 4,
                'sku' => 'configurable_product_sku',
                'productData' => array(
                    'categories' => array(2),
                    'websites' => array(1),
                    'name' => 'Configurable Product name',
                    'description' => 'Configurable Product description',
                    'short_description' => 'Configurable Product short description',
                    'weight' => '10',
                    'status' => '1',
                    'url_key' => 'configurable-product-url-key',
                    'url_path' => 'configurable-product-url-path',
                    'visibility' => '4',
                    'price' => '100',
                    'tax_class_id' => 1, #for deal8, use 4
                    'meta_title' => 'Configurable Product meta title',
                    'meta_keyword' => 'Configurable Product meta keyword',
                    'meta_description' => 'Configurable Product meta description',
                    'configurable_attributes_data' => array(    //for configurable product,
                        array('attribute_code' => 'size'),
                        array('attribute_code' => 'color'),
                    ),
                    'configurable_products_data' => array(
                        'BELE03' => array(),
                        'BELE02' => array(),
                    ),
                ),
            ),
        );

        $data = $this->oAPI->improtArticles($products);
        if (!$data) {
            echo $this->oAPI->errorCode . ':' . $this->oAPI->error;
        }
    }

}

?>

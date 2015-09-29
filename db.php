<?php
/**
 * User: remzi.zaimi
 * Date: 22.10.2014
 * Time: 13:33
 */

$servername = "162.13.206.134";
$username = "jago";
$password = "ne74kvg";
$dbname = "jago";

$servername2 = "192.168.178.115:3306";
$username2 = "magentointerface";
$password2 = "mage01!";
$dbname2 = "shop";

// Create connection
$conn = mysql_connect($servername, $username, $password)
    or die("Unable to connect to MySQL");

//select a database to work with
$selected = mysql_select_db($dbname,$conn)
    or die("Could not select examples");
    
// second connection
$conn2 = mysql_connect($servername2, $username2, $password2, true)
    or die("Unable to connect to MySQL2");

//select a database to work with
$selected2 = mysql_select_db($dbname2,$conn2)
    or die("Could not select examples2");

//execute the SQL query and return records
$result = mysql_query("SELECT * FROM catalog_category_entity_varchar aa where aa.value_id not in(1,2,3,4,5,7) and aa.value is not NULL", $conn);
//fetch tha data from the database
$a_categories = array();
while ($row = mysql_fetch_array($result)) {
    $a_categories[$row['value_id']] = array($row['value']);
    $result2 = mysql_query("SELECT titel, kategorie FROM tbl_katneu where titel='".$row['value']."'", $conn2);
    $row = mysql_fetch_row($result2);
    echo $row[0] . ":" . $row[1];
    echo "<br>";
}

#print_r($a_categories);
mysql_close($conn);
//execute the SQL query and return records
$result = mysql_query("SELECT * FROM tbl_katneu", $conn2);
//fetch tha data from the database
while ($row = mysql_fetch_array($result)) {
    $key = array_search($row['titel'], $a_categories);
    if(is_numeric($key)) {
        echo "true";
        $key=null;
    }
    echo $row['titel'] . ":" . $row['kategorie'];
    echo "<br>";
}

mysql_close($conn2);


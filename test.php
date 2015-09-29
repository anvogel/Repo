<?php
$command = "ls";
$output = shell_exec($command);
echo "<pre>$output</pre>";
die;
?>
<?php


$username = 'jago';
$password = 'jago91';
// Magento login information
$mage_url = "http://623047-db3.jago-ag.de/api/soap?wsdl";
$mage_user = 'wawi';
$mage_api_key = 'c04bbefee2fe4fec592c49ce5327b5d4';
// Initialize the SOAP client
$soap = new SoapClient( $mage_url );

// Login to Magento
$session_id = $soap->login( $mage_user, $mage_api_key );
$result = $soap->call( $session_id, 'sales_order.addComment', array('orderIncrementId' => 'FR24-2200000072', 'status' => 'canceled') );

echo "<pre>";
print_r($result);
echo "</pre>";

?>


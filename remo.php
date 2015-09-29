<?php
$client = new SoapClient('https://www.jago24.fr/api/soap/?wsdl',array('trace' => 1, 'connection_timeout' => 120));

// If somestuff requires api authentification,
// then get a session token
$session = $client->login('jago', 'jago91');

$result = $client->call($session, 'catalog_category.tree');
var_dump($result);

// If you don't need the session anymore
//$client->endSession($session);

?>
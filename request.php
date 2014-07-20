<?php
require_once('lib/CURLRequest.php');

try {
	$req = new CURLRequest(trim($_GET['url']));
	echo $req->get();
} catch(CURLRequestException $e) {
	echo json_encode(array(
		'code' => $e->getCode(),
		'message' => $e->__toString()
	));
}

// EOF
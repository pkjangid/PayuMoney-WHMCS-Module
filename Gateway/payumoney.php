<?php

function payumoney_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"PayUMoney"),
     "MERCHANT_KEY" => array("FriendlyName" => "Merchant Key", "Type" => "text", "Size" => "20", "Description" => "Test Key: JBZaLc, Merchant key here as provided by PayUMoney", ),
     "SALT" => array("FriendlyName" => "Merchant Salt", "Type" => "text", "Size" => "20", "Description" => "Test Salt: GQs7yium, Merchant Salt as provided by PayUMoney", ),
     "PAYU_BASE_URL" => array("FriendlyName" => "PayUMoney Base URL", "Type" => "textarea", "Rows" => "1", "Description" => "https://test.payu.in for TEST mode, https://secure.payu.in for LIVE mode", ),
     "service_provider" => array("FriendlyName" => "Service Provider", "Type" => "text", "Size" => "20", "Description" => "Eg: payu_paisa", ),
	 );

	return $configarray;
}

function payumoney_link($params) {

	# Gateway Specific Variables 
	$MERCHANT_KEY = $params['MERCHANT_KEY'];
	$SALT = $params['SALT'];
	$PAYU_BASE_URL = $params['PAYU_BASE_URL'];
	$service_provider = $params['service_provider'];
	
	# Invoice Variables
	$invoiceid = $params['invoiceid'];   	
	$productinfo = $params['description'];	
	$amount = $params['amount']; # Format: ##.##
	$currency = $params['currency']; # Currency Code

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	$systemurl = $params['systemurl'];
	$currency = $params['currency'];
	
	// Callback URL
	$surl = $systemurl . '/modules/gateways/callback/payumoney.php';
	$furl = $systemurl . '/modules/gateways/callback/payumoney.php';
	
	// Generate transaction ID with invoice ID included
	$txnid = $invoiceid . '-' . substr(hash('sha256', mt_rand() . microtime()), 0, 15);
	
	// Hash Sequence
	$hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
	
	$hashVarsSeq = explode('|', $hashSequence);
	$hash_string = '';	
	foreach($hashVarsSeq as $hash_var) {
		if($hash_var == 'key') {
			$hash_string .= $MERCHANT_KEY;
		} elseif($hash_var == 'txnid') {
			$hash_string .= $txnid;
		} elseif($hash_var == 'amount') {
			$hash_string .= $amount;
		} elseif($hash_var == 'productinfo') {
			$hash_string .= $productinfo;
		} elseif($hash_var == 'firstname') {
			$hash_string .= $firstname;
		} elseif($hash_var == 'email') {
			$hash_string .= $email;
		} else {
			$hash_string .= ''; // udf fields are empty
		}
		$hash_string .= '|';
	}
	
	$hash_string .= $SALT;
	$hash = strtolower(hash('sha512', $hash_string));
	$action = $PAYU_BASE_URL . '/_payment';

	$code = '<form action="'.$action.'" method="post" name="payuForm">
		  <input type="hidden" name="key" value="'.$MERCHANT_KEY.'" />
		  <input type="hidden" name="hash" value="'.$hash.'"/>
		  <input type="hidden" name="txnid" value="'.$txnid.'" />
		  <input type="hidden" name="amount" value="'.$amount.'" />
		  <input type="hidden" name="firstname" value="'.htmlspecialchars($firstname).'" />
		  <input type="hidden" name="email" value="'.$email.'" />
		  <input type="hidden" name="phone" value="'.$phone.'" />
		  <input type="hidden" name="productinfo" value="'.htmlspecialchars($productinfo).'" />
		  <input type="hidden" name="surl" value="'.$surl.'" />
		  <input type="hidden" name="furl" value="'.$furl.'" />
		  <input type="hidden" name="service_provider" value="'.$service_provider.'" />
		  <input type="hidden" name="udf1" value="" />
		  <input type="hidden" name="udf2" value="" />
		  <input type="hidden" name="udf3" value="" />
		  <input type="hidden" name="udf4" value="" />
		  <input type="hidden" name="udf5" value="" />
      	  <input type="submit" value="Pay Now" class="btn btn-primary" />
      </form>';
	  
	return $code;
}

?>
<?php
/**
 * PayUMoney Gateway Callback File
 */

// Require WHMCS initialization
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Get gateway variables
$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module not active
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned from payment gateway
$status = $_POST["status"];
$mihpayid = $_POST["mihpayid"];
$txnid = $_POST["txnid"];
$amount = $_POST["amount"];
$hash = $_POST["hash"];
$key = $_POST["key"];
$productinfo = $_POST["productinfo"];
$firstname = $_POST["firstname"];
$email = $_POST["email"];

// Validate the hash
$SALT = $gatewayParams['SALT'];
$hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";

// Generate return hash for validation
$hashVarsSeq = explode('|', $hashSequence);
$hash_string = $SALT . '|' . $status;

// Reverse order for response hash
$hashVars = array_reverse($hashVarsSeq);
foreach($hashVars as $hash_var) {
    $hash_string .= '|';
    $hash_string .= isset($_POST[$hash_var]) ? $_POST[$hash_var] : '';
}

$calculatedHash = strtolower(hash('sha512', $hash_string));

// Extract invoice ID from txnid (format: invoiceid-randomhash)
$invoiceId = null;
if (preg_match('/^(\d+)-/', $txnid, $matches)) {
    $invoiceId = $matches[1];
}

// If not found in txnid, try to extract from productinfo
if (!$invoiceId && preg_match('/Invoice\s+(\d+)/i', $productinfo, $matches)) {
    $invoiceId = $matches[1];
}

if (!$invoiceId) {
    logTransaction($gatewayParams['name'], $_POST, "Invoice ID Not Found");
    die("Invoice ID could not be determined");
}

// Validate invoice ID
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

// Check transaction ID hasn't been processed before
checkCbTransID($txnid);

/**
 * Validate Hash
 */
if ($hash === $calculatedHash) {
    if ($status == "success") {
        /**
         * Successful Payment
         */
        addInvoicePayment(
            $invoiceId,
            $txnid,
            $amount,
            0, // Payment fee
            $gatewayModuleName
        );
        
        logTransaction($gatewayParams['name'], $_POST, "Successful");
        
        // Redirect to invoice
        callback3DSecureRedirect($invoiceId, true);
        
    } else {
        /**
         * Unsuccessful/Failed Payment
         */
        logTransaction($gatewayParams['name'], $_POST, "Failed: " . $status);
        
        // Redirect to invoice with error
        callback3DSecureRedirect($invoiceId, false);
    }
} else {
    /**
     * Invalid Hash - Possible fraud attempt
     */
    logTransaction($gatewayParams['name'], $_POST, "Hash Validation Failed - Calculated: " . $calculatedHash . " | Received: " . $hash);
    die("Hash Validation Failed");
}
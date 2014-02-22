<?php
/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    ipn.php - Amazon Payment module IPN interface

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer.  If not, see <http://www.gnu.org/licenses/>.
*/

// We must be authenticated to insert into the database
session_start();
$_SESSION['userId'] = 1;
// Save path of directory containing index.php
$crm_root = realpath(dirname(__FILE__) . '/../..');
// Bootstrap the crm
require_once('../../include/crm.inc.php');
// Only handle successful transactions
if (strtolower($_POST['status']) != 'ps') {
    die();
}
// Verify the signature
// First construct http parameter string
$clauses = array();
uksort($_POST, 'strcmp');
foreach ($_POST as $key => $value) {
    $clauses[] = urlencode($key) . '=' . urlencode($value);
}
$http_params = implode('&', $clauses);
// Generate the request parameters
$q = 'Action=VerifySignature'
    . '&UrlEndPoint=' . rawurlencode('http://' . $config_host . base_path() . 'modules/amazon_payment/ipn.php')
    . '&HttpParameters=' . rawurlencode($http_params)
    . '&Version=2008-09-17';
// Send the request
$method = 'GET';
$url = 'https://fps.amazonaws.com/?' . $q;
$options = array(
    'https' => array(
        'method' => $method
    )
);
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
// Check for success
if (strpos($result, '<VerifySignatureResult><VerificationStatus>Success</VerificationStatus></VerifySignatureResult>') === false) {
    die();
}
// Check if the payment already exists
// Skip transactions that have already been imported
$payment_opts = array(
    'filter' => array('confirmation' => $_POST['transactionId'])
);
$data = crm_get_data('payment', $payment_opts);
if (count($data) > 0) {
    die();
}
// Parse the data and insert into the database
// 'USD 12.34' goes to ['USD', '1234']
$parts = explode(' ', $_POST['transactionAmount']);
file_put_contents($debug, print_r($parts, true) . "\n", FILE_APPEND);
$payment_amount = payment_parse_currency($parts[1], $parts[0]);
// Determine cid
$cid = $_POST['referenceId'];
if (empty($cid)) {
    // Check if the amazon name is linked to a contact
    $opts = array('filter'=>array('amazon_name'=>$_POST['buyerName']));
    $contact_data = amazon_payment_contact_data($opts);
    if (count($contact_data) > 0) {
        $cid = $contact_data[0]['cid'];
    }
}
$payment = array(
    'date' => date('Y-m-d', $_POST['transactionDate'])
    , 'credit_cid' => $cid
    , 'code' => $payment_amount['code']
    , 'value' => (string)$payment_amount['value']
    , 'description' => $_POST['paymentReason']
    , 'method' => 'amazon'
    , 'confirmation' => $_POST['transactionId']
    , 'amazon_name' => $_POST['buyerName']
);
$payment = payment_save($payment);
// Log out
$_SESSION['userId'] = 0;
session_destroy();

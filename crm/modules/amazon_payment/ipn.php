<?php
/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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

session_start();
$_SESSION['userId'] = 1;

$output = date('c') . "\n";
$output .= print_r($_POST, true) . "\n";
$debug = '/home/elplatt/seltzer/crm/modules/amazon_payment/log.txt';
file_put_contents($debug, $output, FILE_APPEND);

// Save path of directory containing index.php
$crm_root = realpath(dirname(__FILE__) . '/../..');

// Bootstrap the crm
require_once('../../include/crm.inc.php');

// Only handle successful transactions
if (strtolower($_POST['status']) != 'ps') {
    file_put_contents($debug, "Non-PS Status\n\n", FILE_APPEND);
    die();
}

// 'USD 12.34' goes to ['USD', '1234']
$parts = explode(' ', $_POST['transactionAmount']);
file_put_contents($debug, print_r($parts, true) . "\n", FILE_APPEND);
$payment_amount = payment_parse_currency($parts[1], $parts[0]);
$payment = array(
    'date' => date('Y-m-d', $_POST['transactionDate'])
    , 'credit_cid' => $_POST['referenceId']
    , 'code' => $payment_amount['code']
    , 'value' => (string)$payment_amount['value']
    , 'description' => $_POST['paymentReason']
    , 'method' => 'amazon'
    , 'confirmation' => $_POST['transactionId']
    , 'amazon_name' => $_POST['buyerName']
);
file_put_contents($debug, print_r($payment, true) . "\n\n", FILE_APPEND);
$payment = payment_save($payment);

$_SESSION['userId'] = 0;
session_destroy();
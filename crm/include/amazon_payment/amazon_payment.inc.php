<?php

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    amazon_payment.inc.php - Amazon payments extensions for the payment module.

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

/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function amazon_payment_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function amazon_payment_install($old_revision = 0) {
    // Create initial database table
    if ($old_revision < 1) {
        
        // Additional payment info for amazon payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `payment_amazon` (
              `pmtid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `amazon_name` varchar(255) NOT NULL,
              PRIMARY KEY (`pmtid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Additional contact info for amazon payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `contact_amazon` (
              `cid` mediumint(8) unsigned NOT NULL,
              `amazon_name` varchar(255) NOT NULL,
              PRIMARY KEY (`amazon_name`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more amazon contacts.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns all payments assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 * @return An array with each element representing a single payment.
*/
function amazon_payment_contact_data ($opts = array()) {
    
    $sql = "SELECT `cid`, `amazon_name` FROM `contact_amazon` WHERE 1";
    // TODO add filtering etc
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $names = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $name = array(
            'cid' => $row['cid']
            , 'amazon_name' => $row['amazon_name']
        );
        // add contact field if 'cid' is not empty
        if (!empty($row['cid'])) {
            // Grab array of contacts
            $contactarray = member_contact_data(array('cid'=>$row['cid']));
            // assign the first element (which is a contact array) to the 'contact' field
            $name['contact'] = $contactarray[0];
        }
        $row = mysql_fetch_assoc($res);
        $names[] = $name;
    }
    
    return $names;
}

/**
 * Save an amazon payment contact.  Do nothing if it already exists.
 * @param $name The amazon name object.
 */
function amazon_payment_contact_save ($contact) {
    $esc_name = $contact['amazon_name'];
    $sql = "INSERT INTO `contact_amazon` (`amazon_name`) VALUES ('$esc_name')";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
}

/**
 * Implementation of hook_payment_api()
 * Invoke a payment api hook in all modules.
 * @param $payment An associative array representing a payment.
 * @param $op The operation.
 */
function amazon_payment_payment_api ($payment, $op) {
    if ($payment['method'] !== 'amazon') {
        return;
    }
    $esc_name = $payment['amazon_name'];
    $esc_pmtid = $payment['pmtid'];
    switch ($op) {
        case 'insert':
            $sql = "
                INSERT INTO `payment_amazon`
                (`pmtid`, `amazon_name`)
                VALUES
                ('$esc_pmtid', '$esc_name')
            ";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            amazon_payment_contact_save($payment);
            break;
        case 'update':
            $sql = "
                UPDATE `payment_amazon`
                SET `amazon_name` = '$esc_name'
                WHERE `pmtid` = '$esc_pmtid'
            ";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            amazon_payment_contact_save($payment);
            break;
    }
}

// TODO put amazon_payment_contact_table here
// PS: Molly says "Hi, Matt!"
// "Hi, Molly!" - Matt
// @param $opts - 
// 
function amazon_payment_contact_table($opts){
    
    $data = amazon_payment_contact_data($opts);
    
    
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    
    // Add columns
    if (!user_access('payment_view')) { // Permission check
        error_register('User does not have permission to view payments');
        return;
    }
    $table['columns'][] = array("title"=>'Full Name');
    $table['columns'][] = array("title"=>'Amazon Name');
    
    // Add ops column (Not going to worry about this right now)
   
    // Add rows
    foreach ($data as $union) {
        $row = array();
        
        //first column is the full name associated with the union['cid']
        $memberopts = array(
            'cid' => $union['cid'],
        );
        $contact = $union['contact'];
        $contactName = '';
        if (!empty($contact)) {
            $contactName = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        }
        $row[] = $contactName; 
        //second column is  union['amazon_name']
        $row[] = $union['amazon_name'];
        
        //Save row array into the $table structure
        $table['rows'][] = $row;
    }
    
    return $table; 
}


/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function amazon_payment_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'payments':
            if (user_access('payment_add')) {
                $content = theme('amazon_payment_admin');
                $content .= theme('form', amazon_payment_import_form());
                page_add_content_top($page_data, $content, 'Amazon');
            }
            break;
        case 'amazon-admin':
            page_set_title($page_data, 'Administer Amazon Payments');
            page_add_content_top($page_data, theme('table', 'amazon_payment_contact', array('show_export'=>true)), 'View');
            break;
    }
}

/**
 * @return an amazon payments import form structure.
 */
function amazon_payment_import_form () {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'amazon_payment_import'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Import CSV'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to upload amazon payments data in comma-separated (CSV) format.'
                    )
                    , array(
                        'type' => 'file'
                        , 'label' => 'CSV File'
                        , 'name' => 'payment-file'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Import'
                    )
                )
            )
        )
    );
}

/**
 * Handle amazon payment import request.
 *
 * @return The url to display on completion.
 */
function command_amazon_payment_import () {
    
    if (!user_access('payment_add')) {
        error_register('User does not have permission: payment_add');
        return 'index.php?q=payments';
    }
    
    if (!array_key_exists('payment-file', $_FILES)) {
        error_register('No payment file uploaded');
        return 'index.php?q=payments&tab=import';
    }
    
    $csv = file_get_contents($_FILES['payment-file']['tmp_name']);
    $data = csv_parse($csv);
    
    $count = 0;
    foreach ($data as $row) {
        
        // Ignore withdrawals, holds, and failures
        if ($row['Type'] !== 'Payment') {
            continue;
        }
        if ($row['To/From'] !== 'From') {
            continue;
        }
        if ($row['Status'] !== 'Completed') {
            continue;
        }
        
        // Create payment object and save
        $payment = array(
            'date' => date('Y-m-d', strtotime($row['Date']))
            , 'code' => 'USD'
            , 'amount' => payment_normalize_currency($row['Amount'])
            , 'method' => 'amazon'
            , 'confirmation' => $row['Transaction ID']
            , 'notes' => $row['notes']
            , 'amazon_name' => $row['Name']
        );
        $payment = payment_save($payment);
        $count++;
    }
    
    message_register("Successfully imported $count payment(s)");
    
    return 'index.php?q=payments';
}

/**
 * Return themed html for amazon admin links.
 */
function theme_amazon_payment_admin () {
    return '<p><a href="index.php?q=amazon-admin">Administer</a></p>';
}
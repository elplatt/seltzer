<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    paypal_payment.inc.php - Paypal payments extensions for the payment module.

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
function paypal_payment_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function paypal_payment_install($old_revision = 0) {
    // Create initial database table
    if ($old_revision < 1) {
        
        // Additional payment info for paypal payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `payment_paypal` (
              `pmtid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `paypal_email` varchar(255) NOT NULL,
              PRIMARY KEY (`pmtid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        
        // Additional contact info for paypal payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `contact_paypal` (
              `cid` mediumint(8) unsigned NOT NULL,
              `paypal_email` varchar(255) NOT NULL,
              PRIMARY KEY (`paypal_email`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more paypal payments.
 */
function paypal_payment_data ($opts = array()) {
    $sql = "SELECT `pmtid`, `paypal_email` FROM `payment_paypal` WHERE 1";
    if (isset($opts['pmtid'])) {
        $esc_pmtid = mysql_real_escape_string($opts['pmtid']);
        $sql .= " AND `pmtid`='$esc_pmtid' ";
    }
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    // Read from database and store in a structure
    $paypal_payment_data = array();
    while ($db_row = mysql_fetch_assoc($res)) {
        $paypal_payment_data[] = $db_row;
    }
    return $paypal_payment_data;
};

/**
 * Return data for one or more paypal contacts.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'cid' If specified, returns all payments assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 * @return An array with each element representing a single payment.
*/
function paypal_payment_contact_data ($opts = array()) {
    $sql = "SELECT `cid`, `paypal_email` FROM `contact_paypal` WHERE 1";
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $filter => $value) {
            if ($filter === 'paypal_email') {
                $esc_email = mysql_real_escape_string($value);
                $sql .= " AND `paypal_email`='$esc_email' ";
            }
        }
    }
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $emails = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $email = array(
            'cid' => $row['cid']
            , 'paypal_email' => $row['paypal_email']
        );
        $emails[] = $email;
        $row = mysql_fetch_assoc($res);
    }
    return $emails;
}

/**
 * Save a paypal contact.  If the name is already in the database,
 * the mapping is updated.  When updating the mapping, any fields that are not
 * set are not modified.
 */
function paypal_payment_contact_save ($contact) {
    $esc_name = mysql_real_escape_string($contact['paypal_email']);
    $esc_cid = mysql_real_escape_string($contact['cid']);    
    // Check whether the paypal contact already exists in the database
    $sql = "SELECT * FROM `contact_paypal` WHERE `paypal_email` = '$esc_email'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $row = mysql_fetch_assoc($res);
    if ($row) {
        // Name is already in database, update if the cid is set
        if (isset($contact['cid'])) {
            $sql = "
                UPDATE `contact_paypal`
                SET `cid`='$esc_cid'
                WHERE `paypal_email`='$esc_email'
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
        }
    } else {
        // Name is not in database, insert new
        $sql = "
            INSERT INTO `contact_paypal`
            (`paypal_email`, `cid`) VALUES ('$esc_email', '$esc_cid')";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
}

/**
 * Update paypal_payment data when a payment is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function paypal_payment_payment_api ($payment, $op) {
    if ($payment['method'] !== 'paypal') {
        return;
    }
    $email = $payment['paypal_email'];
    $pmtid = $payment['pmtid'];
    $credit_cid = $payment['credit_cid'];
    $esc_email = mysql_real_escape_string($email);
    $esc_pmtid = mysql_real_escape_string($pmtid);
    // Create link between the paypal payment name and contact id
    $paypal_contact = array();
    if (isset($payment['paypal_email'])) {
        $paypal_contact['paypal_email'] = $email;
    }
    if (isset($payment['credit_cid'])) {
        $paypal_contact['cid'] = $credit_cid;
    }
    switch ($op) {
        case 'insert':
            $sql = "
                INSERT INTO `payment_paypal`
                (`pmtid`, `paypal_email`)
                VALUES
                ('$esc_pmtid', '$esc_email')
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
            paypal_payment_contact_save($paypal_contact);
            break;
        case 'update':
            $sql = "
                UPDATE `payment_paypal`
                SET `paypal_email` = '$esc_email'
                WHERE `pmtid` = '$esc_pmtid'
            ";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            paypal_payment_contact_save($paypal_contact);
            break;
    }
}

/**
 * Generate payments contacts table
 *
 * @param $opts an array of options passed to the paypal_payment_contact_data function
 * @return a table (array) listing the contacts represented by all payments
 *   and their associated paypal email
 */ 
function paypal_payment_contact_table($opts){
    $data = crm_get_data('paypal_payment_contact', $opts);
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    // Check for permissions
    if (!user_access('payment_view')) {
        error_register('User does not have permission to view payments');
        return;
    }
    // Add columns
    $table['columns'][] = array("title"=>'Full Name');
    $table['columns'][] = array("title"=>'Paypal Email');
    // Add rows
    foreach ($data as $union) {
        $row = array();
        //first column is the full name associated with the union['cid']
        $memberopts = array(
            'cid' => $union['cid'],
        );
        $contact = crm_get_one('contact', array('cid'=>$union['cid']));
        $contactName = '';
        if (!empty($contact)) {
            $contactName = theme('contact_name', $contact, true);
        }
        $row[] = $contactName; 
        // Second column is union['paypal_email']
        $row[] = $union['paypal_email'];
        // Save row array into the $table structure
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
function paypal_payment_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'payments':
            if (user_access('payment_edit')) {
                $content = theme('paypal_payment_admin');
                $content .= theme('form', crm_get_form('paypal_payment_import'));
                page_add_content_top($page_data, $content, 'Paypal');
            }
            break;
        case 'paypal-admin':
            page_set_title($page_data, 'Administer Paypal Contacts');
            page_add_content_top($page_data, theme('table', 'paypal_payment_contact', array('show_export'=>true)), 'View');
            break;
    }
}

/**
 * @return a paypal payments import form structure.
 */
function paypal_payment_import_form () {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'paypal_payment_import'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Import CSV'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => 'Use this form to upload paypal payments data in comma-separated (CSV) format.'
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
 * Implementation of hook_form_alter().
 * @param &$form The form being altered.
 * @param &$form_data Metadata about the form.
 * @param $form_id The name of the form.
 */
function paypal_payment_form_alter(&$form, $form_id) {
    if ($form_id === 'payment_edit') {
        // Modify paypal payments only
        $payment = $form['data']['payment'];
        if ($payment['method'] !== 'paypal') {
            return $form;
        }
        // Load paypal_payment
        $paypal_payment_opts = array('pmtid' => $payment['pmtid']);
        $paypal_payment_data = paypal_payment_data($paypal_payment_opts);
        if (count($paypal_payment_data) < 1) {
            error_register("Payment with id $payment[pmtid] missing from payment_paypal table.");
            return;
        }
        $paypal_payment = $paypal_payment_data[0];
        // Loop through all fields in the form
        for ($i = 0; $i < count($form['fields']); $i++) {
            if ($form['fields'][$i]['label'] === 'Edit Payment') {
                // Add paypal email
                $email_field = array(
                    'type' => 'readonly'
                    , 'label' => 'Paypal Email'
                    , 'name' => 'paypal_email'
                    , 'value' => $paypal_payment['paypal_email']
                );
                array_unshift($form['fields'][$i]['fields'], $email_field);
                // Loop through fields in Edit Payment fieldset
                $fieldset = $form['fields'][$i];
                for ($j = 0; $j < count($fieldset['fields']); $j++) {
                    // Since the payment is generated by a module,
                    // users shouldn't be able to change the method
                    if ($fieldset['fields'][$j]['name'] === 'method') {
                        $form['fields'][$i]['fields'][$j]['options'] = array('paypal' => 'Paypal');
                        $form['fields'][$i]['fields'][$j]['value'] = paypal;
                    }
                }
            }
        }
    }
    return $form;
}

/**
 * Handle paypal payment import request.
 *
 * @return The url to display on completion.
 */
function command_paypal_payment_import () {
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
        
        // Skip transactions that have already been imported
        $payment_opts = array(
            'filter' => array('confirmation' => $row['Transaction ID'])
        );
        $data = payment_data($payment_opts);
        if (count($data) > 0) {
            continue;
        }
        // Parse value
        $value = payment_parse_currency($row['Gross']);
        // Create payment object
        $payment = array(
            'date' => date('Y-m-d', strtotime($row['Date']))
            , 'code' => $value['code']
            , 'value' => $value['value']
            , 'description' => $row['Name'] . ' Paypal Payment'
            , 'method' => 'paypal'
            , 'confirmation' => $row['Transaction ID']
            , 'notes' => $row['Item Title']
            , 'paypal_email' => $row['From Email Address']
        );
        // Check if the paypal email is linked to a contact
        $opts = array('filter'=>array('paypal_email'=>$row['From Email Address']));
        $contact_data = paypal_payment_contact_data($opts);
        if (count($contact_data) > 0) {
            $payment['credit_cid'] = $contact_data[0]['cid'];
        }
        // Save the payment
        $payment = payment_save($payment);
        $count++;
    }
    message_register("Successfully imported $count payment(s)");
    return 'index.php?q=payments';
}

/**
 * Return themed html for paypal admin links.
 */
function theme_paypal_payment_admin () {
    return '<p><a href="index.php?q=paypal-admin">Administer</a></p>';
}
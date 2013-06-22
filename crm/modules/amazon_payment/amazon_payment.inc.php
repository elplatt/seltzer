<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
        if (!$res) crm_error(mysql_error());
        
        // Additional contact info for amazon payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `contact_amazon` (
              `cid` mediumint(8) unsigned NOT NULL,
              `amazon_name` varchar(255) NOT NULL,
              PRIMARY KEY (`amazon_name`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Implementation of hook_data_alter().
 * @param $type The type of the data being altered.
 * @param $data An array of structures of the given $type.
 * @param $opts An associative array of options.
 * @return An array of modified structures.
 */
function amazon_payment_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'payment':
            // Get amazon payments
            $pmtids = array();
            foreach ($data as $payment) { $pmtids[] = $payment['pmtid']; }
            $opts = array('pmtid' => $pmtids);
            $amazon_payment_map = crm_map(crm_get_data('amazon_payment', $opts), 'pmtid');
            // Add amazon data to each payment data
            foreach ($data as $i => $payment) {
                $data[$i]['amazon'] = $amazon_payment_map[$payment['pmtid']];
            }
    }
    return $data;
}

/**
 * Return data for one or more amazon payments.
 */
function amazon_payment_data ($opts = array()) {
    $sql = "SELECT `pmtid`, `amazon_name` FROM `payment_amazon` WHERE 1";
    if (isset($opts['pmtid'])) {
        if (is_array($opts['pmtid'])) {
            $terms = array();
            foreach ($opts['pmtid'] as $id) { $terms[] = mysql_real_escape_string($id); }
            $sql .= " AND `pmtid` IN (" . join(',', $terms) . ") ";
        } else {
            $esc_pmtid = mysql_real_escape_string($opts['pmtid']);
            $sql .= " AND `pmtid`='$esc_pmtid' ";
        }
    }
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    // Read from database and store in a structure
    $amazon_payment_data = array();
    while ($db_row = mysql_fetch_assoc($res)) {
        $amazon_payment_data[] = $db_row;
    }
    return $amazon_payment_data;
};

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
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $filter => $value) {
            if ($filter === 'amazon_name') {
                $esc_name = mysql_real_escape_string($value);
                $sql .= " AND `amazon_name`='$esc_name' ";
            }
        }
    }
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $names = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $name = array(
            'cid' => $row['cid']
            , 'amazon_name' => $row['amazon_name']
        );
        $names[] = $name;
        $row = mysql_fetch_assoc($res);
    }
    return $names;
}

/**
 * Save an amazon contact.  If the name is already in the database,
 * the mapping is updated.  When updating the mapping, any fields that are not
 * set are not modified.
 */
function amazon_payment_contact_save ($contact) {
    $esc_name = mysql_real_escape_string($contact['amazon_name']);
    $esc_cid = mysql_real_escape_string($contact['cid']);    
    // Check whether the amazon contact already exists in the database
    $sql = "SELECT * FROM `contact_amazon` WHERE `amazon_name` = '$esc_name'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $row = mysql_fetch_assoc($res);
    if ($row) {
        // Name is already in database, update if the cid is set
        if (isset($contact['cid'])) {
            $sql = "
                UPDATE `contact_amazon`
                SET `cid`='$esc_cid'
                WHERE `amazon_name`='$esc_name'
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
        }
    } else {
        // Name is not in database, insert new
        $sql = "
            INSERT INTO `contact_amazon`
            (`amazon_name`, `cid`) VALUES ('$esc_name', '$esc_cid')";
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
}

/**
 * Update amazon_payment data when a payment is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function amazon_payment_payment_api ($payment, $op) {
    if ($payment['method'] !== 'amazon') {
        return $payment;
    }
    $name = $payment['amazon_name'];
    $pmtid = $payment['pmtid'];
    $credit_cid = $payment['credit_cid'];
    $esc_name = mysql_real_escape_string($name);
    $esc_pmtid = mysql_real_escape_string($pmtid);
    // Create link between the amazon payment name and contact id
    $amazon_contact = array();
    if (isset($payment['amazon_name'])) {
        $amazon_contact['amazon_name'] = $name;
    }
    if (isset($payment['credit_cid'])) {
        $amazon_contact['cid'] = $credit_cid;
    }
    switch ($op) {
        case 'insert':
            $sql = "
                INSERT INTO `payment_amazon`
                (`pmtid`, `amazon_name`)
                VALUES
                ('$esc_pmtid', '$esc_name')
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
            amazon_payment_contact_save($amazon_contact);
            break;
        case 'update':
            $sql = "
                UPDATE `payment_amazon`
                SET `amazon_name` = '$esc_name'
                WHERE `pmtid` = '$esc_pmtid'
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
            amazon_payment_contact_save($amazon_contact);
            break;
        case 'delete':
            $sql = "
                DELETE FROM `payment_amazon`
                WHERE `pmtid`='$esc_pmtid'";
                $res = mysql_query($sql);
                if (!$res) crm_error(mysql_error());
            break;
    }
    return $payment;
}

/**
 * Generate payments contacts table.
 *
 * @param $opts an array of options passed to the amazon_payment_contact_data function
 * @return a table (array) listing the contacts represented by all payments
 *   and their associated amazon name
 */
function amazon_payment_contact_table ($opts) {
    $data = crm_get_data('amazon_payment_contact', $opts);
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
    $table['columns'][] = array("title"=>'Amazon Name');
    // Add ops column
    if (!$export && (user_access('payment_edit') || user_access('payment_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
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
        // Second column is union['amazon_name']
        $row[] = $union['amazon_name'];
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
function amazon_payment_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'payments':
            if (user_access('payment_edit')) {
                $content = theme('amazon_payment_admin');
                $content .= theme('form', crm_get_form('amazon_payment_import'));
                page_add_content_top($page_data, $content, 'Amazon');
            }
            break;
        case 'amazon-admin':
            page_set_title($page_data, 'Administer Amazon Contacts');
            page_add_content_top($page_data, theme('table', 'amazon_payment_contact', array('show_export'=>true)), 'View');
            page_add_content_top($page_data, theme('form', crm_get_form('amazon_payment_contact_add')), 'Add');
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
 * Return the form structure for the add amazon contact form.
 *
 * @param The cid of the contact to add a amazon contact for.
 * @return The form structure.
*/
function amazon_payment_contact_add_form () {
    
    // Ensure user is allowed to edit amazon contacts
    if (!user_access('payment_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'amazon_payment_contact_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Amazon Contact',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Amazon Name',
                        'name' => 'amazon_name'
                    ),
                    array(
                        'type' => 'text',
                        'label' => "Member's Name",
                        'name' => 'cid',
                        'autocomplete' => 'contact_name'
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Add'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Implementation of hook_form_alter().
 * @param &$form The form being altered.
 * @param &$form_data Metadata about the form.
 * @param $form_id The name of the form.
 */
function amazon_payment_form_alter($form, $form_id) {
    if ($form_id === 'payment_edit') {
        // Modify amazon payments only
        $payment = $form['data']['payment'];
        if ($payment['method'] !== 'amazon') {
            return $form;
        }
        $amazon_payment = $payment['amazon'];
        if (empty($amazon_payment)) {
            error_register("Payment type 'amazon' but no associated data for payment:$payment[pmtid].");
            return $form;
        }
        // Loop through all fields in the form
        for ($i = 0; $i < count($form['fields']); $i++) {
            if ($form['fields'][$i]['label'] === 'Edit Payment') {
                // Add amazon name
                $name_field = array(
                    'type' => 'readonly'
                    , 'label' => 'Amazon Name'
                    , 'name' => 'amazon_name'
                    , 'value' => $amazon_payment['amazon_name']
                );
                array_unshift($form['fields'][$i]['fields'], $name_field);
                // Loop through fields in Edit Payment fieldset
                $fieldset = $form['fields'][$i];
                for ($j = 0; $j < count($fieldset['fields']); $j++) {
                    // Since the payment is generated by a module,
                    // users shouldn't be able to change the method
                    if ($fieldset['fields'][$j]['name'] === 'method') {
                        $form['fields'][$i]['fields'][$j]['options'] = array('amazon' => 'Amazon');
                        $form['fields'][$i]['fields'][$j]['value'] = amazon;
                    }
                }
            }
        }
    }
    return $form;
}

/**
 * Handle amazon payment import request.
 *
 * @return The url to display on completion.
 */
function command_amazon_payment_import () {
    if (!user_access('payment_add')) {
        error_register('User does not have permission: payment_add');
        return crm_url('payments');
    }
    if (!array_key_exists('payment-file', $_FILES)) {
        error_register('No payment file uploaded');
        return crm_url('payments&tab=import');
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
        // Skip transactions that have already been imported
        $payment_opts = array(
            'filter' => array('confirmation' => $row['Transaction ID'])
        );
        $data = payment_data($payment_opts);
        if (count($data) > 0) {
            continue;
        }
        // Parse value
        $value = payment_parse_currency($row['Amount']);
        // Create payment object
        $payment = array(
            'date' => date('Y-m-d', strtotime($row['Date']))
            , 'code' => $value['code']
            , 'value' => $value['value']
            , 'description' => $row['Name'] . ' Amazon Payment'
            , 'method' => 'amazon'
            , 'confirmation' => $row['Transaction ID']
            , 'notes' => $row['notes']
            , 'amazon_name' => $row['Name']
        );
        // Check if the amazon name is linked to a contact
        $opts = array('filter'=>array('amazon_name'=>$row['Name']));
        $contact_data = amazon_payment_contact_data($opts);
        if (count($contact_data) > 0) {
            $payment['credit_cid'] = $contact_data[0]['cid'];
        }
        // Save the payment
        $payment = payment_save($payment);
        $count++;
    }
    message_register("Successfully imported $count payment(s)");
    return crm_url('payments');
}

/**
 * Add an amazon contact.
 * @return The url to display on completion.
 */
function command_amazon_payment_contact_add (){
    amazon_payment_contact_save($_POST);
    return crm_url('amazon-admin');
}

/**
 * Return themed html for amazon admin links.
 */
function theme_amazon_payment_admin () {
    return '<p><a href=' . crm_url('amazon-admin') . '>Administer</a></p>';
}
<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
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
    return 2;
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        
        // Additional contact info for paypal payments
        $sql = '
            CREATE TABLE IF NOT EXISTS `contact_paypal` (
              `cid` mediumint(8) unsigned NOT NULL,
              `paypal_email` varchar(255) NOT NULL,
              PRIMARY KEY (`paypal_email`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ';
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
    }
    if ($old_revision < 2) {
        $sql = '
            ALTER TABLE `payment_paypal`
            CHANGE COLUMN paypal_email email varchar(255);
        ';    
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        $sql = '
            ALTER TABLE `contact_paypal`
            CHANGE COLUMN paypal_email email varchar(255);
        ';    
        $res = mysql_query($sql);
        if (!$res) crm_error(mysql_error());
        $sql = '
            ALTER TABLE `contact_paypal` DROP PRIMARY KEY, 
            ADD PRIMARY KEY(`email`);
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
function paypal_payment_data_alter ($type, $data = array(), $opts = array()) {
    switch ($type) {
        case 'payment':
            // Get paypal payments
            $pmtids = array();
            foreach ($data as $payment) { $pmtids[] = $payment['pmtid']; }
            $opts = array('pmtid' => $pmtids);
            $paypal_payment_map = crm_map(crm_get_data('paypal_payment', $opts), 'pmtid');
            // Add paypal data to each payment data
            foreach ($data as $i => $payment) {
                if (array_key_exists($payment['pmtid'], $paypal_payment_map)) {
                    $data[$i]['paypal'] = $paypal_payment_map[$payment['pmtid']];
                }
            }
    }
    return $data;
}

/**
 * Return data for one or more paypal payments.
 */
function paypal_payment_data ($opts = array()) {
    $sql = "SELECT `pmtid`, `email` FROM `payment_paypal` WHERE 1";
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
    $sql = "SELECT `cid`, `email` FROM `contact_paypal` WHERE 1";
    if (isset($opts['filter'])) {
        foreach ($opts['filter'] as $filter => $value) {
            if ($filter === 'email') {
                $esc_email = mysql_real_escape_string($value);
                $sql .= " AND `email`='$esc_email' ";
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
            , 'email' => $row['email']
        );
        $emails[] = $email;
        $row = mysql_fetch_assoc($res);
    }
    return $emails;
}

// Contact & Payment addition, deletion, update ////////////////////////////////

/**
 * Update paypal payment contact data when a contact is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function paypal_payment_contact_api ($contact, $op) {
    switch ($op) {
        case 'create':
            paypal_payment_contact_save ($contact);
            break;
        case 'update':
            // TODO
            break;
        case 'delete':
            paypal_payment_contact_delete($contact);
            break;
    }
    return $contact;
}

/**
 * Save a paypal contact.  If the name is already in the database,
 * the mapping is updated.  When updating the mapping, any fields that are not
 * set are not modified.
 */
function paypal_payment_contact_save ($contact) {
    $esc_email = mysql_real_escape_string($contact['email']);
    $esc_cid = mysql_real_escape_string($contact['cid']);    
    // Check whether the paypal contact already exists in the database
    $sql = "SELECT * FROM `contact_paypal` WHERE `email` = '$esc_email'";
    $res = mysql_query($sql);
    if (!$res) crm_error(mysql_error());
    $row = mysql_fetch_assoc($res);
    if ($row) {
        // Name is already in database, update if the cid is set
        if (isset($contact['cid'])) {
            $sql = "
                UPDATE `contact_paypal`
                SET `cid`='$esc_cid'
                WHERE `email`='$esc_email'
            ";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
        }
    } else {
        // Name is not in database, insert new
        if (!empty($esc_email)) {
            $sql = "
                INSERT INTO `contact_paypal`
                (`email`, `cid`)
                VALUES
                ('$esc_email', '$esc_cid')";
            $res = mysql_query($sql);
            if (!$res) crm_error(mysql_error());
        }
    }
}

/**
 * Delete a paypal contact.
 * @param $paypal_payment_contact The paypal_payment_contact data structure to delete, must have a 'cid' element.
 */
function paypal_payment_contact_delete ($paypal_payment_contact) {
    $esc_cid = mysql_real_escape_string($paypal_payment_contact['cid']);
    $sql = "DELETE FROM `contact_paypal` WHERE `cid`='$esc_cid'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    if (mysql_affected_rows() > 0) {
        message_register('Paypal contact info deleted for: ' . theme('contact_name', $esc_cid));
    }
    return crm_url('paypal-admin');
}

/**
 * Update paypal_payment data when a payment is updated.
 * @param $contact The contact data array.
 * @param $op The operation being performed.
 */
function paypal_payment_payment_api ($payment, $op) {
    if ($payment['method'] !== 'paypal') {
        return $payment;
    }
    $email = $payment['email'];
    $pmtid = $payment['pmtid'];
    $credit_cid = $payment['credit_cid'];
    $esc_email = mysql_real_escape_string($email);
    $esc_pmtid = mysql_real_escape_string($pmtid);
    // Create link between the paypal payment name and contact id
    $paypal_contact = array();
    if (isset($payment['email'])) {
        $paypal_contact['email'] = $email;
    }
    if (isset($payment['credit_cid'])) {
        $paypal_contact['cid'] = $credit_cid;
    }
    switch ($op) {
        case 'insert':
            $sql = "
                INSERT INTO `payment_paypal`
                (`pmtid`, `email`)
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
                SET `email` = '$esc_email'
                WHERE `pmtid` = '$esc_pmtid'
            ";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
            paypal_payment_contact_save($paypal_contact);
            break;
        case 'delete':
            $sql = "
                DELETE FROM `payment_paypal`
                WHERE `pmtid`='$esc_pmtid'";
                $res = mysql_query($sql);
                if (!$res) crm_error(mysql_error());
            break;
    }
    return $payment;
}

// Table & Page rendering //////////////////////////////////////////////////////

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
        // Second column is union['email']
        $row[] = $union['email'];
        if (!$export && (user_access('payment_edit') || user_access('payment_delete'))) {
            // Construct ops array
            $ops = array();
            // Add edit op
            // TODO
            // Add delete op
            if (user_access('payment_delete')) {
                $ops[] = '<a href=' . crm_url('delete&type=paypal_payment_contact&id=' . $contact['cid']) . '>delete</a>';
            }
            // Add ops row
            $row[] = join(' ', $ops);
        }
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
            page_add_content_top($page_data, theme('table', crm_get_table('paypal_payment_contact', array('show_export'=>true)), 'View'));
            page_add_content_top($page_data, theme('form', crm_get_form('paypal_payment_contact_add')), 'Add');
            break;
    }
}

// Forms ///////////////////////////////////////////////////////////////////////

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
 * Return the form structure for the add paypal contact form.
 *
 * @param The cid of the contact to add a paypal contact for.
 * @return The form structure.
*/
function paypal_payment_contact_add_form () {
    
    // Ensure user is allowed to edit paypal contacts
    if (!user_access('payment_edit')) {
        return crm_url('paypal-admin');
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'paypal_payment_contact_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Paypal Contact',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Paypal Email Address',
                        'name' => 'email'
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
 * Return the delete paypal contact form structure.
 *
 * @param $cid The cid of the paypal contact to delete.
 * @return The form structure.
*/
function paypal_payment_contact_delete_form ($cid) {
    
    // Ensure user is allowed to delete paypal contacts
    if (!user_access('payment_edit')) {
        return crm_url('paypal-admin');
    }
    
    // Get paypal contact data
    $data = crm_get_data('paypal_payment_contact', array('cid'=>$cid));
    $paypal_payment_contact = $data[0];
    
    // Construct paypal contact name
    $paypal_payment_contact_name = "paypal contact:$paypal_payment_contact[cid] email:$paypal_payment_contact[email]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'paypal_payment_contact_delete',
        'hidden' => array(
            'cid' => $paypal_payment_contact['cid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Paypal Contact',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the paypal contact "' . $paypal_payment_contact_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Delete'
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
function paypal_payment_form_alter(&$form, $form_id) {
    if ($form_id === 'payment_edit') {
        // Modify paypal payments only
        $payment = $form['data']['payment'];
        if ($payment['method'] !== 'paypal') {
            return $form;
        }
        $paypal_payment = $payment['paypal'];
        if (empty($paypal_payment)) {
            error_register("Payment type 'paypal' but no associated data for payment:$payment[pmtid].");
            return $form;
        }
        // Loop through all fields in the form
        for ($i = 0; $i < count($form['fields']); $i++) {
            if ($form['fields'][$i]['label'] === 'Edit Payment') {
                // Add paypal email
                $email_field = array(
                    'type' => 'readonly'
                    , 'label' => 'Paypal Email'
                    , 'name' => 'email'
                    , 'value' => $paypal_payment['email']
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

// Commands ////////////////////////////////////////////////////////////////////

/**
 * Handle paypal payment import request.
 *
 * @return The url to display on completion.
 */
function command_paypal_payment_import () {
    if (!user_access('payment_edit')) {
        error_register('User does not have permission: payment_edit');
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
            , 'email' => $row['From Email Address']
        );
        // Check if the paypal email is linked to a contact
        $opts = array('filter'=>array('email'=>$row['From Email Address']));
        $contact_data = paypal_payment_contact_data($opts);
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
 * Add a paypal contact.
 * @return The url to display on completion.
 */
function command_paypal_payment_contact_add () {
    paypal_payment_contact_save($_POST);
    return crm_url('paypal-admin');
}

/**
 * Delete a paypal contact.
 * @param $paypal_payment_contact The paypal_payment_contact data structure to delete, must have a 'cid' element.
 */
function command_paypal_payment_contact_delete () {
    paypal_payment_contact_delete($_POST);
    return crm_url('paypal-admin');
}

// Themes //////////////////////////////////////////////////////////////////////

/**
 * Return themed html for paypal admin links.
 */
function theme_paypal_payment_admin () {
    return '<p><a href=' . crm_url('paypal-admin') . '>Administer</a></p>';
}

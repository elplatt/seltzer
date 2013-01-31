<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    payment.inc.php - Payment tracking module
    
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
function payment_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function payment_permissions () {
    return array(
        'payment_view'
        , 'payment_edit'
        , 'payment_delete'
    );
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function payment_install($old_revision = 0) {
    // Create initial database table
    if ($old_revision < 1) {
        $sql = '
            CREATE TABLE IF NOT EXISTS `payment` (
              `pmtid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `date` date DEFAULT NULL,
              `description` varchar(255) NOT NULL,
              `code` varchar(8) NOT NULL,
              `value` mediumint(8) NOT NULL,
              `credit` mediumint(8) unsigned NOT NULL,
              `debit` mediumint(8) unsigned NOT NULL,
              `method` varchar(255) NOT NULL,
              `confirmation` varchar(255) NOT NULL,
              `notes` text NOT NULL,
              `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`pmtid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
}

// Utility functions ///////////////////////////////////////////////////////////

/**
 * Parse a string and return a currency.
 * @param $value A string representation of a currency value.
 * @param $code Optional currency code.
 */
function payment_parse_currency ($value, $code = null) {
    if (!isset($code)) {
        // TODO replace this with global default -Ed 2013-01-13
        $code = 'USD';
    }
    // Determine sign
    $sign = 1;
    if (preg_match('/^\(.*\)$/', $value) || preg_match('/^\-/', $value)) {
        $sign = -1;
    }
    // Remove all irrelevant characters
    switch ($code) {
        case 'USD':
            $to_remove = '/[^0-9\.]/';
            break;
        default:
            $to_remove = '//';
    }
    $clean_value = preg_replace($to_remove, '', $value);
    // Split the amount into parts
    $count = 0;
    switch ($code) {
        case 'USD':
            $parts = split('\.', $clean_value);
            $dollars = $parts[0];
            $count = 100 * $dollars;
            if (count($parts) > 1 && !empty($parts[1])) {
                // This assumes there are exactly two digits worth of cents
                if (strlen($parts[1]) != 2) {
                    error_register("Warning: parsing of cents failed: '$parts[1]'");
                }
                $count += intval($parts[1]);
            }
            break;
    }
    // Construct currency structure
    $currency_value = array(
        'code' => $code
        , 'value' => $count * $sign
    );
    return $currency_value;
}

/**
 * Format a currency.
 * @param $value A currency structure.
 * @param $symbol If true, include symbol (default true).
 * @return A string representation of $value.
 */
function payment_format_currency ($value, $symbol = true) {
    $result = '';
    $count = $value['value'];
    $sign = 1;
    if ($count < 0) {
        $count *= -1;
        $sign = -1;
    }
    switch ($value['code']) {
        case 'USD':
            if (strlen($count) > 2) {
                $dollars = substr($count, 0, -2);
                $cents = substr($count, -2);
            } else {
                $dollars = '0';
                $cents = sprintf('%02d', $count);
            }
            if ($symbol) {
                $result .= '$';
            }
            $result .= $dollars . '.' . $cents;
            if ($sign < 0) {
                $result = '(' . $result . ')';
            }
            break;
        default:
            $result = $value['value'];
    }
    return $result;
}

/**
 * Add two currency values.
 * @param $a A currency structure.
 * @param $b A currency structure.
 * @return The sum of $a and $b
 */
function payment_add_currency ($a, $b) {
    if ($a['code'] != $b['code']) {
        error_register('Attempted to add currencies of different types');
        return array();
    }
    return array(
        'code' => $a['code']
        , 'value' => $a['value'] + $b['value']
    );
}

/**
 * Inverts a currency value.
 * @param $value The currency value.
 * @return A copy of $value with the amount multiplied by -1.
 */
function payment_invert_currency ($value) {
    $value['value'] *= -1;
    return $value;
}

// DB to Object mapping ////////////////////////////////////////////////////////

/**
 * Return data for one or more payments.
 *
 * @param $opts An associative array of options, possible keys are:
 *   'pmtid' If specified, returns a single payment with the matching id;
 *   'cid' If specified, returns all payments assigned to the contact with specified id;
 *   'filter' An array mapping filter names to filter values;
 *   'join' A list of tables to join to the payment table;
 *   'order' An array of associative arrays of the form 'field'=>'order'.
 * @return An array with each element representing a single payment.
*/ 
function payment_data ($opts = array()) {
    
    $join_contact = false;
    if (!array_key_exists('join', $opts) || in_array('contact', $opts['join'])) {
        $join_contact = true;
    }
    
    $decimals = PAYMENT_DECIMALS;
    $sql = "
        SELECT
        `pmtid`
        , `date`
        , `description`
        , `code`
        , `value`
        , `credit`
        , `debit`
        , `method`
        , `confirmation`
        , `notes`
        FROM `payment`
    ";
    $sql .= "WHERE 1 ";
    if (array_key_exists('pmtid', $opts)) {
        $pmtid = mysql_real_escape_string($opts['pmtid']);
        $sql .= " AND `pmtid`='$pmtid' ";
    }
    if (array_key_exists('cid', $opts)) {
        $cid = mysql_real_escape_string($opts['cid']);
        $sql .= " AND (`debit`='$cid' OR `credit`='$cid') ";
    }
    if (array_key_exists('filter', $opts)) {
        foreach($opts['filter'] as $name => $value) {
            $esc_value = mysql_real_escape_string($value);
            switch ($name) {
                case 'confirmation':
                    $sql .= " AND (`confirmation`='$esc_value') ";
                    break;
            }
        }
    }
    // Specify the order the results should be returned in
    if (isset($opts['order'])) {
        $field_list = array();
        foreach ($opts['order'] as $field => $order) {
            $clause = '';
            switch ($field) {
                case 'date':
                    $clause .= "`date` ";
                    break;
                case 'created':
                    $clause .= "`created` ";
                    break;
                default:
                    continue;
            }
            if (strtolower($order) === 'asc') {
                $clause .= 'ASC';
            } else {
                $clause .= 'DESC';
            }
            $field_list[] = $clause;
        }
        if (!empty($field_list)) {
            $sql .= " ORDER BY " . implode(',', $field_list) . " ";
        }
    } else {
        // Default to date, created from newest to oldest
        $sql .= " ORDER BY `date` DESC, `created` DESC ";
    }
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $payments = array();
    $cid_map = array();
    $row = mysql_fetch_assoc($res);
    while ($row) {
        $payment = array(
            'pmtid' => $row['pmtid']
            , 'date' => $row['date']
            , 'description' => $row['description']
            , 'code' => $row['code']
            , 'value' => $row['value']
            , 'credit_cid' => $row['credit']
            , 'debit_cid' => $row['debit']
            , 'method' => $row['method']
            , 'confirmation' => $row['confirmation']
            , 'notes' => $row['notes']
        );
        $payments[] = $payment;
        $cid_map[$row['credit']] = true;
        $cid_map[$row['debit']] = true;
        $row = mysql_fetch_assoc($res);
    }
    
    if ($join_contact) {
        $contact_map = array();
        $cids = array_keys($cid_map);
        $data = member_contact_data(array('cid'=>$cids));
        foreach ($data as $row) {
            $contact_map[$row['cid']] = $row;
        }
        
        foreach ($payments as $i => $row) {
            if (!empty($row['credit_cid'])) {
                $payments[$i]['credit'] = $contact_map[$row['credit_cid']];
            }
            if (!empty($row['debit_cid'])) {
                $payments[$i]['debit'] = $contact_map[$row['debit_cid']];
            }
        }
    }
    
    return $payments;
}

/**
 * Save a payment to the database.  If the payment has a key called "pmtid"
 * an existing payment will be updated in the database.  Otherwise a new payment
 * will be added to the database.  If a new payment is added to the database,
 * the returned array will have a "pmtid" field corresponding to the database id
 * of the new payment.
 * 
 * @param $payment An associative array representing a payment.
 * @return A new associative array representing the payment.
 */
function payment_save ($payment) {

    // Verify permissions and validate input
    if (!user_access('payment_edit')) {
        error_register('Permission denied: payment_edit');
        return NULL;
    }
    if (empty($payment)) {
        return NULL;
    }
    
    // Sanitize input
    $esc_pmtid = mysql_real_escape_string($payment['pmtid']);
    $esc_date = mysql_real_escape_string($payment['date']);
    $esc_description = mysql_real_escape_string($payment['description']);
    $esc_code = mysql_real_escape_string($payment['code']);
    $esc_value = mysql_real_escape_string($payment['value']);
    $esc_credit = mysql_real_escape_string($payment['credit_cid']);
    $esc_debit = mysql_real_escape_string($payment['debit_cid']);
    $esc_method = mysql_real_escape_string($payment['method']);
    $esc_confirmation = mysql_real_escape_string($payment['confirmation']);
    $esc_notes = mysql_real_escape_string($payment['notes']);
    
    if (!empty($payment['pmtid'])) {
        // Payment already exists, update
        $sql = "
            UPDATE `payment`
            SET
            `date`='$esc_date'
            , `description` = '$esc_description'
            , `code` = '$esc_code'
            , `value` = '$esc_value'
            , `credit` = '$esc_credit'
            , `debit` = '$esc_debit'
            , `method` = '$esc_method'
            , `confirmation` = '$esc_confirmation'
            , `notes` = '$esc_notes'
            WHERE
            `pmtid` = '$esc_pmtid'
        ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $op = 'update';
    } else {
        // Payment does not yet exist, create
        $sql = "
            INSERT INTO `payment`
            (
                `date`
                , `description`
                , `code`
                , `value`
                , `credit`
                , `debit`
                , `method`
                , `confirmation`
                , `notes`
            )
            VALUES
            (
                '$esc_date'
                , '$esc_description'
                , '$esc_code'
                , '$esc_value'
                , '$esc_credit'
                , '$esc_debit'
                , '$esc_method'
                , '$esc_confirmation'
                , '$esc_notes'
            )
        ";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $payment['pmtid'] = mysql_insert_id();
        $op = 'insert';
    }

    $payment = payment_invoke_api($payment, $op);
    
    return $payment;
}

/**
 * Call hooks when a payment is modified.
 * @param $payment An associative array representing the payment
 * @param $op A string describing the operation, values are:
 *   insert
 *   update
 */
function payment_invoke_api($payment, $op) {
    foreach (module_list() as $module) {
        $hook = $module . '_payment_api';
        if (function_exists($hook)) {
            call_user_func($hook, $payment, $op);
        }
    }
    return $payment;
}

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table structure for a table of payments.
 *
 * @param $opts The options to pass to payment_data().
 * @return The table structure.
*/
function payment_table ($opts) {
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get data
    $data = payment_data($opts);
    if (count($data) < 1) {
        return array();
    }
    
    // Initialize table
    $table = array(
        "id" => '',
        "class" => '',
        "rows" => array(),
        "columns" => array()
    );
    
    // Add columns
    if (user_access('payment_view')) { // Permission check
        $table['columns'][] = array("title"=>'date');
        $table['columns'][] = array("title"=>'description');
        $table['columns'][] = array("title"=>'credit');
        $table['columns'][] = array("title"=>'debit');
        $table['columns'][] = array("title"=>'amount');
        $table['columns'][] = array("title"=>'method');
        $table['columns'][] = array("title"=>'confirmation');
        $table['columns'][] = array("title"=>'notes');
    }
    // Add ops column
    if (!$export && (user_access('payment_edit') || user_access('payment_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Add rows
    foreach ($data as $payment) {
        
        $row = array();
        if (user_access('payment_view')) {
            
            $row[] = $payment['date'];
            $row[] = $payment['description'];
            if (array_key_exists('credit', $payment)) {
                $contact = $payment['credit'];
                $row[] = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
            } else {
                $row[] = '';
            }
            if (array_key_exists('debit', $payment)) {
                $contact = $payment['debit'];
                $row[] = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
            } else {
                $row[] = '';
            }
            $row[] = payment_format_currency($payment, true);
            $row[] = $payment['method'];
            $row[] = $payment['confirmation'];
            $row[] = $payment['notes'];
        }
        
        if (!$export && (user_access('payment_edit') || user_access('payment_delete'))) {
            // Add ops column
            // TODO
            $ops = array();
            if (user_access('payment_edit')) {
               $ops[] = "<a href=\"index.php?q=payment&pmtid=$payment[pmtid]\">edit</a>";
            }
            if (user_access('payment_delete')) {
                $ops[] = "<a href=\"index.php?q=delete&type=payment&id=$payment[pmtid]\">delete</a>";
            }
            $row[] = join(' ', $ops);
        }
        
        $table['rows'][] = $row;
    }
    
    return $table;
}

/**
 * Return a table showing a contacts payment history.
 * @param $opts An associative array of options.  Possible keys are:
 *   cid - The id of the contact to display
 * @return A table object.
 */
function payment_history_table ($opts) {
    
    $cid = $opts['cid'];
    if (!$cid) {
        error_register('Unable to create payment history: no contact specified');
        return array();
    }
    
    // Show oldest to newest unless otherwise specified
    if (!isset($opts['order'])) {
        $opts['order'] = array('date'=>'asc', 'created'=>'asc');
    }
    $payments = payment_data($opts);
    $balance = null;
    
    $table = array(
        'columns' => array(
            array('title' => 'Date')
            , array('title' => 'Description')
            , array('title' => 'Amount')
            , array('title' => 'Method')
            , array('title' => 'To/From')
            , array('title' => 'Balance')
        )
        , 'rows' => array()
    );
    
    foreach ($payments as $payment) {
        
        $contact = '';
        if ($payment['credit_cid'] === $cid) {
            $payment = payment_invert_currency($payment);
            $contact = $payment['debit'];
        } else {
            $contact = $payment['credit'];
        }
        
        $contactName = '';
        if (!empty($contact)) {
            $contactName = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        }
        
        if (isset($balance)) {
            $balance = payment_add_currency($balance, $payment);
        } else {
            $balance = $payment;
        }
        
        $row = array();
        $row[] = $payment['date'];
        $row[] = $payment['description'];
        $row[] = payment_format_currency($payment);
        $row[] = $payment['method'];
        $row[] = $contactName;
        $row[] = payment_format_currency($balance);
        
        $table['rows'][] = $row;
    }
    
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return Array mapping payment method values to descriptions.
 */
function payment_method_options () {
    $options = array();
    $options['cash'] = 'Cash';
    $options['cheque'] = 'Cheque';
    $options['other'] = 'Other';
    return $options;
}

/**
 * @return The form structure for adding a payment.
*/
function payment_add_form () {
    
    // Ensure user is allowed to edit payments
    if (!user_access('payment_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'payment_add'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Add Payment'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Credit'
                        , 'name' => 'credit'
                        , 'autocomplete' => 'member_name'
                        , 'class' => 'focus'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Date'
                        , 'name' => 'date'
                        , 'value' => date("Y-m-d")
                        , 'class' => 'date'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Description'
                        , 'name' => 'description'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Amount'
                        , 'name' => 'amount'
                    )
                    , array(
                        'type' => 'select'
                        , 'label' => 'Method'
                        , 'name' => 'method'
                        , 'options' => payment_method_options()
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Check/Rcpt Num'
                        , 'name' => 'confirmation'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Debit'
                        , 'name' => 'debit'
                        , 'autocomplete' => 'member_name'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Add'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Create a form structure for editing a payment.
 *
 * @param &$form_data Array for storing extra info
 * @param $pmtid The id of the payment to edit.
 * @return The form structure.
*/
function payment_edit_form (&$form_data, $pmtid) {
    
    // Ensure user is allowed to edit payments
    if (!user_access('payment_edit')) {
        error_register('User does not have permission: payment_edit');
        return NULL;
    }
    
    $data = payment_data(array('pmtid'=>$pmtid));
    if (count($data) < 1) {
        return NULL;
    }
    $payment = $data[0];
    $form_data['payment'] = $payment;
    
    $credit = '';
    $credit_cid = '';
    $debit = '';
    $debit_cid = '';
    if (array_key_exists('credit', $payment)) {
        $contact = $payment['credit'];
        $credit = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        $credit_cid = $contact['cid'];
    }
    if (array_key_exists('debit', $payment)) {
        $contact = $payment['debit'];
        $debit = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        $debit_cid = $contact['cid'];
    }
    
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'payment_edit'
        , 'hidden' => array(
            'pmtid' => $payment['pmtid']
            , 'code' => $payment['code']
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Edit Payment'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Credit'
                        , 'name' => 'credit_cid'
                        , 'description' => $credit
                        , 'value' => $credit_cid
                        , 'autocomplete' => 'member_name'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Date'
                        , 'name' => 'date'
                        , 'value' => $payment['date']
                        , 'class' => 'date'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Description'
                        , 'name' => 'description'
                        , 'value' => $payment['description']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Amount'
                        , 'name' => 'value'
                        , 'value' => payment_format_currency($payment, false)
                    )
                    , array(
                        'type' => 'select'
                        , 'label' => 'Method'
                        , 'name' => 'method'
                        , 'options' => payment_method_options()
                        , 'value' => $payment['method']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Check/Rcpt Num'
                        , 'name' => 'confirmation'
                        , 'value' => $payment['confirmation']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Debit'
                        , 'name' => 'debit_cid'
                        , 'description' => $debit
                        , 'value' => $debit_cid
                        , 'autocomplete' => 'member_name'
                    )
                    , array(
                        'type' => 'textarea'
                        , 'label' => 'Notes'
                        , 'name' => 'notes'
                        , 'value' => $payment['notes']
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Save'
                    )
                )
            )
        )
    );
    
    return $form;
}

/**
 * Return the payment form structure.
 *
 * @param $pmtid The id of the key assignment to delete.
 * @return The form structure.
*/
function payment_delete_form ($pmtid) {
    
    // Ensure user is allowed to delete keys
    if (!user_access('payment_delete')) {
        return NULL;
    }
    
    // Get data
    $data = payment_data(array('pmtid'=>$pmtid));
    $payment = $data[0];
    
    // Construct key name
    $amount = payment_format_currency($payment);
    $payment_name = "Payment:$payment[pmtid] - $amount";
    if (array_key_exists('credit', $payment)) {
        $contact = $payment['credit'];
        $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        $payment_name .= " - Credit: $name";
    }
    if (array_key_exists('debit', $payment)) {
        $contact = $payment['debit'];
        $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        $payment_name .= " - Debit: $name";
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'payment_delete',
        'hidden' => array(
            'pmtid' => $payment['pmtid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Payment',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the payment "' . $payment_name . '"? This cannot be undone.',
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

// Command handlers ////////////////////////////////////////////////////////////

/**
 * Handle payment delete request.
 *
 * @return The url to display on completion.
 */
function command_payment_delete() {
    global $esc_post;
    
    // Verify permissions
    if (!user_access('payment_delete')) {
        error_register('Permission denied: payment_delete');
        return 'index.php?q=payment&pmtid=' . $esc_post['pmtid'];
    }
    
    // Query database
    $sql = "
        DELETE FROM `payment`
        WHERE `pmtid`='$esc_post[pmtid]'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    if (mysql_affected_rows($res) > 0) {
        message_register('Deleted payment with id ' . $_POST['pmtid']);
    }
    
    return 'index.php?q=payments';
}

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function payment_page_list () {
    $pages = array();
    if (user_access('payment_edit')) {
        $pages[] = 'payments';
        $pages[] = 'payment';
    }
    return $pages;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function payment_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'payments':
            page_set_title($page_data, 'Payments');
            if (user_access('payment_edit')) {
                $content = theme('form', payment_add_form());
                $content .= theme('table', 'payment', array('show_export'=>true));
                page_add_content_top($page_data, $content, 'View');
            }
            break;
        case 'payment':
            page_set_title($page_data, 'Payment');
            if (user_access('payment_edit')) {
                $content = theme('form', crm_get_form('payment_edit_form', $_GET['pmtid']));
                page_add_content_top($page_data, $content);
            }
            break;
        case 'member':
            // TODO, not sure if this should be here in here or member, we need to
            // consider dependencies more carefully. -Ed 2013-01-02
            if (user_id() == $_GET['cid'] || user_access('payment_view')) {
                $content = theme('table', 'payment_history', array('cid' => $_GET['cid']));
                page_add_content_top($page_data, $content, 'Account');
            }
            break;
    }
}

// Command handlers ////////////////////////////////////////////////////////////

/**
 * Handle payment add request.
 *
 * @return The url to display on completion.
 */
function command_payment_add() {
    
    $value = payment_parse_currency($_POST['amount'], $_POST['code']);
    $payment = array(
        'date' => $_POST['date']
        , 'description' => $_POST['description']
        , 'code' => $value['code']
        , 'value' => $value['value']
        , 'credit_cid' => $_POST['credit']
        , 'debit_cid' => $_POST['debit']
        , 'method' => $_POST['method']
        , 'confirmation' => $_POST['confirmation']
        , 'notes' => $_POST['notes']
    );
    $payment = payment_save($payment);
    
    message_register('Added 1 payment.');
    
    return 'index.php?q=payments';
}

/**
 * Handle payment edit request.
 *
 * @return The url to display on completion.
 */
function command_payment_edit() {
    // Verify permissions
    if (!user_access('payment_edit')) {
        error_register('Permission denied: payment_edit');
        return 'index.php?q=payments';
    }
    // Parse and save payment
    $payment = $_POST;
    $value = payment_parse_currency($_POST['value'], $_POST['code']);
    $payment['code'] = $value['code'];
    $payment['value'] = $value['value'];
    payment_save($payment);
    
    message_register('Updated 1 payment.');
    
    return 'index.php?q=payments';
}

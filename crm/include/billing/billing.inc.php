<?php

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    billing.inc.php - Billing module

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
function billing_revision () {
    return 1;
}

/**
 * @return An array of the permissions provided by this module.
 */
function billing_permissions () {
    return array();
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function billing_install($old_revision = 0) {
    // Create initial database table
    if ($old_revision < 1) {
        // TODO
    }
}

// DB to Object mapping ////////////////////////////////////////////////////////

// Table data structures ///////////////////////////////////////////////////////

/**
 * Return a table showing a contacts billing history.
 * @param $opts An associative array of options.  Possible keys are:
 *   cid - The id of the contact to display
 * @return A table object.
 */
function billing_history_table ($opts) {
    
    $cid = $opts['cid'];
    if (!$cid) {
        error_register('Unable to create billing history: no contact specified');
        return array();
    }
    
    $payments = payment_data($opts);
    $balance_fraction = 0;
    
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
        $amount_sign = $payment['amount_sign'];
        if ($payment['credit_cid'] === $cid) {
            $amount_sign = -1*$payment['amount_sign'];
            $contact = $payment['debit'];
        } else {
            $contact = $payment['credit'];
        }
        
        $contactName = '';
        if (!empty($contact)) {
            $contactName = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
        }
        
        // Construct amount
        $amount = '';
        if ($amount_sign < 0) {
            $amount .= '-';
        }
        $amount .= $payment['amount_whole'] . '.' . $payment['amount_fraction'];
        $formattedAmount = payment_normalize_currency($amount, true);
        
        // Update balance, stored as fractions of a currency unit
        $whole = $payment['amount_whole'];
        $fraction = $payment['amount_fraction'];
        $balance_fraction += $amount_sign * ($whole * pow(10, PAYMENT_DECIMALS) + $fraction);
        
        // Convert balance to string
        $bal_sign = $balance_fraction < 0 ? -1 : 1;
        $balance_dollars = floor($bal_sign * $balance_fraction / pow(10, PAYMENT_DECIMALS));
        $dollar_fractions = $balance_dollars * pow(10, PAYMENT_DECIMALS);
        $balance_cents = round(($bal_sign * $balance_fraction - $dollar_fractions) * 100 / pow(10, PAYMENT_DECIMALS));
        $formattedBalance = payment_normalize_currency(sprintf('%d.%02d', $bal_sign*$balance_dollars, $balance_cents), true);
        
        $row = array();
        $row[] = $payment['date'];
        $row[] = $payment['description'];
        $row[] = $formattedAmount;
        $row[] = $payment['method'];
        $row[] = $contactName;
        $row[] = $formattedBalance;
        
        $table['rows'][] = $row;
    }
    
    return $table;
}

// Forms ///////////////////////////////////////////////////////////////////////

// Command handlers ////////////////////////////////////////////////////////////

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function billing_page_list () {
    $pages = array();
    return $pages;
}

/**
 * Page hook.  Adds module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function billing_page (&$page_data, $page_name, $options) {
    switch ($page_name) {
        case 'member':
            if (user_id() == $_GET['cid'] || user_access('payment_view')) {
                $content = theme('table', 'billing_history', array('cid' => $_GET['cid']));
                page_add_content_top($page_data, $content, 'Account');
            }
            break;
        default:
    }
}

<?php

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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
        'payment_edit'
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
              `amount` decimal(16,6) NOT NULL,
              `credit` mediumint(8) unsigned NOT NULL,
              `debit` mediumint(8) unsigned NOT NULL,
              `method` varchar(255) NOT NULL,
              `confirmation` varchar(255) NOT NULL,
              `notes` text NOT NULL
              PRIMARY KEY (`pmtid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
}

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * @return Array mapping payment method values to descriptions.
 */
function payment_method_options () {
    $options = array();
    $options['cash'] = 'Cash';
    $options['check'] = 'Check';
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

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function payment_page_list () {
    $pages = array();
    if (user_access('payment_edit')) {
        $pages[] = 'payments';
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
                page_add_content_top($page_data, theme('form', payment_add_form()));
            }
            break;
    }
}


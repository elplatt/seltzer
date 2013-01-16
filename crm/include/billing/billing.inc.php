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

// Forms ///////////////////////////////////////////////////////////////////////

/**
 * Form for initiating membership billings.
 * @return The form structure.
 */
function billing_form () {
    
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'billing'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Process Billings'
                , 'fields' => array(
                    array(
                        'type' => 'submit'
                        , 'value' => 'Process'
                    )
                )
            )
        )
    );
    
    return $form;
}

// Command handlers ////////////////////////////////////////////////////////////

// Pages ///////////////////////////////////////////////////////////////////////

/**
 * @return An array of pages provided by this module.
 */
function billing_page_list () {
    $pages = array();
    return $pages;
}

function billing_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        case 'plans':
            
            // Add view and add tabs
            if (user_access('member_plan_add')) {
                page_add_content_top($page_data, theme('form', billing_form()), 'Billing');
            }
            
            break;
    }
}
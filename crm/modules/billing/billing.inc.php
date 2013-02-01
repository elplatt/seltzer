<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
        case 'payments':
            
            // Add view and add tabs
            if (user_access('payment_edit')) {
                page_add_content_top($page_data, theme('form', billing_form()), 'Billing');
            }
            
            break;
    }
}

/**
 * Run billings
 */
function command_billing () {
    // Get current date and last bill date
    $today = date('Y-m-d');
    $last_billed = variable_get('billing_last_date', '');
    // Find memberships that start before today and end after the last bill date
    $filter = array('active'=>true);
    if (!empty($last_billed)) {
        $filter['starts-after'] = $last_billed;
    }
    $membership_data = member_membership_data(array('filter' => $filter));
    // Bill each membership
    foreach ($membership_data as $membership) {
        _billing_bill_membership($membership, $today, $last_billed);
    }
    // Set last billed date to today
    variable_set('billing_last_date', $today);
    $begin = empty($last_billed) ? 'the beginning of time' : $last_billed;
    message_register("Billings processed from $begin through $today.");
    return 'index.php?q=payments';
}

/**
 * Run billing for a single membership.
 * @param $membership The membership to bill.
 * @param $until Bill up to and including this day.
 * @param $after Start billing after this day or beginning of time if not given.
 */
function _billing_bill_membership ($membership, $until, $after = '') {
    $price = payment_parse_currency($membership['plan']['price']);
    $price['value'] *= -1;
    // Day to bill on
    // TODO allow plans to specify their start date -Ed 2013-01-20
    $day_of_month = 1;
    $until_date = strtotime($until);
    $membership_start = strtotime($membership['start']);
    // Find first unbilled day
    if (empty($after) || $membership_start > strtotime($after)) {
        // Membership started on or after first billable day
        // Start billing on membership start date
        $period_start = $membership_start;
    } else {
        // Membership started before first billable date
        // Find first billing period starting after $after
        $begin = strtotime($after . ' +1 day');
        $days = _billing_days_remaining($day_of_month, getdate($begin));
        $period_start = strtotime("+$days days", $begin);
    }
    // Check for partial month and bill prorated
    $period_info = getdate($period_start);
    if ($period_info['mday'] != $day_of_month) {
        // Parital month, prorate
        $prorated = _billing_prorate($period_info, $day_of_month, $price);
        $payment = array(
            'date' => date('Y-m-d', $period_start)
            , 'description' => 'Dues: ' . $membership['plan']['name']
            , 'code' => $prorated['code']
            , 'value' => $prorated['value']
            , 'credit_cid' => $membership['cid']
            , 'method' => 'cash'
        );
        payment_save($payment);
        // Advance to beginning of first full period
        $days = _billing_days_remaining($day_of_month, $period_info);
        $period_start = strtotime("+$days days", $period_start);
    }
    // Bill each full billing period
    while ($period_start < $until_date) {
        $payment = array(
            'date' => date('Y-m-d', $period_start)
            , 'description' => 'Dues: ' . $membership['plan']['name']
            , 'code' => $price['code']
            , 'value' => $price['value']
            , 'credit_cid' => $membership['cid']
            , 'method' => 'cash'
        );
        payment_save($payment);
        // Advance to next billing period
        $period_start = strtotime('+1 month', $period_start);
    }    
}

/**
 * Find the number of days left in a billing period.
 * $day_of_month The day of the month billing periods begin on.
 * $date_info A date, as returned by getdate().
 * $return The number of days remaining after $date_info.
 */
function _billing_days_remaining ($day_of_month, $date_info) {
    $days = $day_of_month - $date_info['mday'];
    if ($days < 0) {
        $days += cal_days_in_month(CAL_GREGORIAN, $date_info['mon'], $date_info['year']);
    }
    return $days;
}

/**
 * Calculate prorated billing amount.
 * @param $period_info Billing start date (as returned by getdate()).
 * @param $day_of_month The day of month billing periods start on.
 * @param $price Price array representing a full billing period.
 */
function _billing_prorate ($period_info, $day_of_month, $price) {
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $period_info['mon'], $period_info['year']);
    $partial = $day_of_month - $period_info['mday'];
    $ending_month = $period_info['mon'];
    $ending_year = $period_info['year'];
    if ($partial < 0) {
        $partial += cal_days_in_month(CAL_GREGORIAN, $period_info['mon'], $period_info['year']);
    } else {
        $ending_month--;
        if ($ending_month < 1) {
            $ending_year--;
            $ending_month += 12;
        }
    }
    $full = cal_days_in_month(CAL_GREGORIAN, $ending_month, $ending_year);
    $prorated = array(
        'code' => $price['code']
        , 'value' => ceil($price['value'] * $partial / $full)
    );
    return $prorated;
}

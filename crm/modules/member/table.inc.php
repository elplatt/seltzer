<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    table.inc.php - Member module - table structures

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
 * Return a table structure representing members.
 *
 * @param $opts Options to pass to member_data().
 * @return The table structure.
*/
function member_table ($opts = NULL) {
    
    // Ensure user is allowed to view members
    if (!user_access('member_view')) {
        return NULL;
    }
    
    // Determine settings
    $export = false;
    foreach ($opts as $option => $value) {
        switch ($option) {
            case 'export':
                $export = $value;
                break;
        }
    }
    
    // Get member data
    $members = member_data($opts);
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => '',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    
    if (user_access('member_view')) {
        if ($export) {
            $table['columns'][] = array('title'=>'Contact ID','class'=>'');
            $table['columns'][] = array('title'=>'Last','class'=>'');
            $table['columns'][] = array('title'=>'First','class'=>'');
            $table['columns'][] = array('title'=>'Middle','class'=>'');
        } else {
            $table['columns'][] = array('title'=>'Name','class'=>'');
        }
        $table['columns'][] = array('title'=>'Membership','class'=>'');
        $table['columns'][] = array('title'=>'E-Mail','class'=>'');
        $table['columns'][] = array('title'=>'Phone','class'=>'');
        if (!array_key_exists('exclude', $opts) || !in_array('emergencyName', $opts['exclude'])) {
            $table['columns'][] = array('title'=>'Emergency Contact','class'=>'');
        }
        if (!array_key_exists('exclude', $opts) || !in_array('emergencyPhone', $opts['exclude'])) {
            $table['columns'][] = array('title'=>'Emergency Phone','class'=>'');
        }
    }
    // Add ops column
    if (!$export && (user_access('member_edit') || user_access('member_delete'))) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Loop through member data
    foreach ($members as $member) {
        
        // Add user data
        $row = array();
        if (user_access('member_view')) {
            
            // Construct name
            $contact = $member['contact'];
            $name_link = theme('contact_name', $contact, true);
            // Construct membership info
            $recentMembership = end($member['membership']);
            $plan = '';
            if (!empty($recentMembership) && empty($recentMembership['end'])) {
                $plan = $recentMembership['plan']['name'];
            }
            
            // Add cells
            if ($export) {
                $row[] = $member['contact']['cid'];
                $row[] = $member['contact']['lastName'];
                $row[] = $member['contact']['firstName'];
                $row[] = $member['contact']['middleName'];
            } else {
                $row[] = $name_link;
            }
            $row[] = $plan;
            $row[] = $member['contact']['email'];
            $row[] = $member['contact']['phone'];
            if (!array_key_exists('exclude', $opts) || !in_array('emergencyName', $opts['exclude'])) {
                $row[] = $member['contact']['emergencyName'];
            }
            if (!array_key_exists('exclude', $opts) || !in_array('emergencyPhone', $opts['exclude'])) {
                $row[] = $member['contact']['emergencyPhone'];
            }
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (user_access('member_edit')) {
            $ops[] = '<a href=' . crm_url('contact&cid=' . $member['cid'] . '&tab=edit') .'>edit</a>';
        }
        
        // Add delete op
        if (user_access('member_delete')) {
            $ops[] = '<a href=' . crm_url('delete&type=contact&amp;id=' . $member['cid']) . '>delete</a>';
        }
        
        // Add ops row
        if (!$export && (user_access('member_edit') || user_access('member_delete'))) {
            $row[] = join(' ', $ops);
        }
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return table structure for all active voting members.
 * 
 * @return The table structure.
*/
function member_voting_report_table () {
    
    // Ensure user is allowed to view members
    if (!user_access('member_view')) {
        return NULL;
    }
    
    // Get member data
    $members = member_data(array('filter'=>array('voting'=>true, 'active'=>true)));
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => 'member-voting-report',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    
    if (user_access('member_view')) {
        $table['columns'][] = array('title'=>'Name','class'=>'name');
        $table['columns'][] = array('title'=>'Present','class'=>'check');
        $table['columns'][] = array('title'=>'A','class'=>'');
        $table['columns'][] = array('title'=>'B','class'=>'');
        $table['columns'][] = array('title'=>'C','class'=>'');
        $table['columns'][] = array('title'=>'D','class'=>'');
        $table['columns'][] = array('title'=>'E','class'=>'');
    }

    // Loop through member data
    foreach ($members as $member) {
        
        // Add user data
        $row = array();
        if (user_access('member_view')) {
            $name = $member['contact']['lastName']
                . ', ' . $member['contact']['firstName'];
            if (!empty($member['contact']['middleName'])) {
                $name .= ' ' . $member['contact']['middleName'];
            }
            $row[] = $name;
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
            $row[] = ' ';
        }
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return a table structure representing membership plans.
 *
 * @param $opts Options to pass to member_plan_data().
 * @return The table structure.
*/
function member_plan_table ($opts = NULL) {
    
    // Ensure user is allowed to view membership plans
    if (!user_access('member_plan_edit')) {
        return NULL;
    }
    
    // Get membership plan data
    $plans = member_plan_data($opts);
    
    // Create table structure
    $table = array(
        'id' => '',
        'class' => '',
        'rows' => array()
    );
    
    // Add columns
    $table['columns'] = array();
    if (user_access('member_plan_edit')) {
        $table['columns'][] = array('title'=>'Name','class'=>'');
        $table['columns'][] = array('title'=>'Price','class'=>'');
        $table['columns'][] = array('title'=>'Active','class'=>'');
        $table['columns'][] = array('title'=>'Voting','class'=>'');
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    
    // Loop through plan data
    foreach ($plans as $plan) {
        
        // Add plan data to table
        $row = array();
        if (user_access('member_plan_edit')) {
            
            // Add cells
            $row[] = $plan['name'];
            $row[] = $plan['price'];
            $row[] = $plan['active'] ? 'Yes' : 'No';
            $row[] = $plan['voting'] ? 'Yes' : 'No';
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (user_access('member_plan_edit')) {
            $ops[] = '<a href=' . crm_url('plan&pid=' . $plan['pid'] . '&tab=edit') . '>edit</a>';
        }
        
        // Add delete op
        if (user_access('member_plan_edit')) {
            $ops[] = '<a href=' . crm_url('delete&type=member_plan&amp;id=' . $plan['pid']) . '>delete</a>';
        }
        
        // Add ops row
        if (user_access('member_plan_edit')) {
            $row[] = join(' ', $ops);
        }
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return a table structure representing a member's past and current memberships.
 *
 * @param $opts Options to pass to member_membership_data().
 * @return The table structure.
*/
function member_membership_table ($opts = NULL) {
    // Ensure user is allowed to view members
    if (!user_access('member_membership_view')) {
        return NULL;
    }
    // Get member data
    $memberships = member_membership_data($opts);
    // Create table structure
    $table = array(
        'id' => '',
        'class' => '',
        'rows' => array()
    );
    // Add columns
    $table['columns'] = array();
    if (user_access('member_membership_view')) {
        $table['columns'][] = array('title'=>'Start','class'=>'');
        $table['columns'][] = array('title'=>'End','class'=>'');
        $table['columns'][] = array('title'=>'Plan','class'=>'');
        $table['columns'][] = array('title'=>'Price','class'=>'');
    }
    // Add ops column
    if (user_access('member_membership_edit')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }
    // Loop through membership data
    foreach ($memberships as $membership) {
        // Add user data
        $row = array();
        if (user_access('member_membership_view')) {
            $row[] = $membership['start'];
            $row[] = $membership['end'];
            $row[] = $membership['plan']['name'];
            $row[] = $membership['plan']['price'];
        }
        // Construct ops array
        $ops = array();
        // Add delete op
        if (user_access('member_membership_edit')) {
            $ops[] = '<a href=' . crm_url('membership&sid=' . $membership['sid'] . '&tab=edit') . '>edit</a>';
            $ops[] = '<a href=' . crm_url('delete&type=member_membership&amp;id=' . $membership['sid']) . '>delete</a>';
        }
        // Add ops row
        if (!empty($ops)) {
            $row[] = join(' ', $ops);
        }
        // Add row to table
        $table['rows'][] = $row;
    }
    // Return table
    return $table;
}

/**
 * Return a table structure representing contact info.
 *
 * @param $opts Options to pass to member_contact_data().
 * @return The table structure.
*/
function member_contact_table ($opts) {
    
    // Get contact data
    $data = member_contact_data($opts);
    $contact = $data[0];
    if (empty($contact) || count($contact) < 1) {
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
    $table['columns'][] = array("title"=>'Name', 'class'=>'', 'id'=>'');
    $table['columns'][] = array("title"=>'Email', 'class'=>'', 'id'=>'');
    $table['columns'][] = array("title"=>'Phone', 'class'=>'', 'id'=>'');
    $table['columns'][] = array("title"=>'Emergency contact', 'class'=>'', 'id'=>'');
    $table['columns'][] = array("title"=>'Emergency phone', 'class'=>'', 'id'=>'');
    
    // Add row
    $table['rows'][] = array(
        theme('contact_name', $contact),
        $contact['email'],
        $contact['phone'],
        $contact['emergencyName'],
        $contact['emergencyPhone']
    );
    
    return $table;
}

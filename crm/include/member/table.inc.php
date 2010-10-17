<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Return table structure representing members
 *
*/
function member_table ($opts = NULL) {
    
    // Ensure user is allowed to view members
    if (!user_access('member_view')) {
        return NULL;
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
        $table['columns'][] = array('title'=>'Name','class'=>'');
        $table['columns'][] = array('title'=>'Membership','class'=>'');
        $table['columns'][] = array('title'=>'E-Mail','class'=>'');
        $table['columns'][] = array('title'=>'Phone','class'=>'');
        $table['columns'][] = array('title'=>'Emergency Contact','class'=>'');
        $table['columns'][] = array('title'=>'Emergency Phone','class'=>'');
    }
    // Add ops column
    if (user_access('member_edit') || user_access('member_delete')) {
        $table['columns'][] = array('title'=>'Ops','class'=>'');
    }

    // Loop through member data
    foreach ($members as $member) {
        
        // Add user data
        $row = array();
        if (user_access('member_view')) {
            
            // Construct name
            $name = $member['contact']['lastName'] . ", ";
            $name .= $member['contact']['firstName'];
            if (!empty($member['contact']['middleName'])) {
                $name .= ' ' . $member['contact']['middleName'];
            }
            
            // Construct membership info
            $recentMembership = end($member['membership']);
            $plan = '';
            if (!empty($recentMembership) && empty($recentMembership['end'])) {
                $plan = $recentMembership['plan']['name'];
            }
            
            // Add cells
            $row[] = $name;
            $row[] = $plan;
            $row[] = $member['contact']['email'];
            $row[] = $member['contact']['phone'];
            $row[] = $member['contact']['emergencyName'];
            $row[] = $member['contact']['emergencyPhone'];
        }
        
        // Construct ops array
        $ops = array();
        
        // Add edit op
        if (user_access('member_edit')) {
            $ops[] = '<a href="member.php?mid=' . $member['mid'] . '">edit</a> ';
        }
        
        // Add delete op
        if (user_access('member_delete')) {
            $ops[] = '<a href="delete.php?type=member&id=' . $member['mid'] . '">delete</a>';
        }
        
        // Add ops row
        $row[] = join(' ', $ops);
        
        // Add row to table
        $table['rows'][] = $row;
    }
    
    // Return table
    return $table;
}

/**
 * Return voting member report table
*/
function member_voting_report_table () {
    
    // Ensure user is allowed to view members
    if (!user_access('member_view')) {
        return NULL;
    }
    
    // Get member data
    $members = member_data(array('filter'=>array(array('voting'))));
    
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
 * Return table structure representing memberships
 *
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
            $ops[] = '<a href="delete.php?type=member_membership&id=' . $membership['sid'] . '">delete</a>';
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
 * Return contact table structure
 *
 * @param $opts Associative array of options
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
        member_name($contact['firstName'], $contact['middleName'], $contact['lastName']),
        $contact['email'],
        $contact['phone'],
        $contact['emergencyName'],
        $contact['emergencyPhone']
    );
    
    return $table;
}

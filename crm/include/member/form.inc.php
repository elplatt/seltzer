<?php 

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    form.inc.php - Member module - form structures

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
 * @return The form structure for adding a member.
*/
function member_add_form () {
    
    // Ensure user is allowed to view members
    if (!user_access('member_add')) {
        return NULL;
    }
    
    // Generate default start date, first of current month
    $start = date("Y-m-01");
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Member',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'First Name',
                        'name' => 'firstName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Middle Name',
                        'name' => 'middleName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Last Name',
                        'name' => 'lastName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Email',
                        'name' => 'email'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Phone',
                        'name' => 'phone'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Contact',
                        'name' => 'emergencyName'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Phone',
                        'name' => 'emergencyPhone'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Username',
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Plan',
                        'name' => 'pid',
                        'selected' => $member['plan']['pid'],
                        'options' => member_plan_options(array('filter'=>array(array('active'))))
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start Date',
                        'name' => 'start',
                        'value' => $start,
                        'class' => 'date'
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
 * Return the form structure for adding a membership.
 *
 * @param cid the cid of the member to add a membership for.
 * @return The form structure.
*/
function member_membership_add_form ($cid) {
    
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_membership_add',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Membership',
                'fields' => array(
                    array(
                        'type' => 'select',
                        'label' => 'Plan',
                        'name' => 'pid',
                        'options' => member_plan_options()
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start',
                        'name' => 'start',
                        'class' => 'date'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'End',
                        'name' => 'end',
                        'class' => 'date'
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
 * Return the form structure to delete a member.
 *
 * @param $cid The cid of the member to delete.
 * @return The form structure.
*/
function member_delete_form ($cid) {
    
    // Ensure user is allowed to delete members
    if (!user_access('member_delete')) {
        return NULL;
    }
    
    // Get member data
    $data = member_data(array('cid'=>$cid));
    $member = $data[0];
    
    // Construct member name
    if (empty($member) || count($member) < 1) {
        return array();
    }
    $member_name = $member['contact']['firstName'];
    if (!empty($member['contact']['middleName'])) {
        $member_name .= ' ' . $member['contact']['middleName'];
    }
    $member_name .= ' ' . $member['contact']['lastName'];
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_delete',
        'hidden' => array(
            'cid' => $member['contact']['cid'],
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Member',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the member "' . $member_name . '"? This cannot be undone.',
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Also delete user?',
                        'name' => 'deleteUser'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Also delete contact info?',
                        'name' => 'deleteContact'
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
 * Return the form structure to delete a membership.
 *
 * @param $sid id of the membership to delete.
 * @return The form structure.
*/
function member_membership_delete_form ($sid) {
    
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return NULL;
    }
    
    // Get membership data
    $data = member_membership_data(array('sid'=>$sid));
    $membership = $data[0];
    
    // Construct member name
    /* TODO
    if (empty($member) || count($member) < 1) {
        return array();
    }
    $member_name = $member['contact']['firstName'];
    if (!empty($member['contact']['middleName'])) {
        $member_name .= ' ' . $member['contact']['middleName'];
    }
    $member_name .= ' ' . $member['contact']['lastName'];
    */
    
    // Construct membership name
    $membership_name = "user:$membership[cid] $membership[start] -- $membership[end]";
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_membership_delete',
        'hidden' => array(
            'sid' => $membership['sid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Membership',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the membership "' . $membership_name . '"? This cannot be undone.',
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
 * Return the form structure for editing a contact.
 *
 * @param $cid id of the contact to edit.
 * @return The form structure.
*/
function member_contact_edit_form ($cid) {
    
    // Ensure user is allowed to edit contacts
    if (!user_access('contact_edit')) {
        return NULL;
    }
    
    // Get contact data
    $data = member_contact_data(array('cid'=>$cid));
    $contact = $data[0];
    if (empty($contact) || count($contact) < 1) {
        return array();
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'contact_update',
        'hidden' => array(
            'cid' => $cid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Contact Info',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'First Name',
                        'name' => 'firstName',
                        'value' => $contact['firstName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Middle Name',
                        'name' => 'middleName',
                        'value' => $contact['middleName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Last Name',
                        'name' => 'lastName',
                        'value' => $contact['lastName'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Email',
                        'name' => 'email',
                        'value' => $contact['email'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Phone',
                        'name' => 'phone',
                        'value' => $contact['phone'],
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Contact',
                        'name' => 'emergencyName',
                        'value' => $contact['emergencyName']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Emergency Phone',
                        'name' => 'emergencyPhone',
                        'value' => $contact['emergencyPhone']
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Update'
                    )
                )
            )
        )
    );
    
    return $form;
}

// Filters

/**
 * Return the form structure for a member filter.
 * 
 * @return The form structure.
*/
function member_filter_form () {

    // Available filters    
    $filters = array(
        'all' => 'All',
        'voting' => 'Voting',
        'active' => 'Active'
    );
    
    // Default filter
    $selected = empty($_SESSION['member_filter_option']) ? 'all' : $_SESSION['member_filter_option'];
    
    // Construct hidden fields to pass GET params
    $hidden = array();
    foreach ($_GET as $key => $val) {
        $hidden[$key] = $val;
    }
    
    $form = array(
        'type' => 'form',
        'method' => 'get',
        'command' => 'member_filter',
        'hidden' => $hidden,
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Filter',
                'fields' => array(
                    array(
                        'type' => 'select',
                        'name' => 'filter',
                        'options' => $filters,
                        'selected' => $selected
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Filter'
                    )
                )
            )
        )
    );
    
    return $form;
}

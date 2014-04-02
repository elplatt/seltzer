<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
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

// Members /////////////////////////////////////////////////////////////////////

/**
 * @return The form structure for adding a member.
*/
function member_add_form () {
    
    // Ensure user is allowed to add members
    if (!user_access('member_add')) {
        error_register('Permission denied: member_add');
        return NULL;
    }
    
    // Start with contact form
    $form = crm_get_form('contact');
    
    // Generate default start date, first of current month
    $start = date("Y-m-d");
    
    // Change form command
    $form['command'] = 'member_add';
    
    // Add member data
    $form['fields'][] = array(
        'type' => 'fieldset',
        'label' => 'User Info',
        'fields' => array(
            array(
                'type' => 'text',
                'label' => 'Username',
                'name' => 'username'
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset',
        'label' => 'Membership Info',
        'fields' => array(
            array(
                'type' => 'select',
                'label' => 'Plan',
                'name' => 'pid',
                'selected' => '',
                'options' => member_plan_options(array('filter'=>array('active'=>true)))
            ),
            array(
                'type' => 'text',
                'label' => 'Start Date',
                'name' => 'start',
                'value' => $start,
                'class' => 'date'
            )
        )
    );
    
    return $form;
}

// Plans ///////////////////////////////////////////////////////////////////////

/**
 * @return The form structure for adding a membership plan.
*/
function member_plan_add_form () {
    
    // Ensure user is allowed to edit plans
    if (!user_access('member_plan_edit')) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_plan_add',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Add Membership Plan',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Name',
                        'name' => 'name'
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Price',
                        'name' => 'price'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Voting',
                        'name' => 'voting'
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Active',
                        'name' => 'active',
                        'checked' => true
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
 * Returns the form structure for editing a membership plan.
 *
 * @param $pid The pid of the membership plan to edit.
 * @return The form structure.
*/
function member_plan_edit_form ($pid) {
    
    // Ensure user is allowed to edit plans
    if (!user_access('member_plan_edit')) {
        return NULL;
    }
    
    // Get plan data
    $plans = member_plan_data(array('pid'=>$pid));
    $plan = $plans[0];
    if (!$plan) {
        return NULL;
    }
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_plan_update',
        'hidden' => array(
            'pid' => $pid
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Membership Plan',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Name',
                        'name' => 'name',
                        'value' => $plan['name']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Price',
                        'name' => 'price',
                        'value' => $plan['price']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Voting',
                        'name' => 'voting',
                        'checked' => $plan['voting']
                    ),
                    array(
                        'type' => 'checkbox',
                        'label' => 'Active',
                        'name' => 'active',
                        'checked' => $plan['active']
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

/**
 * Return the form structure to delete a membership plan.
 *
 * @param $pid The pid of the plan to delete.
 * @return The form structure.
*/
function member_plan_delete_form ($pid) {
    
    // Ensure user is allowed to edit plans
    if (!user_access('member_plan_edit')) {
        return NULL;
    }
    
    // Get plan description
    $description = theme('member_plan_description', $pid);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_plan_delete',
        'hidden' => array(
            'pid' => $pid,
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Delete Plan',
                'fields' => array(
                    array(
                        'type' => 'message',
                        'value' => '<p>Are you sure you want to delete the plan "' . $description. '"? This cannot be undone.',
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

// Memberships /////////////////////////////////////////////////////////////////

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
                        'options' => member_plan_options(array('filter'=>array('active'=>true)))
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
 * Return the form structure for editing a membership.
 *
 * @param $sid id of the membership to edit.
 * @return The form structure.
*/
function member_membership_edit_form ($sid) {
    
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return NULL;
    }
    
    // Get membership data
    $data = member_membership_data(array('sid'=>$sid));
    $membership = $data[0];
    if (empty($membership) || count($membership) < 1) {
        return array();
    }
    
    // Construct contact name
    $data = member_contact_data(array('cid'=>$membership['cid']));
    $contact = $data[0];
    $name = theme_contact_name($contact['cid']);
    
    // Create form structure
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'member_membership_update',
        'hidden' => array(
            'sid' => $sid,
            'cid' => $membership['cid']
        ),
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Edit Membership Info',
                'fields' => array(
                    array(
                        'type' => 'readonly',
                        'label' => 'Name',
                        'value' => $name
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Plan',
                        'name' => 'pid',
                        'options' => member_plan_options(),
                        'selected' => $membership['pid']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'Start',
                        'name' => 'start',
                        'class' => 'date',
                        'value' => $membership['start']
                    ),
                    array(
                        'type' => 'text',
                        'label' => 'End',
                        'name' => 'end',
                        'class' => 'date',
                        'value' => $membership['end']
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

// Filters /////////////////////////////////////////////////////////////////////

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

// Imports /////////////////////////////////////////////////////////////////////

/**
 * @return the form structure for a member import form.
*/
function member_import_form () {
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'member_import'
        , 'fields' => array(
            array(
                'type' => 'message'
                , 'value' => '<p>To import members, upload a csv.  The csv should have a header row with the following fields:</p><ul><li>First Name</li><li>Middle Name</li><li>Last Name</li><li>Email</li><li>Phone</li><li>Emergency Name</li><li>Emergency Phone</li><li>Username</li><li>Plan</li><li>Start Date</li></ul>'
            )
            , array(
                'type' => 'file'
                , 'label' => 'CSV File'
                , 'name' => 'member-file'
            )
            , array(
                'type' => 'submit'
                , 'value' => 'Import'
            )
        )
    );
}

/**
 * @return the form structure for a plan import form.
*/
function plan_import_form () {
    return array(
        'type' => 'form'
       , 'method' => 'post'
        , 'enctype' => 'multipart/form-data'
        , 'command' => 'member_plan_import'
        , 'fields' => array(
            array(
                'type' => 'message'
                , 'value' => '<p>To import plans, upload a csv.  The csv should have a header row with the following fields:</p><ul><li>Plan Name</li><li>Price</li><li>Active (Set to 1/0 signalling Y/N)</li><li>Voting (Set to 1/0 signalling Y/N)</li></ul>'
            )
            , array(
                'type' => 'file'
                , 'label' => 'CSV File'
                , 'name' => 'plan-file'
            )
            , array(
                'type' => 'submit'
                , 'value' => 'Import'
            )
        )
    );
}
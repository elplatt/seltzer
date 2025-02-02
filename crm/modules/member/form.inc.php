<?php

/*
    Copyright 2009-2025 Edward L. Platt <ed@elplatt.com>
    
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
    // Start with contact form
    $form = crm_get_form('contact');
    // Generate default start date, first of current month
    $start = date("Y-m-d");
    // Change form command
    $form['command'] = 'member_add';
    // Add member data
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'User Info'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Username'
                , 'name' => 'username'
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Member Emergency Contact Details'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Emergency Contact'
                , 'name' => 'emergencyName'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Phone'
                , 'name' => 'emergencyPhone'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Relation'
                , 'name' => 'emergencyRelation'
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Member Address Details'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Address 1'
                , 'name' => 'address1'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 2'
                , 'name' => 'address2'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 3'
                , 'name' => 'address3'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Town/City'
                , 'name' => 'town_city'
            )
            , array(
                'type' => 'text'
                , 'label' => 'Zip/Postal Code'
                , 'name' => 'zipcode'
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Membership Details'
        , 'fields' => array(
            array(
                'type' => 'select'
                , 'label' => 'Plan'
                , 'name' => 'pid'
                , 'selected' => ''
                , 'options' => member_plan_options(array('filter'=>array('active'=>true)))
            )
            , array(
                'type' => 'text'
                , 'label' => 'Start Date'
                , 'name' => 'start'
                , 'value' => $start
                , 'class' => 'date'
            )
        )
    );
    return $form;
}

/**
 * @return The form structure for editing a member.
 */
function member_edit_form ($cid) {
    // Create form
    if ($cid) {
        $member_data = crm_get_data('member', array('cid'=>$cid));
        $member = $member_data[0]['member'];
    }
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_edit'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array()
        , 'submit' => 'Update'
    );
    // Edit member data
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Edit Member Emergency Contact Details'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Emergency Contact'
                , 'name' => 'emergencyName'
                , 'value' => $member['emergencyName']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Phone'
                , 'name' => 'emergencyPhone'
                , 'value' => $member['emergencyPhone']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Emergency Relation'
                , 'name' => 'emergencyRelation'
                , 'value' => $member['emergencyRelation']
            )
        )
    );
    $form['fields'][] = array(
        'type' => 'fieldset'
        , 'label' => 'Edit Member Address Details'
        , 'fields' => array(
            array(
                'type' => 'text'
                , 'label' => 'Address 1'
                , 'name' => 'address1'
                , 'value' => $member['address1']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 2'
                , 'name' => 'address2'
                , 'value' => $member['address2']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Address 3'
                , 'name' => 'address3'
                , 'value' => $member['address3']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Town/City'
                , 'name' => 'town_city'
                , 'value' => $member['town_city']
            )
            , array(
                'type' => 'text'
                , 'label' => 'Zip/Postal Code'
                , 'name' => 'zipcode'
                , 'value' => $member['zipcode']
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
        return null;
    }
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_plan_add'
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Add Membership Plan'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Name'
                        , 'name' => 'name'
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Price'
                        , 'name' => 'price'
                    )
                    , array(
                        'type' => 'checkbox'
                        , 'label' => 'Voting'
                        , 'name' => 'voting'
                    )
                    , array(
                        'type' => 'checkbox'
                        , 'label' => 'Active'
                        , 'name' => 'active'
                        , 'checked' => true
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
 * Returns the form structure for editing a membership plan.
 * @param $pid The pid of the membership plan to edit.
 * @return The form structure.
 */
function member_plan_edit_form ($pid) {
    // Ensure user is allowed to edit plans
    if (!user_access('member_plan_edit')) {
        return null;
    }
    // Get plan data
    $plans = member_plan_data(array('pid'=>$pid));
    $plan = $plans[0];
    if (!$plan) {
        return null;
    }
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_plan_update'
        , 'hidden' => array(
            'pid' => $pid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Edit Membership Plan'
                , 'fields' => array(
                    array(
                        'type' => 'text'
                        , 'label' => 'Name'
                        , 'name' => 'name'
                        , 'value' => $plan['name']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Price'
                        , 'name' => 'price'
                        , 'value' => $plan['price']
                    )
                    , array(
                        'type' => 'checkbox'
                        , 'label' => 'Voting'
                        , 'name' => 'voting'
                        , 'checked' => $plan['voting']
                    )
                    , array(
                        'type' => 'checkbox'
                        , 'label' => 'Active'
                        , 'name' => 'active'
                        , 'checked' => $plan['active']
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Update'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return the form structure to delete a membership plan.
 * @param $pid The pid of the plan to delete.
 * @return The form structure.
 */
function member_plan_delete_form ($pid) {
    // Ensure user is allowed to edit plans
    if (!user_access('member_plan_edit')) {
        return null;
    }
    // Get plan description
    $description = theme('member_plan_description', $pid);
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_plan_delete'
        , 'hidden' => array(
            'pid' => $pid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Delete Plan'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => '<p>Are you sure you want to delete the plan "' . $description. '"? This cannot be undone.</p>'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Delete'
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
 * @param cid the cid of the member to add a membership for.
 * @return The form structure.
 */
function member_membership_add_form ($cid) {
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return null;
    }
    // Generate default start date, first of current month
    $start = date("Y-m-d");
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_membership_add'
        , 'hidden' => array(
            'cid' => $cid
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Add Membership'
                , 'fields' => array(
                    array(
                        'type' => 'select'
                        , 'label' => 'Plan'
                        , 'name' => 'pid'
                        , 'options' => member_plan_options(array('filter'=>array('active'=>true)))
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Start'
                        , 'name' => 'start'
                        , 'class' => 'date'
                        , 'value' => $start
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'End'
                        , 'name' => 'end'
                        , 'class' => 'date'
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
 * Return the form structure for editing a membership.
 * @param $sid id of the membership to edit.
 * @return The form structure.
 */
function member_membership_edit_form ($sid) {
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return null;
    }
    // Get membership data
    $data = member_membership_data(array('sid'=>$sid));
    $membership = $data[0];
    if (empty($membership) || count($membership) < 1) {
        return array();
    }
    // Construct contact name
    $data = member_data(array('cid'=>$membership['cid']));
    $contact = $data[0];
    $name = theme_contact_name($contact['cid']);
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_membership_update'
        , 'hidden' => array(
            'sid' => $sid
            , 'cid' => $membership['cid']
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Edit Membership Info'
                , 'fields' => array(
                    array(
                        'type' => 'readonly'
                        , 'label' => 'Name'
                        , 'value' => $name
                    )
                    , array(
                        'type' => 'select'
                        , 'label' => 'Plan'
                        , 'name' => 'pid'
                        , 'options' => member_plan_options()
                        , 'selected' => $membership['pid']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'Start'
                        , 'name' => 'start'
                        , 'class' => 'date'
                        , 'value' => $membership['start']
                    )
                    , array(
                        'type' => 'text'
                        , 'label' => 'End'
                        , 'name' => 'end'
                        , 'class' => 'date'
                        , 'value' => $membership['end']
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Update'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return the form structure to delete a membership.
 * @param $sid id of the membership to delete.
 * @return The form structure.
 */
function member_membership_delete_form ($sid) {
    // Ensure user is allowed to edit memberships
    if (!user_access('member_membership_edit')) {
        return null;
    }
    // Get membership data
    $membership = (member_membership_data(array('sid'=>$sid))[0]);
    $contact = (contact_data(array('cid'=>$membership['cid']))[0]);
    // Construct membership name
    $membership_name = "user: " . theme('contact_name', $contact, true) . " $membership[start] -- $membership[end]";
    // Create form structure
    $form = array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_membership_delete'
        , 'hidden' => array(
            'sid' => $membership['sid']
            , 'cid' => $membership['cid']
        )
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Delete Membership'
                , 'fields' => array(
                    array(
                        'type' => 'message'
                        , 'value' => '<p>Are you sure you want to delete the membership "' . $membership_name . '"? This cannot be undone.</p>'
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Delete'
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
 * @return The form structure.
 */
function member_filter_form () {
    // Available filters
    $filters = array(
        'all' => 'All'
        , 'active' => 'Active'
        , 'voting' => 'Voting'
    );
    // Default filter
    $selected = empty($_SESSION['member_filter_option']) ? 'all' : $_SESSION['member_filter_option'];
    // Construct hidden fields to pass GET params
    $hidden = array();
    foreach ($_GET as $key => $val) {
        $hidden[$key] = $val;
    }
    $form = array(
        'type' => 'form'
        , 'method' => 'get'
        , 'command' => 'member_filter'
        , 'hidden' => $hidden
        , 'fields' => array(
            array(
                'type' => 'fieldset'
                , 'label' => 'Filter'
                , 'fields' => array(
                    array(
                        'type' => 'select'
                        , 'name' => 'filter'
                        , 'options' => $filters
                        , 'selected' => $selected
                    )
                    , array(
                        'type' => 'submit'
                        , 'value' => 'Filter'
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
                , 'value' => '<p>To import members, upload a csv.  The csv should have a header row with the following fields:</p>
                <ul>
                <li>First Name</li>
                <li>Middle Name</li>
                <li>Last Name</li>
                <li>Email</li>
                <li>Phone</li>
                <li>Address 1</li>
                <li>Address 2</li>
                <li>Address 3</li>
                <li>Town/City</li>
                <li>Zip/Postal Code</li>
                <li>Emergency Name</li>
                <li>Emergency Phone</li>
                <li>Emergency Relation</li>
                <li>Username</li>
                <li>Plan</li>
                <li>Start Date</li>
                </ul>'
            )
            , array(
                'type' => 'file'
                , 'label' => 'CSV File'
                , 'name' => 'member-file'
            )
            , array(
                'type' => 'checkbox'
                , 'label' => 'Create plans from file'
                , 'name' => 'create-plan'
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
                , 'value' => '<p>To import plans, upload a csv.  The csv should have a header row with the following fields:</p>
                <ul>
                <li>Plan Name</li>
                <li>Price</li>
                <li>Active (Set to 1/0 signalling Y/N)</li>
                <li>Voting (Set to 1/0 signalling Y/N)</li>
                </ul>'
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

/**
 * @return the form structure for member renotify form.
 */
function member_renotify_form () {
    global $db_connect;
    $sql = "
        SELECT *
        FROM `user` 
        JOIN `contact` USING(`cid`)
        ORDER BY firstName, LastName
    ";
    $res = mysqli_query($db_connect, $sql);
    if (!$res) crm_error(mysqli_error($res));
    $fields = array();
    while($user_row = mysqli_fetch_assoc($res)) {
        array_push($fields,array(
            'type' => 'checkbox'
            , 'label' => "${user_row['firstName']} ${user_row['lastName']} (${user_row['username']}) [${user_row['email']}]"
            , 'name' => 'cid[]'
            , 'value' => $user_row['cid']
        ));
    }
    array_push($fields, array(
        'type' => 'submit'
        , 'value' => 'Renotify'
    ));
    return array(
        'type' => 'form'
        , 'method' => 'post'
        , 'command' => 'member_renotify'
        , 'fields' => $fields
    );
}

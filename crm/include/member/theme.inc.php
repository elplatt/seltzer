<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Member module - theming

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
 * Return the themed html for a table of members.
 *
 * @param $opts Options to pass to member_data().
 * @return The themed html string.
*/
function theme_member_table ($opts = NULL) {
    return theme('table', member_table($opts));
}

/**
 * Return the themed html for a single member's contact info.
 * 
 * @param $opts The options to pass to member_contact_data().
 * @return The themed html string.
*/
function theme_member_contact_table ($opts = NULL) {
    return theme('table_vertical', member_contact_table($opts));
}

/**
 * @return The themed html for an add member form.
*/
function theme_member_add_form () {
    return theme('form', member_add_form());
}

/**
 * Returned the themed html for edit member form.
 *
 * @param $cid The cd of the member to edit.
 * @return The themed html.
*/
function theme_member_edit_form ($cid) {
    return theme('form', member_edit_form($cid));
}

/**
 * Return the themed html for an edit contact form.
 *
 * @param $cid The cid of the contact to edit.
 * @return The themed html string.
*/
function theme_member_contact_edit_form ($cid) {
    return theme('form', member_contact_edit_form($cid));
}

/**
 * @return The themed html for a member filter form.
*/
function theme_member_filter_form () {
    return theme('form', member_filter_form());
}

/**
 * @return The themed html for a member voting report.
*/
function theme_member_voting_report () {
    return theme('table', member_voting_report_table());
}

/**
 * @return The themed html for an active member email report.
*/
function theme_member_email_report ($opts) {
    $output = '<div class="member-email-report">';
    $title = '';
    if (isset($opts['filter']) && isset($opts['filter']['active'])) {
        $title = $opts['filter']['active'] ? 'Active ' : 'Lapsed ';
    }
    $title .= 'Email Report';
    $output .= "<h2>$title</h2>";
    $output .= '<textarea rows="10" cols="80">';
    $output .= member_email_report($opts);
    $output .= '</textarea>';
    $output .= '</div>';
    return $output;
}

/**
 * Return the themed html for a membership table.
 *
 * @param $opts The options to pass to member_membership_data().
 * @return The themed html string.
 */
function theme_member_membership_table ($opts = NULL) {
    return theme('table', member_membership_table($opts));
}

/**
 * Return the themed html for a membership add form.
 *
 * @param $cid the cid of the member who owns the membership
 * @return The themed html string.
 */
function theme_member_membership_add_form ($cid) {
    return theme('form', member_membership_add_form($cid));
}

/**
 * Return the themed html for a membership edit form.
 *
 * @param $sid The sid of the membership to edit.
 * @return The themed html string.
*/
function theme_member_membership_edit_form ($sid) {
    return theme('form', member_membership_edit_form($sid));
}

/**
 * Return the themed html for a membership plan table.
 *
 * @param $opts The options to pass to member_plan_data().
 * @return The themed html string.
 */
function theme_member_plan_table ($opts = NULL) {
    return theme('table', member_plan_table($opts));
}

/**
 * Return the themed html for a membership plan add form.
 *
 * @return The themed html string.
 */
function theme_member_plan_add_form () {
    return theme('form', member_plan_add_form());
}

/**
 * Return the themed html for a membership plan edit form.
 *
 * @param $pid The pid of the membership plan to edit.
 * @return The themed html string.
*/
function theme_member_plan_edit_form ($pid) {
    return theme('form', member_plan_edit_form($pid));
}

/**
 * Return the themed html for a contact's name.
 *
 * @param $cid The cid of the contact.
 * @return The themed html string.
 */
function theme_member_contact_name ($cid) {
    
    // Get member data
    $data = member_data(array('cid' => $cid));
    if (count($data) < 1) {
        return '';
    }
    
    $output = member_name(
        $data[0]['contact']['firstName']
        , $data[0]['contact']['middleName']
        , $data[0]['contact']['lastName']);
    
    return $output;
}

/**
 * Return the themed html description for a plan.
 *
 * @param $pid The pid of the plan.
 * @return The themed html string.
 */
function theme_member_plan_description ($pid) {
    
    // Get plan data
    $data = member_plan_data(array('pid' => $pid));
    if (count($data) < 1) {
        return '';
    }
    
    $output = $data[0]['name'] . ' : ' . $data[0]['price'];
    
    return $output;
}

/**
 * Return the text of an email notifying administrators that a user has been created.
 * @param $cid The contact id of the new member.
 */
function theme_member_created_email ($cid) {
    
    // Get info on the logged in user
    $data = member_contact_data(array('cid'=>user_id()));
    $admin = $data[0];
    $adminName = member_name($admin['firstName'], $admin['middleName'], $admin['lastName']);
    
    // Get info on member
    $data = member_data(array('cid'=>$cid));
    $member = $data[0];
    $contact = $member['contact'];
    $name = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    
    // Get info on member's plan
    $data = member_membership_data(array('cid'=>$cid, $filter=>array('active'=>true)));
    $date = $data[0]['start'];
    $plan = $data[0]['plan']['name'];
    
    $output = "<p>Contact info:<br/>\n";
    $output .= "Name: $name<br/>\n";
    $output .= "Email: $contact[email]<br/>\n";
    $output .= "Phone: $contact[phone]\n</p>\n";
    $output .= "<p>Membership info:<br/>\n";
    $output .= "Plan: $plan<br/>\n";
    $output .= "Start date: $date\n</p>\n";
    $output .= "<p>Entered by: $adminName</p>\n";
    
    return $output;
}

/**
 * Return the text of an email welcoming a new member.
 * @param $cid The contact id of the new member.
 * @param $confirm_url The url for the new user to confirm their email.
 */
function theme_member_welcome_email ($cid, $confirm_url) {
    $vars = array(
        'type' => 'welcome'
        , 'confirm_url' => $confirm_url
    );
    return template_render('email', $vars);
}

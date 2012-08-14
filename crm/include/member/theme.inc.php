<?php 

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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
    return theme_table(member_table($opts));
}

/**
 * Return the themed html for a single member's contact info.
 * 
 * @param $opts The options to pass to member_contact_data().
 * @return The themed html string.
*/
function theme_member_contact_table ($opts = NULL) {
    return theme_table_vertical(member_contact_table($opts));
}

/**
 * @return The themed html for an add member form.
*/
function theme_member_add_form () {
    return theme_form(member_add_form());
}

/**
 * Returned the themed html for edit member form.
 *
 * @param $cid The cd of the member to edit.
 * @return The themed html.
*/
function theme_member_edit_form ($cid) {
    return theme_form(member_edit_form($cid));
}

/**
 * Return the themed html for an edit contact form.
 *
 * @param $cid The cid of the contact to edit.
 * @return The themed html string.
*/
function theme_member_contact_edit_form ($cid) {
    return theme_form(member_contact_edit_form($cid));
}

/**
 * @return The themed html for a member filter form.
*/
function theme_member_filter_form () {
    return theme_form(member_filter_form());
}

/**
 * @return The themed html for a member voting report.
*/
function theme_member_voting_report () {
    return theme_table(member_voting_report_table());
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
    return theme_table(member_membership_table($opts));
}

/**
 * Return the themed html for a membership add form.
 *
 * @param $cid the cid of the member who owns the membership
 * @return The themed html string.
 */
function theme_member_membership_add_form ($cid) {
    return theme_form(member_membership_add_form($cid));
}

/**
 * Return the themed html for a membership edit form.
 *
 * @param $sid The sid of the membership to edit.
 * @return The themed html string.
*/
function theme_member_membership_edit_form ($sid) {
    return theme_form(member_membership_edit_form($sid));
}

/**
 * Return the themed html for a membership plan table.
 *
 * @param $opts The options to pass to member_plan_data().
 * @return The themed html string.
 */
function theme_member_plan_table ($opts = NULL) {
    return theme_table(member_plan_table($opts));
}

/**
 * Return the themed html for a membership plan add form.
 *
 * @return The themed html string.
 */
function theme_member_plan_add_form () {
    return theme_form(member_plan_add_form());
}

/**
 * Return the themed html for a membership plan edit form.
 *
 * @param $pid The pid of the membership plan to edit.
 * @return The themed html string.
*/
function theme_member_plan_edit_form ($pid) {
    return theme_form(member_plan_edit_form($pid));
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

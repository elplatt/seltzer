<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Return themed html for a table of members
*/
function theme_member_table ($opts = NULL) {
    return theme_table(member_table($opts));
}

/**
 * Return themed html for a single members contact info
*/
function theme_member_contact_table ($opts = NULL) {
    return theme_table_vertical(member_contact_table($opts));
}

/**
 * Returned themed html for add member form.
*/
function theme_member_add_form () {
    return theme_form(member_add_form());
}

/**
 * Returned themed html for edit member form.
 *
 * @param $mid The id of the member to edit
*/
function theme_member_edit_form ($mid) {
    return theme_form(member_edit_form($mid));
}

/**
 * Returned themed html for edit contact form.
 *
 * @param $cid The id of the contact to edit
*/
function theme_member_contact_edit_form ($cid) {
    return theme_form(member_contact_edit_form($cid));
}

/**
 * Return themed html for a member filter form
*/
function theme_member_filter_form () {
    return theme_form(member_filter_form());
}

/**
 * Return themed html for a member votin report
*/
function theme_member_voting_report () {
    return theme_table(member_voting_report_table());
}

/**
 * Return themed html for a membership table
 */
function theme_member_membership_table ($opts = NULL) {
    return theme_table(member_membership_table($opts));
}

/**
 * Return themed html for a membership add form
 *
 * @param $mid the member id of the member who owns the membership
 */
function theme_member_membership_add_form ($mid) {
    return theme_form(member_membership_add_form($mid));
}
<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    page.inc.php - Member module - tabbed page structures

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
 * @return An array of pages provided by this module.
 */
function member_page_list () {
    $pages = array();
    if (user_access('member_view')) {
        $pages[] = 'members';
        $pages[] = 'member';
    }
    if (user_access('member_plan_edit')) {
        $pages[] = 'plans';
        $pages[] = 'plan';
    }
    if (user_access('member_membership_edit') && user_access('member_edit')) {
        $pages[] = 'membership';
    }
    return $pages;
}

/**
 * Page hook.  Adds member module content to a page before it is rendered.
 *
 * @param &$page_data Reference to data about the page being rendered.
 * @param $page_name The name of the page being rendered.
 * @param $options The array of options passed to theme('page').
*/
function member_page (&$page_data, $page_name, $options) {
    
    switch ($page_name) {
        
        case 'members':
            
            // Set page title
            page_set_title($page_data, 'Members');
            
            // Add view tab
            if (user_access('member_view')) {
                $view .= theme('member_filter_form');
                $opts = array(
                    'filter'=>$_SESSION['member_filter']
                    , 'show_export'=>true
                    , 'exclude'=>array('emergencyName', 'emergencyPhone')
                );
                $view .= theme('table', 'member', $opts);
                page_add_content_top($page_data, $view, 'View');
            }
            
            // Add add tab
            if (user_access('member_add')) {
                page_add_content_top($page_data, theme('member_add_form'), 'Add');
            }
            
            // Add import tab
            if (user_access('contact_edit') && user_access('member_edit')) {
                page_add_content_top($page_data, theme('form', member_import_form()), 'Import');
            }
            
            break;
        
        case 'plans':
            
            // Set page title
            page_set_title($page_data, 'Plans');
            
            // Add view, add and import tabs
            if (user_access('member_plan_edit')) {
                page_add_content_top($page_data, theme('table', 'member_plan'), 'View');
                page_add_content_top($page_data, theme('member_plan_add_form'), 'Add');
                page_add_content_top($page_data, theme('form', plan_import_form()), 'Import');
            }
            
            break;
        
        case 'plan':
            
            // Capture plan id
            $pid = $_GET['pid'];
            if (empty($pid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, 'Plan: ' . theme('member_plan_description', $pid));
            
            // Add edit tab
            if (user_access('member_plan_edit')) {
                page_add_content_top($page_data, theme('member_plan_edit_form', $pid), 'Edit');
            }
            
            break;
        
        case 'member':
            
            // Capture member id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, theme('member_contact_name', $cid));
            
            // Add view tab
            $view_content = '';
            if (user_id() == $_GET['cid'] || user_access('member_view')) {
                $view_content .= '<h3>Contact Info</h3>';
                $view_content .= theme('table_vertical', 'member_contact', array('cid' => $cid));
            }
            if (user_id() == $_GET['cid'] || user_access('user_edit')) {
                // TODO this should probably be moved to the user module some refactoring is done. -Ed 2012-10-06
                $view_content .= '<h3>User Info</h3>';
                $view_content .= theme('table_vertical', 'user', array('cid' => $cid));
            }
            if (!empty($view_content)) {
                page_add_content_top($page_data, $view_content, 'View');
            }
            
            // Add edit tab
            if (user_id() == $_GET['cid'] || (user_access('contact_edit') && user_access('member_edit'))) {
                page_add_content_top($page_data, theme('member_contact_edit_form', $cid), 'Edit');
            }
            
            // Add plan and role tabs
            if (user_access('member_membership_edit')) {
                $plan = theme('table', 'member_membership', array('cid' => $cid));
                $plan .= theme('member_membership_add_form', $cid);
                page_add_content_top($page_data, $plan, 'Plan');
                
                $roles = theme('user_role_edit_form', $cid);
                page_add_content_top($page_data, $roles, 'Roles');
            }
            
            break;
        
        case 'membership':
            
            // Capture sid id
            $sid = $_GET['sid'];
            if (empty($sid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, member_membership_description($sid));
            
            // Add edit tab
            if (user_access('member_membership_edit') && user_access('member_edit')) {
                page_add_content_top($page_data, theme('member_membership_edit_form', $sid), 'Edit');
            }
            break;
        
        case 'reports':
            if (user_access('member_view')) {
                $reports = theme('member_email_report', array('filter'=>array('active'=>true)));
                $reports .= theme('member_email_report', array('filter'=>array('active'=>false)));
                //$reports .= theme('member_voting_report');
                page_add_content_bottom($page_data, $reports);
            }
            break;
    }
}


<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
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
                $view = theme('form', crm_get_form('member_filter'));
                $opts = array(
                    'filter'=>$_SESSION['member_filter']
                    , 'show_export'=>true
                    , 'exclude'=>array('emergencyName', 'emergencyPhone')
                );
                $view .= theme('table', crm_get_table('member', $opts));
                page_add_content_top($page_data, $view, 'View');
            }
            
            // Add add tab
            if (user_access('member_add')) {
                page_add_content_top($page_data, theme('form', crm_get_form('member_add')), 'Add');
            }
            
            // Add import tab
            if (user_access('contact_add') && user_access('user_add') && user_access('member_add')) {
                page_add_content_top($page_data, theme('form', crm_get_form('member_import')), 'Import');
            }
            
            break;
        
        case 'plans':
            
            // Set page title
            page_set_title($page_data, 'Plans');
            
            // Add view, add and import tabs
            if (user_access('member_plan_edit')) {
                page_add_content_top($page_data, theme('table', crm_get_table('member_plan')), 'View');
                page_add_content_top($page_data, theme('form', crm_get_form('member_plan_add')), 'Add');
                page_add_content_top($page_data, theme('form', crm_get_form('plan_import')), 'Import');
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
                page_add_content_top($page_data, theme('form', crm_get_form('member_plan_edit', $pid)), 'Edit');
            }
            
            break;
        
        case 'contact':
            
            // Capture member id
            $cid = $_GET['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Add plan and role tabs
            if (user_access('member_membership_edit') || $cid == user_id()) {
                $plan = theme('table', crm_get_table('member_membership', array('cid' => $cid)));
                $plan .= theme('form', crm_get_form('member_membership_add', $cid));
                page_add_content_top($page_data, $plan, 'Plan');
            }
            if (user_access('member_membership_edit')) {
                $roles = theme('form', crm_get_form('user_role_edit', $cid));
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
                page_add_content_top($page_data, theme('form', crm_get_form('member_membership_edit', $sid)), 'Edit');
            }
            break;
        
        case 'reports':
            if (user_access('member_view')) {
                $reports = theme('member_membership_report');
                $reports .= theme('member_email_report', array('filter'=>array('active'=>true)));
                $reports .= theme('member_email_report', array('filter'=>array('active'=>false)));
                //$reports .= theme('member_voting_report');
                page_add_content_bottom($page_data, $reports);
            }
            break;
    }
}


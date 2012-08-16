<?php 

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
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
                $view .= theme('table', 'member', array('filter'=>$_SESSION['member_filter'], 'show_export'=>true));
                page_add_content_top($page_data, 'View', $view);
            }
            
            // Add add tab
            if (user_access('member_add')) {
                page_add_content_top($page_data, 'Add', theme('member_add_form'));
            }
            
            break;
        
        case 'plans':
            
            // Set page title
            page_set_title($page_data, 'Plans');
            
            // Add view and add tabs
            if (user_access('member_plan_edit')) {
                page_add_content_top($page_data, 'View', theme('table', 'member_plan'));
                page_add_content_top($data['Add'], theme('member_plan_add_form'));
            }
            
            break;
        
        case 'plan':
            
            // Capture plan id
            $pid = $options['pid'];
            if (empty($pid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, 'Plan: ' . theme('member_plan_description', $pid));
            
            // Add edit tab
            if (user_access('member_plan_edit')) {
                page_add_content_top($page_data, 'Edit', theme('member_plan_edit_form', $pid));
            }
            
            break;
        
        case 'member':
            
            // Capture member id
            $cid = $options['cid'];
            if (empty($cid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, theme('member_contact_name', $cid));
            
            // Add view tab
            if (user_access('member_view')) {
                page_add_content_top($page_data, 'View', theme('table_vertical', 'member_contact', array('cid' => $cid)));
            }
            
            // Add edit tab
            if (user_id() == $options['cid'] || (user_access('contact_edit') && user_access('member_edit'))) {
                page_add_content_top($page_data, 'Edit', theme('member_contact_edit_form', $cid));
            }
            
            // Add plan and role tabs
            if (user_access('member_membership_edit')) {
                $plan = theme('table', 'member_membership', array('cid' => $cid));
                $plan .= theme('member_membership_add_form', $cid);
                page_add_content_top($page_data, 'Plan', $plan);
                
                $roles = theme('user_role_edit_form', $cid);
                page_add_content_top($page_data, 'Roles', $roles);
            }
            
            break;
        
        case 'membership':
            
            // Capture sid id
            $sid = $options['sid'];
            if (empty($sid)) {
                return;
            }
            
            // Set page title
            page_set_title($page_data, member_membership_description($sid));
            
            // Add edit tab
            if (user_access('member_membership_edit') && user_access('member_edit')) {
                page_add_content_top($page_data, 'Edit', theme('member_membership_edit_form', $sid));
            }
            
            break;
    }
}


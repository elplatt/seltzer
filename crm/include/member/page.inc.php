<?php 

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
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
 * Member page hook
*/
function member_page(&$data, $page, $options) {
    
    switch ($page) {
        
        case 'members':
            
            // Set page title
            $data['#title'] = 'Members';
            
            // Add view tab
            if (!isset($data['View'])) {
                $data['View'] = array();
            }
            array_unshift($data['View'], theme('member_table', array('filter'=>$_SESSION['member_filter'])));
            array_unshift($data['View'], theme('member_filter_form'));
            
            // Add add tab
            if (!isset($data['Add'])) {
                $data['Add'] = array();
            }
            array_unshift($data['Add'], theme('member_add_form'));
        
        case 'member':
            
            // Capture member id
            $mid = $options['mid'];
            if (empty($mid)) {
                return;
            }
            
            // Set page title
            $data['#title'] = theme('member_contact_name', member_contact_id($mid));
            
            // Add view tab
            if (!isset($data['View'])) {
                $data['View'] = array();
            }
            array_unshift($data['View'], theme('member_contact_table', array('cid' => member_contact_id($mid))));
            
            // Add edit tab
            if (!isset($data['Edit'])) {
                $data['Edit'] = array();
            }
            array_unshift($data['Edit'], theme('member_contact_edit_form', member_contact_id($mid)));
            
            // Add plan tab
            if (!isset($data['Plan'])) {
                $data['Plan'] = array();
            }
            $plan = theme('member_membership_table', array('mid' => $mid));
            $plan .= theme('member_membership_add_form', $mid);
            array_unshift($data['Plan'], $plan);
            
            break;
    }
}

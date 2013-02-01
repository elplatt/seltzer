<?php 

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    utility.inc.php - Member module - utility functions

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
 * Convert first, middle, last into a single name string.
 *
 * @param $first First name
 * @param $middle Middle name
 * @param $last Last name
 *
 * @return the name string.
 */
function member_name ($first, $middle, $last) {
    $name = $last . ", ";
    $name .= $first;
    if (!empty($middle)) {
        $name .= ' ' . $middle;
    }
    return $name;
}

/**
 * Generate description for a membership plan.
 *
 * @param $sid The sid of the membership.
 * @return The description string.
 */
function member_membership_description ($sid) {
    
    // Get membership data
    $data = member_membership_data(array('sid'=>$sid));
    $membership = $data[0];
    
    // Get member contact info
    $data = member_contact_data(array('cid'=>$membership['cid']));
    $contact = $data[0];
    
    // Construct description
    $description = 'Membership : ';
    $description .= member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
    $description .= ' : ' . $membership['plan']['name'];
    $description .= ' : Starting ' . $membership['start'];
    
    return $description;
}

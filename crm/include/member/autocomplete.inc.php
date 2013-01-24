<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    autocomplete.inc.php - Provides data for autocomplete elements

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
 * Return a list of members matching a text fragment.
 * @param $fragment
 */
function member_name_autocomplete ($fragment) {
    $data = array();
    if (user_access('member_view')) {
        $members = member_data(array('filter'=>array('nameLike'=>$fragment)));
        foreach ($members as $member) {
            $contact = $member['contact'];
            $row = array();
            $row['value'] = $contact['cid'];
            $row['label'] = member_name($contact['firstName'], $contact['middleName'], $contact['lastName']);
            $data[] = $row;
        }
    }
    return $data;
}

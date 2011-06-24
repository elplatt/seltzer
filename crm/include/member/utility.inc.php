<?php 

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
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

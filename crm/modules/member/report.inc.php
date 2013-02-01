<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    report.inc.php - Member module - reports

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
 * @return A comma-separated list of user emails.
 * @param $opts - Options to pass to member_data().
 */
function member_email_report ($opts) {
    $result = array();
    $data = member_data($opts);
    foreach ($data as $row) {
        $email = trim($row['contact']['email']);
        if (!empty($email)) {
            $result[] = $email;
        }
    }
    return join($result, ', ');
}

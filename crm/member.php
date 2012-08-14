<?php

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    member.php - View or edit single member

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

require_once('include/crm.inc.php');

$member = member_data(array('mid'=>$_GET['mid']));

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" type="text/css" href="css/ui-lightness/jquery-ui-1.8.14.custom.css"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
    <script type="text/javascript" src="script.js"></script>
    <title><?php print title(); ?></title>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php print theme('header'); ?>
        </div>
        <div class="content">
            <?php print theme('errors'); ?>
            <?php //print theme('member_membership_add_form', $_GET['mid']); ?>
            <?php //print theme('member_membership_table', array('mid' => $_GET['mid'])); ?>
            <?php //print theme('member_contact_edit_form', $member[0]['contact']['cid']); ?>
            <?php print theme('page', 'member', array('cid' => $_GET['cid'])); ?>
        </div>
        <div class="footer">
            <?php print theme('footer'); ?>
        </div>
    </div>
</body>
</html>

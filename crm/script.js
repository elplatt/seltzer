/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    script.js - General javascript code

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

// Add datepicker to necessary fields
$(document).ready(function () {
    
    // Enable date picker
    $('input.date').datepicker({"dateFormat" : "yy-mm-dd"});
    
    // Set up tabbing
    showTab();
    $('ul.page-nav li a').click(function () {
        showTab($(this).attr('href'));
        return false;
    });
});

var showTab = function (hash) {
    $('fieldset.tab').hide();
    $('ul.page-nav li a').removeClass('active');
    if (hash == null) {
        hash = window.location.hash;
    }
    if (hash === '') {
        $('fieldset#tab-view').show();
        $('ul.page-nav li a[href="#tab-view"]').addClass('active');
    } else {
        $('fieldset' + hash).show();
        $('ul.page-nav li a[href="' + hash + '"]').addClass('active');
    }
}
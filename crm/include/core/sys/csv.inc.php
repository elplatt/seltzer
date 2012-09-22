<?php

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    csv.inc.php - Parses comma-separated variable files

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
 * Converts csv data into a data structure.  The returned value is an array
 * with each element representing a row of the csv.  Each element is an array
 * with column names as keys and fields as values.
 *
 * @param $csv_data The data.
 * @param $row_terminate The character represening a new row.
 * @param $field_terminate The character representing the end of a field.
 * @param $field_quote The character used to quote fields.
 * @param $field_escape The character used to escape special characters in the field content.
 *
 * @return The data structure corresponding to the csv data.
 */
function csv_parse ($content, $row_terminate = "\n", $field_terminate = ",", $field_quote = '"', $field_escape = "\\") {
    
    $result = array();
    $header = array();
    $row = array();

    $field = '';
    $field_quoted = false;
    
    $in_body = false;
    
    $field_index = 0;
    
    $index = 0;
    $length = strlen($content);
    while ($index < $length) {
        $char = $content{$index};
        if ($char == $field_escaper) {
            // Escaped character
            $index++;
            $field .= $content{$index};
        } else if ($char == $field_wrapper) {
            if ($field_quoted) {
                // We've reached the end of a quoted field
                while ($index < $length) {
                    if ($index == $length - 1) {
                        // End of content
                        $index++;
                        break;
                    }
                    $char = $content{$index + 1};
                    if ($char == $field_terminate) {
                        break;
                    }
                    if ($char == $row_terminate) {
                        break;
                    }
                }
            } else {
                if (empty($field)) {
                    // We're starting a quoted field
                    $field_quoted = true;
                }
            }
        } else if ($char == $field_terminate && !$field_quoted) {
            // End field or header
            if ($in_body) {
                // Body
                $row[$header[$field_index]] = $field;
            } else {
                // Header
                $header[] = strtolower($field);
            }
            $field = '';
            $field_quoted = false;
            $field_index++;
        } else if ($char == $row_terminate && !$field_quoted) {
            // End field or header
            if ($in_body) {
                // Body
                $row[$header[$field_index]] = $field;
            } else {
                // Header
                $header[] = strtolower($field);
            }
            $field = '';
            $field_quoted = false;
            
            // End row
            $field_index = 0;
            if ($in_body) {
                $result[] = $row;
            }
            $in_body = true;
            $row = array();
        } else {
            // Add character to current field, but skip whitespace at
            // the beginning
            if (preg_match('/\S/', $char) || !empty($field)) {
                $field .= $char;
            }
        }
        $index++;
    }
    
    return $result;
}
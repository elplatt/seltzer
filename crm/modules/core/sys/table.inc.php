<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Core table system

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
 * Themes tabular data.
 *
 * @param $table_name The name of the table or the table data.
 * @param $opts Options to pass to the data function.
 * @return The themed html for a table.
*/
function theme_table ($table_name, $opts = NULL) {
    
    // Check if $table_name is a string
    if (is_string($table_name)) {
        // Construct the name of the function to generate a table
        $generator = $table_name . '_table';
        if (function_exists($generator)) {
            $table = call_user_func($generator, $opts);
        } else {
            return '';
        }
    } else {
        // Support old style of passing the data directly
        $table = $table_name;
    }
    
    // Check if table is empty
    if (empty($table['rows'])) {
        return '';
    }
    
    // Count rows
    $column_count = sizeof($table['columns']);
    $row_count = sizeof($table['rows']);
    
    // Generate url for export
    $new_opts = $opts;
    $new_opts['export'] = true;
    $export = 'export-csv.php?name=' . $table_name . '&opts=' . urlencode(json_encode($new_opts));
    
    // Open table
    $output = "<table";
    if (!empty($table['id'])) {
        $output .= ' id="' . $table['id'] . '"';
    }
    $class = "seltzer-table";
    if (!empty($table['class'])) {
        $class .= ' ' . $table['class'];
    }
    $output .= ' class="' . $class . '"';
    $output .= '>';
    
    $output .= "<thead><tr>";
    
    // Loop through headers
    foreach ($table['columns'] as $col) {
        
        // Open header cell
        $output .= '<th';
        if (!empty($col['id'])) {
            $output .= ' id="' . $col['id'] . '"';
        }
        if (!empty($col['class'])) {
            $output .= ' class="' . $col['class'] . '"';
        }
        $output .= '>';
        
        $output .= $col['title'];
        $output .= '</th>';
    }
    $output .= "</tr>";
    if ($opts['show_export']) {
        $output .= '<tr class="subhead"><td colspan="' . $column_count . '">';
        $output .= $row_count . ' results, export: <a href="' . $export . '">csv</a>';
        $output .= "</td></tr>";
    }
    $output .= "</thead>";
    
    // Output table body
    $output .= "<tbody>";
    
    // Initialize zebra striping
    $zebra = 1;
    
    // Loop through rows
    foreach ($table['rows'] as $row) {
        
        $output .= '<tr';
        if ($zebra % 2 === 0) {
            $output .= ' class="even"';
        } else {
            $output .= ' class="odd"';
        }
        $zebra++;
        $output .= '>';
        
        foreach ($row as $i => $cell) {
            $output .= '<td';
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' id="' . $col['id'] . '"';
            }
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' class="' . $col['class'] . '"';
            }
            $output .= '>';
            $output .= $cell;
            $output .= '</td>';
        }
        
        $output .= '</tr>';
    }
    
    if ($opts['show_export']) {
        $output .= '<tr class="subhead"><td colspan="' . $column_count . '">';
        $output .= $row_count . ' results, export: <a href="' . $export . '">csv</a>';
        $output .= "</td></tr>";
    }
    
    $output .= "</tbody>";
    $output .= "</table>";
    
    return $output;
}

/**
 * Themes tabular data as a CSV.
 *
 * @param $table_name The name of the table or the table data.
 * @param $opts Options to pass to the data function.
 * @return The CSV for a table.
*/
function theme_table_csv ($table_name, $opts = NULL) {
    
    // Check if $table_name is a string
    if (is_string($table_name)) {
        // Construct the name of the function to generate a table
        $generator = $table_name . '_table';
        if (function_exists($generator)) {
            $table = call_user_func($generator, $opts);
        } else {
            return '';
        }
    } else {
        // Support old style of passing the data directly
        $table = $table_name;
    }
    
    // Check if table is empty
    if (empty($table['rows'])) {
        return '';
    }
    
    // Loop through headers
    $cells = array();
    foreach ($table['columns'] as $col) {
        $cells[] = table_escape_csv($col['title']);
    }
    $output .= join(',', $cells) . "\n";
    
    // Loop through rows
    foreach ($table['rows'] as $row) {
        $cells = array();
        foreach ($row as $i => $cell) {
            $cells[] = table_escape_csv($cell);
        }
        $output .= join(',', $cells) . "\n";
    }
    
    return $output;
}

/**
 * Escape a string as a csv cell.
 * @param $cell The cell data
 * @return The escaped string.
 */
function table_escape_csv ($cell) {
    return '"' . str_replace('"', '\"', $cell) . '"';    
}

/**
 * Themes a table with headers in the left column instead of the top row.
 *
 * @param $table_name The name of the table or the table data.
 * @param $opts Options to pass to the data function.
 * @return The themed html for a vertical table
*/
function theme_table_vertical ($table_name, $opts = NULL) {
    
    // Check if $table_name is a string
    if (is_string($table_name)) {
        // Construct the name of the function to generate a table
        $generator = $table_name . '_table';
        if (function_exists($generator)) {
            $table = call_user_func($generator, $opts);
        } else {
            return '';
        }
    } else {
        // Support old style of passing the data directly
        $table = $table_name;
    }
    
    // Check if table is empty
    if (empty($table['rows'])) {
        return '';
    }
    
    // Open table
    $output = "<table";
    if (!empty($table['id'])) {
        $output .= ' id="' . $table['id'] . '"';
    }
    $class = "seltzer-table";
    if (!empty($table['class'])) {
        $class .= " " . $table['class'];
    }
    $output .= ' class="' . $class . '"';
    $output .= '>';
    
    // Output table body
    $output .= "<tbody>";
    
    // Loop through headers
    foreach ($table['columns'] as $i => $col) {
        
        // Open row
        $output .= '<tr>';
        
        // Print header
        $output .= '<td';
        if (!empty($col['id'])) {
            $output .= ' id="' . $col['id'] . '"';
        }
        if (!empty($col['class'])) {
            $output .= ' class="' . $col['class'] . '"';
        }
        $output .= '>';
        
        $output .= $col['title'];
        $output .= '</td>';
        
        // Loop through rows
        foreach ($table['rows'] as $row) {
            
            $output .= '<td';
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' id="' . $col['id'] . '"';
            }
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' class="' . $col['class'] . '"';
            }
            $output .= '>';
            $output .= $row[$i];
            $output .= '</td>';
        }
        
        $output .= '</tr>';
    }
    
    $output .= "</tbody>";
    $output .= "</table>";
    
    return $output;
}

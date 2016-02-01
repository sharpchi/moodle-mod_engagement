<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Output rendering of engagement report
 *
 * @package    report_engagement
 * @copyright  2016 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function fetches snippets from the lang file of the appropriate subplugin and inserts them into the database
 *
 * @param string $category The category (usually the name of the indicator)
 * @return boolean Success
 */
function engagementindicator_populate_snippets_from_lang($category) {
    
    global $DB;
    $dbman = $DB->get_manager();
    $stringman = get_string_manager();
    
    if ($dbman->table_exists('report_engagement_snippets')) {
        if (!$DB->count_records('report_engagement_snippets', array('category'=>$category))) {
            // Add default snippets
            $records = [];
            $counter = 0;
            try {
                // Incrementally check and fetch default snippets from lang file
                do {
                    $record = new stdClass;
                    if ($stringman->string_exists("defaultsnippet$counter", "engagementindicator_$category")) {
                        $record->category = $category;
                        $record->snippet_text = get_string("defaultsnippet$counter", "engagementindicator_$category");
                        $counter += 1;
                        $records[] = $record;
                    } else {
                        break;
                    }
                } while (true);
                $DB->insert_records('report_engagement_snippets', $records);
            } catch (Exception $e) {
                break;
            }
        }
    }
}


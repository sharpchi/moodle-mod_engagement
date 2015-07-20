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
 * Strings
 *
 * @package    engagementindicator_gradebook
 * @copyright  2015 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Standard plugin strings.
$string['pluginname'] = 'Gradebook';
$string['pluginname_help'] = 'This indicator calculates risk rating based data from the gradebook.';
$string['mailer_column_header_help'] = 'Tick the checkbox(es) in this column to send messages to student(s) based on their gradebook data. The data from gradebook items selected in the settings screen are outlined in a column to the right.';

// Settings.
$string['atriskif'] = 'At risk if the following condition(s) are met:';
$string['group_gradeitem_help'] = 'Tick the checkbox to count this gradebook item in the calculations for this indicator. Then, specify the comparison operator, the condition to compare against, and the weighting (number between 0-100) to add to the risk rating if the comparison holds true. For example, if this is set to "less than" and "5", if the record for a student for this grade item is 4, then this grade item will trigger and contribute the specified weighting to the risk rating for this indicator.';

// Errors
$string['weightingsumonehundred'] = "Weightings for Gradebook indicator must sum to 100";

// Renderer.
$string['localrisk'] = 'Local Risk';
$string['logic'] = 'Logic';
$string['riskcontribution'] = 'Risk Contribution';
$string['weighting'] = 'Weighting';
$string['gradeitemvalue'] = 'Grade item value ';
$string['soatrisk'] = ' so at risk';
$string['sonotatrisk'] = ' so not at risk';
$string['gt'] = " greater than ";
$string['notgt'] = " not greater than ";
$string['gte'] = " greater than or equal to ";
$string['notgte'] = " not greater than or equal to ";
$string['lt'] = " less than ";
$string['notlt'] = " not less than ";
$string['lte'] = " less than or equal to ";
$string['notlte'] = " not less than or equal to ";
$string['eq'] = " equal to ";
$string['neq'] = " not equal to ";
$string['neq'] = " not not equal to ";
$string['gradeitem_out_of'] = 'out of {$a}';



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
 * This file defines a class with gradebook indicator logic
 *
 * @package    engagementindicator_gradebook
 * @author     Danny Liu <danny.liu@mq.edu.au>
 * @copyright  2015 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');
require_once(dirname(__FILE__).'/indicator.class.php');

class engagementindicator_gradebook_thresholds_form {

    /**
     * Define the elements to be displayed in the form
     *
     * @param $mform
     * @access public
     * @return void
     */
    public function definition_inner(&$mform) {
		global $DB, $COURSE;
		
		$gradeitems = $DB->get_records_sql("SELECT * FROM {grade_items} WHERE courseid = $COURSE->id ORDER BY sortorder ASC");

		$comparators = [
			'lt' => trim(get_string('lt', 'engagementindicator_gradebook')),
			'lte' => trim(get_string('lte', 'engagementindicator_gradebook')),
			'gt' => trim(get_string('gt', 'engagementindicator_gradebook')),
			'gte' => trim(get_string('gte', 'engagementindicator_gradebook')),
			'eq' => trim(get_string('eq', 'engagementindicator_gradebook')),
			'neq' => trim(get_string('neq', 'engagementindicator_gradebook'))
		];
		$itemtypes = array(array('category'), array('mod','manual'));
		
		$mform->addElement('static', '', "", get_string('atriskif', 'engagementindicator_gradebook'));
		
		// Grade items
		
		foreach ($itemtypes as $itemtype) {
			foreach ($gradeitems as $gradeitem) {
				if (in_array($gradeitem->itemtype, $itemtype)) { // TODO: better way of checking this?
					// Determine group label
					if ($gradeitem->itemtype == 'category') {
						$gradecategories = $DB->get_records_sql("SELECT * FROM {grade_categories} WHERE courseid = $COURSE->id AND id = $gradeitem->iteminstance");
						$gradecategory = reset($gradecategories);
						$gradeitemrow_label = '[' . get_string('category', 'grades') . '] ' . $gradecategory->fullname;
					} else {
						$gradeitemrow_label = $gradeitem->itemname;
					}
					// Populate group
					$gradeitemrow = array();
					$gradeitemrow[] =& $mform->createElement('advcheckbox', 'gradeitem_enabled_'.$gradeitem->id, '', '');
					$gradeitemrow[] =& $mform->createElement('select', 'gradeitem_comparator_'.$gradeitem->id, '', $comparators);
					$gradeitemrow[] =& $mform->createElement('text', 'gradeitem_value_'.$gradeitem->id, '', array('size' => 6));
					if ($gradeitem->grademax) {
						$gradeitemrow[] =& $mform->createElement('static', '', '', get_string('gradeitem_out_of', 'engagementindicator_gradebook', number_format($gradeitem->grademax, 1)) . ' | ');
					}
					$gradeitemrow[] =& $mform->createElement('static', '', '', get_string('weighting', 'engagementindicator_gradebook'));
					$gradeitemrow[] =& $mform->createElement('text', 'gradeitem_weighting_'.$gradeitem->id, '', array('size' => 5));
					$gradeitemrow[] =& $mform->createElement('static', '', '', '%');
					if ($gradeitem->gradetype == 1) {
						$gradeitemhint = " (".number_format($gradeitem->grademin, 1)."-".number_format($gradeitem->grademax, 1).")";
					} else {
						$gradeitemhint = '';
					}
					// Add group
					$mform->addGroup($gradeitemrow, 'group_gradeitem_'.$gradeitem->id, $gradeitemrow_label . $gradeitemhint, array(' '), false);
					$mform->setType('gradeitem_weighting_'.$gradeitem->id, PARAM_INT);
					$mform->setType('gradeitem_value_'.$gradeitem->id, PARAM_RAW);
					$mform->disabledIf('gradeitem_comparator_'.$gradeitem->id, 'gradeitem_enabled_'.$gradeitem->id);
					$mform->disabledIf('gradeitem_value_'.$gradeitem->id, 'gradeitem_enabled_'.$gradeitem->id);
					$mform->disabledIf('gradeitem_weighting_'.$gradeitem->id, 'gradeitem_enabled_'.$gradeitem->id);
				}
			}
		}
    }
	
	public function validation($data, $files) {
		$gradeitems = array();
		$errors = array();
		foreach ($data as $key => $value) {
			if (substr($key, 0, strlen('gradeitem_enabled_')) === 'gradeitem_enabled_') {
				if ($value == '1') {
					$gradeitems[] = substr($key, strlen('gradeitem_enabled_'));
				}
			}
		}
		$weightingsum = 0;
		foreach ($gradeitems as $gradeitem) {
			$weightingsum += $data['gradeitem_weighting_'.$gradeitem];
		}
		if ($weightingsum != 100) {
			foreach ($gradeitems as $gradeitem) {
				$errors['group_gradeitem_'.$gradeitem] = get_string('weightingsumonehundred', 'engagementindicator_gradebook');
			}
			return $errors;
		}
		return null;
	}
	
}

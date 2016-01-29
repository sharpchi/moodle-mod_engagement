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
 * @copyright  2015 Macquarie University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../indicator.class.php');

class indicator_gradebook extends indicator {

    /**
     * get_rawdata
     *
     * @param int $startdate
     * @param int $enddate
     * @access protected
     * @return array            array of risk values, keyed on userid
     */
    protected function get_rawdata($startdate, $enddate) {
        global $CFG, $DB, $COURSE;
		// consider:? https://github.com/moodle/moodle/blob/d3ff82257e91d7d4157f16d53d63cf45e4253309/lib/grade/constants.php
		// consider:? https://docs.moodle.org/27/en/Category_aggregation
		
		require_once($CFG->libdir.'/gradelib.php');
		
		$gradeitems = $DB->get_records_sql("SELECT * FROM {grade_items} WHERE courseid = $COURSE->id ORDER BY sortorder");

		$studentdata = array();
		
		foreach ($gradeitems as $gradeitem) {
			switch ($gradeitem->itemtype) {
				case null:
					break;
				case "course":
					break;
				case "manual":
					$grades = $DB->get_records_sql("SELECT * FROM {grade_grades} WHERE itemid = $gradeitem->id");
					foreach ($grades as $grade) {
						if ($startdate != null && $enddate != null) {
							if ($grade->timemodified < $startdate || $grade->timemodified > $enddate) {
								continue;
							}
						}
						if ($grade->finalgrade != null) {
							$studentdata[$grade->userid][$grade->itemid] = $grade->finalgrade;
						}
					}
					break;
				default:
					$grades = grade_get_grades($COURSE->id, $gradeitem->itemtype, $gradeitem->itemmodule, $gradeitem->iteminstance, $this->userarray);
					foreach ($grades->items as $item) {
						foreach ($item->grades as $key => $data) {
							if ($startdate != null && $enddate != null) {
								if ($data->dategraded < $startdate || $data->dategraded > $enddate) {
									continue;
								}
							}
							if ($data->grade != null) {
								$studentdata[$key][$item->id] = $data->grade;
							}
						}
					}
					break;
			}
		}
		return $studentdata;
    }

    protected function calculate_risks(array $userids) {
		
		$risks = array();
		
		// Workaround if showing user risk instead of course risk (since parent class doesn't allow this to be handled well)
		if (empty($this->rawdata) || $this->userarray == null) {
			$this->userarray = $userids;
			$this->rawdata = $this->get_rawdata($this->startdate, $this->enddate);
		}		
		// Determine which grade items are enabled
		$gradeitems = array();
		foreach ($this->config as $key => $value) {
			if (substr($key, 0, strlen("gradeitem_enabled_")) === "gradeitem_enabled_") {
				if ($value == 1) {
					$gradeitem = new stdClass();
					$gradeid = substr($key, strlen("gradeitem_enabled_"));
					$gradeitem->id = $gradeid;
					$gradeitem->comparator = $this->config["gradeitem_comparator_".$gradeid];
					$gradeitem->value = $this->config["gradeitem_value_".$gradeid];
					$gradeitem->weighting = $this->config["gradeitem_weighting_".$gradeid];
					$gradeitems[] = $gradeitem;
				}
			}
		}
		
		foreach ($userids as $userid) {
			
			$risk = 0;
			$reasons = array();
			
			foreach ($gradeitems as $gradeitem) {
				if (array_key_exists($userid, $this->rawdata) && array_key_exists($gradeitem->id, $this->rawdata[$userid])) {
					$usergrade = $this->rawdata[$userid][$gradeitem->id];
					$thresholdgrade = $gradeitem->value;
					$weighting = $gradeitem->weighting / 100;
					$reason = $this->calculate_risk($gradeitem->id, $risk, $gradeitem->comparator, $usergrade, $thresholdgrade, $weighting);
					$reasons[] = $reason;
				}
			}
			
			$info = new stdClass();
			$info->risk = $risk;
			$info->info = $reasons;
			$risks[$userid] = $info;
			
		}
			
		return $risks;
		
    }
	
	private function calculate_risk($gradeitemid, &$risk, $comparator, $usergrade, $thresholdgrade, $weighting) {
		global $DB, $COURSE;
		
		$reason = new stdClass(); //weighting, localrisk, logic, riskcontribution, title
		$triggered = false;
		$usergrade = sprintf("%.2f", $usergrade);
		
		$giv = get_string('gradeitemvalue', 'engagementindicator_gradebook');
		$atrisk = get_string('soatrisk', 'engagementindicator_gradebook');
		$notatrisk = get_string('sonotatrisk', 'engagementindicator_gradebook');
		
		switch ($comparator) {
			case 'gt':
				if ($usergrade > $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('gt', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('notgt', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
			case 'gte':
				if ($usergrade >= $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('gte', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('notgte', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
			case 'lt':
				if ($usergrade < $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('lt', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('notlt', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
			case 'lte':
				if ($usergrade <= $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('lte', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('notlte', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
			case 'eq':
				if ($usergrade == $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('eq', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('neq', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
			case 'neq':
				if ($usergrade != $thresholdgrade) {
					$risk += $weighting;
					$reason->logic = $giv.$usergrade.get_string('neq', 'engagementindicator_gradebook').$thresholdgrade.$atrisk;
					$triggered = true;
				} else {
					$reason->logic = $giv.$usergrade.get_string('notneq', 'engagementindicator_gradebook').$thresholdgrade.$notatrisk;
				}
				break;
		}
		
		$reason->weighting = sprintf("%d%%", $weighting * 100);
		$reason->localrisk = sprintf("%d%%", $weighting * 100);
		$gradeitems = $DB->get_records_sql("SELECT * FROM {grade_items} WHERE courseid = $COURSE->id ORDER BY sortorder");
		$reason->title = $gradeitems[$gradeitemid]->itemname;
		if ($triggered) {
			$reason->riskcontribution = sprintf("%d%%", $weighting * 100);
			return $reason;
		} else {
			$reason->riskcontribution = sprintf("%d%%", 0);
			return $reason;
		}
	}

    public static function get_defaults() {
        $settings = array();
        return $settings;
    }
	
	public function get_data_for_mailer() {
		
		$risks = $this->get_course_risks();
		$data = array();
		
		foreach ($this->userarray as $userid) {
			$data[$userid] = array();
		}
		
		// Collect and process data
		foreach ($this->userarray as $userid) {
			$obj = $risks[$userid];
			$data[$userid]['risk'] = $obj->risk;
			foreach ($obj->info as $info) {
				if ($info->riskcontribution == '0%') {
					$data[$userid]['nottriggeredby'][] = $info->title . " " . $info->logic;
				} else {
					$data[$userid]['triggeredby'][] = $info->title . " " . $info->logic;
				}
			}
		}
		
		// Parse for display
		$return_columns = array();
		// Column for risk
		$return_column = array();
		$return_column['header'] = get_string('report_gradebook_risk', 'engagementindicator_gradebook');
		$return_column['heatmapdirection'] = 1; // 1 means normal sort i.e. higher numbers are darker
		$return_column['display'] = array();
		foreach ($data as $userid => $record) {
			$return_column['display'][$userid] = '<div><span class="report_engagement_display">'.
				sprintf("%.0f", $risks[$userid]->{'risk'} * 100).
				'</span></div>';
		}
		$return_columns[] = $return_column;
		// Column for triggered
		$return_column = array();
		$return_column['header'] = get_string('report_gradebook_triggered', 'engagementindicator_gradebook');
		$return_column['filterable'] = True;
		$return_column['heatmapdirection'] = 1; // 1 means normal sort i.e. higher numbers are darker
		$return_column['display'] = array();
		foreach ($data as $userid => $record) {
			$return_column['display'][$userid] = '<div>'.
				'<span class="report_engagement_display">'.(isset($record['triggeredby']) ? count($record['triggeredby']) : 0).'</span>';
			if (isset($record['triggeredby'])) {
				$return_column['display'][$userid] .= "<div class='report_engagement_detail'>".implode('<br />', $record['triggeredby'])."</div>";
			}
			$return_column['display'][$userid] .= '</div>';
		}
		$return_columns[] = $return_column;
		// Column for not triggered
		$return_column = array();
		$return_column['header'] = get_string('report_gradebook_nottriggered', 'engagementindicator_gradebook');
		$return_column['filterable'] = True;
		$return_column['heatmapdirection'] = -1; // -1 means reverse sort, i.e. higher numbers are lighter
		$return_column['display'] = array();
		foreach ($data as $userid => $record) {
			$return_column['display'][$userid] = '<div>'.
				'<span class="report_engagement_display">'.(isset($record['nottriggeredby']) ? count($record['nottriggeredby']) : 0).'</span>';
			if (isset($record['nottriggeredby'])) {
				"<div class='report_engagement_detail'>".implode('<br />', $record['nottriggeredby'])."</div>";
			}
			$return_column['display'][$userid] .= '</div>';
		}
		$return_columns[] = $return_column;
		
		// Return
		return $return_columns;
		
	}
	
}

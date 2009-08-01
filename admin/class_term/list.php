<?php
	/*****************************************************************
	 * admin/class_term/list.php  (c) 2008 Jonathan Dieter
	 *
	 * List all class_term options for the current term
	 *****************************************************************/

	$title = "Report List";
	
	include "header.php";                                        // Show header

	if(!$is_admin) {
		log_event($LOG_LEVEL_ERROR, "admin/class_term/list.php", $LOG_DENIED_ACCESS,
				"Attempted to list report options.");

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

		include "footer.php";
		exit(0);
	}

	$showalldeps = true;
	include "core/settermandyear.php";
	include "core/titletermyear.php";

	$query =	"SELECT class.Grade, class.ClassName, class_term.AverageType, " .
				"       class_term.EffortType, class_term.ConductType, " .
				"       class_term.AbsenceType, class.ClassIndex, " .
				"       class_term.CTCommentType, class_term.HODCommentType, " .
				"       class_term.PrincipalCommentType, class_term.CanDoReport, " .
				"       class_term.ReportTemplateType " .
				"       FROM class LEFT OUTER JOIN class_term ON " .
				"            (class.YearIndex           = $yearindex " .
				"             AND class_term.ClassIndex = class.ClassIndex " .
				"             AND class_term.TermIndex  = $termindex) " .
				"WHERE class.YearIndex       = $yearindex " .
				"AND   class.DepartmentIndex = $depindex " .
				"ORDER BY class.Grade, class.ClassName";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if($res->numRows() == 0) {
		echo "      <p align='center'>There are no subjects this term.</p>\n";

		include "footer.php";
		exit(0);
	}

	echo "      <table align='center' border='1'>\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>&nbsp;</th>\n";
	echo "            <th>Class</th>\n";
	echo "            <th>Average</th>\n";
	echo "            <th>Conduct</th>\n";
	echo "            <th>Effort</th>\n";
	echo "            <th>Absences</th>\n";
	echo "            <th>Class<br>Teacher's<br>Comment</th>\n";
	echo "            <th>Head of<br>Department's<br>Comment</th>\n";
	echo "            <th>Principal's<br>Comment</th>\n";
	echo "            <th>Report<br>Template<br>Type</th>\n";
	echo "            <th>Report<br>Editing<br>Enabled?</th>\n";
	echo "         </tr>\n";
	
	/* For each subject, print a row with the subject's name, # of students, and teacher name(s) */
	$alt_count = 0;
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$alt_count += 1;
		if($alt_count % 2 == 0) {
			$alt = " class='alt'";
		} else {
			$alt = " class='std'";
		}
		$editlink = "index.php?location=" .  dbfuncString2Int("admin/class_term/modify.php") .
					"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
					"&amp;key2=" .           dbfuncString2Int($termindex) .
					"&amp;keyname=" .        dbfuncString2Int($row['ClassName']);
		
		echo "         <tr$alt>\n";
		
		/* Generate edit button */
		$editbutton = dbfuncGetButton($editlink, "E", "small", "edit",  "Edit options for this report");
		echo "            <td>$editbutton</td>\n";

		echo "            <td>{$row['ClassName']}</td>\n";                // Print class name

		/* Show average type */
		if($row['AverageType'] == $CLASS_AVG_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['AverageType'] == $CLASS_AVG_TYPE_PERCENT) {
			echo "            <td>Percentage set by Class Teacher</td>\n";
		} elseif($row['AverageType'] == $CLASS_AVG_TYPE_INDEX) {
			echo "            <td>Value set by Class Teacher</td>\n";
		} elseif($row['AverageType'] == $CLASS_AVG_TYPE_CALC) {
			echo "            <td>Percentage calculated from subjects</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show conduct type */
		if($row['ConductType'] == $CLASS_CONDUCT_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['ConductType'] == $CLASS_CONDUCT_TYPE_PERCENT) {
			echo "            <td>Percentage set by Class Teacher</td>\n";
		} elseif($row['ConductType'] == $CLASS_CONDUCT_TYPE_INDEX) {
			echo "            <td>Value set by Class Teacher</td>\n";
		} elseif($row['ConductType'] == $CLASS_CONDUCT_TYPE_CALC) {
			echo "            <td>Percentage calculated from subjects</td>\n";
		} elseif($row['ConductType'] == $CLASS_CONDUCT_TYPE_PUN) {
			echo "            <td>Percentage calculated from punishments</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show effort type */
		if($row['EffortType'] == $CLASS_EFFORT_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['EffortType'] == $CLASS_EFFORT_TYPE_PERCENT) {
			echo "            <td>Percentage set by Class Teacher</td>\n";
		} elseif($row['EffortType'] == $CLASS_EFFORT_TYPE_INDEX) {
			echo "            <td>Value set by Class Teacher</td>\n";
		} elseif($row['EffortType'] == $CLASS_EFFORT_TYPE_CALC) {
			echo "            <td>Percentage calculated from subjects</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show absence type */
		if($row['AbsenceType'] == $ABSENCE_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['AbsenceType'] == $ABSENCE_TYPE_NUM) {
			echo "            <td>Number set by Class Teacher</td>\n";
		} elseif($row['AbsenceType'] == $ABSENCE_TYPE_CALC) {
			echo "            <td>Calculated from attendance</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show class teacher comment type */
		if($row['CTCommentType'] == $COMMENT_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['CTCommentType'] == $COMMENT_TYPE_OPTIONAL) {
			echo "            <td>Optional</td>\n";
		} elseif($row['CTCommentType'] == $COMMENT_TYPE_MANDATORY) {
			echo "            <td>Mandatory</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show HOD comment type */
		if($row['HODCommentType'] == $COMMENT_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['HODCommentType'] == $COMMENT_TYPE_OPTIONAL) {
			echo "            <td>Optional</td>\n";
		} elseif($row['HODCommentType'] == $COMMENT_TYPE_MANDATORY) {
			echo "            <td>Mandatory</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show principal comment type */
		if($row['PrincipalCommentType'] == $COMMENT_TYPE_NONE) {
			echo "            <td><i>None</i></td>\n";
		} elseif($row['PrincipalCommentType'] == $COMMENT_TYPE_OPTIONAL) {
			echo "            <td>Optional</td>\n";
		} elseif($row['PrincipalCommentType'] == $COMMENT_TYPE_MANDATORY) {
			echo "            <td>Mandatory</td>\n";
		} else {
			echo "            <td><b>Unknown</b></td>\n";
		}

		/* Show report template type */
		if(is_null($row['ReportTemplateType'])) {
			$report_type = "<i>None</i>";
		} elseif($row['ReportTemplateType'] == "application/vnd.oasis.opendocument.text") {
			$report_type = "OpenDocument Text";
		} else {
			$report_type = "<b>Unknown</b>";
		}
		echo "            <td>$report_type</td>\n";

		/* Show whether reports are enabled */
		if($row['CanDoReport']) {
			echo "            <td><b>Yes</b></td>\n";
		} else {
			echo "            <td><i>No</i></td>\n";
		}
		echo "         </tr>\n";
	}
	echo "      </table>\n";               // End of table
	
	include "footer.php";
?>
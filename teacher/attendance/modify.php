<?php
/**
 * ***************************************************************
 * teacher/attendance/modify.php (c) 2007-2015 Jonathan Dieter
 *
 * List of all students who were in class
 * ***************************************************************
 */
$subject_name = dbfuncInt2String($_GET['keyname']);
$subjectindex = safe(dbfuncInt2String($_GET['key']));
$periodindex = safe(dbfuncInt2String($_GET['key2']));
$date = safe(dbfuncInt2String($_GET['key3']));

$datestring = date(dbfuncGetDateFormat(), strtotime($date));

$title = "Attendance for $subject_name for $datestring";

include "header.php";

/* Check whether user is authorized to change scores */
$res = &  $db->query(
				"(SELECT Username FROM subjectteacher " .
				 " WHERE SubjectIndex = $subjectindex " .
				 " AND   Username     = \"$username\") " . "UNION " .
				 "(SELECT Username FROM disciplineperms " .
				 " WHERE Permissions >= $PUN_PERM_SUSPEND)");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	$link = "index.php?location=" .
		 dbfuncString2Int("teacher/attendance/modify_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;key2=" . $_GET['key2'] . "&amp;key3=" .
		 $_GET['key3'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 $_GET['next'];
	$query = "SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder FROM timetable, user, " .
			 "       subjectstudent LEFT OUTER JOIN " .
			 "       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
			 "               classlist, classterm, subject " .
			 "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
			 "        AND   classterm.TermIndex = subject.TermIndex " .
			 "        AND   class.ClassIndex = classterm.ClassIndex " .
			 "        AND   class.YearIndex = subject.YearIndex " .
			 "        AND   subject.SubjectIndex=$subjectindex) AS query " .
			 "       ON subjectstudent.Username = query.Username " .
			 "WHERE user.Username             = subjectstudent.Username " .
			 "AND subjectstudent.SubjectIndex = $subjectindex " .
			 "AND timetable.SubjectIndex      = $subjectindex " .
			 "AND timetable.PeriodIndex       = $periodindex " .
			 "AND timetable.DayIndex          = DAYOFWEEK(\"$date\") - 1 " .
			 "GROUP BY user.Username " .
			 "ORDER BY user.FirstName, user.Surname, user.Username";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->numRows() > 0) {
		/* Print punishments */
		
		echo "      <form action=\"$link\" method=\"post\" name=\"pundate\">\n"; // Form method
		
		echo "      <p align=\"center\">\n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Done\"> \n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Cancel\"> \n";
		echo "      </p>\n";
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Student</th>\n";
		echo "            <th><a title=\"In class\">I</a></th>\n";
		echo "            <th><a title=\"Absent\">A</a></th>\n";
		echo "            <th><a title=\"Late\">L</a></th>\n";
		echo "            <th><a title=\"Suspended\">S</a></th>\n";
		echo "         </tr>\n";
		
		/* See whether we have permission to show student as suspended */
		$pres = &  $db->query(
							"SELECT Username FROM disciplineperms " .
							 "WHERE Permissions >= $PUN_PERM_SUSPEND");
		if (DB::isError($pres))
			die($pres->getDebugInfo()); // Check for errors in query
		if ($pres->numRows() > 0 or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
			$is_admin = true;
		} else {
			$is_admin = false;
		}
		
		/* For each assignment, print a row with the title, date, score and comment */
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			$query = "SELECT AttendanceTypeIndex FROM attendance " .
					 "WHERE Username = \"{$row['Username']}\" " .
					 "AND   Date = \"$date\" " .
					 "AND   PeriodIndex = $periodindex " .
					 "AND   SubjectIndex = $subjectindex ";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			
			$absent_selected = "";
			$late_selected = "";
			$inclass_selected = "";
			$suspend_selected = "";
			$all_enabled = "";
			$suspend_enabled = "";
			
			/* Check whether student's status has already been set */
			if ($nrow = $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				if ($nrow['AttendanceTypeIndex'] == $ATT_IN_CLASS) {
					$inclass_selected = "checked=\"checked\"";
				} elseif ($nrow['AttendanceTypeIndex'] == $ATT_ABSENT) {
					$absent_selected = "checked=\"checked\"";
				} elseif ($nrow['AttendanceTypeIndex'] == $ATT_LATE) {
					$late_selected = "checked=\"checked\"";
				} elseif ($nrow['AttendanceTypeIndex'] == $ATT_SUSPENDED) {
					$suspend_selected = "checked=\"checked\"";
				}
			} else {
				/* Check whether student's status for first period has been set */
				$query = "SELECT attendance.AttendanceTypeIndex FROM attendance, period " .
						 "WHERE attendance.Username = \"{$row['Username']}\" " .
						 "AND   attendance.Date = \"$date\" " .
						 "AND   attendance.PeriodIndex = period.PeriodIndex " .
						 "AND   period.Period = 1";
				$nres = &  $db->query($query);
				if (DB::isError($nres))
					die($nres->getDebugInfo()); // Check for errors in query
				if ($nrow = $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					if ($nrow['AttendanceTypeIndex'] == $ATT_IN_CLASS) {
						$inclass_selected = "checked=\"checked\"";
					} elseif ($nrow['AttendanceTypeIndex'] == $ATT_ABSENT) {
						$absent_selected = "checked=\"checked\"";
					} elseif ($nrow['AttendanceTypeIndex'] == $ATT_LATE) {
						$late_selected = "checked=\"checked\"";
					} elseif ($nrow['AttendanceTypeIndex'] == $ATT_SUSPENDED) {
						$suspend_selected = "checked=\"checked\"";
					}
				} else {
					$inclass_selected = "checked=\"checked\"";
				}
			}
			
			/* Check whether we're an administrator */
			if ($is_admin) {
				$suspend_enabled = "";
			} else {
				if ($suspend_selected != "") {
					$all_enabled = "disabled";
				} else {
					$all_enabled = "";
				}
				$suspend_enabled = "disabled";
			}
			
			$alt = " class=\"$alt_step\"";
			echo "         <tr$alt>\n";
			echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			echo "            <td><input type=\"radio\" name=\"att_{$row['Username']}\" value=\"$ATT_IN_CLASS\" $inclass_selected $all_enabled></td>\n";
			echo "            <td><input type=\"radio\" name=\"att_{$row['Username']}\" value=\"$ATT_ABSENT\" $absent_selected $all_enabled></td>\n";
			echo "            <td><input type=\"radio\" name=\"att_{$row['Username']}\" value=\"$ATT_LATE\" $late_selected $all_enabled></td>\n";
			echo "            <td><input type=\"radio\" name=\"att_{$row['Username']}\" value=\"$ATT_SUSPENDED\" $suspend_selected $suspend_enabled></td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";
		echo "      <p align=\"center\">\n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Done\"> \n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Cancel\"> \n";
		echo "      </p>\n";
		
		echo "      </form>\n";
	} else {
		echo "      <p align=\"center\" class=\"subtitle\">There are no students in this subject.</p>\n";
	}
} else {
	include "header.php";
	
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/attendance/modify.php", 
			$LOG_DENIED_ACCESS, "Tried to do attendance for $subject_name.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
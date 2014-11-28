<?php
	/*****************************************************************
	 * admin/attendance/first_period.php  (c) 2007, 2008 Jonathan Dieter
	 *
	 * List all subjects that are first period today
	 *****************************************************************/
	
	$showalldeps = true;
	$showyear    = false;
	$showterm    = false;
	include "core/settermandyear.php";

	if(isset($_GET['key'])) {
		$date = safe(dbfuncInt2String($_GET['key']));
		$datestring = date(dbfuncGetDateFormat(), strtotime($date));
		$title = "Attendance for $datestring (1st Period)";
		$checkterm = $termindex;
		$checkyear = $yearindex;
	} else {
		$date = date("Y-m-d");
		$datestring = "today";
		$title = "Today's attendance (1st Period)";
		$checkterm = $currentterm;
		$checkyear = $currentyear;
	}

	include "header.php";                                    // Show header
	include "core/titletermyear.php";

	/* Check whether user is authorized to change scores */
	$res =&  $db->query("SELECT Username FROM disciplineperms " .
						"WHERE Permissions >= $PUN_PERM_PROXY");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query	
	if($res->numRows() == 0 and !$is_admin) {
		include "header.php";
		
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/attendance/first_period.php", $LOG_DENIED_ACCESS,
					"Tried to do attendance for $subject_name.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$query =	"(SELECT subject.Name, NULL AS ClassName, subject.Grade, period.PeriodIndex, " .
				"        subject.SubjectIndex, attinfo.Date FROM timetable, period, " .
				"        subject, subjectstudent " .
				"        LEFT OUTER JOIN " .
				"          (SELECT attendance.Date, attendance.Username " .
				"                  FROM attendance, period " .
				"           WHERE  attendance.PeriodIndex = period.PeriodIndex " .
				"           AND    period.Period          = 1 " .
				"           AND    attendance.Date        = \"$date\" " .
				"          ) AS attinfo USING (Username) " .
				" WHERE  subject.SubjectIndex        = timetable.SubjectIndex " .
				" AND    subject.ClassIndex          IS NULL " .
				" AND    subject.YearIndex           = $checkyear " .
				" AND    subject.TermIndex           = $checkterm " .
				" AND    subject.DepartmentIndex     = $depindex " .
				" AND    subject.ShowInList          = 1 " .
				" AND    subjectstudent.SubjectIndex = subject.SubjectIndex " .
				" AND    period.PeriodIndex          = timetable.PeriodIndex " .
				" AND    period.Period               = 1 " .
				" AND    timetable.DayIndex          = DAYOFWEEK(\"$date\") - 1 " .
				" GROUP BY subject.SubjectIndex) " .
				"UNION " .
				"(SELECT subject.Name, class.ClassName, class.Grade, period.PeriodIndex, " .
				"        subject.SubjectIndex, attinfo.Date FROM timetable, class, " .
				"        period, subject, subjectstudent " .
				"        LEFT OUTER JOIN " .
				"          (SELECT attendance.Date, attendance.Username " .
				"                  FROM attendance, period " .
				"           WHERE  attendance.PeriodIndex = period.PeriodIndex " .
				"           AND    period.Period          = 1 " .
				"           AND    attendance.Date        = \"$date\" " .
				"          ) AS attinfo USING (Username) " .
				" WHERE  subject.SubjectIndex        = timetable.SubjectIndex " .
				" AND    subject.YearIndex           = $checkyear " .
				" AND    subject.TermIndex           = $checkterm " .
				" AND    subject.DepartmentIndex     = $depindex " .
				" AND    subject.ShowInList          = 1 " .
				" AND    class.ClassIndex            = subject.ClassIndex " .
				" AND    subjectstudent.SubjectIndex = subject.SubjectIndex " .
				" AND    period.PeriodIndex          = timetable.PeriodIndex " .
				" AND    period.Period               = 1 " .
				" AND    timetable.DayIndex          = DAYOFWEEK(\"$date\") - 1 " .
				" GROUP BY subject.SubjectIndex) " .
				"ORDER BY Grade, ClassName, Name";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	if($res->numRows() == 0) {
		echo "      <p align=\"center\" class=\"subtitle\">There is no attendance for this day in this term.</p>\n";
		include "footer.php";
		exit(0);
	}

	/* Print punishments */
	echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Subject</th>\n";
	echo "            <th>Teachers</th>\n";
	echo "            <th>Done</th>\n";
	echo "            <th>Recorded by</th>\n";
	echo "         </tr>\n";
	
	/* For each assignment, print a row with the title, date, score and comment */
	$alt_count = 0;
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$query =	"SELECT user.FirstName, user.Surname FROM attendancedone, user " .
					"WHERE attendancedone.SubjectIndex={$row['SubjectIndex']} " .
					"AND   attendancedone.PeriodIndex={$row['PeriodIndex']} " .
					"AND   attendancedone.Date=\"$date\" " .
					"AND   attendancedone.Username = user.Username ";
		$nres =&  $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		
		if($nres->numRows() == 0) {
			$recorded_name = "<em>N/A</em>";
			$done = False;
		} else {
			$nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC);
					
			$recorded_name = "{$nrow['FirstName']} {$nrow['Surname']}";
			$done = True;
		}
		if(!$done) {
			$bon = "<b>";
			$boff = "</b>";
		} else {
			$bon = "";
			$boff = "";
		}
		$alt_count += 1;
		if($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		$alt = " class=\"$alt_step\"";
		echo "         <tr$alt>\n";
		$link = "index.php?location="    . dbfuncString2Int("teacher/attendance/modify.php") .
						"&amp;key="      . dbfuncString2Int($row['SubjectIndex']) .
						"&amp;key2="     . dbfuncString2Int($row['PeriodIndex']) .
						"&amp;key3="     . dbfuncString2Int($date) .
						"&amp;keyname= " . dbfuncString2Int($row['Name']) .
						"&amp;next="     . dbfuncString2Int("index.php?location=" .
											dbfuncString2Int("admin/attendance/first_period.php") .
											"&amp;key=" . dbfuncString2Int($date));
		echo "            <td>$bon<a href=\"$link\">{$row['Name']}</a>$boff</td>\n";
		$tResult =& $db->query("SELECT user.Username, user.FirstName, user.Surname FROM user, " .
								"       subjectteacher " .
								"WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
								"AND   user.Username = subjectteacher.Username " .
								"ORDER BY user.Username");
		if(DB::isError($tResult)) die($tResult->getDebugInfo());     // Check for errors in query
		
		if($tRow =& $tResult->fetchRow(DB_FETCHMODE_ASSOC)) {
			echo "            <td>$bon{$tRow['FirstName']} {$tRow['Surname']} ({$tRow['Username']})";
			while($tRow =& $tResult->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "<br>\n                {$tRow['FirstName']} {$tRow['Surname']} ({$tRow['Username']})";
			}
			echo "$boff</td>\n";
		} else {
			echo "            <td>$bon<i>No teacher</i>$boff</td>\n";
		}
		echo "            <td>";
		if(!$done) {
			echo "{$bon}No{$boff}";
		} else {
			echo "{$bon}Yes{$boff}";
		}
		echo "</td>\n";
		echo "            <td>$recorded_name</td>\n";
		echo "         </tr>\n";
	}
	echo "      </table>\n";
	echo "      </form>\n";
	
	include "footer.php";
?>
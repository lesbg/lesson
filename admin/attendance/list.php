<?php
	/*****************************************************************
	 * admin/attendance/list.php  (c) 2007, 2008 Jonathan Dieter
	 *
	 * List all days in the term that have attendance
	 *****************************************************************/
	
	$title = "Attendance";

	include "header.php";                                    // Show header
	$showalldeps = true;
	include "core/settermandyear.php";
	include "core/titletermyear.php";

	/* Check whether user is authorized */
	$res =&  $db->query("SELECT Username FROM disciplineperms " .
						"WHERE Permissions >= $PUN_PERM_PROXY");
	if(DB::isError($res)) die($res->getDebugInfo());
	if($res->numRows() == 0 and !$is_admin) {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/attendance/list.php", $LOG_DENIED_ACCESS,
					"Tried to list attendance for this term.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$query =	"SELECT calendar.Date, COUNT(attendance.SubjectIndex) As SubjectCount " .
				"       FROM calendar LEFT OUTER JOIN (attendance INNER JOIN subject ON attendance.SubjectIndex = subject.SubjectIndex " .
				"AND   subject.YearIndex       = $yearindex " .
				"AND   subject.TermIndex       = $termindex) USING (Date), currentterm " .
				"WHERE currentterm.DepartmentIndex = $depindex " .
				"AND   calendar.Date >= currentterm.StartDate " .
				"AND   calendar.Date <= CURRENT_DATE() " .
				"GROUP BY calendar.Date " .
				"ORDER BY calendar.Date DESC ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	if($res->numRows() == 0) {
		echo "      <p align='center' class='subtitle'>There has been no attendance for this term.</p>\n";
		include "footer.php";
		exit(0);
	}

	/* Print attendance */
	echo "      <table align='center' border='1'>\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Date</th>\n";
	echo "            <th>Number of Subjects</th>\n";
	echo "         </tr>\n";
	
	/* For each day, print a row with the date and number of subjects */
	$alt_count = 0;
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$alt_count += 1;
		if($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		$alt = " class='$alt_step'";
		echo "         <tr$alt>\n";
		$link = "index.php?location="    . dbfuncString2Int("admin/attendance/first_period.php") .
						"&amp;key="      . dbfuncString2Int($row['Date']);
		echo "            <td><a href='$link'>{$row['Date']}</a></td>\n";
		echo "            <td>{$row['SubjectCount']}</td>\n";
		echo "         </tr>\n";
	}
	echo "      </table>\n";
	
	include "footer.php";
?>
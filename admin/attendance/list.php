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

	$query =	"SELECT StartDate, CURRENT_DATE() AS EndDate FROM currentterm " .
				"WHERE DepartmentIndex = $depindex ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	if (!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "     <p>Error getting beginning and end of term</p>\n";
		include "footer.php";
		exit(0);
	}

	$start_date = $row['StartDate'];
	$check_date = $start_date;
	$end_date = $row['EndDate'];
	
	/* Print attendance */
	echo "      <table align='center' border='1'>\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Date</th>\n";
	echo "            <th>Number of Subjects</th>\n";
	echo "         </tr>\n";
	
	/* For each day, print a row with the date and number of subjects */
	$alt_count = 0;

	$query =	"SELECT attendancedone.Date, COUNT(attendancedone.SubjectIndex) As SubjectCount " .
				"       FROM currentterm, attendancedone INNER JOIN subject " .
				"            ON (attendancedone.SubjectIndex = subject.SubjectIndex " .
				"                AND   subject.YearIndex     = $yearindex " .
				"                AND   subject.TermIndex     = $termindex) " .
				"WHERE currentterm.DepartmentIndex = $depindex " .
				"AND   attendancedone.Date >= currentterm.StartDate " .
				"AND   attendancedone.Date <= CURRENT_DATE() " .
				"GROUP BY attendancedone.Date " .
				"ORDER BY attendancedone.Date DESC ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
	
	while ($check_date != $end_date) {
		$alt_count += 1;
		if($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		$alt = " class='$alt_step'";
		$link = "index.php?location="    . dbfuncString2Int("admin/attendance/first_period.php") .
						"&amp;key="      . dbfuncString2Int($check_date);
		$day = date('l', strtotime($check_date));
		$show_date = date($dateformat, strtotime($check_date));
		if(isset($row['Date']) and $row['Date'] == $check_date) {
			echo "         <tr$alt>\n";
			echo "            <td><a href='$link'>$day, $show_date</a></td>\n";
			echo "            <td>{$row['SubjectCount']}</td>\n";
			echo "         </tr>\n";
			$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		} else {
			$weekday = date('w', strtotime($check_date));
			if($weekday > 0 and $weekday < 6){
				echo "         <tr$alt>\n";
				echo "            <td><b><a href='$link'>$day, $show_date</a></b></td>\n";
				echo "            <td><b>0</b></td>\n";
				echo "         </tr>\n";
			} else {
				$alt_count -= 1;
			}
		}
		
		$check_date = date ("Y-m-d", strtotime ("+1 day", strtotime($check_date)));
	}
	echo "      </table>\n";
	
	include "footer.php";
?>
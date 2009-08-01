<?php
	/*****************************************************************
	 * admin/subject/timetable.php  (c) 2007 Jonathan Dieter
	 *
	 * Show and modify timetable for a subject
	 *****************************************************************/
	$name         = dbfuncInt2String($_GET['keyname']);
	$subjectindex = dbfuncInt2String($_GET['key']);

	$title            = "$name timetable";

	$showalldeps = true;
	include "core/settermandyear.php";
	include "header.php";                                    // Show header
	$showyear = true;
	$showterm = true;
	$nochangeyt = true;
	include "core/titletermyear.php";
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$res =&  $db->query("SELECT period.PeriodName, period.StartTime, period.EndTime, day.Day, " .
							"       period.Period, timetable.TimetableIndex FROM timetable, period, day " .
							"WHERE timetable.SubjectIndex = $subjectindex " .
							"AND   period.PeriodIndex = timetable.PeriodIndex " .
							"AND   day.DayIndex = timetable.DayIndex " .
							"ORDER BY day.DayIndex, period.StartTime");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		echo "      <table align=\"center\" border=\"1\">\n";
		echo "         <tr>\n";
		echo "            <th>Day</th>\n";
		echo "            <th>Period</th>\n";
		echo "            <th>Start time</th>\n";
		echo "            <th>End time</th>\n";
		echo "         </tr>\n";

		$alt_count = 0;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt = " class=\"alt\"";
			} else {
				$alt = " class=\"std\"";
			}
			echo "         <tr$alt>\n";
			$starttime = date("g:iA", strtotime($row['StartTime']));
			$endtime   = date("g:iA", strtotime($row['EndTime']));
			echo "            <td>{$row['Day']}</td>\n";
			echo "            <td>{$row['Period']}</td>\n";
			echo "            <td>$starttime</td>\n";
			echo "            <td>$endtime</td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";
	} else {  // User isn't authorized to create a punishment
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/subject/timetable.php", $LOG_DENIED_ACCESS,
					"Tried to access timetable for $name.");

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
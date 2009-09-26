<?php
	/*****************************************************************
	 * admin/print/attendance.php  (c) 2006 Jonathan Dieter
	 *
	 * Show printable list of students who are absent, late or suspended
	 *****************************************************************/

	/* Check whether user is authorized to change scores */
	$res =&  $db->query("SELECT Username FROM disciplineperms " .
						"WHERE Permissions >= $PUN_PERM_SUSPEND");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query	

	$dateinfo = date($dateformat);
	$title = "Attendance for $dateinfo";
	
	/* Make sure user has permission to view student's marks for subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $res->numRows() > 0) {
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" " .
			"\"http://www.w3.org/TR/html4/loose.dtd\">\n";
		echo "<html>\n";
		echo "   <head>\n";
		echo "      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
		echo "      <title>$title</title>\n";
		echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"screen\">\n";
		echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"print\">\n";
		echo "   </head>\n";
		echo "   <body>\n";
		echo "      <table class=\"transparent\" width=\"100%\">\n";
		echo "         <tr>\n";
		echo "            <td width=\"120px\" class=\"logo\"><img height=\"73\" width=\"75\" alt=\"LESB&G Logo\" src=\"images/lesbg-small.gif\"></td>\n"; 
		echo "            <td class=\"title\">$title</td>\n";
		echo "            <td width=\"120px\" class=\"home\">\n";
		echo "            </td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";

		
		$query =	"SELECT user.FirstName, user.Surname, user.Username, attendancetype.AttendanceType, " .
					"       query.ClassName, query.Grade FROM attendance, attendancetype, period, " .
					"       user LEFT OUTER JOIN " .
					"       (SELECT class.ClassName, class.Grade, " .
					"               classlist.Username FROM classlist, classterm, class " .
					"        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"        AND   classterm.TermIndex = $termindex " .
					"        AND   classterm.ClassIndex = class.ClassIndex " .
					"        AND   class.YearIndex = $yearindex) " .
					"       AS query USING (Username) " .
					"WHERE attendance.AttendanceTypeIndex = attendancetype.AttendanceTypeIndex " .
					"AND   attendance.Date = CURDATE() " .
					"AND   user.Username = attendance.Username " .
					"AND   attendance.PeriodIndex = period.PeriodIndex " .
					"AND   period.Period = 1 " .
					"AND   attendance.AttendanceTypeIndex > 0 " .
					"ORDER BY query.Grade, query.ClassName, user.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		if($res->numRows() > 0) {
			/* Print punishments */
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Attendance Type</th>\n";
			echo "         </tr>\n";
			
			/* For each assignment, print a row with the title, date, score and comment */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt_step = "alt";
				} else {
					$alt_step = "std";
				}
				$alt = " class=\"$alt_step\"";
				echo "         <tr$alt>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td>{$row['ClassName']}</td>\n";
				echo "            <td>{$row['AttendanceType']}</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">Nobody is absent, late or suspended today.</p>\n";
		}
	} else {
		include "header.php";
		
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/print/attendance.php", $LOG_DENIED_ACCESS,
					"Tried to view printable attendance information.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
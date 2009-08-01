<?php
	/*****************************************************************
	 * admin/class/list_students.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * List all students in a particular class
	 *****************************************************************/

	$classindex = dbfuncInt2String($_GET["key"]);
	$classname  = dbfuncInt2String($_GET["keyname"]);

	$title = "Student List for $classname";
	
	include "header.php";                                          // Show header

	/* Check whether current user is principal */
	$res =&  $db->query("SELECT Username FROM principal " .
						"WHERE Username=\"$username\" AND Level=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_principal = true;
	} else {
		$is_principal = false;
	}

	/* Check whether current user is a counselor */
	$res =&  $db->query("SELECT Username FROM counselorlist " .
						"WHERE Username=\"$username\"");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	/* Check whether current user is a hod */
	$res =&  $db->query("SELECT Username FROM hod, class " .
						"WHERE Username=\"$username\" " .
						"AND hod.DepartmentIndex = class.DepartmentIndex " .
						"AND class.ClassIndex = $classindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	if($is_admin or $is_counselor or $is_hod or $is_principal) {
		/* Get student list */
		/* Calculate conduct mark */
		$nochangeyear = true;
		$showdeps     = false;
		if($is_admin or $is_counselor or $is_principal) {
			$showalldeps = true;
		} else {
			$admin_page  = true;
		}
		include "core/settermandyear.php";
		include "core/titletermyear.php";

		$res =&  $db->query("SELECT user.FirstName, user.Surname, user.Username, user.User1, user.User2, " .
							"       conduct_mark.Score, classterm.Average, classterm.Rank, " .
							"       COUNT(subjectstudent.SubjectIndex) AS SubjectCount " .
							"       FROM class INNER JOIN classlist USING (ClassIndex) " .
							"            INNER JOIN user USING (Username) " .
							"            LEFT OUTER JOIN classterm ON " .
							"               (classterm.ClassListIndex = classlist.ClassListIndex " .
							"                AND classterm.TermIndex  = $termindex) " .
							"            LEFT OUTER JOIN conduct_mark ON " .
							"              (conduct_mark.Username = classlist.Username " .
							"               AND conduct_mark.YearIndex = class.YearIndex " .
							"               AND conduct_mark.TermIndex = $termindex) " .
							"            LEFT OUTER JOIN (subjectstudent " .
							"               INNER JOIN subject USING (SubjectIndex)) ON " .
							"               (subjectstudent.Username = user.Username " .
							"                AND subject.YearIndex = $yearindex " .
							"                AND subject.TermIndex = $termindex) " .
							"WHERE classlist.ClassIndex = $classindex " .
							"GROUP BY user.Username " .
							"ORDER BY user.FirstName, user.Surname, user.Username");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		/* Print students and their class */
		if($res->numRows() > 0) {
			$orderNum = 0;
			echo "      <table align=\"center\" border=\"1\">\n";  // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Order</th>\n";
			echo "            <th>Student</th>\n";
			if($is_admin or $is_principal) {
				echo "            <th>New</th>\n";
				echo "            <th>Special</th>\n";
				echo "            <th>Subjects</th>\n";
			}
			if($is_admin or $is_hod or $is_principal) {
				echo "            <th>Average</th>\n";
				echo "            <th>Rank</th>\n";
			}
			echo "            <th>Conduct</th>\n";
			echo "            <th>Absent</th>\n";
			echo "            <th>Late</th>\n";
			echo "            <th>Suspended</th>\n";
			echo "         </tr>\n";
			
			/* For each student, print a row with the student's name and class information */
			$alt_count = 0;
			
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!is_null($row['Score'])) {
					$conduct = "{$row['Score']}%";
				} else {
					$conduct = "N/A";
				}
				if($row['Average'] == -1) {
					$average = "N/A";
				} else {
					$average = round($row['Average']);
					$average = "$average%";
				}
				if($row['Rank'] == -1) {
					$rank = "N/A";
				} else {
					$rank = $row['Rank'];
				}

				$absent    = "-";
				$late      = "-";
				$suspended = "-";
				$query =    "SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
							"       FROM view_attendance " .
							"WHERE  Username = \"{$row['Username']}\" " .
							"AND    YearIndex = $yearindex " .
							"AND    TermIndex = $termindex " .
							"AND    Period = 1 " .
							"AND    AttendanceTypeIndex > 0 " .
							"GROUP BY AttendanceTypeIndex ";
				$cRes =&   $db->query($query);
				if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
				while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
					if($cRow['AttendanceTypeIndex'] == $ATT_ABSENT)    $absent    = $cRow['Count'];
					if($cRow['AttendanceTypeIndex'] == $ATT_LATE)      $late      = $cRow['Count'];
					if($cRow['AttendanceTypeIndex'] == $ATT_SUSPENDED) $suspended = $cRow['Count'];
				}
				
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class=\"alt\"";
				} else {
					$alt = " class=\"std\"";
				}
				$orderNum++;
				
				$viewlink = "index.php?location=" . dbfuncString2Int("admin/subject/list_student.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
				$editlink = "index.php?location=" . dbfuncString2Int("admin/user/modify.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
				$cnlink =   "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;keyname2=" .      dbfuncSTring2Int($row['FirstName']);
				$mlink =    "index.php?location=" . dbfuncString2Int("user/new_message.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;key2=" .          dbfuncString2Int($MSG_TYPE_USERNAME) .
							"&amp;next=" .          dbfuncString2Int($here);
				$sublink =  "index.php?location=" . dbfuncString2Int("admin/subject/modify_by_student.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;next=" .          dbfuncString2Int($here);
				$hlink =    "index.php?location=" . dbfuncString2Int("student/discipline.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;next=" .          dbfuncString2Int($here);
				$alink =    "index.php?location=" . dbfuncString2Int("student/absence.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;next=" .          dbfuncString2Int($here);
				$ttlink =	"index.php?location=" .  dbfuncString2Int("user/timetable.php") .
							"&amp;key=" .            dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .        dbfuncString2Int($row['FirstName'] . " " . $row['Surname']);
				$replink =	"index.php?location=" .  dbfuncString2Int("report/show.php") .
							"&amp;key=" .            dbfuncString2Int($row['Username']);

				echo "         <tr$alt>\n";
				/* Generate view and edit buttons */
				if($is_admin) {
					$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View student's subjects");
					$ttbutton   = dbfuncGetButton($ttlink,   "T", "small", "tt",   "View student's timetable");
					$subbutton  = dbfuncGetButton($sublink,  "S", "small", "home", "Edit student's subjects");
					$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Edit student");
					$mbutton    = dbfuncGetButton($mlink,    "M", "small", "msg",  "Send message");
					$hbutton    = dbfuncGetButton($hlink,    "H", "small", "view", "Student's conduct history");
					$abutton    = dbfuncGetButton($alink,    "A", "small", "view", "Student's absence history");
					$repbutton  = dbfuncGetButton($replink,  "R", "small", "home", "Student's report");
				} else {
					$viewbutton = "";
					$ttbutton   = "";
					$subbutton  = "";
					$editbutton = "";
					$mbutton    = "";
					$hbutton    = "";
					$abutton    = "";
					$repbutton  = "";
				}

				$cnbutton   = dbfuncGetButton($cnlink,   "C", "small", "cn", "Casenotes for student");
				echo "            <td>$cnbutton$viewbutton$ttbutton$abutton$subbutton$mbutton$hbutton$repbutton$editbutton</td>\n";
				echo "            <td>$orderNum</td>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				if($is_admin or $is_principal) {
					if($row['User1'] == 1) {
						echo "            <td>X</td>\n";
					} else {
						echo "            <td>&nbsp;</td>\n";
					}
					if($row['User2'] == 1) {
						echo "            <td>X</td>\n";
					} else {
						echo "            <td>&nbsp;</td>\n";
					}
					echo "            <td>{$row['SubjectCount']}</td>\n";
				}
				if($is_admin or $is_principal or $is_hod) {
					echo "             <td>$average</td>\n";
					echo "             <td>$rank</td>\n";
				}
				echo "             <td>$conduct</td>\n";
				echo "             <td>$absent</td>\n";
				echo "             <td>$late</td>\n";
				echo "             <td>$suspended</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no students in this class</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/class/list_students.php", $LOG_ADMIN,
				"Viewed student list for class $classname.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/class/list_students.php", $LOG_DENIED_ACCESS,
				"Attempted to view student list for class $classname.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
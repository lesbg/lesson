<?php
/**
 * ***************************************************************
 * admin/punishment/date_student.php (c) 2006-2016 Jonathan Dieter
 *
 * Set date of next punishment for all students up to set date
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['type'])) {
	if (! isset($_POST['type'])) {
		$link = "index.php?location=" .
				 dbfuncString2Int("admin/punishment/date_student.php") .
				 "&amp;next=" . $_GET['next'];
		include "admin/punishment/choose_type.php";
		exit(0);
	} else {
		$_GET['type'] = dbfuncString2Int($_POST['type']);
	}
}
$dtype = dbfuncInt2String($_GET['type']);

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
		 "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
		 "            INNER JOIN groups USING (GroupID) " .
		 "WHERE user.Username='$username' " .
		 "AND   groups.GroupTypeID='activeteacher' " .
		 "AND   groups.YearIndex=$yearindex " .
		 "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
	$is_teacher = true;
} else {
	$is_teacher = false;
}
$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$perm = $row['Permissions'];
} else {
	$perm = $DEFAULT_PUN_PERM;
}

$query = "SELECT DisciplineType " . "       FROM disciplinetype " .
		 "WHERE  disciplinetype.DisciplineTypeIndex = $dtype ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$disc = strtolower($row['DisciplineType']);
} else {
	$disc = "unknown punishment";
}

$query = "SELECT DisciplineDateIndex, PunishDate, EndDate FROM disciplinedate " .
		 "WHERE DisciplineTypeIndex = $dtype " . "AND   Done = 0";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$pindex = $row['DisciplineDateIndex'];
	$enddate = $row['EndDate'];
	$pundate = $row['PunishDate'];
} else {
	$_GET['next'] = dbfuncString2Int(
									"index.php?location=" .
									 dbfuncString2Int(
													"admin/punishment/date_student.php") .
									 "&amp;type=" . $_GET['type'] . "&amp;next=" .
									 $_GET['next']);
	include "admin/punishment/set_date.php";
	exit(0);
}

$title = "Students to be punished during next $disc";
/* Make sure user has permission to view student's marks for subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 ($perm > $PUN_PERM_ALL and $is_teacher)) {
	if ($_POST["action"] == "Check all") {
		$check_all = 1;
	} elseif ($_POST["action"] == "Uncheck all") {
		$check_all = - 1;
	} else {
		$check_all = 0;
	}
	include "header.php";
	
	$link = "index.php?location=" .
			 dbfuncString2Int("admin/punishment/date_student_action.php") .
			 "&amp;type=" . $_GET['type'] . "&amp;next=" . $_GET['next'];
	$query = "SELECT view_discipline.Username, view_discipline.FirstName, view_discipline.Surname, " .
			 "       view_discipline.Date, " .
			 "       view_discipline.Comment, class.ClassName, view_discipline.DisciplineIndex, " .
			 "       view_discipline.PunishDate, view_discipline.Done " .
			 "       FROM class, classterm, classlist, view_discipline LEFT OUTER JOIN view_attendance ON ( " .
			 "            view_discipline.Username = view_attendance.Username " .
			 "            AND view_attendance.Date = '$pundate' " .
			 "            AND view_attendance.Period = 1 " . "       ) " .
			 "WHERE  view_discipline.YearIndex = $yearindex " .
			 "AND    view_discipline.tUsername IS NOT NULL " .
			 "AND    ((view_discipline.PunishDate IS NULL AND view_discipline.Date <= '$enddate') " .
			 "        OR view_discipline.Done = 0) " .
			 "AND    view_attendance.AttendanceTypeIndex != $ATT_ABSENT " .
			 "AND    view_attendance.AttendanceTypeIndex != $ATT_SUSPENDED " .
			 "AND    view_discipline.DisciplineTypeIndex = $dtype " .
			 "AND    classlist.Username = view_discipline.Username " .
			 "AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
			 "AND    classterm.TermIndex = $termindex " .
			 "AND    class.ClassIndex = classterm.ClassIndex " .
			 "AND    class.YearIndex = $yearindex " .
			 "AND    class.DepartmentIndex = $depindex " .
			 "GROUP BY view_discipline.Username " .
			 "ORDER BY class.Grade, class.ClassName, view_discipline.Username ";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->numRows() > 0) {
		/* Print punishments */
		
		echo "      <form action=\"$link\" method=\"post\" name=\"pundate\">\n"; // Form method
		
		echo "      <p align=\"center\">\n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Edit\">&nbsp; \n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Check all\">&nbsp; \n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Uncheck all\">&nbsp; \n";
		echo "         <input type=\"submit\" name=\"action\" value=\"Done\"> \n";
		echo "      </p>\n";
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Student</th>\n";
		echo "            <th>Class</th>\n";
		echo "            <th>Teacher</th>\n";
		echo "            <th>Violation Date</th>\n";
		echo "            <th>Reason</th>\n";
		echo "         </tr>\n";
		
		/* For each assignment, print a row with the title, date, score and comment */
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			if ($check_all == 0) {
				if (isset($_POST['mass'][$row['Username']])) {
					if ($_POST['mass'][$row['Username']] == "on") {
						$checked = "checked";
					} else {
						$checked = "";
					}
				} else {
					if (! is_null($row['Done'])) {
						$checked = "checked";
					} else {
						$checked = "";
					}
				}
			} elseif ($check_all == 1) {
				$checked = "checked";
			} else {
				$checked = "";
			}
			$alt = " class=\"$alt_step\"";
			echo "         <tr$alt>\n";
			echo "            <td><input type='checkbox' name='mass[]' value='{$row['Username']}' id=\"check{$row['Username']}\" $checked></input></td>\n";
			echo "            <td><label for=\"check{$row['Username']}\">{$row['FirstName']} {$row['Surname']} ({$row['Username']})</label></td>\n";
			echo "            <td><label for=\"check{$row['Username']}\">{$row['ClassName']}</label></td>\n";
			$query = "SELECT Date, Comment, DisciplineIndex, tFirstName, tTitle, " .
					 "       tSurname, PunishDate " .
					 "       FROM view_discipline " .
					 "WHERE  YearIndex = $yearindex " .
					 "AND    tUsername IS NOT NULL " .
					 "AND    ((PunishDate IS NULL AND Date <= '$enddate') " .
					 "        OR Done = 0) " .
					 "AND    DisciplineTypeIndex = $dtype " .
					 "AND    Username = '{$row['Username']}' " .
					 "ORDER BY Date, DisciplineIndex";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			echo "            <td nowrap><label for=\"check{$row['Username']}\">";
			if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "{$nrow['tTitle']} {$nrow['tFirstName']} {$nrow['tSurname']}";
				while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
					echo "<br>{$nrow['tTitle']} {$nrow['tFirstName']} {$nrow['tSurname']}";
				}
			}
			echo "</label></td>\n";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			echo "            <td nowrap><label for=\"check{$row['Username']}\">";
			if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				$dateinfo = date($dateformat, strtotime($nrow['Date']));
				echo "$dateinfo";
				while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
					$dateinfo = date($dateformat, strtotime($nrow['Date']));
					echo "<br>$dateinfo";
				}
			}
			echo "</label></td>\n";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			echo "            <td nowrap><label for=\"check{$row['Username']}\">";
			if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "{$nrow['Comment']}";
				while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
					echo "<br>{$nrow['Comment']}";
				}
			}
			echo "</label></td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";
		echo "      </form>\n";
	} else {
		echo "      <p align=\"center\" class=\"subtitle\">No punishments of this type have been issued and not punished yet up to {$_POST['date']}.</p>\n";
	}
} else {
	include "header.php";
	
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/punishment/date_student.php", 
			$LOG_DENIED_ACCESS, "Tried to set next punishment date.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
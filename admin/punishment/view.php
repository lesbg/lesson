<?php
	/*****************************************************************
	 * admin/punishment/view.php  (c) 2006-2013 Jonathan Dieter
	 *
	 * View punishments
	 *****************************************************************/

	/* Get variables */
	$title           = "Set date for next punishment";
	
	$query =	"SELECT ActiveTeacher FROM user WHERE Username='$username' AND ActiveTeacher=1";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$is_teacher = true;
	} else {
		$is_teacher = false;
	}
	
	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = $DEFAULT_PUN_PERM;
	}

	include "header.php";

	/* Make sure user has permission to view student's marks for subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or ($perm > $PUN_PERM_SEE and $is_teacher)) {
		$query =	"SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight, user.Username, " .
					"       user.FirstName, user.Surname, discipline.Date, discipline.Comment, class.ClassName, " .
					"       discipline.DisciplineIndex, tuser.FirstName AS TFirstName, tuser.Title AS TTitle, " .
					"       tuser.Surname AS TSurname, ruser.FirstName AS RFirstName, ruser.Surname AS RSurname, " .
					"       ruser.Title AS RTitle, " .
					"       disciplinedate.PunishDate, discipline.ServedType " .
					"       FROM class, classterm, classlist, disciplinetype, disciplineweight, " .
					"       user, user AS tuser, user AS ruser, discipline LEFT OUTER JOIN disciplinedate ON " .
					"       discipline.DisciplineDateIndex=disciplinedate.DisciplineDateIndex " .
					"WHERE  disciplineweight.YearIndex = $yearindex " .
					"AND    discipline.WorkerUsername IS NOT NULL " .
					"AND    disciplinedate.Done = 0 " .
					"AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
					"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    classlist.Username = user.Username " .
					"AND    discipline.Username = user.Username " .
					"AND    ruser.Username = discipline.RecordUsername " .
					"AND    tuser.Username = discipline.WorkerUsername " .
					"AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"AND    classterm.TermIndex = $termindex " .
					"AND    class.ClassIndex = classterm.ClassIndex " .
					"AND    class.YearIndex = $yearindex " .
					"ORDER BY discipline.Date DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		if($res->numRows() > 0) {
			/* Print punishments */
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Discipline Type</th>\n";
			echo "            <th>Teacher</th>\n";
			echo "            <th>Violation Date</th>\n";
			echo "            <th>Reason</th>\n";
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
				if($row['DisciplineWeight'] == 0) {
					$alt = " class=\"$alt_step\"";
				} elseif($row['Served'] == 1 and $row['ServedType'] == 1) {
					$alt = " class=\"$alt_step\"";
				} elseif($row['Served'] == 1 and $row['ServedType'] == 0) {
					$alt = " class=\"late-$alt_step\"";
				} else {
					$alt = " class=\"almost-$alt_step\"";
				}
				$dateinfo = date($dateformat, strtotime($row['Date']));
				if($row['ServedDate'] != "") {
					$punish_date = date($dateformat, strtotime($row['ServedDate']));
				} else {
					$punish_date = "&nbsp;";
				}
				echo "         <tr$alt>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td>{$row['ClassName']}</td>\n";
				echo "            <td>{$row['DisciplineType']}</td>\n";
				echo "            <td>{$row['TTitle']} {$row['TFirstName']} {$row['TSurname']}</td>\n";
				echo "            <td>$dateinfo</td>\n";
				echo "            <td>{$row['Comment']}</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">No punishments have been issued this term.</p>\n";
		}
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/view.php", $LOG_DENIED_ACCESS,
					"Tried to set next punishment date.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
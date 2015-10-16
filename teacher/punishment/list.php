<?php
/**
 * ***************************************************************
 * teacher/punishment/list.php (c) 2006-2013 Jonathan Dieter
 *
 * Show list of punishments issued by teacher for this term
 * ***************************************************************
 */

/* Get variables */
$teacherusername = safe(dbfuncInt2String($_GET['key']));
$name = dbfuncInt2String($_GET['keyname']);
$title = "Punishments issued by $name ($teacherusername)";

/*
 * Key wasn't included. The only time I've seen this happen is when a student doesn't logout and lets
 * another student use their computer, so we'll force a logout
 */
if (! isset($_GET['key'])) {
	log_event($LOG_LEVEL_ACCESS, "teacher/punishment/list.php", $LOG_ERROR, 
			"Page was accessed without key (Make sure user logged out).");
	include "user/logout.php";
	exit(0);
}

include "core/settermandyear.php";
include "header.php";

/* Make sure user has permission to view student's marks for subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 $teacherusername == $username) {
	include "core/settermandyear.php";
	
	$query = "SELECT ActiveTeacher FROM user WHERE Username='$username' AND ActiveTeacher=1";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$is_teacher = true;
	} else {
		$is_teacher = false;
	}
	
	$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$teacherusername\"";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = $DEFAULT_PUN_PERM;
	}
	
	$query = "SELECT disciplinetype.DisciplineType, disciplineweight.DisciplineWeight, user.Username, " .
			 "       user.FirstName, user.Surname, discipline.Date, discipline.Comment, class.ClassName, " .
			 "       discipline.DisciplineIndex, disciplinetype.PermLevel, " .
			 "       disciplinedate.PunishDate, discipline.ServedType " .
			 "       FROM class, classterm, classlist, disciplinetype, disciplineweight, " .
			 "       user, discipline LEFT OUTER JOIN disciplinedate ON discipline.DisciplineDateIndex = " .
			 "       disciplinedate.DisciplineDateIndex " .
			 "WHERE  discipline.WorkerUsername = \"$teacherusername\" " .
			 "AND    disciplineweight.YearIndex = $yearindex " .
			 "AND    disciplineweight.TermIndex = $termindex " .
			 "AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
			 "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
			 "AND    classlist.Username = user.Username " .
			 "AND    discipline.Username = user.Username " .
			 "AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
			 "AND    classterm.TermIndex        = $termindex " .
			 "AND    classterm.ClassIndex       = class.ClassIndex " .
			 "AND    class.YearIndex            = $yearindex " .
			 "ORDER BY discipline.Date DESC";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	include "core/titletermyear.php";
	
	$pendlink = "index.php?location=" .
				 dbfuncString2Int("teacher/punishment/request/list.php") .
				 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'];
	
	if ($perm > 0 and $is_teacher) {
		$pendbutton = dbfuncGetButton($pendlink, "Pending", "medium", "", 
									"Pending punishments and punishment removals");
		echo "      <p align=\"center\">$pendbutton</p>\n";
	}
	if ($res->numRows() > 0) {
		/* Print punishments */
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th></th>\n";
		echo "            <th>Discipline Type</th>\n";
		echo "            <th>Date</th>\n";
		echo "            <th>Student</th>\n";
		echo "            <th>Class</th>\n";
		echo "            <th>Reason</th>\n";
		echo "            <th>Punishment Date</th>\n";
		echo "            <th>Showed up?</th>\n";
		if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
			 ($perm >= $PUN_PERM_SEE and $is_teacher)) {
			echo "            <th>Recorded By</th>\n";
		}
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
			if ($row['PermLevel'] == 99) {
				$alt = " class=\"$alt_step\"";
			} elseif (! is_null($row['ServedType']) and $row['ServedType'] == 1) {
				$alt = " class=\"$alt_step\"";
			} elseif ((! is_null($row['ServedType']) and $row['ServedType'] == 0) or
					 $row['DisciplineWeight'] == 0) {
				$alt = " class=\"late-$alt_step\"";
			} else {
				$alt = " class=\"almost-$alt_step\"";
			}
			$dateinfo = date($dateformat, strtotime($row['Date']));
			if (! is_null($row['PunishDate'])) {
				$punish_date = date($dateformat, 
									strtotime($row['PunishDate']));
			} else {
				$punish_date = "&nbsp;";
			}
			echo "         <tr$alt>\n";
			if ($is_teacher) {
				if ($perm >= $PUN_PERM_MASS) {
					$dellink = "index.php?location=" .
						 dbfuncString2Int("admin/punishment/delete_confirm.php") .
						 "&amp;key=" .
						 dbfuncString2Int($row['DisciplineIndex']) . "&amp;next=" .
						 dbfuncString2Int(
										"index.php?location=" .
										 dbfuncString2Int(
														"teacher/punishment/list.php") .
										 "&key={$_GET['key']}&keyname={$_GET['keyname']}");
					$delbutton = dbfuncGetButton($dellink, "D", "small", 
												"delete", "Delete punishment");
				} else {
					$dellink = "index.php?location=" .
							 dbfuncString2Int(
											"teacher/punishment/request/new_removal.php") .
							 "&amp;key=" .
							 dbfuncString2Int($row['DisciplineIndex']) .
							 "&amp;next=" .
							 dbfuncString2Int(
											"index.php?location=" .
											 dbfuncString2Int(
															"teacher/punishment/list.php") .
											 "&key={$_GET['key']}&keyname={$_GET['keyname']}");
					$delbutton = dbfuncGetButton($dellink, "R", "small", 
												"delete", 
												"Request that this punishment be deleted");
				}
				echo "            <td nowrap>$delbutton</td>\n";
			} else {
				echo "            <td nowrap>&nbsp;</td>\n";
			}
			echo "            <td nowrap>{$row['DisciplineType']}</td>\n";
			echo "            <td nowrap>$dateinfo</td>\n";
			echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			echo "            <td nowrap>{$row['ClassName']}</td>\n";
			echo "            <td nowrap>{$row['Comment']}</td>\n";
			echo "            <td nowrap>$punish_date</td>\n";
			if (! is_null($row['ServedType']) and $row['ServedType'] == 1) {
				echo "            <td nowrap>Yes</td>\n";
			} elseif (! is_null($row['ServedType']) and $row['ServedType'] == 0) {
				echo "            <td nowrap>No</td>\n";
			} else {
				echo "            <td nowrap>&nbsp;</td>\n";
			}
			if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
				 ($perm >= $PUN_PERM_SEE and $is_teacher)) {
				$query = "SELECT Title, FirstName, Surname " .
				 "       FROM user, discipline " .
				 "WHERE  discipline.DisciplineIndex = {$row['DisciplineIndex']} " .
				 "AND    discipline.RecordUsername = user.Username ";
			$nrs = &  $db->query($query);
			if (DB::isError($nrs))
				die($nrs->getDebugInfo()); // Check for errors in query
			if ($nrow = & $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "            <td nowrap>{$nrow['Title']} {$nrow['FirstName']} {$nrow['Surname']}</td>\n";
			} else {
				echo "            <td nowrap>&nbsp;</td>\n";
			}
		}
		echo "         </tr>\n";
	}
	echo "      </table>\n";
} else {
	echo "      <p align=\"center\" class=\"subtitle\">You have issued no punishments this term.</p>\n";
}
log_event($LOG_LEVEL_EVERYTHING, "teacher/punishment/list.php", $LOG_TEACHER, 
		"Viewed $name's history of issued punishments.");
} else {
/* Log unauthorized access attempt */
log_event($LOG_LEVEL_ERROR, "teacher/punishment/list.php", $LOG_DENIED_ACCESS, 
		"Tried to access $name's history of issued punishments.");

echo "      <p>You do not have permission to access this page</p>\n";
echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
<?php
/**
 * ***************************************************************
 * admin/punishment/pending_confirm.php (c) 2006-2016 Jonathan Dieter
 *
 * Confirm approval or rejection of pending punishments
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']);

/* Get current user's punishment permissions */
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

$title = "LESSON";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

$link = "index.php?location=" .
		 dbfuncString2Int("admin/punishment/pending.php") . "&amp;next=" .
		 $_GET['next'];

/* Check whether current user is authorized to approve pending punishment */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 ($perm >= $PUN_PERM_APPROVE and $is_teacher)) {
	if ($_POST['action'] == "Approve checked") {
		$do = "approve";
	} elseif ($_POST['action'] == "Reject checked") {
		$do = "reject";
	} else {
		$do = "nothing";
	}
	if ($do != "nothing") {
		echo "      <form action=\"$link\" method=\"post\">\n";
		echo "      <p align=\"center\">Are you sure you want to <b>$do</b>:<br>\n";
		foreach ( $_POST['mass'] as $backlogindex ) {
			$backlogindex = intval($backlogindex);
			/* Get information about punishment */
			$query = "SELECT RequestType FROM disciplinebacklog " .
					 "WHERE  DisciplineBacklogIndex = $backlogindex";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$dtype = $row['RequestType'];
				if ($dtype == 1) {
					$query = "SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, " .
						 "       user.Username, user.FirstName, user.Surname, disciplinebacklog.Date, " .
						 "       disciplinebacklog.Comment, disciplinebacklog.DateOfViolation " .
						 "       FROM disciplinetype, disciplinebacklog, user " .
						 "WHERE  disciplinebacklog.DisciplineTypeIndex = " .
						 "       disciplinetype.DisciplineTypeIndex " .
						 "AND    disciplinebacklog.DisciplineBacklogIndex = $backlogindex " .
						 "AND    disciplinebacklog.Username = user.Username " .
						 "AND    disciplinebacklog.RequestType = 1 ";
					$dowhat = "";
				} else {
					$query = "SELECT disciplinetype.DisciplineType, discipline.WorkerUsername, " .
							 "       user.Username, user.FirstName, user.Surname, " .
							 "       discipline.Comment, discipline.Date AS DateOfViolation, " .
							 "       disciplinebacklog.Date " .
							 "       FROM discipline, disciplinetype, disciplineweight, " .
							 "       disciplinebacklog, user " .
							 "WHERE  discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
							 "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
							 "AND    disciplinebacklog.DisciplineBacklogIndex = $backlogindex " .
							 "AND    discipline.Username = user.Username " .
							 "AND    disciplinebacklog.DisciplineIndex = discipline.DisciplineIndex " .
							 "AND    disciplinebacklog.RequestType = 2 ";
					$dowhat = "removal of ";
				}
				$res = &  $db->query($query);
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
					$dateinfo = date($dateformat, 
									strtotime($row['DateOfViolation']));
					$punishment = "{$row['DisciplineType']} on $dateinfo";
					$log_pun = "{$row['DisciplineType']} on {$row['Date']}";
					echo "              $dowhat$punishment for $name? <input type=\"hidden\" name=\"mass[]\" value=\"$backlogindex\"><br>\n";
				}
			}
		}
		echo "      </p>\n";
		
		echo "         <p align=\"center\">\n";
		echo "            <input type='submit' name='action' value='Yes, $do'>&nbsp; \n";
		echo "            <input type='submit' name='action' value='No, I changed my mind'>\n";
		echo "         </p>";
		echo "      </form>\n";
	}
} else {
	log_event($LOG_LEVEL_ERROR, "admin/punishment/pending_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to approve or reject punishments.");
	echo "      <p>You do not have the authority to $do $type.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}
include "footer.php";
?>
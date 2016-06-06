<?php
/**
 * ***************************************************************
 * admin/punishment/approve_all_pending.php (c) 2006-2016 Jonathan Dieter
 *
 * Approve all pending punishments
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']);

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

/* Get current user's punishment permissions */
$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$perm = $row['Permissions'];
} else {
	$perm = $DEFAULT_PUN_PERM;
}
/* Check whether current user is authorized to delete pending punishment */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 ($perm >= $PUN_PERM_APPROVE and $is_teacher)) {
	if ($_POST['action'] == "Yes, approve all punishments") {
		$showalldeps = true; // edit subjects
		include "core/settermandyear.php";
		
		$title = "LESSON - Approving all punishments";
		$noJS = true;
		$noHeaderLinks = true;
		
		include "header.php";
		
		/* Get information about punishment */
		$query = "SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, user.Username, " .
				 "       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment, " .
				 "       disciplinebacklog.DateOfViolation, disciplinebacklog.DisciplineTypeIndex, " .
				 "       disciplinebacklog.DisciplineBacklogIndex " .
				 "       FROM disciplinetype, disciplinebacklog, user " .
				 "WHERE  disciplinebacklog.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
				 "AND    disciplinebacklog.Username = user.Username " .
				 "AND    disciplinebacklog.RequestType = 1 ";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$backlogindex = $row['DisciplineBacklogIndex'];
			$studentusername = $row['Username'];
			$workerusername = $row['WorkerUsername'];
			$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
			$dateinfo = $row['Date'];
			$violdate = $row['DateOfViolation'];
			$printdate = date($dateformat, 
							strtotime($row['DateOfViolation']));
			$thisdate = date($dateformat);
			$reason = safe($row['Comment']);
			$punishment = "{$row['DisciplineType']} on $printdate";
			$log_pun = "{$row['DisciplineType']} on {$row['DateOfViolation']}";
			
			$nrs = &  $db->query(
							"SELECT DisciplineWeightIndex FROM disciplineweight " .
							 "WHERE  DisciplineTypeIndex={$row['DisciplineTypeIndex']} " .
							 "AND    YearIndex=$yearindex " .
							 "AND    TermIndex=$termindex ");
			if (DB::isError($nrs))
				die($nrs->getDebugInfo()); // Check for errors in query
			if ($nrow = & $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
				$weight_index = $nrow['DisciplineWeightIndex'];
				$query = "INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
						 "                        RecordUsername, DateRequested, DateIssued, " .
						 "                        Date, Comment) " .
						 "       VALUES " .
						 "       ($weight_index, '$studentusername', '$workerusername', '$username', " .
						 "        '$dateinfo', '$thisdate', '$violdate', '$reason')";
				$sres = & $db->query($query);
				if (DB::isError($sres))
					die($sres->getDebugInfo()); // Check for errors in query
				update_conduct_mark($studentusername);
				$sres = & $db->query(
									"DELETE FROM disciplinebacklog " .
									 "WHERE DisciplineBacklogIndex = $backlogindex");
				if (DB::isError($sres))
					die($sres->getDebugInfo()); // Check for errors in query
				
				echo "      <p align=\"center\">$punishment for $name successfully approved.</p>\n";
				log_event($LOG_LEVEL_ADMIN, 
						"admin/punishment/approve_all_pending.php", $LOG_ADMIN, 
						"Approved $log_pun for $name.");
			} else {
				echo "      <p align=\"center\">Unable to approve punishment for $studentusername as $punishment has not been set up for this term.</p>\n";
			}
		}
		/* Get information about punishment */
		$query = "SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, user.Username, " .
				 "       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment, " .
				 "       discipline.DateIssued, disciplinebacklog.DisciplineIndex, " .
				 "       disciplinebacklog.DisciplineBacklogIndex " .
				 "       FROM discipline, disciplineweight, disciplinetype, disciplinebacklog, user " .
				 "WHERE  disciplinebacklog.DisciplineIndex = discipline.DisciplineIndex " .
				 "AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
				 "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
				 "AND    discipline.Username = user.Username " .
				 "AND    disciplinebacklog.RequestType = 2 ";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$backlogindex = $row['DisciplineBacklogIndex'];
			$studentusername = $row['Username'];
			$workerusername = $row['WorkerUsername'];
			$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
			$dateinfo = $row['Date'];
			$violdate = $row['DateIssued'];
			$printdate = date($dateformat, strtotime($row['DateIssued']));
			$thisdate = date($dateformat);
			$reason = safe($row['Comment']);
			$punishment = "{$row['DisciplineType']} on $printdate";
			$log_pun = "{$row['DisciplineType']} on {$row['DateIssued']}";
			
			$query = "DELETE FROM discipline WHERE DisciplineIndex = {$row['DisciplineIndex']}";
			$nrs = & $db->query($query);
			if (DB::isError($nrs))
				die($nrs->getDebugInfo()); // Check for errors in query
			update_conduct_mark($studentusername);
			$sres = & $db->query(
								"DELETE FROM disciplinebacklog " .
								 "WHERE DisciplineBacklogIndex = $backlogindex");
			if (DB::isError($sres))
				die($sres->getDebugInfo()); // Check for errors in query
			
			echo "      <p align=\"center\">Removal of $punishment for $name successfully approved.</p>\n";
			log_event($LOG_LEVEL_ADMIN, 
					"admin/punishment/approve_all_pending.php", $LOG_ADMIN, 
					"Removed $log_pun for $name.");
		}
		echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
	} else {
		$title = "LESSON - Cancelling";
		$noJS = true;
		$noHeaderLinks = true;
		$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		
		include "header.php";
		
		echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
			 "</p>\n";
	}
} else {
	log_event($LOG_LEVEL_ERROR, "admin/punishment/approve_all_pending.php", 
			$LOG_DENIED_ACCESS, "Tried to approve $log_pun for $name.");
	echo "      <p>You do not have the authority to approve this punishment.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}

include "footer.php";
?>
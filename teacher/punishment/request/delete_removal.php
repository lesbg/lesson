<?php
/**
 * ***************************************************************
 * teacher/punishment/request/delete_removal.php (c) 2006 Jonathan Dieter
 *
 * Delete pending punishment removal for student
 * ***************************************************************
 */

/* Get variables */
$backlogindex = safe(dbfuncInt2String($_GET['key']));
$nextLink = dbfuncInt2String($_GET['next']);

if ($_POST['action'] == "Yes, re-instate punishment") {
	$title = "LESSON - Re-instating punishment";
	$noJS = true;
	$noHeaderLinks = true;
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Get information about punishment */
	$query = "SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, user.Username, " .
			 "       user.FirstName, user.Surname, disciplinebacklog.Date " .
			 "       FROM disciplinetype, disciplineweight, " .
			 "       discipline, user, disciplinebacklog " .
			 "WHERE  disciplinebacklog.DisciplineBacklogIndex = $backlogindex " .
			 "AND    disciplinebacklog.DisciplineIndex = discipline.DisciplineIndex " .
			 "AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
			 "AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
			 "AND    discipline.Username = user.Username " .
			 "AND    disciplinebacklog.RequestType = 2 ";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
		$dateinfo = date($dateformat, strtotime($row['Date']));
		$punishment = "{$row['DisciplineType']} on $dateinfo";
		$log_pun = "{$row['DisciplineType']} on {$row['Date']}";
		
		/* Check whether current user is authorized to delete pending punishment */
		if ($is_admin or $row['WorkerUsername'] == $username) {
			$res = &  $db->query(
							"DELETE FROM disciplinebacklog " . // Remove punishment from discipline table
								 "WHERE DisciplineBacklogIndex = $backlogindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			
			echo "      <p align='center'>Successfully deleted request for removal of $punishment for $name.</p>\n";
			log_event($LOG_LEVEL_TEACHER, 
					"teacher/punishment/request/delete_removal.php", 
					$LOG_TEACHER, "Re-instated $log_pun for $name.");
			
			echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";
		} else {
			log_event($LOG_LEVEL_ERROR, 
					"teacher/punishment/request/delete_removal.php", 
					$LOG_DENIED_ACCESS, 
					"Tried to re-instate $log_pun for $name.");
			echo "      <p>You do not have the authority to re-instate this punishment.  <a href='$nextLink'>" .
				 "Click here to continue</a>.</p>\n";
		}
	} else {
		echo "      <p align='center'>This pending punishment doesn't exist.  Perhaps you have already deleted it? " .
			 "<a href='$nextLink'>Click here to continue</a>.</p>\n";
	}
} else {
	$title = "LESSON - Cancelling";
	$noJS = true;
	$noHeaderLinks = true;
	$extraMeta = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
	
	include "header.php";
	
	echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." .
		 "</p>\n";
}

include "footer.php";
?>
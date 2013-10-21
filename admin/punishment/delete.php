<?php
	/*****************************************************************
	 * admin/punishment/delete.php  (c) 2006 Jonathan Dieter
	 *
	 * Delete punishment for student
	 *****************************************************************/

	/* Get variables */
	$disciplineindex  = dbfuncInt2String($_GET['key']);
	$nextLink         = dbfuncInt2String($_GET['next']);

	include "core/settermandyear.php";
	
	if($_POST['action'] == "Yes, delete punishment") {
		$title         = "LESSON - Deleting punishment";
		$noJS          = true;
		$noHeaderLinks = true;
		
		include "header.php";
		
		/* Get information about punishment */
		$query =	"SELECT disciplinetype.DisciplineType, user.Username, " .
					"       user.FirstName, user.Surname, discipline.Date " .
					"       FROM disciplinetype, disciplineweight, discipline, user " .
					"WHERE  discipline.DisciplineIndex = $disciplineindex " .
					"AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
					"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    discipline.Username = user.Username ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$name       = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
			$dateinfo   = date($dateformat, strtotime($row['Date']));
			$punishment = "{$row['DisciplineType']} on $dateinfo";
			$log_pun    = "{$row['DisciplineType']} on {$row['Date']}";
			
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
	
			$query =	"SELECT discipline.WorkerUsername, discipline.RecordUsername " .
						"       FROM discipline " .
						"WHERE  discipline.DisciplineIndex = $disciplineindex " .
						"AND    disciplineperms.Username = '$username' " .
						"AND    ((discipline.WorkerUsername = '$username' " .
						"         OR discipline.RecordUsername = '$username') " .
						"        AND $perm >= $PUN_PERM_MASS) " .
						"OR     $perm >= $PUN_PERM_ALL ";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			/* Check whether current user is authorized to delete punishment */
			if(dbfuncGetPermission($permissions, $PERM_ADMIN) or ($res->numRows() > 0 and $is_teacher)) {
				$res =&  $db->query("DELETE FROM discipline " .          // Remove punishment from discipline table
									"WHERE DisciplineIndex = $disciplineindex");
				if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
				update_conduct_mark($row['Username']);

				echo "      <p align=\"center\">$punishment for $name successfully deleted.</p>\n";
				log_event($LOG_LEVEL_ADMIN, "admin/punishment/delete.php", $LOG_ADMIN,
						"Deleted $log_pun for $name.");
				
				echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
			} else {
				log_event($LOG_LEVEL_ERROR, "admin/punishment/delete.php", $LOG_DENIED_ACCESS,
						"Tried to delete $log_pun for $name.");
				echo "      <p>You do not have the authority to remove this punishment.  <a href=\"$nextLink\">" .
							"Click here to continue</a>.</p>\n";
			}
		} else {
			echo "      <p align=\"center\">This punishment doesn't exist.  Perhaps you have already deleted it? " .
			               "<a href=\"$nextLink\">Click here to continue</a>.</p>\n";
		}
	} else {
		$title         = "LESSON - Cancelling";
		$noJS          = true;
		$noHeaderLinks = true;
		$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		
		include "header.php";
		
		echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." . 
					"</p>\n";
	}
	
	include "footer.php";
?>
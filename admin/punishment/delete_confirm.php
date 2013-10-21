<?php
	/*****************************************************************
	 * admin/punishment/delete_confirm.php  (c) 2006-2013 Jonathan Dieter
	 *
	 * Confirm deletion of a punishment from database
	 *****************************************************************/

	 /* Get variables */
	$disciplineindex = dbfuncInt2String($_GET['key']);
	$nextLink        = dbfuncInt2String($_GET['next']);
	
	include "core/settermandyear.php";
		
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
		
		$title           = "LESSON - Confirm to delete $name's $punishment";
		$noJS            = true;
		$noHeaderLinks   = true;
	
		include "header.php";

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
			$link     = "index.php?location=" . dbfuncString2Int("admin/punishment/delete.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;next=" .          $_GET['next'];
			
			echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $punishment for $name?</p>\n";
			echo "      <form action=\"$link\" method=\"post\">\n";
			echo "         <p align=\"center\">";
			echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete punishment\" \>&nbsp; \n";
			echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
			echo "         </p>";
			echo "      </form>\n";
		} else {
			log_event($LOG_LEVEL_ERROR, "admin/punishment/delete_confirm.php", $LOG_DENIED_ACCESS,
					"Tried to delete $log_pun for $name.");
			echo "      <p>You do not have the authority to remove this punishment.  <a href=\"$nextLink\">" .
						"Click here to continue</a>.</p>\n";
		}
	} else {
		$title           = "LESSON - Punishment doesn't exist!";
		$noJS            = true;
		$noHeaderLinks   = true;
	
		include "header.php";

		echo "      <p align=\"center\">This punishment doesn't exist.  Perhaps you have already deleted it? " .
						"<a href=\"$nextLink\">Click here to continue</a>.</p>\n";
	}
	include "footer.php";
?>
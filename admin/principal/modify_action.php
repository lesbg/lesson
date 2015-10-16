<?php
/**
 * ***************************************************************
 * admin/principal/modify_action.php (c) 2006 Jonathan Dieter
 *
 * Add or remove principals.
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check whether user is authorized to change counselors */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	/* Check which button was pressed */
	if ($_POST["action"] == ">") {
		foreach ( $_POST['remove'] as $remUserName ) {
			$res = &  $db->query(
							"DELETE FROM principal " .
								 "WHERE Username     = \"$remUserName\"");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			log_event($LOG_LEVEL_ADMIN, "admin/principal/modify_action.php", $LOG_ADMIN, 
			"Removed $remUserName from being a principal or head of department.");
		}
		include "admin/principal/modify.php";
	} elseif ($_POST["action"] == "<") {
		foreach ( $_POST['add'] as $addUserName ) {
			if ($_POST['level'] == 1) {
				$level = 1;
				$plevel = "principal";
			} else {
				$level = 2;
				$plevel = "head of department";
			}
			$res = &  $db->query(
							"SELECT Username FROM principal " .
							 "WHERE Username = \"$addUserName\"");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			if ($res->numRows() == 0) {
				$res = & $db->query(
								"INSERT INTO principal (Username, Level) VALUES " .
						 "                           (\"$addUserName\", $level)");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
			}
			log_event($LOG_LEVEL_ADMIN, "admin/principal/modify_action.php", 
					$LOG_ADMIN, "Set $addUserName as a $plevel.");
		}
		include "admin/principal/modify.php";
	} elseif ($_POST["action"] == "Done") {
		$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		$noJS = true;
		$noHeaderLinks = true;
		$title = "LESSON - Redirecting...";
		
		include "header.php";
		
		echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";
		
		include "footer.php";
	} else {
		include "admin/principal/modify.php";
	}
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/principal/modify_action.php", 
			$LOG_DENIED_ACCESS, "Attempted to modify principals.");
	
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Unauthorized access!";
	
	include "header.php";
	
	echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
		 "\"$nextLink\">Click here to continue.</a></p>\n";
	
	include "footer.php";
}

?>
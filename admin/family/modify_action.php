<?php
/**
 * ***************************************************************
 * admin/family/modify_action.php (c) 2015 Jonathan Dieter
 *
 * Run query to modify a family code in the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors
$fcode = safe(dbfuncInt2String($_GET['key']));
$fullname = dbfuncInt2String($_GET['keyname']);

/* Check whether user is authorized to modify family codes */
if ($is_admin) {
	
	/* Modify user */
	$query = "UPDATE family SET FamilyName = '{$_POST['sname']}', " .
			 "FatherName = '{$_POST['fathername']}', " .
			 "MotherName = '{$_POST['mothername']}'";
	if (isset($_POST['changepassword']) && $_POST['changepassword'] != "") {
		$query .= ", Password = '{$_POST['changepassword']}'";
	}
	$query .= " WHERE FamilyCode = '$fcode'";
	$aRes = & $db->query($query);
	if (DB::isError($aRes))
		die($aRes->getDebugInfo()); // Check for errors in query
	log_event($LOG_LEVEL_ADMIN, "admin/family/modify_action.php", $LOG_ADMIN, 
		"Modified the {$_POST['sname']} family ($fcode).");
} else { // User isn't authorized to view or change users.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/family/modify_action.php", 
			$LOG_DENIED_ACCESS, "Attempted to modify the $fullname family ($fcode).");
	echo "</p>\n      <p>You do not have permission to modify this family code.</p>\n      <p>";
	$error = true;
}

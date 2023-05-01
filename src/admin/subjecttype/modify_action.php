<?php
/**
 * ***************************************************************
 * admin/subjecttype/modify_action.php (c) 2005 Jonathan Dieter
 *
 * Run query to insert a new subject type into the database.
 * ***************************************************************
 */
$subjecttypeindex = dbfuncInt2String($_GET['key']);
$subjecttype = dbfuncInt2String($_GET['keyname']);
$error = false; // Boolean to store any errors

/* Check whether user is authorized to change scores */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	$aRes = & $db->query(
						"UPDATE subjecttype SET Title={$_POST['title']}, Description={$_POST['descr']} " .
						 "WHERE  SubjectTypeIndex = $subjecttypeindex");
	if (DB::isError($aRes))
		die($aRes->getDebugInfo()); // Check for errors in query
	log_event($LOG_LEVEL_ADMIN, "admin/subjecttype/modify_action.php", $LOG_ADMIN, 
		"Modified information about subject type {$_POST['title']}.");
} else { // User isn't authorized to change subject types.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/subjecttype/modify_action.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to change information about subject type $subjecttype.");
	echo "</p>\n      <p>You do not have permission to change this subject type.</p>\n      <p>";
	$error = true;
}
?>

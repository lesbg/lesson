<?php
/**
 * ***************************************************************
 * admin/family/modify_action.php (c) 2015-2016 Jonathan Dieter
 *
 * Run query to modify a family code in the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors
$fcode = dbfuncInt2String($_GET['key']);
$fcode_db = safe($fcode);

$fullname = dbfuncInt2String($_GET['keyname']);

/* Check whether user is authorized to modify family codes */
if ($is_admin) {
	
	/* Modify user */
	$query = "UPDATE family SET FamilyName = '{$_POST['fname']}' " .
			 " WHERE FamilyCode = '$fcode_db'";
	$aRes = & $db->query($query);
	if (DB::isError($aRes))
		die($aRes->getDebugInfo()); // Check for errors in query
	
	if(!isset($_POST['show_users']) || $_POST['show_users'] != '1') {
			
		/* Remove family members that have been removed */
		foreach($_POST['remove_uname'] as $i => $uname) {
			$query =	"DELETE FROM familylist " .
						"WHERE FamilyCode = '$fcode_db' " .
						"AND   Username = '$uname' ";
			$aRes = & $db->query($query);
			if (DB::isError($aRes))
				die($aRes->getDebugInfo()); // Check for errors in query
		}
		
		/* Add any family members we've added */
		foreach($_POST['uname'] as $val) {
			$uname = $val[0];
			$guardian = $val[1];
			$query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='$uname' AND FamilyCode='$fcode_db'";
			$aRes = & $db->query($query);
			if (DB::isError($aRes))
				die($aRes->getDebugInfo()); // Check for errors in query
				if ($aRes->numRows() == 0) {
					$query = "INSERT INTO familylist (Username, FamilyCode, Guardian) VALUES ('$uname', '$fcode_db', $guardian)";
					$aRes = & $db->query($query);
					if (DB::isError($aRes))
						die($aRes->getDebugInfo()); // Check for errors in query
				} else {
					$query = "UPDATE familylist SET Guardian=$guardian WHERE Username='$uname' AND FamilyCode='$fcode_db'";
					$aRes = & $db->query($query);
					if (DB::isError($aRes))
						die($aRes->getDebugInfo()); // Check for errors in query
				}
		}
	} else {
		if(!isset($_SESSION['post'])) {
			$_SESSION['post'] = array();
		}
		if(!isset($_SESSION['post']['fcode'])) {
			$_SESSION['post']['fcode'] = array();
		}
		$_SESSION['post']['fcode'][] = array($fcode, 0);
	}
	
	/* Add new family members */
	log_event($LOG_LEVEL_ADMIN, "admin/family/modify_action.php", $LOG_ADMIN, 
		"Modified the {$_POST['fname']} family ($fcode).");
} else { // User isn't authorized to view or change users.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/family/modify_action.php", 
			$LOG_DENIED_ACCESS, "Attempted to modify the $fullname family ($fcode).");
	echo "</p>\n      <p>You do not have permission to modify this family code.</p>\n      <p>";
	$error = true;
}

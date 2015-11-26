<?php
/**
 * ***************************************************************
 * admin/family/new_action.php (c) 2015 Jonathan Dieter
 *
 * Run query to insert a new family code into the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors

/* Check whether user is authorized to change scores */
if ($is_admin) {	
	$codei = strtoupper(substr($_POST['sname'], 0, 4));
	
	if ($_POST['autofcode'] == "Y") {
		$res = & $db->query(
						"SELECT FamilyCode FROM family WHERE FamilyCode REGEXP '{$codei}.*' ORDER BY FamilyCode DESC LIMIT 1");
		if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$num = intval(substr($row['FamilyCode'], 4)) + 1;
			$_POST['fcode'] = sprintf("{$codei}%04d", $num);
		} else {
			$_POST['fcode'] = "{$codei}0001";
		}
		echo "</p>\n      <p>The {$_POST['sname']} family's code is {$_POST['fcode']}.</p>\n      <p>";
	}
	
	/* Set primary password to be the same as username if not entered */
	if (! isset($_POST['changepassword']) or $_POST['changepassword'] == "") {
		$_POST['changepassword'] = password_hash($_POST['fcode'], PASSWORD_DEFAULT);
	}
	
	/* Check whether a user already exists with new username */
	$res = & $db->query(
					"SELECT FamilyCode FROM family WHERE FamilyCode = '{$_POST['fcode']}'");
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "</p>\n      <p>There is already a family with that family code.  " .
			 "Press \"Back\" to fix the problem.</p>\n      <p>";
		$error = true;
	} else {
		/* Add new user */
		$query = "INSERT INTO family (FamilyCode, FatherName, MotherName, FamilyName, Password) " .
				 "VALUES ('{$_POST['fcode']}', '{$_POST['fathername']}', '{$_POST['mothername']}', " .
				 "        '{$_POST['sname']}', '{$_POST['changepassword']}')";
		$aRes = & $db->query($query);
		if (DB::isError($aRes))
			die($aRes->getDebugInfo()); // Check for errors in query
		log_event($LOG_LEVEL_ADMIN, "admin/family/new_action.php", $LOG_ADMIN, 
			"Added new code {$_POST['fcode']} for the {$_POST['sname']} family.");
	}
} else { // User isn't authorized to view or change scores.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/family/new_action.php", $LOG_DENIED_ACCESS, 
			"Attempted to create family code for the {$_POST['$sname']} family.");
	echo "</p>\n      <p>You do not have permission to add a family code.</p>\n      <p>";
	$error = true;
}

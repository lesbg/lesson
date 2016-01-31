<?php
/**
 * ***************************************************************
 * admin/user/new_action.php (c) 2005 Jonathan Dieter
 *
 * Run query to insert a new user into the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors

/* Check whether user is authorized to change scores */
if ($is_admin) {	
	$fi = strtolower(substr($_POST['fname'], 0, 1));
	$si = strtolower(substr($_POST['sname'], 0, 1));
	
	if ($_POST['autouname'] == "Y") {
		$num = 1;
		$query = "SELECT Username FROM user WHERE Username REGEXP '{$fi}{$si}.*' ORDER BY Username";
		while(true) {
			$res = & $db->query($query);
			
			$found = false;
			while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(intval(substr($row['Username'], 2)) == $num) {
					$found = true;
					$num += 1;
					break;
				}
			}
			if(!$found) {
				break;
			}
		}
		
		$_POST['uname'] = sprintf("{$fi}{$si}%04d", $num);
		echo "</p>\n      <p>{$_POST['fname']}'s username is {$_POST['uname']}.</p>\n      <p>";
	}
	
	
	/* Check whether a user already exists with new username */
	$res = & $db->query(
					"SELECT Username FROM user WHERE Username = '{$_POST['uname']}'");
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "</p>\n      <p>There is already a user with that username.  " .
			 "Press \"Back\" to fix the problem.</p>\n      <p>";
		$error = true;
	} else {
		/* Add new user */
		echo strlen($_POST['password']);
		if(isset($_POST['password']) && strlen($_POST['password']) > 0) {
			$phash = password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => "15"]);
		} else {
			$phash = password_hash($_POST['uname'], PASSWORD_DEFAULT, ['cost' => "15"]);
		}
		if(isset($_POST['password2']) && strlen($_POST['password2']) > 0) {
			$phash2 = password_hash($_POST['password2'], PASSWORD_DEFAULT, ['cost' => "15"]);
		} else {
			$phash2 = "!!";
		}
		
		$query = "INSERT INTO user (Username, FirstName, Surname, Gender, DOB, Password, Password2, " .
				 "                  Permissions, Title, PhoneNumber, DateType, DateSeparator, " .
				 "                  ActiveStudent, ActiveTeacher, SupportTeacher, DepartmentIndex, " .
				 "                  User1, User2) " .
				 "VALUES ('{$_POST['uname']}', '{$_POST['fname']}', '{$_POST['sname']}', " .
				 "        '{$_POST['gender']}', {$_POST['DOB']}, '$phash', " .
				 "        '$phash2', " .
				 "        {$_POST['perms']}, {$_POST['title']}, '{$_POST['phone']}', " .
				 "        {$_POST['datetype']}, {$_POST['datesep']}, {$_POST['activestudent']}, " .
				 "        {$_POST['activeteacher']}, {$_POST['supportteacher']}, {$_POST['department']}, " .
				 "        {$_POST['user1']}, {$_POST['user2']})";
		echo "$query";
		$aRes = & $db->query($query);
		if (DB::isError($aRes))
			die($aRes->getDebugInfo()); // Check for errors in query
		
		if(!isset($_POST['show_family']) || $_POST['show_family'] != '1') {
			/* Remove any family codes we've been removed from */
			$query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='{$_POST['uname']}'";
			$aRes = & $db->query($query);
			if (DB::isError($aRes))
				die($aRes->getDebugInfo()); // Check for errors in query
			while ( $arow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$found = False;
				foreach($_POST['fcode'] as $val) {
					if($aRow['FamilyCode'] == $val[0])
						$found = True;
				}
				if(!$found) {
					$query = "DELETE FROM familylist WHERE FamilyListIndex={$arow['FamilyListIndex']}";
					$bRes = & $db->query($query);
					if (DB::isError($bRes))
						die($bRes->getDebugInfo()); // Check for errors in query
				}
			}
			
			/* Add any family codes we've been added to */
			foreach($_POST['fcode'] as $val) {
				$fcode = $val[0];
				$guardian = $val[1];
				$query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='{$_POST['uname']}' AND FamilyCode='$fcode'";
				$aRes = & $db->query($query);
				if (DB::isError($aRes))
					die($aRes->getDebugInfo()); // Check for errors in query
				if ($aRes->numRows() == 0) {
					$query = "INSERT INTO familylist (Username, FamilyCode, Guardian) VALUES ('{$_POST['uname']}', '$fcode', $guardian)";
					$aRes = & $db->query($query);
					if (DB::isError($aRes))
						die($aRes->getDebugInfo()); // Check for errors in query
				} else {
					$query = "UPDATE familylist SET Guardian=$guardian WHERE Username='{$_POST['uname']}' AND FamilyCode='$fcode'";
					$aRes = & $db->query($query);
					if (DB::isError($aRes))
						die($aRes->getDebugInfo()); // Check for errors in query
				}
			}
		} else {
			if(!isset($_SESSION['post_family'])) {
				$_SESSION['post_family'] = array();
			}
			if(!isset($_SESSION['post_family']['uname'])) {
				$_SESSION['post_family']['uname'] = array();
			}
			if($_POST['new_user_type'] == 'f' ||$_POST['new_user_type'] == 'm') {
				$guardian = 1;
			} else {
				$guardian = 0;
			}
			$_SESSION['post_family']['uname'][] = array($_POST['uname'], $guardian);
		}
		
		/* Remove any groups we've been removed from */
		$query = "SELECT GroupMemberIndex, GroupIndex FROM groupmem WHERE Member='{$_POST['uname']}'";
		$aRes = & $db->query($query);
		if (DB::isError($aRes))
			die($aRes->getDebugInfo()); // Check for errors in query
		while ( $arow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
			if(!in_array($aRow['GroupIndex'], $_POST['groups'])) {
				$query = "DELETE FROM groupmem WHERE GroupMemberIndex={$arow['GroupMemberIndex']}";
				$bRes = & $db->query($query);
				if (DB::isError($bRes))
					die($bRes->getDebugInfo()); // Check for errors in query
			}
		}
		
		/* Add any groups we've been added to */
		foreach($_POST['groups'] as $group) {
			$query = "SELECT GroupMemberIndex, GroupIndex FROM groupmem WHERE Member='{$_POST['uname']}' AND GroupIndex='$group'";
			$aRes = & $db->query($query);
			if (DB::isError($aRes))
				die($aRes->getDebugInfo()); // Check for errors in query
			if ($aRes->numRows() == 0) {
				$query = "INSERT INTO groupmem (Member, GroupIndex) VALUES ('{$_POST['uname']}', '$group')";
				$aRes = & $db->query($query);
				if (DB::isError($aRes))
					die($aRes->getDebugInfo()); // Check for errors in query
			}
			gen_group_members($group);
		}
		
		log_event($LOG_LEVEL_ADMIN, "admin/user/new_action.php", $LOG_ADMIN, 
			"Added {$_POST['fname']} {$_POST['sname']} ($uname).");
	}
} else { // User isn't authorized to view or change scores.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/user/new_action.php", $LOG_DENIED_ACCESS, 
			"Attempted to create user $fullname.");
	echo "</p>\n      <p>You do not have permission to add a user.</p>\n      <p>";
	$error = true;
}
?>

<?php
	/*****************************************************************
	 * admin/user/new_action.php  (c) 2005 Jonathan Dieter
	 *
	 * Run query to insert a new user into the database.
	 *****************************************************************/

	/* Get variables */
	$error = false;        // Boolean to store any errors
	
	 /* Check whether user is authorized to change scores */
	if($is_admin) {
		/* Set primary password to be the same as username if not entered */
		if(!isset($_POST['password']) or $_POST['password'] == "") {
			$_POST['password'] = $_POST['uname'];
		}
		
		/* Set secondary password to null if not entered */
		if(!isset($_POST['password2']) or $_POST['password2'] == "") {
			$_POST['password2'] = "NULL";
		} else {
			$_POST['password2'] = "MD5('{$_POST['password2']}')";
		}
		
		/* Check whether a user already exists with new username */
		$res  =& $db->query("SELECT username FROM user WHERE username = '{$_POST['uname']}'");
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			echo "</p>\n      <p>There is already a user with that username.  " .
			                    "Press \"Back\" to fix the problem.</p>\n      <p>";
			$error = true;
		} else {
			/* Add new user */
			$query =	"INSERT INTO user (Username, FirstName, Surname, Gender, DOB, Password, Password2, " .
						"                  Permissions, Title, PhoneNumber, DateType, DateSeparator, " .
						"                  ActiveStudent, ActiveTeacher, SupportTeacher, " .
						"                  User1, User2) " .
						"VALUES ('{$_POST['uname']}', '{$_POST['fname']}', '{$_POST['sname']}', " .
						"        '{$_POST['gender']}', {$_POST['DOB']}, MD5('{$_POST['password']}'), " .
						"        {$_POST['password2']}, " .
						"        {$_POST['perms']}, {$_POST['title']}, '{$_POST['phone']}', " .
						"        {$_POST['datetype']}, {$_POST['datesep']}, {$_POST['activestudent']}, " .
						"        {$_POST['activeteacher']}, {$_POST['supportteacher']}, " .
						"        {$_POST['user1']}, {$_POST['user2']})";
			$aRes =& $db->query($query);
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
			log_event($LOG_LEVEL_ADMIN, "admin/user/new_action.php", $LOG_ADMIN,
					"Added {$_POST['fname']} {$_POST['sname']} ($uname).");

		}
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/user/new_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create user $fullname.");
		echo "</p>\n      <p>You do not have permission to add a user.</p>\n      <p>";
		$error = true;
	}
?>

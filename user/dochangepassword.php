<?php
/**
 * ***************************************************************
 * user/dochangepassword.php (c) 2005 Jonathan Dieter
 *
 * Change password for user, or cancel if that's what was chosen
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

if ($password_number == 2) {
	$pass_str = "Password2";
} else {
	$pass_str = "Password";
}

/* Check which button was pressed */
if ($_POST["action"] == "Ok") { // If ok was pressed, try to change password
	/* Check whether password has been set to username and give error if it was */
	if (isset($_POST["new"]) and (
		 strtoupper($_POST["new"]) == strtoupper($username) or 
		 strtoupper($_POST["new"]) == strtoupper("p$username"))) {
		$error = true;
		include "user/changepassword.php";
		exit();
	}
		
	$title = "LESSON - Saving changes...";
	$noHeaderLinks = true;
	$noJS = true;
	
	include "header.php"; // Print header
	
	echo "      <p align='center'>Changing password...";
	
	$good_pw = False;
	
	/* Check whether old MD5 password is correct */
	$res = & $db->query(
					"SELECT Username FROM user " .
					 "WHERE Username = '$username' " .
					 "AND   $pass_str = MD5('{$_POST['old']}')");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->NumRows() > 0) {
		$good_pw = True;
	}
	
	/* Check whether old password_hash password is correct */
	$res = & $db->query(
			"SELECT $pass_str FROM user " .
			"WHERE Username = '$username' ");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) && password_verify($_POST['old'], $row[$pass_str])) {
		$good_pw = True;
	}
	
	if($good_pw) {
		if (strlen($_POST["new"]) >= 6) {
			if ($_POST["new"] == $_POST["confirmnew"]) {
				$phash = password_hash($_POST['new'], PASSWORD_DEFAULT, ['cost' => "15"]);
				$res = & $db->query(
								"UPDATE user SET $pass_str = '$phash' " .
									 "WHERE Username = '$username'");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
				echo "done.</p>\n";
				unset($_SESSION['samepass']);
				unset($_SESSION['samepass2']);
				log_event($LOG_LEVEL_ADMIN, "user/dochangepassword.php", 
						$LOG_USER, "Changed password $password_number.");
			} else {
				echo "failed!</p>\n";
				echo "      <p align='center'>The new password didn't match the confirm new password!</p>\n";
				log_event($LOG_LEVEL_EVERYTHING, "user/dochangepassword.php", 
						$LOG_ERROR, 
						"The new password didn't match the confirm new password.");
			}
		} else {
			echo "failed!</p>\n";
			echo "      <p align='center'>The new password must contain at least six characters!</p>\n";
			log_event($LOG_LEVEL_EVERYTHING, "user/dochangepassword.php", 
					$LOG_ERROR, "The new password wasn't long enough.");
		}
	} else {
		echo "failed!</p>\n";
		echo "      <p align='center'>The old password wasn't correct!</p>\n";
		
		log_event($LOG_LEVEL_ERROR, "user/dochangepassword.php", $LOG_ERROR, 
				"Typed wrong passward when trying to change password.");
	}
	
	echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
	
	include "footer.php";
} else {
	if($samepass) {
		include "user/logout.php";
	} else {
		$extraMeta = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
		$noJS = true;
		$noHeaderLinks = true;
		$title = "LESSON - Cancelling...";
		
		include "header.php";
		
		echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." .
			 "</p>\n";
		
		include "footer.php";
	}
}
?>
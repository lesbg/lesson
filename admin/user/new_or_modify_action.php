<?php
/**
 * ***************************************************************
 * admin/user/new_or_modify_action.php (c) 2005 Jonathan Dieter
 *
 * Show common page information for changing or adding a new user
 * and call appropriate second page.
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check which button was pressed */
if ($_POST["action"] == "Save" || $_POST["action"] == "Update") { // If update or save were pressed, print
	$title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
	$noHeaderLinks = true;
	$noJS = true;
	
	include "header.php"; // Print header
	
	$error = false;
	
	if (! isset($_POST['department']))
		$_POST['department'] = "NULL";
	if ($_POST['department'] != "NULL")
		$_POST['department'] = intval($_POST['department']);
	
	$_POST['uname'] = trim($_POST['uname']);
	if ((! isset($_POST['uname']) or $_POST['uname'] == "") and
		 $_POST["action"] == "Save" and
		 (! isset($_POST['autouname']) or $_POST['autouname'] == "N")) { // Make sure a username was written.
		echo "<p>You need to write a username.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['uname'] = safe($_POST['uname']);
	}
	
	$_POST['fname'] = trim($_POST['fname']);
	if (! isset($_POST['fname']) || $_POST['fname'] == "") { // Make sure a first name was written.
		echo "<p>You need to write a first name.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['fname'] = safe($_POST['fname']);
	}
	
	$_POST['sname'] = trim($_POST['sname']);
	if (! isset($_POST['sname']) || $_POST['sname'] == "") { // Make sure a surname was written.
		echo "<p>You need to write a first name.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['sname'] = safe($_POST['sname']);
	}
	
	if ($_POST['password'] != $_POST['confirmpassword']) { // Make sure passwords match.
		echo "<p>The primary passwords don't match.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['password'] = safe($_POST['password']);
		$_POST['confirmpassword'] = $_POST['password'];
	}
	
	if ($_POST['password2'] != $_POST['confirmpassword2']) { // Make sure passwords match.
		echo "<p>The secondary passwords don't match.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['password2'] = safe($_POST['password2']);
		$_POST['confirmpassword2'] = $_POST['password2'];
	}
	
	$_POST['phone'] = trim($_POST['phone']);
	if ($_POST['phone'] != "") {
		if (substr($_POST['phone'], 0, 1) != "+") {
			if ($phone_prefix != "") {
				if ($phone_RLZ) {
					$_POST['phone'] = ltrim($_POST['phone'], "0");
				}
				$_POST['phone'] = $phone_prefix . $_POST['phone'];
			}
		} else {
			$_POST['phone'] = substr($_POST['phone'], 1);
		}
	}
	
	if (! $error) {
		echo "      <p align=\"center\">Saving changes...";
		
		if (! isset($_POST['perms']) || $_POST['perms'] == "") { // Make sure permissions are in correct format.
			$_POST['perms'] = "0";
		}
		
		if (! isset($_POST['DOB']) || $_POST['DOB'] == "") { // Make sure DOB is in correct format.
			$_POST['DOB'] = "NULL";
		} else {
			$tmpDate = & dbfuncCreateDate($_POST['DOB']);
			$_POST['DOB'] = "'" . $tmpDate . "'";
		}
		
		if (! isset($_POST['title']) || $_POST['title'] == "") { // Make sure title is in correct format.
			$_POST['title'] = "NULL";
		} else {
			$_POST['title'] = "'" . $_POST['title'] . "'";
		}
		
		if ($_POST['datetype'] == "D") // Take care of date type.
			$_POST['datetype'] = "NULL";
		
		if ($_POST['datesep'] == "D") { // Take care of date separator.
			$_POST['datesep'] = "NULL";
		} else {
			$_POST['datesep'] = "'" . $_POST['datesep'] . "'";
		}
		
		if ($_POST['activestudent'] == "on") { // Make sure ActiveStudent is right type.
			$_POST['activestudent'] = "1";
		} else {
			$_POST['activestudent'] = "0";
		}
		
		if ($_POST['activeteacher'] == "on") { // Make sure ActiveTeacher is right type.
			$_POST['activeteacher'] = "1";
		} else {
			$_POST['activeteacher'] = "0";
		}
		
		if ($_POST['supportteacher'] == "on") { // Make sure ActiveTeacher is right type.
			$_POST['supportteacher'] = "1";
		} else {
			$_POST['supportteacher'] = "0";
		}
		
		if ($_POST['user1'] == "on") { // Make sure User1 is right type.
			$_POST['user1'] = "1";
		} else {
			$_POST['user1'] = "0";
		}
		
		if ($_POST['user2'] == "on") { // Make sure User2 is right type.
			$_POST['user2'] = "1";
		} else {
			$_POST['user2'] = "0";
		}
		
		if ($_POST["action"] == "Save") { // Create new user if "Save" was pressed
			include "admin/user/new_action.php";
		} else {
			include "admin/user/modify_action.php"; // Modify user if "Update" was pressed
		}
		
		if ($error) { // If we ran into any errors, print failed, otherwise print done
			echo "failed!</p>\n";
		} else {
			echo "done.</p>\n";
		}
		
		echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page
	}
	
	include "footer.php";
} elseif ($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
	include "admin/user/delete_confirm.php";
} else {
	$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Cancelling...";
	
	include "header.php";
	
	echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
		 "</p>\n";
	
	include "footer.php";
}
?>
<?php
/**
 * ***************************************************************
 * admin/family/new_or_modify_action.php (c) 2015 Jonathan Dieter
 *
 * Show common page information for changing or adding a new
 * family code and call appropriate second page.
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
	
	
	$_POST['fcode'] = trim($_POST['fcode']);
	if ((! isset($_POST['fcode']) or $_POST['fcode'] == "") and
		 $_POST["action"] == "Save" and
		 (! isset($_POST['autofcode']) or $_POST['autofcode'] == "N")) { // Make sure a username was written.
		echo "<p>You need to write a family code.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['fcode'] = safe($_POST['fcode']);
	}
		
	$_POST['sname'] = trim($_POST['sname']);
	if (! isset($_POST['sname']) || $_POST['sname'] == "") { // Make sure a surname was written.
		echo "<p>You need to write a surname.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		$_POST['sname'] = safe($_POST['sname']);
	}
	
	$_POST['fathername'] = trim($_POST['fathername']);
	if (! isset($_POST['fathername']) || $_POST['fathername'] == "") { // Make sure a first name was written.
		$_POST['fathername'] = "";
	} else {
		$_POST['fathername'] = safe($_POST['fathername']);
	}
	
	$_POST['mothername'] = trim($_POST['mothername']);
	if (! isset($_POST['mothername']) || $_POST['mothername'] == "") { // Make sure a first name was written.
		$_POST['mothername'] = "";
	} else {
		$_POST['mothername'] = safe($_POST['mothername']);
	}
	
	
	if ($_POST['changepassword'] != $_POST['confirmpassword']) { // Make sure passwords match.
		echo "<p>The passwords don't match.  Press \"Back\" to fix this.</p>\n";
		$error = true;
	} else {
		if (isset($_POST['changepassword']) && $_POST['changepassword'] != "") {
			$_POST['changepassword'] = password_hash(safe($_POST['changepassword']), PASSWORD_DEFAULT);
			$_POST['confirmpassword'] = $_POST['password'];
		}
	}
	
	$_POST['phone'] = trim($_POST['phone']);
	if ($_POST['phone'] != "") {
		if (substr($_POST['phone'], 0, 1) != "+") {
			if ($phone_prefix != "") {
				if ($phone_RLZ) {
					$_POST['phone'] = ltrim($_POST['phone'], "0");
				}
				$_POST['phone'] = "+" . $phone_prefix . safe($_POST['phone']);
			}
		} else {
			$_POST['phone'] = safe($_POST['phone']);
		}
	}
	
	if (! $error) {
		echo "      <p align=\"center\">Saving changes...";
				
		if ($_POST["action"] == "Save") { // Create new user if "Save" was pressed
			include "admin/family/new_action.php";
		} else {
			include "admin/family/modify_action.php"; // Modify user if "Update" was pressed
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
	include "admin/family/delete_confirm.php";
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
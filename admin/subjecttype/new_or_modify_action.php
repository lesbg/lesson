<?php
/**
 * ***************************************************************
 * admin/subjecttype/new_or_modify_action.php (c) 2005 Jonathan Dieter
 *
 * Change subject type information
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check which button was pressed */
if ($_POST["action"] == "Save" || $_POST["action"] == "Update") { // If update or save were pressed, print
                                                                    // common info and go to the appropriate page.
	/* Check for input errors */
	$format_error = False;
	$errorlist = "";
	if (! isset($_POST['title']) || is_null($_POST['title']) ||
		 $_POST['title'] == "") { // Make sure name has been entered
		$errorlist .= "<p class=\"error\" align=\"center\">You must specifiy a subject type name!</p>\n";
		$format_error = True;
		log_event($LOG_LEVEL_ADMIN, 
				"admin/subjecttype/new_or_modify_action.php", $LOG_ERROR, 
				"Tried to set subject type information, but forgot to specify a subject type name.");
	}
	
	if (! $format_error) {
		$_POST['title'] = "'" . $db->escapeSimple($_POST['title']) . "'";
		if (! isset($_POST['descr']) || is_null($_POST['descr']) ||
			 $_POST['descr'] == "") {
			$_POST['descr'] = "NULL";
		} else {
			$_POST['descr'] = "'" . $db->escapeSimple($_POST['descr']) . "'";
		}
		
		$errorlist = ""; // Clear error list. This list will now only contain database errors.
		
		$title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
		$noHeaderLinks = true;
		$noJS = true;
		
		include "header.php"; // Print header
		
		echo "      <p align=\"center\">Saving changes...";
		
		if ($_POST["action"] == "Save") { // Create new subject if "Save" was pressed
			include "admin/subjecttype/new_action.php";
		} else {
			include "admin/subjecttype/modify_action.php"; // Change subject if "Update" was pressed
		}
		
		if ($error) { // If we ran into any errors, print failed, otherwise print done
			echo "failed!</p>\n";
		} else {
			echo "done.</p>\n";
		}
		echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page
		include "footer.php";
	} else {
		if ($_POST["action"] == "Save") {
			include "admin/subjecttype/new.php";
		} else {
			include "admin/subjecttype/modify.php";
		}
	}
} elseif ($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
	include "admin/subjecttype/delete_confirm.php";
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
<?php
	/*****************************************************************
	 * admin/book/new_or_modify_copy_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Change book copy information
	 *****************************************************************/

	/* Get variables */
	$key          = dbfuncInt2String($_GET['key']);                    // Key
	$nextLink     = dbfuncInt2String($_GET['next']);                   // Link to next page
	
	/* Check which button was pressed */
	if($_POST["action"] == "Save" or $_POST["action"] == "Update") {   // If update or save were pressed, print
		                                                               //  common info and go to the right page.
		/* Check for input errors */
		$format_error = False;
		$errorlist    = "";
		if(!isset($_POST['number']) or is_null($_POST['number']) or $_POST['number'] == "") {  // Make sure number has been entered
			$errorlist      .= "<p class='error' align='center'>You must specify a book number!</p>\n";
			$format_error    = True;
		}
		
		$number = safe($_POST['number']);

		if(!$format_error){	
			$errorlist = "";   // Clear error list.  This list will now only contain database errors.
			
			$book         = "LESSON - Saving changes...";
			$noHeaderLinks = true;
			$noJS          = true;
			
			include "header.php";
			
			echo "      <p align='center'>Saving changes...";
			
			if($_POST['type'] == "new") {          // Create new title if "Save" was pressed
				include "admin/book/new_copy_action.php";
			} else {
				include "admin/book/modify_copy_action.php";    // Change title if "Update" was pressed
			}
			
			if($error) {    // If we ran into any errors, print failed, otherwise print done
				echo "failed!</p>\n";
			} else {
				echo "done.</p>\n";
			}
			echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
			include "footer.php";
		} else {
			if($_POST['type'] == "new") {
				include "admin/book/new_copy.php";
			} else {
				include "admin/book/modify_copy.php";
			}
		}
	} else { // if($_POST['action'] == "Cancel")
		$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		$noJS          = true;
		$noHeaderLinks = true;
		$book         = "LESSON - Cancelling...";
		
		include "header.php";
		
		echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." . 
					"</p>\n";
		
		include "footer.php";
	}
?>
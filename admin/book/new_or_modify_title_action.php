<?php
	/*****************************************************************
	 * admin/book/new_or_modify_title_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Change book title information
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
		if(!isset($_POST['title']) or is_null($_POST['title']) or $_POST['title'] == "") {  // Make sure name has been entered
			$errorlist      .= "<p class='error' align='center'>You must specify the book's title!</p>\n";
			$format_error    = True;
		}
		
		$title = safe($_POST['title']);
		
		if(!isset($_POST['id']) or is_null($_POST['id']) or $_POST['id'] == "") {  // Make sure name has been entered
			$errorlist      .= "<p class='error' align='center'>You must specify the book's ID!</p>\n";
			$format_error    = True;
		} else {
			$id = safe($_POST['id']);
			if($_POST['type'] == "new") {
				$query =	"SELECT BookTitleIndex FROM book_title " .
							"WHERE BookTitleIndex = '$id'";
				$res =& $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());             // Check for errors in query
				if($res->numRows() > 0) {
					$errorlist      .= "<p class='error' align='center'>This book ID is already being used.  Please choose another.</p>\n";
					$format_error    = True;
				}
			}
		}

		$cost = floatval($_POST['cost']);
		
		if(!$format_error){	
			$errorlist = "";   // Clear error list.  This list will now only contain database errors.
			
			$book         = "LESSON - Saving changes...";
			$noHeaderLinks = true;
			$noJS          = true;
			
			include "header.php";
			
			echo "      <p align='center'>Saving changes...";
			
			if($_POST['type'] == "new") {          // Create new title if "Save" was pressed
				include "admin/book/new_title_action.php";
			} else {
				include "admin/book/modify_title_action.php";    // Change title if "Update" was pressed
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
				include "admin/book/new_title.php";
			} else {
				include "admin/book/modify_title.php";
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
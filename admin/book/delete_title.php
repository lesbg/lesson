<?php
	/*****************************************************************
	 * admin/book/delete_title.php  (c) 2010 Jonathan Dieter
	 *
	 * Delete book title from database
	 *****************************************************************/

	/* Get variables */
	$bookindex = dbfuncInt2String($_GET['key']);
	$book      = dbfuncInt2String($_GET['keyname']);
	$nextLink      = dbfuncInt2String($_GET['next']);
	
	include "core/settermandyear.php";
	
	if($_POST['action'] == "Yes, delete title") {
		$title         = "LESSON - Deleting book type";
		$noJS          = true;
		$noHeaderLinks = true;

		include "header.php";

		/* Check whether current user is authorized to change scores */
		if($is_admin) {
			$errorname = "";
			$iserror   = False;
			
			$query =	"DELETE FROM book WHERE BookTitleIndex='$bookindex'";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());              // Check for errors in query
			$query =	"DELETE FROM book_title WHERE BookTitleIndex='$bookindex'";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());              // Check for errors in query
				
			echo "      <p align=\"center\">$book successfully deleted.</p>\n";
			log_event($LOG_LEVEL_ADMIN, "admin/book/delete_title.php", $LOG_ADMIN,
					"Deleted book type $book.");

			echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
		} else {
			log_event($LOG_LEVEL_ERROR, "admin/book/delete_title.php", $LOG_DENIED_ACCESS,
					"Tried to delete book title $book.");
			echo "      <p>You do not have the authority to remove this book title.  <a href=\"$nextLink\">" .
			               "Click here to continue</a>.</p>\n";
		}
	} else {
		$title         = "LESSON - Cancelling";
		$noJS          = true;
		$noHeaderLinks = true;
		$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
		
		include "header.php";
		
		echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." . 
					"</p>\n";
	}
	
	include "footer.php";
?>
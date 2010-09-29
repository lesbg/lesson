<?php
	/*****************************************************************
	 * teacher/book/check_out_copy_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Check out copy of a book in the database
	 *****************************************************************/

	/* Get variables */
	$nextLink     = dbfuncInt2String($_GET['next']);             // Link to next page
	$bookindex       = dbfuncInt2String($_GET['key']);
	
	$query =	"SELECT book_title_owner.Username FROM book_title_owner, book " .
				"WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
				"AND   book.BookIndex = $bookindex " .
				"AND   book_title_owner.Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	/* Check whether user is authorized to check out a copy to another user */
	if($is_admin or $res->numRows() > 0) {
		
		/* Check which button was pressed */
		if($_POST["action"] == "Check out")  {
			$errorlist = "";
			
			if(!isset($_POST['state']) or $_POST['state'] == "") {
				$errorlist    .= "<p align='center' class='error'>You must enter the current state of the book</p>\n      <p>";       // Print error message
				$format_error  = True;
			} else {
				$state = intval($_POST['state']);
			}
			
			if(!isset($_POST['student']) or $_POST['student'] == "") {
				$errorlist    .= "<p align='center' class='error'>You must choose someone to check the book out to</p>\n      <p>";       // Print error message
				$format_error  = True;
			} else {
				$student = safe($_POST['student']);
			}
			
			if(!isset($_POST['comment']) or $_POST['comment'] == "") {
				$comment = "NULL"; 
			} else {
				$comment = "'" . safe($_POST['comment']) . "'";
			}
			
			if(!$format_error){	
				include "core/settermandyear.php";
				
				$title         = "LESSON - Checking out book...";
				$noHeaderLinks = true;
				$noJS          = true;
				
				include "header.php";                                        // Print header
				
				echo "      <p align='center'>Checking out book...";
			
				$query =	"INSERT INTO book_status (BookIndex, OutState, OutDate, OutTeacherUsername, Username, Comment) " .
							"VALUES ($bookindex, $state, NOW(), '$username', '$student', $comment)";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
				
				echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
				
				include "footer.php";
			} else {
				include "teacher/book/check_out_copy.php";
			}
		} elseif($_POST["action"] == "Cancel")  {
			$extraMeta     = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Redirecting...";
			
			include "header.php";
			
			echo "      <p align='center'>Redirecting you to <a href='$nextLink'>$nextLink</a></p>\n";
			
			include "footer.php";
		} else {
			include "teacher/book/check_out_copy.php";
		}
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/check_out_copy_action.php", $LOG_DENIED_ACCESS,
				"Attempted to issue mass punishment.");
		
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Unauthorized access!";
		
		include "header.php";
		
		echo "      <p align='center'>You do not have permission to access this page. <a href=" .
		                               "'$nextLink'>Click here to continue.</a></p>\n";
		
		include "footer.php";
	}
	
?>
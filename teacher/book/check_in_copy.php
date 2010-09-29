<?php
	/*****************************************************************
	 * teacher/book/check_in_copy.php  (c) 2010 Jonathan Dieter
	 *
	 * Check out a copy of a book to a user
	 *****************************************************************/

	$title           = "Check in " . dbfuncInt2String($_GET['keyname']);
	$bookindex       = dbfuncInt2String($_GET['key']);

	include "header.php";

	$query =	"SELECT book_title_owner.Username FROM book_title_owner, book " .
				"WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
				"AND   book.BookIndex = $bookindex " .
				"AND   book_title_owner.Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if($is_admin or $res->numRows() > 0) {
		$link  =	"index.php?location=" . dbfuncString2Int("teacher/book/check_in_copy_action.php") .
					"&amp;key=" .			$_GET['key'] .
					"&amp;keyname=" .		$_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
				 
		$query =	"SELECT InState, OutState, Comment " .
					"       FROM book_status " .
					"WHERE book_status.BookIndex = $bookindex " .
					"AND   book_status.InState IS NULL";

		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		$ok = false;
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$ok = true;
			$comment = $row['Comment'];
			$outstate = $row['OutState'];
		}
		
		if($ok) {
			if(isset($errorlist)) {
				echo $errorlist;
			}
			
			if(!isset($_POST['state'])) {
				$_POST['state'] = $outstate;
			} else {
				$_POST['state'] = intval($_POST['state']);
			}
			
			if(!isset($_POST['comment'])) {
				$_POST['comment'] = htmlspecialchars($comment, ENT_QUOTES);
			} else {
				$_POST['comment'] = htmlspecialchars($_POST['comment'], ENT_QUOTES);
			}
			
			echo "      <form action='$link' method='post' name='checkout'>\n"; // Form method
			echo "         <table class='transparent' align='center'>\n";   // Table headers
			
			/* Show book type name */
			echo "         <tr>\n";
			echo "            <td><b>Incoming state</b></td>\n";
			echo "            <td>\n";
			echo "               <select name='state'>\n";
			$query =	"SELECT BookStateIndex, BookState " .
						"FROM book_state " .
						"ORDER BY BookStateIndex DESC";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
			
			while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($nrow['BookStateIndex'] == $_POST['state']) {
					$default = "selected";
				} else {
					$default = "";
				}

				echo "                  <option value='{$nrow['BookStateIndex']}' $default>{$nrow['BookState']}</option>\n";
			}
			echo "               </select>\n";
			echo "            </td>\n";
			echo "         </tr>\n";
			echo "         <tr>\n";
			echo "            <td><b>Comment</b></td>\n";
			echo "            <td><textarea rows='3' cols='40' name='comment'>{$_POST['comment']}</textarea></td>\n";
			echo "         </tr>\n";

			echo "         </table>\n";
			echo "         <p align='center'>\n";
			echo "            <input type='submit' name='action' value='Check in'>&nbsp; \n";
			echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
			echo "         </p>\n";
			echo "      </form>\n";
				
		} else {
			echo "      <p>This copy is already checked in</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/book/check_in_copy.php", $LOG_ADMIN,
				"Checked out copy of $title.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/check_in_copy.php", $LOG_DENIED_ACCESS,
				"Attempted to check out copy of $title.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
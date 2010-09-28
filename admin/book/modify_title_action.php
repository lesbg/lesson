<?php
	/*****************************************************************
	 * admin/book/modify_title_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Run query to modify a current book title into the database.
	 *****************************************************************/
	 
	$booktitleindex = dbfuncInt2String($_GET['key']);
	$book      = dbfuncInt2String($_GET['keyname']);
	$error         = false;                                       // Boolean to store any errors
	
	if($is_admin) {
		$aRes =& $db->query("UPDATE book_title SET BookTitle='$title', " .
							"       BookTitleIndex='$id', " .
							"       Cost='$cost' " .
							"WHERE  BookTitleIndex = '$booktitleindex'");
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		$aRes =& $db->query("UPDATE book_title_owner SET BookTitleIndex='$id' " .
							"WHERE  BookTitleIndex = '$booktitleindex'");
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		$query =	"SELECT BookTitleIndex FROM book_title_owner " .
					"WHERE  BookTitleIndex = '$id' " .
					"AND    YearIndex      = $yearindex";
		$aRes =& $db->query($query);
		if($aRes->numRows() > 0) {
			$aRes =& $db->query("UPDATE book_title_owner SET Username='$teacher' " .
								"WHERE  BookTitleIndex = '$id'" .
								"AND    YearIndex      = $yearindex");
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query			
		} else {
			$aRes =& $db->query("INSERT INTO book_title_owner (Username, BookTitleIndex, YearIndex) " .
								"VALUES ('$teacher', '$id', $yearindex)");
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		}
		$aRes =& $db->query("UPDATE book SET BookTitleIndex='$id' " .
							"WHERE  BookTitleIndex = '$booktitleindex'");
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		log_event($LOG_LEVEL_ADMIN, "admin/book/modify_title_action.php", $LOG_ADMIN,
				"Modified information about book title {$_POST['title']}.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/book/modify_title_action.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about book title $book.");
		echo "</p>\n      <p>You do not have permission to change this book title.</p>\n      <p>";
		$error = true;
	}
?>

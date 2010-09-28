<?php
	/*****************************************************************
	 * admin/book/new_copy_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Run query to insert a new book title into the database.
	 *****************************************************************/
	$booktitleindex = dbfuncInt2String($_GET['key']);
	$book           = dbfuncInt2String($_GET['keyname']);

	$error = false;        // Boolean to store any errors
	
	if($is_admin) {
		/* Add new book type */
		$res =&  $db->query("INSERT INTO book (BookNumber, BookTitleIndex) " .
							"VALUES ('$number', '$booktitleindex')");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		log_event($LOG_LEVEL_ADMIN, "admin/book/new_copy_action.php", $LOG_ADMIN,
				"Created new copy of $book.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/book/new_copy_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create new copy of $book.");
		echo "</p>\n      <p>You do not have permission to add a book title.</p>\n      <p>";
		$error = true;
	}
?>

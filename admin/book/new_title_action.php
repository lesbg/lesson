<?php
	/*****************************************************************
	 * admin/book/new_title_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Run query to insert a new book title into the database.
	 *****************************************************************/

	$error = false;        // Boolean to store any errors
	
	if($is_admin) {
		/* Add new book type */
		$res =&  $db->query("INSERT INTO book_title (BookTitle, BookTitleIndex, Cost) " .
							"VALUES ('{$_POST['title']}', '{$_POST['id']}', {$_POST['cost']})");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		log_event($LOG_LEVEL_ADMIN, "admin/book/new_title_action.php", $LOG_ADMIN,
				"Created new book type {$_POST['name']}.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/book/new_title_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create new book title {$_POST['name']}.");
		echo "</p>\n      <p>You do not have permission to add a book title.</p>\n      <p>";
		$error = true;
	}
?>

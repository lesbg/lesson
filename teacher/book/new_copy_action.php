<?php
	/*****************************************************************
	 * teacher/book/new_copy_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Run query to insert a new copy of a book into the database.
	 *****************************************************************/
	$booktitleindex = dbfuncInt2String($_GET['key']);
	$book           = dbfuncInt2String($_GET['keyname']);

	$error = false;        // Boolean to store any errors

	$query =	"SELECT Username FROM book_title_owner " .
				"WHERE BookTitleIndex='$booktitleindex' " .
				"AND   Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if($is_admin or $res->numRows() > 0) {
		/* Add new book type */
		$res =&  $db->query("INSERT INTO book (BookNumber, BookTitleIndex) " .
							"VALUES ('$number', '$booktitleindex')");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		log_event($LOG_LEVEL_ADMIN, "teacher/book/new_copy_action.php", $LOG_ADMIN,
				"Created new copy of $book.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/new_copy_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create new copy of $book.");
		echo "</p>\n      <p>You do not have permission to add a book title.</p>\n      <p>";
		$error = true;
	}
?>

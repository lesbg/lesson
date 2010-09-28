<?php
	/*****************************************************************
	 * admin/book/modify_copy_action.php  (c) 2010 Jonathan Dieter
	 *
	 * Run query to modify a current copy of a book in the database.
	 *****************************************************************/
	 
	$bookindex = dbfuncInt2String($_GET['key']);
	$book      = dbfuncInt2String($_GET['keyname']);
	$copy      = dbfuncInt2String($_GET['keyname2']);
	$error         = false;                                       // Boolean to store any errors
	
	if($is_admin) {
		$aRes =& $db->query("UPDATE book SET BookNumber='$number' " .
							"WHERE  BookIndex = '$bookindex'");
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
		log_event($LOG_LEVEL_ADMIN, "admin/book/modify_copy_action.php", $LOG_ADMIN,
				"Modified information about copy $copy of $book.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/book/modify_copy_action.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about about copy $copy of $book.");
		echo "</p>\n      <p>You do not have permission to change this copy.</p>\n      <p>";
		$error = true;
	}
?>

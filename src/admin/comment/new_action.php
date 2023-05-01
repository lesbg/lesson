<?php
/**
 * ***************************************************************
 * admin/comment/new_action.php (c) 2005 Jonathan Dieter
 *
 * Run query to insert a new comment type into the database.
 * ***************************************************************
 */
$error = false; // Boolean to store any errors

if ($is_admin) {
	/* Add new comment type */
	if ($_POST['number'] == "") {
		$query = "INSERT INTO comment (Comment, Strength) " .
			 "VALUES ('{$_POST['comment']}', {$_POST['strength']})";
	} else {
		$query = "INSERT INTO comment (CommentIndex, Comment, Strength) " .
				 "VALUES ({$_POST['number']}, '{$_POST['comment']}', {$_POST['strength']})";
	}
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	$res = & $db->query("SELECT LAST_INSERT_ID() AS CommentIndex");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['CommentIndex'] != 0) {
		$commentindex = $row['CommentIndex'];
		
		$res = &  $db->query(
						"DELETE FROM commenttype " .
						 "WHERE CommentIndex = $commentindex ");
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		
		if (isset($values)) {
			foreach ( $values as $subjecttypeindex ) {
				$res = &  $db->query(
								"INSERT INTO commenttype (CommentIndex, SubjectTypeIndex) " .
								 "VALUES ($commentindex, $subjecttypeindex)");
				if (DB::isError($res))
					die($res->getDebugInfo()); // Check for errors in query
			}
		}
	}
	log_event($LOG_LEVEL_ADMIN, "admin/comment/modify_action.php", $LOG_ADMIN, 
			"Created new comment #$commentindex.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/comment/new_action.php", 
			$LOG_DENIED_ACCESS, "Attempted to create new comment.");
	echo "</p>\n      <p>You do not have permission to create a comment.</p>\n      <p>";
	$error = true;
}
?>

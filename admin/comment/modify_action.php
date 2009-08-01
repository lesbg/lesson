<?php
	/*****************************************************************
	 * admin/comment/modify_action.php  (c) 2005 Jonathan Dieter
	 *
	 * Run query to insert a new comment type into the database.
	 *****************************************************************/
	 
	$commentindex = dbfuncInt2String($_GET['key']);
	$error         = false;                                       // Boolean to store any errors
	
	if($is_admin) {
		$query =	"UPDATE comment SET " .
					"       Comment='{$_POST['comment']}', " .
					"       Strength={$_POST['strength']}, " .
					"       CommentIndex={$_POST['number']} " .
					"WHERE  CommentIndex = $commentindex";
		$aRes =& $db->query($query);
		if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query

		$res =&  $db->query("DELETE FROM commenttype " .
							"WHERE CommentIndex = $commentindex ");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		if(isset($values)) {
			foreach($values as $subjecttypeindex) {
				$res =&  $db->query("INSERT INTO commenttype (CommentIndex, SubjectTypeIndex) " .
									"VALUES ({$_POST['number']}, $subjecttypeindex)");
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			}
		}
		log_event($LOG_LEVEL_ADMIN, "admin/comment/modify_action.php", $LOG_ADMIN,
				"Modified information about comment type {$_POST['comment']}.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/comment/modify_action.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about comment type $comment.");
		echo "</p>\n      <p>You do not have permission to change this comment type.</p>\n      <p>";
		$error = true;
	}
?>

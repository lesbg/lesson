<?php
	/*****************************************************************
	 * admin/subjecttype/new_action.php  (c) 2005 Jonathan Dieter
	 *
	 * Run query to insert a new subject type into the database.
	 *****************************************************************/

	$error = false;        // Boolean to store any errors
	
	 /* Check whether user is authorized to change scores */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		/* Check whether a subject type already exists with same name */
		$res  =& $db->query("SELECT SubjectTypeIndex FROM subjecttype WHERE Title = {$_POST['title']}");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			echo "</p>\n      <p>There is already a subject type with that name.  " .     // Error if subject type already exists
			                    "Press \"Back\" to fix the problem.</p>\n      <p>";
			$error = true;
		} else {
			/* Add new subject type */
			$aRes =& $db->query("INSERT INTO subjecttype (Title, Description) " . 
								"VALUES ({$_POST['title']}, {$_POST['descr']})");
			if(DB::isError($aRes)) die($aRes->getDebugInfo());           // Check for errors in query
			log_event($LOG_LEVEL_ADMIN, "admin/subjecttype/modify_action.php", $LOG_ADMIN,
					"Modified information about subject type {$_POST['title']}.");
		}
	} else {  // User isn't authorized to add a subject type.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/subjecttype/new_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create new subject type $subjecttype.");
		echo "</p>\n      <p>You do not have permission to add a subject type.</p>\n      <p>";
		$error = true;
	}
?>

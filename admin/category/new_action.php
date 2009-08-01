<?php
	/*****************************************************************
	 * admin/category/new_action.php  (c) 2005 Jonathan Dieter
	 *
	 * Run query to insert a new category type into the database.
	 *****************************************************************/

	$error = false;        // Boolean to store any errors
	
	if($is_admin) {
		/* Add new category type */
		$res =&  $db->query("INSERT INTO category (CategoryName) " .
							"VALUES ('{$_POST['name']}')");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$res =& $db->query("SELECT CategoryIndex FROM category WHERE CategoryIndex IS NULL");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$categoryindex = $row['CategoryIndex'];

			$res =&  $db->query("DELETE FROM categorytype " .
								"WHERE CategoryIndex = $categoryindex ");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

			if(isset($values)) {
				foreach($values as $subjecttypeindex) {
					$res =&  $db->query("INSERT INTO categorytype (CategoryIndex, SubjectTypeIndex) " .
										"VALUES ($categoryindex, $subjecttypeindex)");
					if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
				}
			}
		}
		log_event($LOG_LEVEL_ADMIN, "admin/category/modify_action.php", $LOG_ADMIN,
				"Created new category type {$_POST['name']}.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/subjecttype/new_action.php", $LOG_DENIED_ACCESS,
				"Attempted to create new subject type $subjecttype.");
		echo "</p>\n      <p>You do not have permission to add a category type.</p>\n      <p>";
		$error = true;
	}
?>

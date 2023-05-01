<?php
/**
 * ***************************************************************
 * admin/category/modify_action.php (c) 2005 Jonathan Dieter
 *
 * Run query to insert a new category type into the database.
 * ***************************************************************
 */
$categoryindex = dbfuncInt2String($_GET['key']);
$category = dbfuncInt2String($_GET['keyname']);
$error = false; // Boolean to store any errors

if ($is_admin) {
	$aRes = & $db->query(
						"UPDATE category SET CategoryName='{$_POST['name']}' " .
						 "WHERE  CategoryIndex = $categoryindex");
	if (DB::isError($aRes))
		die($aRes->getDebugInfo()); // Check for errors in query
	
	$res = &  $db->query(
					"DELETE FROM categorytype " .
					 "WHERE CategoryIndex = $categoryindex ");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if (isset($values)) {
		foreach ( $values as $subjecttypeindex ) {
			$res = &  $db->query(
							"INSERT INTO categorytype (CategoryIndex, SubjectTypeIndex) " .
							 "VALUES ($categoryindex, $subjecttypeindex)");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
		}
	}
	log_event($LOG_LEVEL_ADMIN, "admin/category/modify_action.php", $LOG_ADMIN, 
			"Modified information about category type {$_POST['name']}.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/category/modify_action.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to change information about category type $category.");
	echo "</p>\n      <p>You do not have permission to change this category type.</p>\n      <p>";
	$error = true;
}
?>

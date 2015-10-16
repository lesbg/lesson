<?php
/**
 * ***************************************************************
 * admin/category/delete.php (c) 2005 Jonathan Dieter
 *
 * Delete category type from database
 * ***************************************************************
 */

/* Get variables */
$categoryindex = dbfuncInt2String($_GET['key']);
$category = dbfuncInt2String($_GET['keyname']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete category type") {
	$title = "LESSON - Deleting category type";
	$noJS = true;
	$noHeaderLinks = true;
	
	include "header.php";
	
	/* Check whether current user is authorized to change scores */
	if ($is_admin) {
		$errorname = "";
		$iserror = False;
		
		$query = "SELECT assignment.AssignmentIndex FROM assignment, categorylist " .
				 "WHERE categorylist.CategoryIndex = $categoryindex " .
				 "AND   assignment.CategoryListIndex = categorylist.CategoryListIndex ";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		if ($res->numRows() > 0) {
			$errorname .= "      <p align=\"center\">You cannot delete $category until you set all assignments so they aren't of this category type.</p>\n";
			$iserror = True;
			log_event($LOG_LEVEL_ADMIN, "admin/category/delete.php", $LOG_ERROR, 
					"Attempted to delete category type $category, but there were still assignments with that type.");
		}
		
		if ($iserror) {
			echo $errorname;
		} else {
			$query = "SELECT SubjectIndex FROM categorylist " .
					 "WHERE CategoryIndex = $categoryindex " .
					 "GROUP BY SubjectIndex";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$subjectindex = $row['SubjectIndex'];
				/* Delete stuff */
				$dRes = & $db->query(
									"DELETE FROM categorylist " .
									 "WHERE CategoryIndex = $categoryindex " .
									 "AND   SubjectIndex = $subjectindex");
				if (DB::isError($dRes))
					die($dRes->getDebugInfo());
					
					/* Find total weight not including new category */
				$query = "SELECT SUM(Weight) AS TotalWeight FROM categorylist " .
						 "WHERE SubjectIndex=$subjectindex " .
						 "GROUP BY SubjectIndex";
				$dRes = &  $db->query($query);
				
				/* Find total weight not including new category */
				$query = "SELECT SUM(Weight) AS TotalWeight FROM categorylist " .
						 "WHERE SubjectIndex=$subjectindex " .
						 "GROUP BY SubjectIndex";
				$dRes = &  $db->query($query);
				if (DB::isError($dRes))
					die($dRes->getDebugInfo());
				if ($dRow = & $dRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					$total_weight = floatval($dRow['TotalWeight']);
				} else {
					$total_weight = 0.0;
				}
				
				/* Update total weight for all categories */
				$query = "UPDATE categorylist SET TotalWeight=$total_weight " .
						 "WHERE SubjectIndex=$subjectindex";
				$dRes = &  $db->query($query);
				if (DB::isError($dRes))
					die($dRes->getDebugInfo());
			}
			$res = &  $db->query(
							"DELETE FROM categorytype " .
							 "WHERE CategoryIndex = $categoryindex");
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			$res = &  $db->query(
							"DELETE FROM category " . // Remove category type from category table
							 "WHERE CategoryIndex = $categoryindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			
			echo "      <p align=\"center\">$category successfully deleted.</p>\n";
			log_event($LOG_LEVEL_ADMIN, "admin/category/delete.php", $LOG_ADMIN, 
					"Deleted category type $category.");
		}
		echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/category/delete.php", 
				$LOG_DENIED_ACCESS, "Tried to delete category type $category.");
		echo "      <p>You do not have the authority to remove this category type.  <a href=\"$nextLink\">" .
			 "Click here to continue</a>.</p>\n";
	}
} else {
	$title = "LESSON - Cancelling";
	$noJS = true;
	$noHeaderLinks = true;
	$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
	
	include "header.php";
	
	echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
		 "</p>\n";
}

include "footer.php";
?>
<?php
/**
 * ***************************************************************
 * admin/subjecttype/delete.php (c) 2005 Jonathan Dieter
 *
 * Delete subject type from database
 * ***************************************************************
 */

/* Get variables */
$subjecttypeindex = dbfuncInt2String($_GET['key']);
$subjecttype = dbfuncInt2String($_GET['keyname']);
$nextLink = dbfuncInt2String($_GET['next']);

include "core/settermandyear.php";

if ($_POST['action'] == "Yes, delete subject type") {
	$title = "LESSON - Deleting subject type";
	$noJS = true;
	$noHeaderLinks = true;
	
	include "header.php";
	
	/* Check whether current user is authorized to change scores */
	if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$errorname = "";
		$iserror = False;
		
		$res = &  $db->query(
						"SELECT SubjectIndex FROM subject " . // Check whether subjecttype to be deleted has any subjects
						 "WHERE SubjectTypeIndex = $subjecttypeindex");
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		if ($res->numRows() > 0) {
			$errorname .= "      <p align=\"center\">You cannot delete $subjecttype until you change all subjects so they " .
		 "aren't of this subject type.</p>\n";
			$iserror = True;
			log_event($LOG_LEVEL_ADMIN, "admin/subjecttype/delete.php", 
					$LOG_ERROR, 
					"Attempted to delete subject type $subjecttype, but there were still subjects of that type.");
		}
		
		if ($iserror) { // Check whether there have been any errors during the
			echo $errorname; // sanity checks
		} else {
			$res = &  $db->query(
							"DELETE FROM subjecttype " . // Remove subject type from subjecttype table
							 "WHERE SubjectTypeIndex = $subjecttypeindex");
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
			
			echo "      <p align=\"center\">$subjecttype successfully deleted.</p>\n";
			log_event($LOG_LEVEL_ADMIN, "admin/subjecttype/delete.php", 
					$LOG_ADMIN, "Deleted subject type $subjecttype.");
		}
		echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/subjecttype/delete.php", 
				$LOG_DENIED_ACCESS, "Tried to delete subject type $subjecttype.");
		echo "      <p>You do not have the authority to remove this subject type.  <a href=\"$nextLink\">" .
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
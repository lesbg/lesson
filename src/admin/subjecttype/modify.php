<?php
/**
 * ***************************************************************
 * admin/subjecttype/modify.php (c) 2005 Jonathan Dieter
 *
 * Change information about subject type
 * ***************************************************************
 */

/* Get variables */
$title = "Change type information for " . dbfuncInt2String($_GET['keyname']);
$subjecttypeindex = dbfuncInt2String($_GET['key']);
$link = "index.php?location=" .
		 dbfuncString2Int("admin/subjecttype/new_or_modify_action.php") .
		 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
		 "&amp;next=" . dbfuncString2Int($backLink);

include "header.php"; // Show header

/* Check whether user is authorized to change subject */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	/* Get subject information */
	$fRes = & $db->query(
						"SELECT Title, Description FROM subjecttype " .
						 "WHERE SubjectTypeIndex = $subjecttypeindex");
	if (DB::isError($fRes))
		die($fRes->getDebugInfo()); // Check for errors in query
	if ($fRow = & $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
		if (isset($errorlist)) { // If there were errors, print them, and reset fields
			echo $errorlist;
			$_POST['title'] = htmlspecialchars($_POST['title']);
			$_POST['descr'] = htmlspecialchars($_POST['descr']);
		} else {
			$_POST['title'] = htmlspecialchars($fRow['Title']);
			$_POST['descr'] = htmlspecialchars($fRow['Description']);
		}
		
		echo "      <form action=\"$link\" name=\"modSubj\" method=\"post\">\n"; // Form method
		echo "         <table class=\"transparent\" align=\"center\">\n"; // Table headers
		
		/* Show subject type name */
		echo "            <tr>\n";
		echo "               <td>Name of subject type</td>\n";
		echo "               <td><input type=\"text\" name=\"title\" value=\"{$_POST['title']}\" size=35></td>\n";
		echo "            </tr>\n";
		
		/* Show subject type description */
		echo "            <tr>\n";
		echo "               <td>Description</td>\n";
		echo "               <td><input type=\"text\" name=\"descr\" value=\"{$_POST['descr']}\" size=35></td>\n";
		echo "            </tr>\n";
		
		echo "         </table>\n"; // End of table
		echo "         <p align=\"center\">\n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Update\" \>\n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Delete\" \>\n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\" \>\n";
		echo "         </p>\n";
		echo "      </form>\n";
	} else { // Couldn't find $subjecttypeindex in subjecttype table
		echo "      <p align=\"center\">Can't find subject type.  Have you deleted it?</p>\n";
		echo "      <p align=\"center\"><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "admin/subjecttype/modify.php", $LOG_ADMIN, 
			"Opened subject type: $title for editing.");
} else { // User isn't authorized to view or change scores.
	/* Get subject name and log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/subjecttype/modify.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to change information about the subject type: $title.");
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
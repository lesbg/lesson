<?php
/**
 * ***************************************************************
 * teacher/assignment/delete_confirm.php (c) 2004 Jonathan Dieter
 *
 * Confirm deletion of assignment from database
 * ***************************************************************
 */

/* Get variables */
$assignmentindex = safe(dbfuncInt2String($_GET['key']));

$title = "LESSON - Confirm to delete assignment";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to change scores */
$res = &  $db->query(
				"SELECT subjectteacher.Username FROM subjectteacher, assignment " .
				 "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
				 "AND   assignment.AssignmentIndex  = $assignmentindex " .
				 "AND   subjectteacher.Username     = '$username'");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 or $is_admin) {
	$res = &  $db->query(
					"SELECT Date, Title FROM assignment " .
						 "WHERE assignment.AssignmentIndex  = $assignmentindex ");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$dateinfo = date($dateformat, strtotime($row['Date']));
	$link = "index.php?location=" .
			 dbfuncString2Int("teacher/assignment/delete.php") . "&amp;key=" .
			 $_GET['key'] . "&amp;next=" . $_GET['next'];
	
	echo "      <p align='center'>Are you <strong>sure</strong> you want to delete {$row['Title']} " .
		 "($dateinfo) and all of its scores?</p>\n";
	echo "      <form action='$link' method='post'>\n";
	echo "         <p align='center'>";
	echo "            <input type='submit' name='action' value='Yes, delete assignment' \>&nbsp; \n";
	echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	echo "      <p>You do not have the authority to remove this assignment, or this assignment has already " .
		 "been deleted.  <a href='$nextLink'>Click here to continue</a>.</p>\n";
}

include "footer.php";
?>
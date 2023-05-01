<?php
/**
 * ***************************************************************
 * student/descr.php (c) 2004 Jonathan Dieter
 *
 * Prints assignment description
 * ***************************************************************
 */
$assignmentindex = safe(dbfuncInt2String($_GET['key'])); // Get key for assignment

$res = & $db->query(
				"SELECT Title, Description FROM assignment " . // Run query to get description
				 "WHERE AssignmentIndex = $assignmentindex");
if (DB::isError($res))
	die($res->getMessage()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) { // Check whether there is a description
	$title = $row['Title']; // Set title to assignment title
	$noJS = true;
	$noHeaderLinks = true;
	
	include "header.php";
	
	echo $row['Description'] . "\n"; // Print description
	
	include "footer.php";
}
?>
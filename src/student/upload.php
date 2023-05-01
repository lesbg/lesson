<?php
/**
 * ***************************************************************
 * student/upload.php (c) 2006-2008 Jonathan Dieter
 *
 * Upload an assignment
 * ***************************************************************
 */

/* Get variables */
$assignmentindex = safe(dbfuncInt2String($_GET['key']));
$name = dbfuncInt2String($_GET['keyname']);
$studentusername = safe(dbfuncInt2String($_GET['key2']));
$subject = dbfuncInt2String($_GET['key2name']);

/* See whether $username is in subject */
$query = "SELECT subject.SubjectIndex, Score, Uploadable, UploadName FROM subject INNER JOIN assignment " .
		 "       USING (SubjectIndex) INNER JOIN subjectstudent USING (SubjectIndex) " .
		 "       LEFT OUTER JOIN mark ON (mark.Username = subjectstudent.Username " .
		 "       AND mark.AssignmentIndex = assignment.AssignmentIndex) " .
		 "WHERE subjectstudent.Username        = '$studentusername' " .
		 "AND   assignment.AssignmentIndex = $assignmentindex ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
	
/* Make sure user has permission to upload file */
if ($is_admin or ($studentusername == $username and $res->numRows() > 0)) {
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$subjectindex = $row['SubjectIndex'];
	$title = "Upload Homework";
	$subtitle = $row['Title'];
	
	$yearindex = $row['YearIndex'];
	$termindex = $row['TermIndex'];
	include "header.php";
	
	$nochangeyt = true;
	
	include "core/titletermyear.php";
	
	if ($row['Uploadable'] == 1 and
		 (is_null($row['Score']) or $row['Score'] == $MARK_LATE)) {
		$link = "index.php?location=" .
		 dbfuncString2Int("student/doupload.php") . "&amp;key=" .
		 dbfuncString2Int($assignmentindex) . "&amp;keyname=" .
		 dbfuncString2Int($name) . "&amp;key2=" .
		 dbfuncString2Int($studentusername) . "&amp;key2name=" .
		 dbfuncString2Int($subject);
	echo "      <form enctype='multipart/form-data' action='$link' method='post'>\n";
	echo "         <p align='center'>Choose the file you want to upload: <input name='hw' type='file'></p>\n";
	echo "         <p align='center'><input type='hidden' name='MAX_FILE_SIZE' value='102400000'><input type='submit' value='Ok'></p>\n";
	echo "      </form>\n";
} else {
	echo "      <p>You are not allowed to upload your homework for this assignment.</p>\n";
}
} else {
$title = "Error";

include "header.php";

/* Log unauthorized access attempt */
log_event($LOG_LEVEL_ERROR, "student/upload.php", $LOG_DENIED_ACCESS, 
		"Tried to upload homework for $name for $subject.");

echo "      <p>You do not have permission to access this page</p>\n";
echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
<?php
/**
 * ***************************************************************
 * teacher/report/close_confirm.php (c) 2008 Jonathan Dieter
 *
 * Confirm that teacher is finished with reports
 * ***************************************************************
 */

/* Get variables */
$title = "LESSON - Confirm";
$noJS = true;
$noHeaderLinks = true;

include "header.php";

/* Check whether current user is principal */
$res = &  $db->query(
				"SELECT Username FROM principal " .
				 "WHERE Username='$username' AND Level=1");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_principal = true;
} else {
	$is_principal = false;
}

/* Check whether current user is a hod */
$res = &  $db->query(
				"SELECT hod.Username FROM hod, term, subject " .
				 "WHERE hod.Username         = '$username' " .
				 "AND   hod.DepartmentIndex  = term.DepartmentIndex " .
				 "AND   term.TermIndex       = subject.TermIndex " .
				 "AND   subject.SubjectIndex = $subjectindex");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_hod = true;
} else {
	$is_hod = false;
}

/* Check whether user is authorized to change scores */
$query = "(SELECT subjectteacher.Username FROM subjectteacher " .
		 " WHERE subjectteacher.SubjectIndex = $subjectindex " .
		 " AND   subjectteacher.Username     = '$username')";
if ($student_username != "") {
	$query .= "UNION " .
		 "(SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
		 " WHERE  classlist.Username         = '$student_username' " .
		 " AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
		 " AND    classterm.ClassIndex       = class.ClassIndex " .
		 " AND    classterm.TermIndex        = $termindex " .
		 " AND    class.ClassTeacherUsername = '$username' " .
		 " AND    class.YearIndex            = $yearindex)";
}
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() == 0 and ! $is_admin and ! $is_hod and ! $is_principal) {
	log_event($LOG_LEVEL_ERROR, "teacher/report/close_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to close reports for $subject.");
	echo "      <p>You do not have the authority to close these reports.  <a href='$nextLink'>" .
		 "Click here to continue</a>.</p>\n";
}

$link = "index.php?location=" .
		 dbfuncString2Int("teacher/report/close_action.php") . "&amp;key=" .
		 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
		 $_GET['next'];
if (isset($_GET['key2'])) {
	$link .= "&amp;key2=" . $_GET['key2'] . "&amp;keyname2=" . $_GET['keyname2'];
}

echo "      <p align='center'>Are you <b>sure</b> you are finished working on your reports for $subject</p>\n";
echo "      <form action='$link' method='post'>\n";
echo "         <p align='center'>";
echo "            <input type='submit' name='action' value='Yes, I&#039;m finished' \>&nbsp; \n";
echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";

include "footer.php";
?>
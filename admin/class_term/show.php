<?php
/**
 * ***************************************************************
 * admin/class_term/show.php (c) 2009 Jonathan Dieter
 *
 * Show report template
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['next']))
	$_GET['next'] = dbfuncString2Int($backLink);
$reportindex = safe(dbfuncInt2String($_GET['key']));

$MAX_SIZE = 10 * 1024 * 1024;

include "core/settermandyear.php";

$query = "SELECT report.ReportTemplate, report.ReportTemplateType " .
		 "       FROM report " .
		 "WHERE report.ReportIndex = $reportindex ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	echo "      <p align='center'>Can't find report.  Have you deleted it?</p>\n";
	echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
	exit(0);
}

if (is_null($row['ReportTemplate'])) {
	/* Print error message */
	include "header.php";
	echo "      <p>There's no report template for this report.</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
	exit(0);
}

$report_template = & $row['ReportTemplate'];
$report_template_type = $row['ReportTemplateType'];

/* Check whether current user is principal */
$res = &  $db->query(
				"SELECT Username FROM principal " .
				 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_principal = true;
} else {
	$is_principal = false;
}

/* Check whether current user is a hod */
$res = &  $db->query(
				"SELECT hod.Username FROM hod " .
				 "WHERE hod.Username        = '$username'");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_hod = true;
} else {
	$is_hod = false;
}

/* Check whether user is authorized to change scores */
$res = & $db->query(
				"SELECT class.ClassIndex FROM class, classterm " .
				"WHERE classterm.ClassIndex = class.ClassIndex " .
				"AND   class.YearIndex = $yearindex " .
				"AND   class.ClassTeacherUsername = '$username'");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_ct = true;
} else {
	$is_ct = false;
}

if (! $is_admin) {
	/* Print error message */
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Error";
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php", 
			$LOG_DENIED_ACCESS, "Tried to access report templates.");
	
	include "footer.php";
	exit(0);
}

header("Content-type: $report_template_type");
header("Content-disposition: attachment; filename=report.odt");

print $report_template;

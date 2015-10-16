<?php
/**
 * ***************************************************************
 * student/absence.php (c) 2007-2014 Jonathan Dieter
 *
 * Print information about student's attendance history for term
 * ***************************************************************
 */

/* Get variables */
$studentusername = dbfuncInt2String($_GET['key']);
$name = dbfuncInt2String($_GET['keyname']);
$title = "Attendance history for $name ($studentusername)";

/*
 * Key wasn't included. The only time I've seen this happen is when a student doesn't logout and lets
 * another student use their computer, so we'll force a logout
 */
if (! isset($_GET['key'])) {
	log_event($LOG_LEVEL_ACCESS, "student/absence.php", $LOG_ERROR, 
			"Page was accessed without key (Make sure user logged out).");
	include "user/logout.php";
	exit(0);
}

include "header.php";

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

/* Check whether current user is a counselor */
$res = &  $db->query(
				"SELECT Username FROM counselorlist " .
				 "WHERE Username=\"$username\"");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_counselor = true;
} else {
	$is_counselor = false;
}

/* Check whether current user is a hod */
$query = "SELECT hod.Username FROM hod, class, classterm, classlist " .
		 "WHERE hod.Username='$username' " .
		 "AND hod.DepartmentIndex = class.DepartmentIndex " .
		 "AND classlist.Username = '$studentusername' " .
		 "AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
		 "AND classterm.ClassIndex = class.ClassIndex";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_hod = true;
} else {
	$is_hod = false;
}

/* Make sure user has permission to view student's marks for subject */
if ($is_admin or $is_hod or $is_principal or $is_counselor or
	 $studentusername == $username) {
	include "core/settermandyear.php";
	include "core/titletermyear.php";
	
	$query = "SELECT AttendanceTypeIndex, Date FROM attendance, subject " .
			 "WHERE attendance.SubjectIndex = subject.SubjectIndex " .
			 "AND   attendance.Username  = \"$studentusername\" " .
			 "AND   subject.YearIndex = $yearindex " .
			 "AND   subject.TermIndex = $termindex " . "ORDER BY Date DESC";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Date</th>\n";
	echo "            <th>Absent</th>\n";
	echo "            <th>Late</th>\n";
	echo "            <th>Suspended</th>\n";
	echo "         </tr>\n";
	
	$alt_count = 0;
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$alt_count += 1;
		if ($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		if ($row['AttendanceTypeIndex'] == $ATT_SUSPENDED) {
			$alt = " class=\"late-$alt_step\"";
		} else {
			$alt = " class=\"$alt_step\"";
		}
		
		$absent = "&nbsp;";
		$late = "&nbsp;";
		$suspended = "&nbsp;";
		if ($row['AttendanceTypeIndex'] == $ATT_ABSENT)
			$absent = "X";
		if ($row['AttendanceTypeIndex'] == $ATT_LATE)
			$late = "X";
		if ($row['AttendanceTypeIndex'] == $ATT_SUSPENDED)
			$suspended = "X";
		$dateinfo = date("l, $dateformat", strtotime($row['Date']));
		echo "         <tr$alt>\n";
		echo "            <td>$dateinfo</td>\n";
		echo "            <td align=\"center\">$absent</td>\n";
		echo "            <td align=\"center\">$late</td>\n";
		echo "            <td align=\"center\">$suspended</td>\n";
		echo "         </tr>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "student/absence.php", $LOG_STUDENT, 
			"Viewed $name's absence history.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "student/absence.php", $LOG_DENIED_ACCESS, 
			"Tried to access $name's absence history.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
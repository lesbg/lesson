<?php
/**
 * ***************************************************************
 * svg/timetable.svg.php (c) 2007 Jonathan Dieter
 *
 * Show timetable
 * ***************************************************************
 */
header("Content-type: image/svg+xml");
echo "<?xml version='1.0' standalone='no'?>\n";
echo "<?xml-stylesheet href='../css/standard.css' type='text/css'?>\n";

include "../globals.php"; // Include global variables

/* Create connection to database */
require_once "DB.php"; // Get DB class
include "../core/dbfunc.php"; // Get database connection functions
$db = & dbfuncConnect(); // Connect to database and store in $db

$LEFT = 0;
$TOP = 0;
$WIDTH = 1200;
$HEIGHT = 600;
$BORDER = 10;

$rleft = $LEFT - $BORDER;
$rtop = $TOP - $BORDER;
$rwidth = $WIDTH + $BORDER * 2;
$rheight = $HEIGHT + $BORDER * 2;

echo "<!DOCTYPE svg PUBLIC '-//W3C//DTD SVG 1.1//EN' 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'>";
echo "<svg width='100%' height='100%' version='1.1' viewBox='$rleft $rtop $rwidth $rheight' xmlns='http://www.w3.org/2000/svg' xml:space='preserve'>";

session_name("LESSONSESSION");
session_start();
$username = $_SESSION['username'];
$ttusername = safe(dbfuncInt2String($_GET['key']));
$tttype = safe(dbfuncInt2String($_GET['key2']));
$yearindex = dbfuncGetYearIndex(); // Get current year
if (isset($_SESSION['yearindex'])) { // Set yearindex to session variable if set
	$yearindex = $_SESSION['yearindex'];
}
if ($tttype == "c")
	$query = "SELECT DepartmentIndex FROM class WHERE ClassIndex=$ttusername";
else
	$query = "SELECT DepartmentIndex FROM user WHERE Username='$ttusername'";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());
$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
$depindex = $row['DepartmentIndex'];
if (is_null($depindex)) {
	$query = "SELECT class.DepartmentIndex FROM class, classterm, classlist " .
			 "WHERE  classlist.Username       = '$ttusername' " .
			 "AND    class.ClassIndex         = classterm.ClassIndex " .
			 "AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
			 "AND    class.YearIndex          = $yearindex " .
			 "ORDER BY classterm.TermIndex DESC LIMIT 1";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$depindex = $row['DepartmentIndex'];
}
if (is_null($depindex)) {
	$query = "SELECT DepartmentIndex FROM department " .
			 "ORDER BY DepartmentIndex " . "LIMIT 1";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$depindex = $row['DepartmentIndex'];
}
$permissions = dbfuncGetPermissions(); // Get user's permissions from database<

if ($tttype == "c" or $username == $ttusername or
	 dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	$query = "SELECT MIN(period.StartTime) AS Min, MAX(period.EndTime) AS Max FROM period " .
	 "WHERE period.DepartmentIndex = $depindex ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) { // Run through list of all assignments
	$min = strtotime($row['Min']);
	$max = strtotime($row['Max']);
}
$res = &  $db->query("SELECT Day FROM day ORDER BY DayIndex");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
$left = $LEFT;
$top = $TOP;
$daycount = $res->numRows();
$daywidth = $WIDTH / $daycount;
$titleheight = $HEIGHT / 15;

$mval = ($HEIGHT - $titleheight) / ($max - $min);

/* Draw white filled rectangle around border of image */
echo "<rect x='$LEFT' y='$TOP' width='$WIDTH' height='$HEIGHT' class='th' style='fill: grey' />";

/* Print days of week */
echo "<rect x='$LEFT' y='$TOP' width='$WIDTH' height='$titleheight' class='th' />";
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
	echo "<line x1='$left' y1='$TOP' x2='$left' y2='$HEIGHT' class='th' />";
	$txtx = $left + ($daywidth / 2);
	$txty = $TOP + ($titleheight / 2) + 5;
	echo "<text x='$txtx' y='$txty' class='large' style='text-anchor: middle' >";
	echo "{$row['Day']}";
	echo "</text>\n";
	$left += $daywidth;
}

/* Print subjects that occur on a daily basis for this department */
$query = "SELECT period.PeriodName, period.StartTime, period.EndTime,  " .
		 "       day.DayIndex FROM day, period " .
		 "WHERE period.DepartmentIndex = $depindex " . "AND   period.Period < 1 " .
		 "ORDER BY day.DayIndex, period.StartTime";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

$day = - 1;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
	/* Calculate size of box for period and print */
	$left = ($row['DayIndex'] - 1) * $daywidth;
	$top = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
	$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;
	echo "<rect x='$left' y='$top' width='$daywidth' height='$height' class='th' style='fill: white' />";
	
	/* Print start time, name, and end time of period */
	$txtx = $left + ($daywidth / 2);
	$txtsy = $top + 5;
	$txtcy = $top + ($height / 2) + 5;
	$txtey = $top + $height + 5;
	$starttime = date("g:iA", strtotime($row['StartTime']));
	$endtime = date("g:iA", strtotime($row['EndTime']));
	/*
	 * echo "<text x='$txtx' y='$txtsy' class='medium' stroke-width='1' stroke='white' style='text-anchor: middle' >";
	 * echo "$starttime";
	 * echo "</text>\n";
	 * echo "<text x='$txtx' y='$txtsy' class='medium' style='text-anchor: middle' >";
	 * echo "$starttime";
	 * echo "</text>\n";
	 */
	echo "<text x='$txtx' y='$txtcy' class='medium' style='text-anchor: middle' >";
	echo "{$row['PeriodName']}";
	echo "</text>\n";
	/*
	 * echo "<text x='$txtx' y='$txtey' class='medium' stroke-width='1' stroke='white' style='text-anchor: middle' >";
	 * echo "$endtime";
	 * echo "</text>\n";
	 * echo "<text x='$txtx' y='$txtey' class='medium' style='text-anchor: middle' >";
	 * echo "$endtime";
	 * echo "</text>\n";
	 */
}

/* Print boxes for subjects taught by this user */
if ($tttype == "t") {
	$query = "SELECT period.PeriodName, period.StartTime, period.EndTime, period.PeriodIndex, " .
		 "       day.Day, day.DayIndex FROM subject, day, subjectteacher, timetable, period, " .
		 "       currentterm " . "WHERE subjectteacher.Username = '$ttusername' " .
		 "AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
		 "AND   subject.YearIndex = $yearindex " .
		 "AND   subject.TermIndex = currentterm.TermIndex " .
		 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
		 "AND   day.DayIndex = timetable.DayIndex " .
		 "AND   period.PeriodIndex = timetable.PeriodIndex " .
		 "GROUP BY period.PeriodIndex, day.DayIndex " .
		 "ORDER BY day.DayIndex, period.StartTime";
} elseif ($tttype == "s") {
	$query = "SELECT period.PeriodName, period.StartTime, period.EndTime, period.PeriodIndex, " .
			 "       day.Day, day.DayIndex FROM subject, day, subjectstudent, timetable, period, " .
			 "       currentterm " .
			 "WHERE subjectstudent.Username = '$ttusername' " .
			 "AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
			 "AND   subject.YearIndex = $yearindex " .
			 "AND   subject.TermIndex = currentterm.TermIndex " .
			 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
			 "AND   day.DayIndex = timetable.DayIndex " .
			 "AND   period.PeriodIndex = timetable.PeriodIndex " .
			 "GROUP BY period.PeriodIndex, day.DayIndex " .
			 "ORDER BY day.DayIndex, period.StartTime";
} elseif ($tttype == "c") {
	$query = "SELECT period.PeriodName, period.StartTime, period.EndTime, period.PeriodIndex, " .
			 "       day.Day, day.DayIndex FROM subject, class, day, timetable, period, " .
			 "       currentterm " . "WHERE subject.YearIndex = $yearindex " .
			 "AND   ((subject.ClassIndex = $ttusername " .
			 "		 AND class.ClassIndex = $ttusername) " . "       OR " .
			 "       (subject.Grade = class.Grade " .
			 "        AND class.ClassIndex = $ttusername)) " .
			 "AND   subject.TermIndex = currentterm.TermIndex " .
			 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
			 "AND   day.DayIndex = timetable.DayIndex " .
			 "AND   period.PeriodIndex = timetable.PeriodIndex " .
			 "GROUP BY period.PeriodIndex, day.DayIndex " .
			 "ORDER BY day.DayIndex, period.StartTime";
	print $query;
}
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
	/* Calculate size of box for period and print */
	$left = ($row['DayIndex'] - 1) * $daywidth;
	$top = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
	$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;
	echo "<rect x='$left' y='$top' width='$daywidth' height='$height' class='th' style='fill: white' />";
	
	/* Print start time, name, and end time of period */
	$txtx = $left + ($daywidth / 2);
	$txtsy = $top + 5;
	$txtey = $top + $height + 5;
	$starttime = date("g:iA", strtotime($row['StartTime']));
	$endtime = date("g:iA", strtotime($row['EndTime']));
	echo "<text x='$txtx' y='$txtsy' class='medium' stroke-width='4' stroke='white' style='text-anchor: middle' >";
	echo "$starttime";
	echo "</text>\n";
	echo "<text x='$txtx' y='$txtsy' class='medium' style='text-anchor: middle' >";
	echo "$starttime";
	echo "</text>\n";
	echo "<text x='$txtx' y='$txtey' class='medium' stroke-width='4' stroke='white' style='text-anchor: middle' >";
	echo "$endtime";
	echo "</text>\n";
	echo "<text x='$txtx' y='$txtey' class='medium' style='text-anchor: middle' >";
	echo "$endtime";
	echo "</text>\n";
}

/* Print subjects taught by this user */
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
	/* Calculate size of box for period */
	$left = ($row['DayIndex'] - 1) * $daywidth;
	$top = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
	$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;
	
	/* Print start time, name, and end time of period */
	$txtx = $left + ($daywidth / 2);
	if ($tttype == "t") {
		$nquery = "SELECT subject.ShortName, subject.Name FROM subject, subjectteacher, timetable, " .
			 "       currentterm " .
			 "WHERE subjectteacher.Username = '$ttusername' " .
			 "AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
			 "AND   subject.YearIndex = $yearindex " .
			 "AND   subject.TermIndex = currentterm.TermIndex " .
			 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
			 "AND   timetable.DayIndex = {$row['DayIndex']} " .
			 "AND   timetable.PeriodIndex = {$row['PeriodIndex']} " .
			 "ORDER BY subject.Name";
	} elseif ($tttype == "s") {
		$nquery = "SELECT subject.ShortName, subject.Name FROM subject, subjectstudent, timetable, " .
				 "       currentterm " .
				 "WHERE subjectstudent.Username = '$ttusername' " .
				 "AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
				 "AND   subject.YearIndex = $yearindex " .
				 "AND   subject.TermIndex = currentterm.TermIndex " .
				 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
				 "AND   timetable.DayIndex = {$row['DayIndex']} " .
				 "AND   timetable.PeriodIndex = {$row['PeriodIndex']} " .
				 "ORDER BY subject.Name";
	} elseif ($tttype == "c") {
		$nquery = "SELECT subject.ShortName, subject.Name FROM subject, class, timetable, " .
				 "       currentterm " . "WHERE subject.YearIndex = $yearindex " .
				 "AND   ((subject.ClassIndex = $ttusername " .
				 "		 AND class.ClassIndex = $ttusername) " . "       OR " .
				 "       (subject.Grade = class.Grade " .
				 "        AND class.ClassIndex = $ttusername)) " .
				 "AND   subject.TermIndex = currentterm.TermIndex " .
				 "AND   timetable.SubjectIndex = subject.SubjectIndex " .
				 "AND   timetable.DayIndex = {$row['DayIndex']} " .
				 "AND   timetable.PeriodIndex = {$row['PeriodIndex']} " .
				 "ORDER BY subject.Name";
	}
	
	$nrs = &  $db->query($nquery);
	if (DB::isError($nrs))
		die($nrs->getDebugInfo()); // Check for errors in query
	$txtcy = ($top + ($height / 2) + 5) - (($nrs->numRows() - 1) * 10);
	while ( $nrow = & $nrs->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "<text x='$txtx' y='$txtcy' class='large' style='text-anchor: middle' >";
		if (is_null($nrow['ShortName']) or $nrow['ShortName'] == "") {
			$name = htmlspecialchars($nrow['Name']);
		} else {
			$name = htmlspecialchars($nrow['ShortName']);
		}
		echo "$name";
		echo "</text>\n";
		$txtcy += 20;
	}
}
}
echo "</svg>\n";

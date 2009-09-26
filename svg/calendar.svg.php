<?php
	/*****************************************************************
	 * svg/calendar.svg.php  (c) 2007 Jonathan Dieter
	 *
	 * Create an SVG calendar.
	 *****************************************************************/
	header("Content-type: image/svg+xml");
	echo "<?xml version=\"1.0\" standalone=\"no\"?>\n";
	echo "<?xml-stylesheet href=\"../css/standard.css\" type=\"text/css\"?>\n";

	include "../globals.php";                          // Include global variables
	
	/* Create connection to database */
	require_once "DB.php";                          // Get DB class
	include "../core/dbfunc.php";                   // Get database connection functions
	$db =& dbfuncConnect();                         // Connect to database and store in $db

	$LEFT   = 0;
	$TOP    = 0;
	$WIDTH  = 1200;
	$HEIGHT = 600;
	$BORDER = 10;

	$rleft   = $LEFT - $BORDER;
	$rtop    = $TOP - $BORDER;
	$rwidth  = $WIDTH + $BORDER*2;
	$rheight = $HEIGHT + $BORDER*2;

	echo "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\">";
	echo "<svg width=\"100%\" height=\"100%\" version=\"1.1\" viewBox=\"$rleft $rtop $rwidth $rheight\" xmlns=\"http://www.w3.org/2000/svg\" xml:space=\"preserve\">";

	session_start();
	$username    = $_SESSION['_auth_LESSONSESSION']['username'];
	if(isset($_GET['key'])) {
		$month = safe(dbfuncInt2String($_GET['key']));
		$day   = -1;
	} else {
		$month = 1;
	}
	$yearindex   = dbfuncGetYearIndex();            // Get current year
	$depindex    = dbfuncGetDepIndex();             // Get current department index
	$termindex   = dbfuncGetTermIndex($depindex);   // Get current term
	$permissions = dbfuncGetPermissions();         // Get user's permissions from database<
	if(isset($_SESSION['depindex'])) {
		$depindex  = $_SESSION['depindex'];
	}
	if(isset($_SESSION['yearindex'])) {              // Set yearindex to session variable if set
		$yearindex = $_SESSION['yearindex'];
	}
	if(isset($_SESSION['termindex'])) {              // Set termindex to session variable if set
		$termindex = $_SESSION['termindex'];
	}
	$tttype     = safe(dbfuncInt2String($_GET['key2']));

	if($username == $ttusername or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$query =	"SELECT MIN(period.StartTime) AS Min, MAX(period.EndTime) AS Max FROM period " .
					"WHERE period.DepartmentIndex = $depindex ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Run through list of all assignments
			$min = strtotime($row['Min']);
			$max = strtotime($row['Max']);
		}
		$res =&  $db->query("SELECT Day FROM day ORDER BY DayIndex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		$left = $LEFT;
		$top  = $TOP;
		$daycount = $res->numRows();
		$daywidth = $WIDTH / $daycount;
		$titleheight = $HEIGHT / 15;

		$mval = ($HEIGHT - $titleheight) / ($max - $min);

		/* Draw white filled rectangle around border of image */
		echo "<rect x=\"$LEFT\" y=\"$TOP\" width=\"$WIDTH\" height=\"$HEIGHT\" class=\"th\" style=\"fill: grey\" />";

		/* Print days of week */
		echo "<rect x=\"$LEFT\" y=\"$TOP\" width=\"$WIDTH\" height=\"$titleheight\" class=\"th\" />";
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Run through list of all assignments
			echo "<line x1=\"$left\" y1=\"$TOP\" x2=\"$left\" y2=\"$HEIGHT\" class=\"th\" />";
			$txtx = $left + ($daywidth / 2);
			$txty = $TOP + ($titleheight / 2) + 5;
			echo "<text x=\"$txtx\" y=\"$txty\" class=\"large\" style=\"text-anchor: middle\" >";
			echo "{$row['Day']}";
			echo "</text>\n";
			$left += $daywidth;
		}

		/* Print subjects that occur on a daily basis for this department */
		$query =	"SELECT period.PeriodName, period.StartTime, period.EndTime,  " .
					"       day.DayIndex FROM day, period " .
					"WHERE period.DepartmentIndex = $depindex " .
					"AND   period.Period < 1 " .
					"ORDER BY day.DayIndex, period.StartTime";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

		$day = -1;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			/* Calculate size of box for period and print */
			$left   = ($row['DayIndex']-1) * $daywidth;
			$top    = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
			$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;
			echo "<rect x=\"$left\" y=\"$top\" width=\"$daywidth\" height=\"$height\" class=\"th\" style=\"fill: white\" />";

			/* Print start time, name, and end time of period */
			$txtx   = $left + ($daywidth / 2);
			$txtsy  = $top + 5;
			$txtcy  = $top + ($height / 2) + 5;
			$txtey  = $top + $height + 5;
			$starttime = date("g:iA", strtotime($row['StartTime']));
			$endtime   = date("g:iA", strtotime($row['EndTime']));
			/*
			echo "<text x=\"$txtx\" y=\"$txtsy\" class=\"medium\" stroke-width=\"1\" stroke=\"white\" style=\"text-anchor: middle\" >";
			echo "$starttime";
			echo "</text>\n";
			echo "<text x=\"$txtx\" y=\"$txtsy\" class=\"medium\" style=\"text-anchor: middle\" >";
			echo "$starttime";
			echo "</text>\n";*/
			echo "<text x=\"$txtx\" y=\"$txtcy\" class=\"medium\" style=\"text-anchor: middle\" >";
			echo "{$row['PeriodName']}";
			echo "</text>\n";
			/*echo "<text x=\"$txtx\" y=\"$txtey\" class=\"medium\" stroke-width=\"1\" stroke=\"white\" style=\"text-anchor: middle\" >";
			echo "$endtime";
			echo "</text>\n";
			echo "<text x=\"$txtx\" y=\"$txtey\" class=\"medium\" style=\"text-anchor: middle\" >";
			echo "$endtime";
			echo "</text>\n";*/
		}

		/* Print boxes for subjects taught by this user */
		if($tttype == "t") {
			$query =	"SELECT period.PeriodName, period.StartTime, period.EndTime, period.PeriodIndex, " .
						"       day.Day, day.DayIndex FROM subject, day, subjectteacher, timetable, period " .
						"WHERE subjectteacher.Username = \"$ttusername\" " .
						"AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
						"AND   subject.YearIndex = $yearindex " .
						"AND   subject.TermIndex = $termindex " .
						"AND   timetable.SubjectIndex = subject.SubjectIndex " .
						"AND   day.DayIndex = timetable.DayIndex " .
						"AND   period.PeriodIndex = timetable.PeriodIndex " .
						"GROUP BY period.PeriodIndex, day.DayIndex " .
						"ORDER BY day.DayIndex, period.StartTime";
		} elseif($tttype == "s") {
			$query =	"SELECT period.PeriodName, period.StartTime, period.EndTime, period.PeriodIndex, " .
						"       day.Day, day.DayIndex FROM subject, day, subjectstudent, timetable, period " .
						"WHERE subjectstudent.Username = \"$ttusername\" " .
						"AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
						"AND   subject.YearIndex = $yearindex " .
						"AND   subject.TermIndex = $termindex " .
						"AND   timetable.SubjectIndex = subject.SubjectIndex " .
						"AND   day.DayIndex = timetable.DayIndex " .
						"AND   period.PeriodIndex = timetable.PeriodIndex " .
						"GROUP BY period.PeriodIndex, day.DayIndex " .
						"ORDER BY day.DayIndex, period.StartTime";
		}
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			/* Calculate size of box for period and print */
			$left   = ($row['DayIndex']-1) * $daywidth;
			$top    = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
			$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;
			echo "<rect x=\"$left\" y=\"$top\" width=\"$daywidth\" height=\"$height\" class=\"th\" style=\"fill: white\" />";

			/* Print start time, name, and end time of period */
			$txtx   = $left + ($daywidth / 2);
			$txtsy  = $top + 5;
			$txtey  = $top + $height + 5;
			$starttime = date("g:iA", strtotime($row['StartTime']));
			$endtime   = date("g:iA", strtotime($row['EndTime']));
			echo "<text x=\"$txtx\" y=\"$txtsy\" class=\"medium\" stroke-width=\"4\" stroke=\"white\" style=\"text-anchor: middle\" >";
			echo "$starttime";
			echo "</text>\n";
			echo "<text x=\"$txtx\" y=\"$txtsy\" class=\"medium\" style=\"text-anchor: middle\" >";
			echo "$starttime";
			echo "</text>\n";
			echo "<text x=\"$txtx\" y=\"$txtey\" class=\"medium\" stroke-width=\"4\" stroke=\"white\" style=\"text-anchor: middle\" >";
			echo "$endtime";
			echo "</text>\n";
			echo "<text x=\"$txtx\" y=\"$txtey\" class=\"medium\" style=\"text-anchor: middle\" >";
			echo "$endtime";
			echo "</text>\n";
		}

		/* Print subjects taught by this user */
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			/* Calculate size of box for period */
			$left   = ($row['DayIndex']-1) * $daywidth;
			$top    = ((strtotime($row['StartTime']) - $min) * $mval) + $titleheight;
			$height = (strtotime($row['EndTime']) - strtotime($row['StartTime'])) * $mval;

			/* Print start time, name, and end time of period */
			$txtx   = $left + ($daywidth / 2);
			if($tttype == "t") {
				$nquery =	"SELECT subject.ShortName FROM subject, subjectteacher, timetable " .
							"WHERE subjectteacher.Username = \"$ttusername\" " .
							"AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
							"AND   subject.YearIndex = $yearindex " .
							"AND   subject.TermIndex = $termindex " .
							"AND   timetable.SubjectIndex = subject.SubjectIndex " .
							"AND   timetable.DayIndex = {$row['DayIndex']} " .
							"AND   timetable.PeriodIndex = {$row['PeriodIndex']} " .
							"ORDER BY subject.Name";
			} elseif($tttype == "s") {
				$nquery =	"SELECT subject.ShortName FROM subject, subjectstudent, timetable " .
							"WHERE subjectstudent.Username = \"$ttusername\" " .
							"AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
							"AND   subject.YearIndex = $yearindex " .
							"AND   subject.TermIndex = $termindex " .
							"AND   timetable.SubjectIndex = subject.SubjectIndex " .
							"AND   timetable.DayIndex = {$row['DayIndex']} " .
							"AND   timetable.PeriodIndex = {$row['PeriodIndex']} " .
							"ORDER BY subject.Name";
			}

			$nrs =&  $db->query($nquery);
			if(DB::isError($nrs)) die($nrs->getDebugInfo());         // Check for errors in query
			$txtcy = ($top + ($height / 2) + 5) - (($nrs->numRows()-1) * 10);
			while ($nrow =& $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "<text x=\"$txtx\" y=\"$txtcy\" class=\"large\" style=\"text-anchor: middle\" >";
				echo "{$nrow['ShortName']}";
				echo "</text>\n";
				$txtcy += 20;
			}
		}
	}

?>
</svg>


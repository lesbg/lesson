<?php
/**
 * ***************************************************************
 * teacher/assignment/print.php (c) 2005-2013 Jonathan Dieter
 *
 * Show grades for class
 * ***************************************************************
 */

/* Get variables */
$title = dbfuncInt2String($_GET['keyname']);
$subjectindex = safe(dbfuncInt2String($_GET['key']));

include "core/settermandyear.php";

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" " .
	 "\"http://www.w3.org/TR/html4/loose.dtd\">\n";
echo "<html>\n";
echo "   <head>\n";
echo "      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
echo "      <title>$title</title>\n";
echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"screen\">\n";
echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"print\">\n";
echo "   </head>\n";
echo "   <body>\n";
echo "      <table class=\"transparent\" width=\"100%\">\n";
echo "         <tr>\n";
$useragent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('|MSIE ([0-6].[0-9]{1,2})|', $useragent, $matched)) {
	// Can't handle transparent png's, so we'll give them transparent gif's
	?><td width="200px" class="logo"><img height="50" width="200"
	alt="LESSON Logo" src="images/lesson_logo_small.gif"></td><?php
} else {
	// Can't handle transparent png's, so we'll give them transparent gif's
	?><td width="200px" class="logo"><img height="50" width="200"
	alt="LESSON Logo" src="images/lesson_logo_small.png"></td><?php
}
echo "            <td class=\"title\">$title</td>\n";
echo "            <td width=\"120px\" class=\"home\">\n";

/* Check whether user is authorized to change scores */
$res = & $db->query(
				"SELECT subjectteacher.Username FROM subjectteacher " .
				 "WHERE subjectteacher.SubjectIndex = $subjectindex " .
				 "AND   subjectteacher.Username     = \"$username\"");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 || dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	/* Get class index */
	$res = & $db->query(
					"SELECT ClassIndex FROM subject " .
						 "WHERE subject.SubjectIndex = $subjectindex");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	$class_index = $row['ClassIndex'];
	
	$nochangeyt = true;
	
	include "core/titletermyear.php";
	
	echo "      </table>\n";
	
	echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>&nbsp;</th>\n";
	echo "            <th>Student</th>\n";
	
	/* For each student, print a row with the student's name and final score */
	if ($class_index == NULL) {
		$query = "SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder, " .
			 "       subjectstudent.Average FROM user, " .
			 "       subjectstudent LEFT OUTER JOIN " .
			 "       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
			 "               classlist, classterm, subject " .
			 "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
			 "        AND   classterm.TermIndex = subject.TermIndex " .
			 "        AND   class.ClassIndex = classterm.ClassIndex " .
			 "        AND   class.YearIndex = subject.YearIndex " .
			 "        AND subject.SubjectIndex = $subjectindex) AS query " .
			 "       ON subjectstudent.Username = query.Username " .
			 "WHERE user.Username = subjectstudent.Username " .
			 "AND subjectstudent.SubjectIndex = $subjectindex " .
			 "ORDER BY user.FirstName, user.Surname, user.Username";
	} else {
		$query = "SELECT user.FirstName, user.Surname, user.Username, classlist.ClassOrder, " .
				 "       subjectstudent.Average FROM user, classterm, " .
				 "       classlist LEFT OUTER JOIN subjectstudent " .
				 "       ON (classlist.Username = subjectstudent.Username " .
				 "           AND subjectstudent.SubjectIndex = $subjectindex) " .
				 "WHERE user.Username = classlist.Username " .
				 "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				 "AND   classterm.TermIndex = $termindex " .
				 "AND   classterm.ClassIndex = $class_index " .
				 "ORDER BY user.FirstName, user.Surname, user.Username";
	}
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	$alt_count = 0;
	$order = 1;
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$alt_count += 1;
		if ($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		
		$alt = " class=\"$alt_step\"";
		echo "         <tr$alt>\n";
		echo "            <td nowrap>$order</td>\n";
		$order += 1;
		
		echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
		if ($row['Average'] == - 1 or $row['Average'] == NULL) {
			echo "            <td nowrap align=\"center\">-</td>\n";
		} else {
			$average = round($row['Average']);
			echo "            <td nowrap>$average%</td>\n";
		}
		echo "         </tr>\n";
	}
	
	$alt_count += 1;
	if ($alt_count % 2 == 0) {
		$alt_step = "alt";
	} else {
		$alt_step = "std";
	}
	$alt = " class=\"$alt_step\"";
	
	echo "         <tr$alt>\n";
	echo "            <td nowrap>&nbsp;</td>\n";
	echo "            <td nowrap><i>Class Average</i></td>\n";
	
	/* Get total subject average */
	$query = "SELECT Average FROM subject " .
			 "WHERE SubjectIndex = $subjectindex ";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if ($row['Average'] > - 1) {
			$average = round($row['Average']) . "%";
		} else {
			$average = "N/A";
		}
		echo "            <td nowrap><i>$average</i></td>\n";
	}
	echo "         </tr>\n";
	echo "      </table>\n"; // End of table
	
	log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/print.php", 
			$LOG_TEACHER, "Accessed printable marks for $title.");
} else { // User isn't authorized to view or change scores.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/assignment/print.php", 
			$LOG_DENIED_ACCESS, "Tried to access printable marks for $title.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>

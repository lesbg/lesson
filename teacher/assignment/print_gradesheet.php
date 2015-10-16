<?php
/**
 * ***************************************************************
 * teacher/assignment/print_gradesheet.php (c) 2005, 2006 Jonathan Dieter
 *
 * Show a printable gradesheet for class
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

/* Check whether user is authorized to view printable marks */
$res = & $db->query(
				"SELECT subjectteacher.Username FROM subjectteacher " .
				 "WHERE subjectteacher.SubjectIndex = $subjectindex " .
				 "AND   subjectteacher.Username     = \"$username\"");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0 || dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	
	/* Get student list */
	$res = &  $db->query(
					"SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder FROM user, " .
						 "       subjectstudent LEFT OUTER JOIN " .
						 "       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
						 "               classlist, classterm, subject " .
						 "        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
						 "        AND   classterm.TermIndex = subject.TermIndex " .
						 "        AND   class.ClassIndex = classterm.ClassIndex " .
						 "        AND   class.YearIndex = subject.YearIndex " .
						 "        AND subject.SubjectIndex=$subjectindex) AS query " .
						 "       ON subjectstudent.Username = query.Username " .
						 "WHERE user.Username=subjectstudent.Username " .
						 "AND subjectstudent.SubjectIndex=$subjectindex " .
						 "ORDER BY user.FirstName, user.Surname, user.Username");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
		
	/* Print assignments and scores */
	if ($res->numRows() > 0) {
		$nochangeyt = true;
		
		include "core/titletermyear.php";
		echo "            </td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";
		
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Student</th>\n";
		
		$count = 1;
		while ( $count < 11 ) {
			echo "            <th><br><br><br><br><br><br></th>\n";
			$count += 1;
		}
		
		/* For each student, print a row with the student's name and score on each assignment */
		$alt_count = 0;
		$order = 1;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "         <tr>\n";
			/* echo " <td nowrap>{$row['ClassOrder']}</td>\n"; */
			echo "            <td nowrap>$order</td>\n";
			$order += 1;
			echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			
			$count = 1;
			while ( $count < 11 ) {
				echo "            <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";
				$count += 1;
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>No students</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/print_gradesheet.php", 
			$LOG_TEACHER, "Accessed printable gradesheet for $title.");
} else { // User isn't authorized to view or change scores.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/assignment/print_gradesheet.php", 
			$LOG_DENIED_ACCESS, 
			"Tried to access printable gradesheet for $title.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>

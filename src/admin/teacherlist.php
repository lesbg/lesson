<?php
/**
 * ***************************************************************
 * admin/teacherlist.php (c) 2004-2007, 2016 Jonathan Dieter
 *
 * List all active teachers and what subjects they are currently
 * teaching
 * ***************************************************************
 */
$title = "Teacher List";

include "header.php"; // Show header

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) { // Make sure user has permission to view and
	$showalldeps = true;
	include "core/settermandyear.php"; // edit teachers
	include "core/titletermyear.php";
	
	/* Get teacher list */
	$query =	"SELECT user.Title, user.FirstName, user.Surname, user.Username, " .
				"       user.PhoneNumber, COUNT(timetable.TimetableIndex) AS PeriodCount " .
				"       FROM user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
				"                 INNER JOIN groups USING (GroupID) " .
				"                 LEFT OUTER JOIN " .
				"                  (subjectteacher INNER JOIN timetable USING (SubjectIndex) " . 
				"                                  INNER JOIN subject ON timetable.SubjectIndex=subject.SubjectIndex AND subject.YearIndex=$yearindex " . 
				"                                  INNER JOIN currentterm ON subject.TermIndex=currentterm.TermIndex) ON (user.Username=subjectteacher.Username) " .
				"WHERE groups.GroupTypeID='activeteacher' " .
				"AND   groups.YearIndex=$yearindex " .
				"AND   user.DepartmentIndex = $depindex " .
				"GROUP BY user.Username " . 
				"ORDER BY user.Username";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
		
	/* Print all teachers and the subjects they teach */
	if ($res->numRows() > 0) {
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Teacher</th>\n";
		echo "            <th>Subjects</th>\n";
		echo "            <th>Periods Teaching</th>\n";
		echo "         </tr>\n";
		
		/* For each teacher, print a row with the teacher's name and their subjects */
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class=\"alt\"";
			} else {
				$alt = " class=\"std\"";
			}
			$editlink = "index.php?location=" .
						 dbfuncString2Int("admin/user/modify.php") . "&amp;key=" .
						 dbfuncString2Int($row['Username']) . "&amp;keyname=" .
						 dbfuncString2Int(
										$row['FirstName'] . " " .
										 $row['Surname']);
			$ttlink = "index.php?location=" .
					 dbfuncString2Int("user/timetable.php") . "&amp;key=" .
					 dbfuncString2Int($row['Username']) . "&amp;keyname=" .
					 dbfuncString2Int(
									$row['FirstName'] . " " . $row['Surname']);
			$textLink = "index.php?location=" .
						 dbfuncString2Int("admin/sms/send.php") . "&amp;key=" .
						 dbfuncString2Int($row['Username']) . "&amp;keyname=" .
						 dbfuncString2Int(
										$row['FirstName'] . " " .
										 $row['Surname']);
			$bookLink = "index.php?location=" .
						 dbfuncString2Int("teacher/book/book_list.php") .
						 "&amp;key=" . dbfuncString2Int($row['Username']) .
						 "&amp;keyname=" .
						 dbfuncString2Int(
										$row['FirstName'] . " " .
										 $row['Surname']);
			$classResult = & $db->query(
									"SELECT subject.Name FROM subject, subjectteacher, currentterm " .
									 "WHERE subjectteacher.SubjectIndex = subject.SubjectIndex " .
									 "AND   subjectteacher.Username     = \"{$row['Username']}\" " .
									 "AND   subject.YearIndex  = $yearindex " .
									 "AND   (subject.TermIndex = currentterm.TermIndex OR subject.TermIndex = NULL) " .
									 "ORDER BY subject.Name");
			if (DB::isError($classResult))
				die($classResult->getDebugInfo()); // Check for errors in query
			
			echo "         <tr$alt>\n";
			
			/* Generate view and edit buttons */
			$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", 
										"Edit teacher");
			$ttbutton = dbfuncGetButton($ttlink, "T", "small", "tt", 
										"View teacher's timetable");
			$textbutton = dbfuncGetButton($textLink, "M", "small", "view", 
										"Send text message");
			$bookbutton = dbfuncGetButton($bookLink, "B", "small", "cn", 
										"See teacher's books");
			if ($row['PhoneNumber'] == "") {
				echo "            <td align=\"right\">$ttbutton $editbutton $bookbutton</td>\n";
			} else {
				echo "            <td align=\"right\">$textbutton $ttbutton $editbutton $bookbutton</td>\n";
			}
			echo "            <td>{$row['Title']} {$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			if ($classRow = & $classResult->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "            <td>{$classRow['Name']}";
				while ( $classRow = & $classResult->fetchRow(DB_FETCHMODE_ASSOC) ) {
					echo "<br>\n                {$classRow['Name']}";
				}
				echo "</td>\n";
				echo "<td>{$row['PeriodCount']}</td>\n";
			} else {
				echo "            <td><i>None</i></td>\n";
				echo "            <td>0</td>\n";
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>There are no active teachers</p>\n";
	}
} else {
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
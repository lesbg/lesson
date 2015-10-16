<?php
/**
 * ***************************************************************
 * student/lateinfo.php (c) 2006, 2007 Jonathan Dieter
 *
 * Print information about late assignments
 * ***************************************************************
 */
$query = "SELECT assignment.CategoryListIndex FROM assignment, subjectstudent, subject " .
		 "WHERE  assignment.SubjectIndex  = subjectstudent.SubjectIndex " .
		 "AND    subjectstudent.Username = '$username' " .
		 "AND    subject.SubjectIndex    = subjectstudent.SubjectIndex " .
		 "AND    subject.YearIndex       = $yearindex " .
		 "AND    subject.TermIndex       = $termindex " .
		 "AND    (subject.AverageType     = $AVG_TYPE_PERCENT OR subject.AverageType = $AVG_TYPE_GRADE) " .
		 "AND    assignment.CategoryListIndex IS NOT NULL";
$pres = &  $db->query($query);
if (DB::isError($pres))
	die($pres->getDebugInfo()); // Check for errors in query

if ($pres->numRows() > 0) {
	$has_categories = True;
} else {
	$has_categories = False;
}

$query = "SELECT Title, Date, DueDate, AssignmentIndex, Description, DescriptionData, " .
		 "       DescriptionFileType, AverageType, ShowAverage, " .
		 "       Uploadable, assignment.Weight, Score, Percentage, mark.Comment, " .
		 "       subjectstudent.Average AS StudentSubjectAverage, " .
		 "       CanModify, CategoryName, subject.SubjectIndex " .
		 "       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
		 "       INNER JOIN mark USING (AssignmentIndex) INNER JOIN subjectstudent " .
		 "       ON (subjectstudent.SubjectIndex = subject.SubjectIndex AND subjectstudent.Username = mark.Username) " .
		 "       LEFT OUTER JOIN categorylist USING (CategoryListIndex) LEFT OUTER JOIN category USING (CategoryIndex) " .
		 "WHERE mark.Username     = '$studentusername' " .
		 "AND   Hidden       = 0 " . "AND   mark.Score   = $MARK_LATE " .
		 "AND   subject.CanModify = 1 " . "AND   YearIndex    = $yearindex " .
		 "AND   TermIndex    = $termindex " .
		 "ORDER BY Date DESC, AssignmentIndex DESC";

$pres = &  $db->query($query);
if (DB::isError($pres))
	die($pres->getDebugInfo()); // Check for errors in query
	
/* Print assignments and scores */
if ($pres->numRows() > 0) {
	echo "      <p class='subtitle' align='center'>Late Assignments</p>\n";
	echo "      <table align='center' border='1'>\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Title</th>\n";
	echo "            <th>Subject</th>\n";
	echo "            <th>Teacher</th>\n";
	if ($has_categories)
		echo "            <th>Category</th>\n";
	echo "            <th>Date</th>\n";
	echo "            <th>Due Date</th>\n";
	echo "            <th>Score</th>\n";
	echo "            <th>Comment</th>\n";
	echo "         </tr>\n";
	
	/* For each assignment, print subject, teacher, assignment title, date, score, and any comments */
	$alt_count = 0;
	while ( $prow = & $pres->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$alt_count += 1;
		if ($alt_count % 2 == 0) {
			$alt_step = "alt";
		} else {
			$alt_step = "std";
		}
		echo "         <tr class='late-$alt_step'>\n";
		
		if (is_null($row['DescriptionFileType'])) {
			if (is_null($row['Description'])) {
				echo "            <td>{$row['Title']}</td>\n";
			} else {
				$newwin = "index.php?location=" .
						 dbfuncString2Int("student/descr.php") . "&amp;key=" .
						 dbfuncString2Int($row['AssignmentIndex']);
				echo "          <td><a class='late' href='javascript:popup(&quot;$newwin&quot;)'>{$row['Title']}</a></td>\n";
			}
		} else {
			$newwin = "index.php?location=" .
					 dbfuncString2Int("student/open_descr.php") . "&amp;key=" .
					 dbfuncString2Int($row['AssignmentIndex']);
			echo "          <td><a class='late' href='$newwin'>{$row['Title']}</a></td>\n";
		}
		echo "            <td>{$prow['SubjectName']}</td>\n"; // Name of class
		
		/* Print name(s) of teacher(s) */
		echo "            <td>";
		$query = "SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
				 "WHERE subjectteacher.SubjectIndex = {$prow['SubjectIndex']} " .
/*						"AND   subjectteacher.ShowTeacher  = '1' " .*/
						"AND   user.Username               = subjectteacher.Username";
		$teacherRes = & $db->query($query);
		if (DB::isError($teacherRes))
			die($teacherRes->getDebugInfo());
		if ($teacherRow = & $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
			$teacherRow['Title'] = htmlspecialchars($teacherRow['Title']);
			$teacherRow['FirstName'] = htmlspecialchars(
														$teacherRow['FirstName']);
			$teacherRow['Surname'] = htmlspecialchars($teacherRow['Surname']);
			$teacherp = "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
			
			/* If there's more than one teacher, separate with commas */
			while ( $teacherRow = & $teacherRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$teacherRow['Title'] = htmlspecialchars($teacherRow['Title']);
				$teacherRow['FirstName'] = htmlspecialchars(
															$teacherRow['FirstName']);
				$teacherRow['Surname'] = htmlspecialchars(
														$teacherRow['Surname']);
				$teacherp .= ", {$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
			}
		}
		if (strlen($teacherp) > 30) {
			echo substr($teacherp, 0, 27) . "...</td>\n";
		} else {
			echo "$teacherp</td>\n";
		}
		
		if ($has_categories) {
			if (is_null($row['CategoryName'])) {
				echo "<td><i>None</i></td>\n";
			} else {
				echo "<td>{$row['CategoryName']}</td>\n";
			}
		}
		
		$dateinfo = date($dateformat, strtotime($prow['Date']));
		if (isset($prow['DueDate'])) {
			$duedateinfo = date($dateformat, strtotime($prow['DueDate']));
		} else {
			$duedateinfo = "";
		}
		echo "            <td>$dateinfo</td>\n";
		echo "            <td>$duedateinfo</td>\n";
		echo "            <td>&nbsp;</td>\n";
		if ($prow['Score'] == $MARK_LATE) {
			if ($prow['Comment'] == "" or is_null($prow['Comment'])) {
				echo "            <td>Late</td>\n";
			} else {
				echo "            <td>{$prow['Comment']}</td>\n";
			}
		} else {
			echo "            <td>{$prow['Comment']}</td>\n";
		}
		echo "         </tr>\n";
	}
	echo "      </table>\n"; // End of table
}
?>
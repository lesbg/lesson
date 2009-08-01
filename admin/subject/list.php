<?php
	/*****************************************************************
	 * admin/subject/list.php  (c) 2004, 2005 Jonathan Dieter
	 *
	 * List all subjects for the current term and each subject's 
	 * teacher(s)
	 *****************************************************************/

	$title = "Subject List";
	
	include "header.php";                                        // Show header

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {         // Make sure user has permission to view and
		$showalldeps = true;                                     //  edit subjects
		include "core/settermandyear.php";
		include "core/titletermyear.php";

		if(!isset($_GET['sort'])) $_GET['sort'] = '0';
		if($_GET['sort'] == '1') {
			$sortorder = "query.Grade DESC, query.ClassName DESC, query.Name DESC";
		} elseif($_GET['sort'] == '2') {
			$sortorder = "StudentCount, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '3') {
			$sortorder = "StudentCount DESC, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '4') {
			$sortorder = "query.NoMarks DESC, query.AssignmentCount, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '5') {
			$sortorder = "query.NoMarks, query.AssignmentCount DESC, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '6') {
			$sortorder = "query.Average, query.AssignmentCount, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '7') {
			$sortorder = "query.Average DESC, query.AssignmentCount DESC, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '8') {
			$sortorder = "timetablequery.PeriodCount, query.Grade, query.ClassName, query.Name";
		} elseif($_GET['sort'] == '9') {
			$sortorder = "timetablequery.PeriodCount DESC, query.Grade, query.ClassName, query.Name";
		} else {
			$sortorder = "query.Grade, query.ClassName, query.Name";
		}
		
		$nameAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                           "&amp;sort=0", "A", "small", "sort", "Sort ascending");
		$nameDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                           "&amp;sort=1", "D", "small", "sort", "Sort descending");
		$stctAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=2", "A", "small", "sort", "Sort ascending");
		$stctDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=3", "D", "small", "sort", "Sort descending");
		$asctAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=4", "A", "small", "sort", "Sort ascending");
		$asctDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=5", "D", "small", "sort", "Sort descending");
		$avgAsc  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=6", "A", "small", "sort", "Sort ascending");
		$avgDec  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=7", "D", "small", "sort", "Sort descending");
		$perAsc  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=8", "A", "small", "sort", "Sort ascending");
		$perDec  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/subject/list.php") .
		                            "&amp;sort=9", "D", "small", "sort", "Sort descending");
		
		$query =	"SELECT query.CanDoReport, query.SubjectIndex, query.Average, query.ClassName, " .
					"       query.Grade, query.RealGrade, query.ClassIndex, query.Name, " .
					"       query.AssignmentCount, query.NoMarks, timetablequery.PeriodCount, " .
					"       COUNT(subjectstudent.SubjectIndex) AS StudentCount, " .
					"       MIN(subjectstudent.ReportDone) AS ReportDone " .
					"FROM ( " .
					"      (SELECT subject.CanDoReport, subject.SubjectIndex, " .
					"              NULL AS ClassName, subject.Grade, subject.NoMarks, " .
					"              subject.Grade AS RealGrade, subject.Period, subject.Name, NULL AS ClassIndex, " .
					"              subject.Average, COUNT(assignment.SubjectIndex) AS AssignmentCount " .
					"       FROM subject LEFT OUTER JOIN assignment ON " .
					"            subject.SubjectIndex = assignment.SubjectIndex " .
					"       WHERE subject.YearIndex = $yearindex " .
					"       AND   subject.TermIndex = $termindex " .
					"       AND   subject.ClassIndex IS NULL " .
					"       GROUP BY subject.SubjectIndex) " .
					"      UNION " .
					"      (SELECT subject.CanDoReport, subject.SubjectIndex, " .
					"              class.ClassName, class.Grade, subject.NoMarks, " .
					"              NULL AS RealGrade, subject.Period, subject.Name, class.ClassIndex, " .
					"              subject.Average, COUNT(assignment.SubjectIndex) AS AssignmentCount " .
					"       FROM class, subject LEFT OUTER JOIN assignment ON " .
					"            subject.SubjectIndex = assignment.SubjectIndex " .
					"       WHERE subject.YearIndex = $yearindex " .
					"       AND   subject.TermIndex = $termindex " .
					"       AND   subject.ClassIndex = class.ClassIndex " .
					"       AND   subject.ClassIndex IS NOT NULL " .
					"       GROUP BY subject.SubjectIndex) " .
					" ) AS query " .
					"LEFT OUTER JOIN subjectstudent ON " .
					" query.SubjectIndex = subjectstudent.SubjectIndex, " .
					"(SELECT subject.SubjectIndex, COUNT(timetable.TimetableIndex) AS PeriodCount " .
					"        FROM subject LEFT OUTER JOIN timetable USING (SubjectIndex) " .
					"        WHERE subject.YearIndex = $yearindex " .
					"        AND   subject.TermIndex = $termindex " .
					"        GROUP BY subject.SubjectIndex) AS timetablequery " .
					"WHERE timetablequery.SubjectIndex = query.SubjectIndex " .
					"GROUP BY query.SubjectIndex " .
					"ORDER BY $sortorder";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$newlink =  "index.php?location=" .  dbfuncString2Int("admin/subject/new.php") . // link to create a new subject 
					"&amp;next=" .           dbfuncString2Int("index.php?location=" .
											dbfuncString2Int("admin/subject/list.php"));
		$newbutton = dbfuncGetButton($newlink, "New subject", "medium", "", "Create new subject");
		echo "      <p align=\"center\">$newbutton</p>\n";

		/* Print subjects and the teachers that teach them */
		if($res->numRows() > 0) {
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Subject $nameAsc $nameDec</th>\n";
			echo "            <th>Teacher(s)</th>\n";
			echo "            <th>Students $stctAsc $stctDec</th>\n";
			echo "            <th>Periods $perAsc $perDec</th>\n";
			echo "            <th>Assignments $asctAsc $asctDec</th>\n";
			echo "            <th>Average $avgAsc $avgDec</th>\n";
			echo "         </tr>\n";
			
			/* For each subject, print a row with the subject's name, # of students, and teacher name(s) */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class=\"alt\"";
				} else {
					$alt = " class=\"std\"";
				}
				$viewlink = "index.php?location=" .  dbfuncString2Int("teacher/assignment/list.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']);
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/subject/modify_list.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;key2=" .           dbfuncString2Int($row['ClassIndex']) .
							"&amp;key3=" .           dbfuncString2Int($row['RealGrade']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']);
				$ttlink =	"index.php?location=" .  dbfuncString2Int("admin/subject/timetable.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']);
				$replink =	"index.php?location=" .  dbfuncString2Int("teacher/report/modify.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']);
				
				echo "         <tr$alt>\n";
				
				/* Generate view and edit buttons */
				$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",  "View subject scores");
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit",  "Add or remove students from subject");
				$ttbutton  =   dbfuncGetButton($ttlink,   "T", "small", "tt",    "View subject's timetable");
				if($row['CanDoReport']) {
					if($row['ReportDone']) {
						$repbutton =   dbfuncGetButton($replink,   "V", "small", "report","View report");
					} else {
						$repbutton =   dbfuncGetButton($replink,   "R", "small", "report","Edit report");
					}
				} else {
					$repbutton = "";
				}
				echo "            <td>$ttbutton$viewbutton$editbutton$repbutton</td>\n";
				echo "            <td>{$row['Name']}</td>\n";                // Print subject name
				
				/* Get the name(s) of the teacher(s) of the subject */
				$tResult =& $db->query("SELECT user.Username, user.FirstName, user.Surname FROM user, " .
									   "       subjectteacher " .
									   "WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
									   "AND   user.Username = subjectteacher.Username " .
									   "ORDER BY user.Username");
				if(DB::isError($tResult)) die($tResult->getDebugInfo());     // Check for errors in query
				
				if($tRow =& $tResult->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo "            <td>{$tRow['FirstName']} {$tRow['Surname']} ({$tRow['Username']})";
					while($tRow =& $tResult->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "<br>\n                {$tRow['FirstName']} {$tRow['Surname']} ({$tRow['Username']})";
					}
					echo "</td>\n";
				} else {
					echo "            <td><i>No teacher</i></td>\n";
				}
				
				echo "            <td>{$row['StudentCount']}</td>\n";                    // Print number of students
				echo "            <td>{$row['PeriodCount']}</td>\n";
				if($row['NoMarks'] == 1) {
					echo "            <td>X</td>\n";
				} else {
					echo "            <td>{$row['AssignmentCount']}</td>\n";
				}
				if($row['Average'] == "-1") {
					echo "            <td><i>N/A</i></td>\n";
				} else {
					$average = round($row['Average']);
					echo "            <td>$average%</td>\n";
				}

				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no subjects this term.</p>\n";
		}
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
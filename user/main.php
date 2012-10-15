<?php
	/*****************************************************************
	 * user/main.php  (c) 2004-2009 Jonathan Dieter
	 *
	 * Initial page that shows what classes the user is in, if they
	 * are a student or what classes they teach if they are a teacher
	 *****************************************************************/

	/* Title */
	$title = $fullname;
	$noJS  = true;
	
	/* Logout link */
	$homelink   = "index.php?location=" . dbfuncString2Int("user/logout.php");
	$homebutton = dbfuncGetButton($homelink, "Logout", "small", "logout", "Logout of LESSON");

	/* Welcome */
	include "header.php";
	
	$main_page = true;
	$show_all_deps = true;
	include "core/settermandyear.php";
	include "core/titletermyear.php";

	echo "       <p>&nbsp;</p>\n";
	echo "       <div class='button' style='position: absolute; left: 15px'>\n";
	/* Check whether Administrator, and show Admin Tool hyperlink if so */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$adminToolsLink  = "index.php?location=" . dbfuncString2Int("admin/tools.php");
		echo "      <p><a href='$adminToolsLink'>Admin Tools</a></p>\n";
	}
	
	/* Provide link for changing password */
	$changePWLink = "index.php?location=" . dbfuncString2Int("user/changepassword.php");
	echo "      <p><a href='$changePWLink'>Change Password</a></p>\n";
	$timetableLink = "index.php?location=" . dbfuncString2Int("user/timetable.php") .
					"&amp;key=" . dbfuncString2Int($username) .
					"&amp;keyname=" . dbfuncString2Int($fullname);
	echo "      <p><a href='$timetableLink'>Timetable</a></p>\n";

	/* If we're supposed to proofread reports this term, show link */
	/*$query =	"SELECT user.Username " .
				"       FROM department, user, class, class_term, classterm " .
				"WHERE user.Username                  = classlist.Username " .
				"AND   classterm.TermIndex            = class_term.TermIndex " .
				"AND   classterm.ClassListIndex       = classlist.ClassListIndex " .
				"AND   classterm.ReportProofread      = 1 " .
				"AND   classterm.ReportProofDone      = 0 " .
				"AND   class_term.ClassIndex          = classlist.ClassIndex " .
				"AND   class_term.CanDoReport         = 1 " .
				"AND   class.ClassIndex               = classlist.ClassIndex " .
				"AND   class.YearIndex                = $yearindex " .
				"AND   class_term.TermIndex           = $termindex " .
				"AND   department.DepartmentIndex     = class.DepartmentIndex " .
				"AND   department.ProofreaderUsername = '$username' ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$prLink =	"index.php?location=" . dbfuncString2Int("teacher/report/proofread_list.php");
		echo "      <p><a href='$prLink'>Proofread reports</a></p>\n";
	}*/

	/* If user is a class teacher and there are class reports ready, show link */
	$query =	"SELECT classterm.ClassTermIndex, class.ClassName, " .
				"       classterm.CanDoReport, MIN(classlist.ReportDone) AS ReportDone " .
				"       FROM class, classterm, classlist " .
				"WHERE class.ClassTeacherUsername = '$username' " .
				"AND   class.YearIndex            = $yearindex " .
				"AND   classterm.ClassIndex       = class.ClassIndex " .
				"AND   classterm.TermIndex        = $termindex " .
				"AND   classlist.ClassTermIndex   = classterm.ClassTermIndex " .
				"GROUP BY class.ClassIndex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) and ($row['CanDoReport'] or $row['ReportDone'])) {
		$clLink =	"index.php?location=" . dbfuncString2Int("teacher/report/class_list.php") .
					"&amp;key=" .           dbfuncString2Int($row['ClassTermIndex']) .
					"&amp;keyname=" .       dbfuncString2Int($row['ClassName']);
		echo "      <p><a href='$clLink'>Class reports for {$row['ClassName']}</a></p>\n";
	}

	/* If user is responsible for any books, show book link */
	$query =	"SELECT book_title.BookTitleIndex FROM book_title, book_title_owner " .
				"WHERE book_title_owner.Username = '$username' " .
				"AND   book_title_owner.YearIndex = $currentyear " .
				"AND   book_title_owner.BookTitleIndex = book_title.BookTitleIndex " .
				"AND   book_title.Retired = 0";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$bookLink =	"index.php?location=" . dbfuncString2Int("teacher/book/book_list.php") .
					"&amp;key=" .           dbfuncString2Int($username) .
					"&amp;keyname=" .       dbfuncString2Int($fullname);
		echo "      <p><a href='$bookLink'>Book list</a></p>\n";
	}
				
	/* If user is a hod, show class list hyperlink */
	$res =&  $db->query("SELECT Username FROM hod " .
						"WHERE Username='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$clLink      = "index.php?location=" . dbfuncString2Int("admin/class/list.php");
		echo "      <p><a href='$clLink'>Class list</a></p>\n";
	}

	/* If user is a counselor, show class list hyperlink and support teachers hyperlink*/
	$res =&  $db->query("SELECT Username FROM counselorlist " .
						"WHERE Username='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$clLink      = "index.php?location=" . dbfuncString2Int("admin/class/list.php");
		$supportLink = "index.php?location=" . dbfuncString2Int("admin/support/modify.php") .
												  "&amp;next=" . dbfuncString2Int("index.php?location=" . dbfuncString2Int("user/main.php"));
		echo "      <p><a href='$clLink'>Class list</a></p>\n";
		echo "      <p><a href='$supportLink'>Support teachers</a></p>\n";
	}

	/* If user is an active teacher, show Casenotes and Punishments history hyperlinks */
	$nrs =&  $db->query("SELECT Username FROM user WHERE Username='$username' AND " .
						"ActiveTeacher=1");
	if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
	if($nrs->numRows() > 0) {
		$nrs =&  $db->query("SELECT WorkerUsername FROM casenotenew " .
							"WHERE  WorkerUsername = '$username'");
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		$wlLink = "index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php");
		
		if($nrs->numRows() > 0) {
			$cr = $nrs->numRows();
			echo "      <p><b><a href='$wlLink'>New Casenotes ($cr)</a></b></p>\n";
		} else {
			echo "      <p><a href='$wlLink'>New Casenotes (0)</a></p>\n";
		}
		
		$query =    "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$perm = $row['Permissions'];
		} else {
			$perm = 0;
		}
		
		if($perm >= $PUN_PERM_MASS or dbfuncGetPermission($permissions, $PERM_ADMIN)) {
			$punLink =	"index.php?location=" . dbfuncString2Int("admin/punishment/tools.php");
			echo "      <p><a href='$punLink'>Punishment Tools</a></p>\n";
		}
		$disclink = "index.php?location=" . dbfuncString2Int("teacher/punishment/list.php") .
					"&amp;key="               . dbfuncString2Int($username) .
					"&amp;keyname="           . dbfuncString2Int($fullname);
		echo "      <p><a href='$disclink'>Issued Punishments</a></p>\n";
	}

	/* Check whether teacher is taking attendance for a punishment */
	$query =	"SELECT disciplinetype.DisciplineType, disciplinedate.DisciplineTypeIndex " .
				"       FROM disciplinedate, disciplinetype " .
				"WHERE disciplinedate.Username = '$username' " .
				"AND   disciplinedate.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
				"AND   disciplinedate.Done=0";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$link =	"index.php?location=" . dbfuncString2Int("teacher/punishment/date/modify.php") .
				"&amp;type="          . dbfuncString2Int($row['DisciplineTypeIndex']) .
				"&amp;next="          . dbfuncString2Int("index.php?location=" .
												dbfuncString2Int("user/main.php"));
		$pun_type = strtolower($row['DisciplineType']);
		echo "<p><a href='$link'>Punishment attendance for next $pun_type</a></p>\n";
	}
	echo "      </div>\n";

	/* Get classes */
	$studentusername = $username;
	$query =	"SELECT subject.Name, subject.SubjectIndex, subject.Period, " .
				"       subject.ShowAverage, subjectstudent.Average, subject.AverageType, " .
				"       subject.AverageTypeIndex FROM subject, subjectstudent " .
				"WHERE subjectstudent.SubjectIndex = subject.SubjectIndex " .
				"AND   subject.AverageType        != $AVG_TYPE_NONE " .
				"AND   subject.YearIndex           = $yearindex " .
				"AND   subject.TermIndex           = $termindex " .
				"AND   subjectstudent.Username     = '$studentusername' " .
				"ORDER BY subject.Period, subject.Name";
	$res =& $db->query($query);

	if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
	
	/* If user is a student in at least one subject, print out class table */
	if($res->numRows() > 0) {
		include "student/lateinfo.php";

		/* First give option to show all assignments */
		$alllink =	"index.php?location=" .  dbfuncString2Int("student/allinfo.php") .
					"&amp;key=" .            dbfuncString2Int($username) .
					"&amp;keyname=" .        dbfuncString2Int($fullname) .
					"&amp;show=" .           dbfuncString2Int("a");
		$hwlink = 	"index.php?location=" .  dbfuncString2Int("student/allinfo.php") .
					"&amp;key=" .            dbfuncString2Int($username) .
					"&amp;keyname=" .        dbfuncString2Int($fullname) .
					"&amp;show=" .           dbfuncString2Int("u");
		$thwlink = 	"index.php?location=" .  dbfuncString2Int("student/allinfo.php") .
					"&amp;key=" .            dbfuncString2Int($username) .
					"&amp;keyname=" .        dbfuncString2Int($fullname) .
					"&amp;show=" .           dbfuncString2Int("t");
		
		$allbutton = dbfuncGetButton($alllink, "View all assignments", "medium", "", "");
		$hwbutton  = dbfuncGetButton($hwlink,  "View homework", "medium", "", "");
		$thwbutton  = dbfuncGetButton($thwlink,  "View today's homework", "medium", "", "");
		echo "      <p align='center'>$hwbutton $thwbutton $allbutton</p>";

		
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Subject</th>\n";
		echo "            <th>Teacher(s)</th>\n";
		echo "            <th>Average</th>\n";
		echo "         </tr>\n";
		
		/* For each subject, print a row with the subject name and teacher(s) */
		$alt_count = 0;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			$namelink = "index.php?location=" .  dbfuncString2Int("student/subjectinfo.php") . 
						"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
						"&amp;key2=" .           dbfuncString2Int($username) .
						"&amp;keyname=" .        dbfuncString2Int($row['Name']) .
						"&amp;key2name=" .       dbfuncString2Int($fullname);      // Get link to class
			echo "         <tr$alt>\n";
			echo "            <td><a href='$namelink'>{$row['Name']}</a></td>\n";
			echo "            <td>";
			
			/* Get information about teacher(s) */
			$teacherRes =& $db->query("SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
									  "WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
									  /*"AND   subjectteacher.Show         = '1' " .*/
									  "AND   user.Username               = subjectteacher.Username");
			if(DB::isError($teacherRes)) die($teacherRes->getDebugInfo());          // Check for errors in query
			if($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
				
				/* If there's more than one teacher, separate with commas */
				while ($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo ", {$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
				}
			}
			echo "            </td>\n";    // Table footers
			$average_type       = $row['AverageType'];
			$average_type_index = $row['AverageTypeIndex'];
			
			if($average_type != $AVG_TYPE_NONE) {
				if($row['ShowAverage'] == "1") {
					if($row['Average'] == "-1") {
						echo "            <td><i>N/A</i></td>\n";
					} else {
						if($average_type == $AVG_TYPE_PERCENT) {
							$average = round($row['Average']);
							echo "            <td>$average%</td>\n";
						} elseif($average_type == $AVG_TYPE_INDEX or $average_type == $AVG_TYPE_GRADE) {
							$query =	"SELECT Input, Display FROM nonmark_index " .
										"WHERE NonmarkTypeIndex = $average_type_index " .
										"AND   NonmarkIndex     = {$row['Average']}";
							$sres =& $db->query($query);
							if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query
							if($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
								$average = $srow['Display'];
							} else {
								$average = "?";
							}
							echo "            <td>$average</td>\n";
						}
					}
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
			} else {
				echo "            <td><i>N/A</i></td>\n";
			}

			echo "         </tr>\n";
		}
		echo "      </table>\n";           // End of table
		/* Calculate conduct mark */
		$disclink =	"index.php?location=" .  dbfuncString2Int("student/discipline.php") .
					"&amp;key=" .            dbfuncString2Int($username) .
					"&amp;keyname=" .        dbfuncString2Int($fullname);
		$query =    "SELECT classlist.Conduct FROM classlist, classterm, class " .
					"WHERE  classlist.Username = '$username' " .
					"AND    classlist.ClassTermIndex = classterm.ClassTermindex " .
					"AND    classterm.TermIndex = $termindex " .
					"AND    classterm.ClassIndex = class.ClassIndex " .
					"AND    class.YearIndex = $yearindex ";
		$conductRes =&   $db->query($query);
		if(DB::isError($conductRes)) die($conductRes->getDebugInfo());          // Check for errors in query
		if($conductRow =& $conductRes->fetchrow(DB_FETCHMODE_ASSOC) and $conductRow['Conduct'] != "") {
			if($conductRow['Conduct'] < 0) $conductRow['Conduct'] = 0;
			echo "      <p class='subtitle' align='center'><a href='$disclink'>Conduct: {$conductRow['Conduct']}%</a></p>\n";
		}
		echo "      <p></p>\n";
	}
	
	/* Get subject information for current teacher */
	$query =	"SELECT Name, SubjectIndex, Average, MAX(StudentCount) AS StudentCount, MIN(ReportDone) AS ReportDone, ClassIndex, CanDoReport, MAX(SubjectTeacher) AS SubjectTeacher FROM " .
				"       ((SELECT subject.Name, subject.SubjectIndex, subject.Average, COUNT(subjectstudent.Username) AS StudentCount, MIN(subjectstudent.ReportDone) AS ReportDone, subject.ClassIndex, subject.CanDoReport, 1 AS SubjectTeacher " .
				"         FROM subject " .
				"         LEFT OUTER JOIN subjectstudent USING (SubjectIndex), subjectteacher " .
				"         WHERE subjectteacher.SubjectIndex = subject.SubjectIndex " .
				"         AND subject.YearIndex = $yearindex " .
				"         AND subject.TermIndex = $termindex " .
				"         AND subjectteacher.Username = '$username' " .
				"         AND subject.ShowInList = 1 " .
				"         GROUP BY subject.SubjectIndex) " .
				"        UNION " .
				"        (SELECT subject.Name, subject.SubjectIndex, subject.Average, COUNT(subjectstudent.Username) AS StudentCount, MIN(subjectstudent.ReportDone) AS ReportDone, subject.ClassIndex, subject.CanDoReport, 0 AS SubjectTeacher " .
				"         FROM subject " .
				"         INNER JOIN subjectstudent USING (SubjectIndex) " .
				"         INNER JOIN classlist USING (Username) " .
				"         INNER JOIN classterm ON (classterm.ClassTermIndex=classlist.ClassTermIndex AND classterm.TermIndex=subject.TermIndex) " .
				"         INNER JOIN class ON (class.ClassIndex=classterm.ClassIndex AND class.YearIndex=subject.YearIndex) " .
				"         INNER JOIN support_class ON (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
				"         WHERE support_class.Username = '$username' " .
				"         AND subject.YearIndex = $yearindex " .
				"         AND subject.TermIndex = $termindex " .
				"         AND subject.ShowInList = 1 " .
				"         GROUP BY subject.SubjectIndex)) AS subject_list " .
				"GROUP BY SubjectIndex " .
				"ORDER BY Name, SubjectIndex ";
	$nrs =&  $db->query($query);
	if(DB::isError($nrs)) die($nrs->getDebugInfo());          // Check for errors in query

	/* If user teaches at least one subject, print out teacher table */
	if($nrs->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Subject</th>\n";
		echo "            <th>Students</th>\n";
		echo "            <th>Average</th>\n";
		echo "         </tr>\n";
		
		/* For each class, print a row with the subject name and number of students */
		$alt_count = 0;
		while ($row =& $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			echo "         <tr$alt>\n";
			$row['Name'] = htmlspecialchars($row['Name']);
			
			echo "            <td>";
			if($row['CanDoReport'] == 1) {
				$reportlink =	"index.php?location=" .  dbfuncString2Int("teacher/report/modify.php") .
								"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
								"&amp;keyname=" .        dbfuncString2Int($row['Name']); // Get link to report
				if($row['ReportDone'] == 0) {
					$reportbutton = dbfuncGetButton($reportlink, "R", "small", "report", "Edit report information");
				} else {
					$reportbutton = dbfuncGetButton($reportlink, "V", "small", "report", "View report information");
				}
				echo "$reportbutton&nbsp;";
			}
			if($row['ClassIndex'] != NULL) {
				$query =	"SELECT ClassName FROM class WHERE ClassIndex={$row['ClassIndex']}";
				$trs =&  $db->query($query);
				if(DB::isError($trs)) die($trs->getDebugInfo());          // Check for errors in query
			
				if ($trow =& $trs->fetchRow(DB_FETCHMODE_ASSOC)) {
					$ttlink =	"index.php?location=" .  dbfuncString2Int("user/timetable.php") .
								"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
								"&amp;keyname=" .        dbfuncString2Int($trow['ClassName']) .
								"&amp;key2=" .           dbfuncString2Int("c"); // Get link to report
					$ttbutton = dbfuncGetButton($ttlink, "T", "small", "edit", "Class timetable");
					echo "$ttbutton&nbsp;";
				}
			}
			if($row['StudentCount'] != NULL and $row['StudentCount'] > 0) {
				$namelink = "index.php?location=" .  dbfuncString2Int("teacher/assignment/list.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']); // Get link to subject
				
				echo "<a href='$namelink'>{$row['Name']}</a></td>\n";
			} else {
				echo "{$row['Name']}</td>\n";
			}
			echo "            <td>{$row['StudentCount']}</td>\n";  // Print student count
			if($row['Average'] == "-1") {
				echo "            <td><i>N/A</i></td>\n";
			} else {
				$average = round($row['Average']);
				echo "            <td>$average%</td>\n";
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
	}

	/* Get subject information for support teacher */
	$query =	"SELECT class.ClassName, class.ClassIndex, COUNT(support.StudentUsername) AS StudentCount " .
				"       FROM user, support, class, classterm, classlist " .
				"WHERE support.WorkerUsername   = '$username' " .
				"AND   user.Username            = support.WorkerUsername " .
				"AND   user.ActiveTeacher       = 1 " .
				"AND   user.SupportTeacher      = 1 " .
				"AND   support.StudentUsername  = classlist.Username " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"AND   classterm.TermIndex      = $termindex " .
				"AND   classterm.ClassIndex     = class.ClassIndex " .
				"AND   class.YearIndex          = $yearindex " .
				"GROUP BY class.ClassName " .
				"ORDER BY class.Grade, class.ClassName, class.ClassIndex";
	$nrs =&  $db->query($query);

	if(DB::isError($nrs)) die($nrs->getDebugInfo());          // Check for errors in query
	/* If user is a support teacher for at least one student, show student information */
	if($nrs->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Class</th>\n";
		echo "            <th>Students</th>\n";
		echo "         </tr>\n";
		
		/* For each class, print a row with the subject name and number of students */
		$alt_count = 0;
		while ($row =& $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			$row['Name'] = htmlspecialchars($row['Name']);
			
			$namelink = "index.php?location=" .  dbfuncString2Int("teacher/support/list.php") .
						"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($row['ClassName']); // Get link to subject
			echo "         <tr$alt>\n";
			echo "            <td><a href='$namelink'>{$row['ClassName']}</a></td>\n";
			echo "            <td>{$row['StudentCount']}</td>\n";  // Print student count
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
	}
	
	/* Closing tags */
	include "footer.php";
?>
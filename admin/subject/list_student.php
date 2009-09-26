<?php
	/*****************************************************************
	 * admin/subject/list_student.php  (c) 2005-2007 Jonathan Dieter
	 *
	 * List all subjects that the student is currently in.
	 *****************************************************************/
	
	$studentusername = dbfuncInt2String($_GET["key"]);
	$studentname     = dbfuncInt2String($_GET["keyname"]);
	
	$title = "Subject List for $studentname";
	
	include "header.php";                                        // Show header

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {         // Make sure user has permission to view student's
		$showalldeps = true;                                     //  subject list
		$showdeps    = false;
		include "core/settermandyear.php";
		include "core/titletermyear.php";
		
		/* Get classes */
		$query =	"SELECT subject.Name, subject.SubjectIndex, subject.Period, " .
					"       subject.ShowAverage, subjectstudent.Average FROM subject, subjectstudent " .
					"WHERE subjectstudent.SubjectIndex = subject.SubjectIndex " .
					"AND   subject.AverageType         > 0 " .
					"AND   subject.YearIndex           = $yearindex " .
					"AND   subject.TermIndex           = $termindex " .
					"AND   subjectstudent.Username     = \"$studentusername\" " .
					"ORDER BY subject.Period, subject.Name";
		$res =& $db->query($query);
		
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		
		/* If user is a student in at least one class, print out class table */
		if($res->numRows() > 0) {
			include "student/lateinfo.php";
			
			/* First give option to show all assignments */
			$alllink =	"index.php?location=" .  dbfuncString2Int("student/allinfo.php") .
						"&amp;key=" .            dbfuncString2Int($studentusername) .
						"&amp;keyname=" .        dbfuncString2Int($studentname) .      // Link to all classes
						"&amp;show=" .           dbfuncString2Int("a");
			$hwlink = 	"index.php?location=" .  dbfuncString2Int("student/allinfo.php") .
						"&amp;key=" .            dbfuncString2Int($studentusername) .
						"&amp;keyname=" .        dbfuncString2Int($studentname) .      // Link to all classes
						"&amp;show=" .           dbfuncString2Int("u");
			
			$allbutton = dbfuncGetButton($alllink, "View all assignments", "medium", "", "");
			$hwbutton  = dbfuncGetButton($hwlink,  "View homework", "medium", "", "");
			echo "      <p align=\"center\">$hwbutton $allbutton</p>";
			
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
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
					$alt = " class=\"alt\"";
				} else {
					$alt = " class=\"std\"";
				}
				$namelink = "index.php?location=" .  dbfuncString2Int("student/subjectinfo.php") . 
							"&amp;key=" .            dbfuncString2Int($row['SubjectIndex']) .
							"&amp;key2=" .           $_GET['key'] .
							"&amp;keyname=" .        dbfuncString2Int($row['Name']) . 
							"&amp;key2name=" .       $_GET['keyname'];      // Get link to class
				echo "         <tr$alt>\n";
				echo "            <td><a href=\"$namelink\">{$row['Name']}</a></td>\n";
				echo "            <td>";
				
				/* Get information about teacher(s) */
				$teacherRes =& $db->query("SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
										"WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
										/*"AND   subjectteacher.Show         = '1' " .*/
										"AND   user.Username               = subjectteacher.Username");
				if(DB::isError($teacherRes)) die($teacherRes->getDebugInfo()); // Check for errors in query
				if($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
					
					/* If there's more than one teacher, separate with commas */
					while ($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo ", {$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
					}
				}
				echo "            </td>\n";    // Table footers
				if($row['ShowAverage'] == "1") {
					if($row['Average'] == "-1") {
						echo "            <td><i>N/A</i></td>\n";
					} else {
						$average = round($row['Average']);
						echo "            <td>$average%</td>\n";
					}
				} else {
					echo "            <td><i>Hidden</i></td>\n";
				}
				echo "         </tr>\n";
			}
			echo "      </table>\n";           // End of table
			echo "      <p></p>\n";
		}
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
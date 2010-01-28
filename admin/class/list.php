<?php
	/*****************************************************************
	 * admin/class/list.php  (c) 2004-2006 Jonathan Dieter
	 *
	 * List all classes for the current year and the number of
	 * students in each class
	 *****************************************************************/

	$title = "Class List";
	
	include "header.php";                                        // Show header

	/* Check whether current user is a counselor */
	$res =&  $db->query("SELECT Username FROM counselorlist " .
						"WHERE Username='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	if($is_admin or $is_counselor) {
		$showalldeps = true;
	} else {
		$admin_page  = true;
	}
	include "core/settermandyear.php";

	/* Check whether current user is a hod */
	$res =&  $db->query("SELECT Username FROM hod " .
						"WHERE Username='$username' " .
						"AND   DepartmentIndex=$depindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	/* Check whether current user is principal */
	$res =&  $db->query("SELECT Username FROM principal " .
						"WHERE Username=\"$username\" AND Level=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_principal = true;
	} else {
		$is_principal = false;
	}

	if($is_admin or $is_principal or $is_counselor or $is_hod) {
		include "core/titletermyear.php";
		
		/* Get class list */
		$res =&  $db->query("SELECT class.ClassName, class.Grade, class.ClassIndex, " .
							"       user.Username, user.Title, user.FirstName, user.Surname, " .
							"       classterm.ClassTermIndex, classterm.CanDoReport, " .
							"       MIN(classlist.ReportDone) AS ReportDone, " .
							"       COUNT(classlist.Username) AS StudentCount " .
							"       FROM class INNER JOIN classterm USING (ClassIndex) " .
							"       LEFT OUTER JOIN user ON " .
							"       user.Username = class.ClassTeacherUsername " .
							"       LEFT OUTER JOIN classlist USING (ClassTermIndex) " .
							"WHERE  class.YearIndex = $yearindex " .
							"AND    classterm.TermIndex = $termindex " .
							"AND    class.DepartmentIndex = $depindex " .
							"GROUP BY classterm.ClassTermIndex " .
							"ORDER BY class.Grade, class.ClassName, class.ClassIndex");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
		/* Print classes and the # of students in each class */
		if($res->numRows() > 0) {
			/*
			if($is_admin) {
				$newlink   = "index.php?location=" .  dbfuncString2Int("admin/class/new.php");
				$newbutton = dbfuncGetButton($newlink, "New class", "medium", "", "Create new class");
				echo "      <p align='center'>$newbutton</p>\n";
			}
			*/
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Class</th>\n";
			if($is_admin) echo "            <th>Grade</th>\n";
			echo "            <th>Class Teacher</th>\n";
			echo "            <th>Students</th>\n";
			if($is_admin) echo "            <th>Delete</th>\n";
			echo "         </tr>\n";
			
			/* For each subject, print a row with the subject's name, and # of students */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				$viewlink = "index.php?location=" .  dbfuncString2Int("admin/class/list_students.php") .
							"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['ClassName']);
				$rptbutton = "";
				if($is_admin or $is_hod or $is_principal) {
					/* Check whether subject is open for report editing */
					if($row['CanDoReport'] or $row['ReportDone']) {
						$rptlink =  "index.php?location=" .  dbfuncString2Int("teacher/report/class_list.php") .
									"&amp;key=" .            dbfuncString2Int($row['ClassTermIndex']) .
									"&amp;keyname=" .        dbfuncString2Int($row['ClassName']);
						if($row['ReportDone']) {
							$rptbutton = dbfuncGetButton($rptlink,  "V", "small", "report", "View reports for class");
						} else {
							$rptbutton = dbfuncGetButton($rptlink,  "R", "small", "report", "Edit reports for class");
						}
					}
				}
				if($is_admin) {
					$editlink = "index.php?location=" .  dbfuncString2Int("admin/class/modify.php") .
								"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
								"&amp;keyname=" .        dbfuncString2Int($row['ClassName']);
					$editbutton = dbfuncGetButton($editlink, "E", "small", "edit",   "Edit class");
				} else {
					$editbutton = "";
				}

				echo "         <tr$alt>\n";
				/* Generate view and edit buttons */
				$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View class");
				$ttlink =	"index.php?location=" .  dbfuncString2Int("user/timetable.php") .
							"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['ClassName']) .
							"&amp;key2=" .           dbfuncString2Int("c"); // Get link to report
				$ttbutton = dbfuncGetButton($ttlink, "T", "small", "edit", "Class timetable");
				echo "            <td>$viewbutton$editbutton$rptbutton$ttbutton</td>\n"; 
				echo "            <td>{$row['ClassName']}</td>\n"; // Print class name
				if($is_admin) echo "            <td>{$row['Grade']}</td>\n";
				if($row['Surname'] != "") {
					echo "            <td>{$row['Title']} {$row['FirstName']} {$row['Surname']}</td>\n";
				} else {
					echo "            <td><i>None</i></td>\n";
				}
			
				/* Get list of students in class */
				echo "            <td>{$row['StudentCount']}</td>\n";                                                // Print number of students
				
				if($is_admin) {
					$dellink =  "index.php?location=" .  dbfuncString2Int("admin/class/delete_confirm.php") .
								"&amp;key=" .            dbfuncString2Int($row['ClassIndex']) .
								"&amp;keyname=" .        dbfuncString2Int($row['ClassName']);
					$delbutton = dbfuncGetButton($dellink, "X", "small", "delete", "Delete class");
					echo "            <td align='center'>$delbutton</td>\n"; // Print delete link
				}
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no classes this year.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/class/list.php", $LOG_ADMIN,
				"Viewed list of classes.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/class/list.php", $LOG_DENIED_ACCESS,
				"Attempted to view list of classes.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
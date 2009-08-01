<?php
	/*****************************************************************
	 * admin/studentlist.php  (c) 2004 Jonathan Dieter
	 *
	 * List all active students and which class they are in this year
	 *****************************************************************/

	$title = "Student List";
	
	include "header.php";                                          // Show header

	if($res->numRows() > 0) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$is_admin = true;
	} else {
		$is_admin = false;
	}

	if($is_admin or $is_counselor) {           // Make sure user has permission to view and
		$showalldeps = true;
		include "core/settermandyear.php";                         //  edit students
		include "core/titletermyear.php";
		
		if($_GET['sort'] == '1') {
			$sortorder = "user.Username DESC";
		} elseif($_GET['sort'] == '2') {
			$sortorder = "query.Grade, query.ClassName, query.ClassOrder, user.Username";
		} elseif($_GET['sort'] == '3') {
			$sortorder = "query.Grade DESC, query.ClassName DESC, query.ClassOrder DESC, user.Username DESC";
		} elseif($_GET['sort'] == '4') {
			$sortorder = "user.FirstName, user.Surname, user.Username";
		} elseif($_GET['sort'] == '5') {
			$sortorder = "user.FirstName DESC, user.Surname DESC, user.Username DESC";
		} elseif($_GET['sort'] == '6') {
			$sortorder = "user.Surname, user.FirstName, user.Username";
		} elseif($_GET['sort'] == '7') {
			$sortorder = "user.Surname DESC, user.FirstName DESC, user.Username DESC";
		} elseif($_GET['sort'] == '8') {
			$sortorder = "user.House, user.Username";
		} elseif($_GET['sort'] == '9') {
			$sortorder = "user.House DESC, user.Username DESC";
		} else {
			$sortorder = "user.Username";
		}
		
		$unameAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") . 
		                            "&amp;sort=0", "A", "small", "sort", "Sort ascending");
		$unameDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=1", "D", "small", "sort", "Sort descending");
		$fnameAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=4", "A", "small", "sort", "Sort ascending");
		$fnameDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=5", "D", "small", "sort", "Sort descending");
		$lnameAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=6", "A", "small", "sort", "Sort ascending");
		$lnameDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=7", "D", "small", "sort", "Sort descending");
		$classAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=2", "A", "small", "sort", "Sort ascending");
		$classDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=3", "D", "small", "sort", "Sort descending");
		$houseAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=8", "A", "small", "sort", "Sort ascending");
		$houseDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/studentlist.php") .
		                            "&amp;sort=9", "D", "small", "sort", "Sort descending");
		
		/* Get student list */
		if($yearindex == $currentyear) {
			$query =	"SELECT user.FirstName, user.Surname, user.Username, user.User1, user.User2, " .
						"       user.House, query.ClassName, query.Grade, COUNT(subjectstudent.SubjectIndex) AS SubjectCount FROM user LEFT OUTER JOIN " .
						"       (SELECT class.ClassName, class.Grade, classlist.ClassOrder, " .
						"        classlist.Username FROM classlist, class WHERE " .
						"        classlist.ClassIndex = class.ClassIndex AND class.YearIndex = $yearindex) " .
						"       AS query ON user.Username = query.Username LEFT OUTER JOIN (subjectstudent INNER JOIN subject USING (SubjectIndex)) ON (subjectstudent.Username = user.Username AND subject.YearIndex = $yearindex AND subject.TermIndex = $termindex) " .
						"WHERE user.ActiveStudent = '1' " .
						"GROUP BY user.Username " .
						"ORDER BY $sortorder";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		} else {
			$query =	"SELECT user.FirstName, user.Surname, user.Username, user.User1, user.User2, " .
						"       user.House, query.ClassName, query.Grade, COUNT(subjectstudent.SubjectIndex) AS SubjectCount FROM user INNER JOIN " .
						"       (SELECT class.ClassName, class.Grade, classlist.ClassOrder, " .
						"        classlist.Username FROM classlist, class WHERE " .
						"        classlist.ClassIndex = class.ClassIndex AND class.YearIndex = $yearindex) " .
						"       AS query USING (Username) LEFT OUTER JOIN (subjectstudent INNER JOIN subject USING (SubjectIndex)) ON (subjectstudent.Username = user.Username AND subject.YearIndex = $yearindex AND subject.TermIndex = $termindex) " .
						"GROUP BY user.Username " .
						"ORDER BY $sortorder";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		}
		
		/* Print students and their class */
		if($res->numRows() > 0) {
			echo "      <table align=\"center\" border=\"1\">\n";  // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>First Name $fnameAsc $fnameDec</th>\n";
			echo "            <th>Last Name $lnameAsc $fnameDec</th>\n";
			echo "            <th>Username $unameAsc $unameDec</th>\n";
			echo "            <th>Class $classAsc $classDec</th>\n";
			echo "            <th>House $houseAsc $houseDec</th>\n";
			echo "            <th>New</th>\n";
			echo "            <th>Special</th>\n";
			echo "            <th>Subjects</th>\n";
			echo "         </tr>\n";
			
			/* For each student, print a row with the student's name and what class they're in */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class=\"alt\"";
				} else {
					$alt = " class=\"std\"";
				}
				echo "         <tr$alt>\n";
				
				$viewlink = "index.php?location=" . dbfuncString2Int("admin/subject/list_student.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
				$editlink = "index.php?location=" . dbfuncString2Int("admin/user/modify.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
				$cnlink =   "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;keyname2=" .      dbfuncSTring2Int($row['FirstName']);
				$mlink =    "index.php?location=" . dbfuncString2Int("user/new_message.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;key2=" .          dbfuncString2Int($MSG_TYPE_USERNAME) .
							"&amp;next=" .          dbfuncString2Int($here);
				$sublink =  "index.php?location=" . dbfuncString2Int("admin/subject/modify_by_student.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;next=" .          dbfuncString2Int($here);
				$hlink =    "index.php?location=" . dbfuncString2Int("student/discipline.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;next=" .          dbfuncString2Int($here);
				$alink =    "index.php?location=" . dbfuncString2Int("student/absence.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;next=" .          dbfuncString2Int($here);
				$ttlink =	"index.php?location=" .  dbfuncString2Int("user/timetable.php") .
							"&amp;key=" .            dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .        dbfuncString2Int($row['FirstName'] . " " . $row['Surname']);
				
				/* Generate view and edit buttons */
				if($is_admin) {
					$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View student's subjects");
					$ttbutton =   dbfuncGetButton($ttlink,   "T", "small", "tt",   "View student's timetable");
					$subbutton  = dbfuncGetButton($sublink,  "S", "small", "home", "Edit student's subjects");
					$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Edit student");
					$mbutton    = dbfuncGetButton($mlink,    "M", "small", "msg",  "Send message");
					$hbutton    = dbfuncGetButton($hlink,    "H", "small", "view", "Student's conduct history");
					$abutton    = dbfuncGetButton($alink,    "A", "small", "view", "Student's absence history");
				} else {
					$viewbutton = "";
					$ttbutton   = "";
					$subbutton  = "";
					$editbutton = "";
					$mbutton    = "";
					$hbutton    = "";
					$abutton    = "";
				}
				
				$cnbutton   = dbfuncGetButton($cnlink,   "C", "small", "cn",   "Casenotes for student");
				echo "            <td>$cnbutton$viewbutton$ttbutton$abutton$subbutton$mbutton$hbutton$editbutton</td>\n";
				echo "            <td>{$row['FirstName']}</td>\n";
				echo "            <td>{$row['Surname']}</td>\n";
				echo "            <td>{$row['Username']}</td>\n";
				if($row['ClassName'] != NULL) {
					echo "            <td>{$row['ClassName']}</td>\n";
				} else {
					echo "            <td><i>None</i></td>\n";
				}
				if($row['House'] != NULL) {
					echo "            <td>{$row['House']}</td>\n";
				} else {
					echo "            <td><i>None</i></td>\n";
				}
				if($row['User1'] == 1) {
					echo "            <td>X</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
				if($row['User2'] == 1) {
					echo "            <td>X</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
				echo "            <td>{$row['SubjectCount']}</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no active students</p>\n";
		}
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
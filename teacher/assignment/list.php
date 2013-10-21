<?php
	/*****************************************************************
	 * teacher/assignment/list.php  (c) 2004-2013 Jonathan Dieter
	 *
	 * Show assignments and marks for a subject
	 *****************************************************************/

	/* Get variables */
	$title        = dbfuncInt2String($_GET['keyname']);
	$subjectindex = safe(dbfuncInt2String($_GET['key']));
	
	include "header.php";                                    // Show header
	include "core/settermandyear.php";
	
	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher " .
					   "WHERE subjectteacher.SubjectIndex = $subjectindex " .
					   "AND   subjectteacher.Username     = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	if($res->numRows() > 0)
		$is_teacher = true;
	
	$query =	"SELECT support_class.Username " .
				"         FROM subject " .
				"         INNER JOIN subjectstudent USING (SubjectIndex) " .
				"         INNER JOIN classlist USING (Username) " .
				"         INNER JOIN classterm ON (classterm.ClassTermIndex=classlist.ClassTermIndex AND classterm.TermIndex=subject.TermIndex) " .
				"         INNER JOIN class ON (class.ClassIndex=classterm.ClassIndex AND class.YearIndex=subject.YearIndex) " .
				"         INNER JOIN support_class ON (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
				"         WHERE support_class.Username = '$username' " .
				"         AND subject.SubjectIndex = $subjectindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	if($res->numRows() > 0)
		$is_support_class_teacher = true;
				
	if($is_teacher or $is_support_class_teacher or $is_admin) {
		$query =    "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$perm = $row['Permissions'];
		} else {
			$perm = $DEFAULT_PUN_PERM;
		}
		
		/* Get whether marks can be modified */
		$res =& $db->query("SELECT AverageType, AverageTypeIndex, CanModify FROM subject " .
						   "WHERE subject.SubjectIndex = $subjectindex");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
		$row       =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		if($is_admin) {
			$can_modify = 1;
		} else {
			$can_modify = $row['CanModify'];
		}

		if(!$is_teacher and !$is_admin)
			$can_modify = 0;
			
		$average_type       = $row['AverageType'];
		$average_type_index = $row['AverageTypeIndex'];

		$nochangeyt = true;
		
		include "core/titletermyear.php";
		$newlink =  	"index.php?location=" . dbfuncString2Int("teacher/assignment/new.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
		$agendalink =	"index.php?location=" . dbfuncString2Int("teacher/assignment/list_agenda.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
		$optlink =  	"index.php?location=" . dbfuncString2Int("teacher/subject/modify.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
		if($average_type == $AVG_TYPE_PERCENT) {
			$prtlink =  "index.php?location=" . dbfuncString2Int("teacher/assignment/print.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
		}
		$cltlink =  	"index.php?location=" . dbfuncString2Int("teacher/assignment/print_gradesheet.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
						
		$agendabutton = dbfuncGetButton($agendalink, "Agenda items", "medium", "", "List agenda items for this subject");
		if(($can_modify == 1) and $average_type != $AVG_TYPE_NONE){
			$newbutton    = dbfuncGetButton($newlink, "New assignment", "medium", "", "Create new assignment for this subject");
			$optbutton    = dbfuncGetButton($optlink, "Subject options", "medium", "", "Edit options for this subject");
		} else {
			$newbutton = "";
			$optbutton = "";
		}

		if($average_type == $AVG_TYPE_PERCENT) {
			$prtbutton = dbfuncGetButton($prtlink, "Printable marks", "medium", "", "View printable marks for this subject");
		} else {
			$prtbutton = "";
		}
		$cltbutton = dbfuncGetButton($cltlink, "Printable gradesheet", "medium", "", "View printable gradesheet for this subject");

		echo "      <p align='center'>$agendabutton $newbutton $optbutton $prtbutton $cltbutton</p>\n";
	
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Student</th>\n";
		
		if($average_type != 0) {
			$rowcount = 0;
			
			/* Get assignment list */
			$query =	"SELECT assignment.Title, assignment.Date, assignment.Hidden, " .
						"       assignment.AssignmentIndex, category.CategoryName " .
						"       FROM assignment " .
						"       LEFT OUTER JOIN categorylist USING (CategoryListIndex) " .
						"       LEFT OUTER JOIN category USING (CategoryIndex) " .
						"WHERE assignment.SubjectIndex = $subjectindex " .
						"AND   assignment.Agenda = 0 " .
						"ORDER BY Date, AssignmentIndex";
			$res =& $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());

			// Run through list of all assignments and print each assignment and date
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$rowcount += 1;
				$dateinfo = date($dateformat, strtotime($row['Date']));
				$row['Title'] = htmlspecialchars($row['Title']);
				$hidden   = $row['Hidden'];

				$link     = "index.php?location=" . dbfuncString2Int("teacher/assignment/modify.php") .
							"&amp;key=" .               dbfuncString2Int($row['AssignmentIndex']) .
							"&amp;keyname=" .           dbfuncString2Int($row['Title']);
				$headtype = "";
				if($hidden == 1) $headtype=" class='hidden'";
				if(is_null($row['CategoryName'])) {
					$catinfo = "";
				} else {
					$catinfo = "<br><span class='small'>{$row['CategoryName']}</span>";
				}
				if($can_modify == 1) {
					echo "            <th$headtype width=10px><a$headtype href='$link'>{$row['Title']}<br> ({$dateinfo}){$catinfo}</a></th>\n";
				} else {
					echo "            <th$headtype width=10px>{$row['Title']}<br>($dateinfo){$catinfo}</th>\n";
				}
			}
			if($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
				echo "            <th width=10px>Total</th>\n"; // Show total percentage if desired
			}
		}
		echo "         </tr>\n";

		/* For each student, print a row with the student's name and score on each assignment*/
		if($is_support_class_teacher and !$is_teacher and !$is_admin) {
			$query =	"SELECT user.FirstName, user.Surname, user.Username, classlist.ClassOrder, " .
						"       subjectstudent.Average FROM user, " .
						"       subject " .
						"       INNER JOIN subjectstudent USING (SubjectIndex)" .
						"       INNER JOIN classlist USING (Username) " .
						"       INNER JOIN classterm ON (classterm.ClassTermIndex=classlist.ClassTermIndex AND classterm.TermIndex=subject.TermIndex) " .
						"       INNER JOIN class ON (class.ClassIndex=classterm.ClassIndex AND class.YearIndex=subject.YearIndex) " .
						"       INNER JOIN support_class ON (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
						"WHERE support_class.Username = '$username' " .
						"AND user.Username=subjectstudent.Username " .
						"AND subject.SubjectIndex=$subjectindex " .
						"ORDER BY user.FirstName, user.Surname, user.Username";
		} else {
			$query =	"SELECT user.FirstName, user.Surname, user.Username, query.ClassOrder, " .
						"       subjectstudent.Average FROM user, " .
						"       subjectstudent LEFT OUTER JOIN " .
						"       (SELECT classlist.ClassOrder, classlist.Username FROM class, " .
						"               classterm, classlist, subject " .
						"        WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
						"        AND   classterm.TermIndex = subject.TermIndex " .
						"        AND   class.ClassIndex = classterm.ClassIndex " .
						"        AND   class.YearIndex = subject.YearIndex " .
						"        AND subject.SubjectIndex=$subjectindex) AS query " .
						"       ON subjectstudent.Username = query.Username " .
						"WHERE user.Username=subjectstudent.Username " .
						"AND subjectstudent.SubjectIndex=$subjectindex " .
						"ORDER BY user.FirstName, user.Surname, user.Username";			
		}
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			
		$alt_count     = 0;
		$order         = 1;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}

			$alt = " class='$alt_step'";
			echo "         <tr$alt>\n";

			if($currentyear == $yearindex) {
				$cnlink =   "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;keyname2=" .      dbfuncSTring2Int($row['FirstName']);
				$cnbutton = dbfuncGetButton($cnlink,   "C", "small", "cn",   "Casenotes");
			} else {
				$cnbutton = "";
			}
			if($currentyear == $yearindex and $currentterm == $termindex and ($perm >= $PUN_PERM_REQUEST or dbfuncGetPermission($permissions, $PERM_ADMIN))) {
				if($perm == $PUN_PERM_REQUEST) {
					$punlink =  "index.php?location=" . dbfuncString2Int("teacher/punishment/request/new.php") .
								"&amp;key=" .           dbfuncString2Int($row['Username']) .
								"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
								"&amp;next=" .          dbfuncString2Int("index.php?location=" .
															dbfuncString2Int("teacher/assignment/list.php") .
															"&amp;key=" . $_GET['key'] .
															"&amp;keyname=" . $_GET['keyname']);
					$punbutton = dbfuncGetButton($punlink,   "P", "small", "delete",   "Request Punishment");
				} else {
					$punlink =  "index.php?location=" . dbfuncString2Int("admin/punishment/new.php") .
								"&amp;key=" .           dbfuncString2Int($row['Username']) .
								"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
								"&amp;next=" .          dbfuncString2Int("index.php?location=" .
															dbfuncString2Int("teacher/assignment/list.php") .
															"&amp;key=" . $_GET['key'] .
															"&amp;keyname=" . $_GET['keyname']);
					$punbutton = dbfuncGetButton($punlink,   "P", "small", "delete",   "Issue Punishment");
				}
			} else {
				$punbutton = "";
			}

			echo "            <td nowrap>$punbutton$cnbutton $order</td>\n";
			$order += 1;

			if($average_type != 0) {
				$link     = "index.php?location=" . dbfuncString2Int("student/subjectinfo.php") .
							"&amp;key2=" .          dbfuncString2Int($row['Username']) .
							"&amp;key2name=" .      dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;key=" .           $_GET['key'] .
							"&amp;keyname=" .       $_GET['keyname'];
				echo "            <td nowrap><a href='$link'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
			
				$query =	"SELECT mark.Percentage, mark.Score, assignment.Weight, " .
							"       assignment.Hidden " .
							"       FROM assignment LEFT OUTER JOIN mark ON " .
							"       (mark.AssignmentIndex=assignment.AssignmentIndex AND " .
							"        mark.Username = '{$row['Username']}') " .
							"WHERE assignment.SubjectIndex = $subjectindex " .
							"AND   assignment.Agenda = 0 " .
							"ORDER BY assignment.Date, assignment.AssignmentIndex";
				$mres =& $db->query($query);
				if(DB::isError($mres)) die($mres->getDebugInfo());           // Check for errors in query

				$rowcount = 0;
				while ($mRow =& $mres->fetchRow(DB_FETCHMODE_ASSOC)) {
					$rowcount += 1;
					$hidden = $mRow['Hidden'];
					
					$alt = "";
					if($hidden == 1) {
						$alt = " class='hidden-$alt_step'";
					}
					
					if($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
						if($mRow['Score'] == $MARK_LATE) {
							if($can_modify == 1) {
								if($hidden == 0) {
									$alt    = " class='late-$alt_step'";
								}
								echo "            <td$alt nowrap><i>Late</i></td>\n";
							} else {
								echo "            <td$alt nowrap>0%</td>\n";
							}
						} elseif($mRow['Score'] == $MARK_ABSENT) {
							echo "            <td$alt nowrap><i>Absent</i></td>\n";
						} elseif($mRow['Score'] == $MARK_EXEMPT) {
							echo "            <td$alt nowrap><i>Exempt</i></td>\n";
						} elseif(is_null($mRow['Score'])) {
							if($can_modify == 1) {
								if($hidden == 0) {
									$alt    = " class='unmarked-$alt_step'";
								}
								echo "            <td$alt nowrap>&nbsp;</td>\n";
							} else {
								echo "            <td$alt nowrap><i>Exempt</i></td>\n";
							}
						} else {
							$average = round($mRow['Percentage']);
							echo "            <td$alt nowrap>$average%</td>\n";
						}
					} elseif($average_type == $AVG_TYPE_INDEX) {
						if(!isset($average_type_index) or $average_type_index == "" or !isset($mRow['Score']) or $mRow['Score'] == "") {
							if($can_modify == 1 and $hidden == 0) {
								$alt    = " class='unmarked-$alt_step'";
							}
							$average = "";
						} else {
							$query =	"SELECT Input, Display FROM nonmark_index " .
										"WHERE NonmarkTypeIndex = $average_type_index " .
										"AND   NonmarkIndex     = {$mRow['Score']}";
							$sres =& $db->query($query);
							if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query
							if($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
								$average = $srow['Display'];
							} else {
								if($can_modify == 1 and $hidden == 0) {
									$alt    = " class='unmarked-$alt_step'";
								}
								$average = "N/A";
							}
						}
						echo "            <td$alt nowrap>$average</td>\n";
					}
				}
				if($average_type == $AVG_TYPE_PERCENT) {  // Show average percentage for all students
					if($row['Average'] == -1) {
						echo "            <td nowrap><b>N/A</b></td>\n";
					} else {
						$average = round($row['Average']);
						echo "            <td nowrap><b>$average%</b></td>\n";
					}
				} elseif($average_type == $AVG_TYPE_GRADE) {  // Show average percentage for all students
					if($row['Average'] == -1) {
						echo "            <td nowrap><b>N/A</b></td>\n";
					} else {
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex = {$row['Average']}";
						$sres =& $db->query($query);
						if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query
						if($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$average = $srow['Display'];
						} else {
							$average = "?";
						}
						echo "            <td nowrap><b>$average</b></td>\n";
					}
				}
				
			} else {
				echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			}
			echo "         </tr>\n";
		}
		if($no_marks == 0 and ($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE)) {  // Show average percentage for all students
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			$alt = " class='$alt_step'";

			echo "         <tr$alt>\n";
			echo "            <td nowrap>&nbsp;</td>\n";
			echo "            <td nowrap><i>Class Average</i></td>\n";

			/* Get assignment averages */
			$query =	"SELECT Average, Hidden FROM assignment " .
						"WHERE SubjectIndex = $subjectindex " .
						"AND   Agenda = 0 " .
						"ORDER BY Date, AssignmentIndex";
			$res =& $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($row['Average'] > -1) {
					$average = round($row['Average']) . "%";
				} else {
					$average = "N/A";
				}
				if($row['Hidden'] == "1") {
					$alt = " class='hidden-$alt_step'";
				} else {
					$alt = "";
				}
				echo "            <td$alt nowrap><i>$average</i></td>\n";
			}
			
			if($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) {
				/* Get total subject average */
				$query =	"SELECT Average FROM subject " .
							"WHERE SubjectIndex = $subjectindex ";
				$res =& $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					if($row['Average'] > -1) {
						$average = round($row['Average']) . "%";
					} else {
						$average = "N/A";
					}
					echo "            <td nowrap><b><i>$average</i></b></td>\n";
				}
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n";
		log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/list.php", $LOG_TEACHER,
				"Accessed marks for $title.");
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/assignment/list.php", $LOG_DENIED_ACCESS, 
					"Tried to access marks for $title.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>

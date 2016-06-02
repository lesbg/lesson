<?php
/**
 * ***************************************************************
 * student/subjectinfo.php (c) 2004-2007 Jonathan Dieter
 *
 * Print information about how student is doing in a subject
 * ***************************************************************
 */

/* Get variables */
$studentusername = safe(dbfuncInt2String($_GET['key2']));
$subject = dbfuncInt2String($_GET['keyname']);
$name = dbfuncInt2String($_GET['key2name']);
$title = "$subject - $name";
$subjectindex = safe(dbfuncInt2String($_GET['key']));

/*
 * Key wasn't included. The only time I've seen this happen is when a student doesn't logout and lets
 * another student use their computer, so we'll force a logout
 */
if (! isset($_GET['key'])) {
	log_event($LOG_LEVEL_ACCESS, "student/subjectinfo.php", $LOG_ERROR, 
			"Page was accessed without key (Make sure user logged out).");
	include "user/logout.php";
	exit(0);
}

include "header.php";
/* Check whether current user is principal */
$res = &  $db->query(
				"SELECT Username FROM principal " .
				 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_principal = true;
} else {
	$is_principal = false;
}

/* Check whether current user is a counselor */
$res = &  $db->query(
				"SELECT Username FROM counselorlist " .
				 "WHERE Username=\"$username\"");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_counselor = true;
} else {
	$is_counselor = false;
}

/* Check whether current user is a hod */
$query = "SELECT hod.Username FROM hod, class, classterm, classlist " .
		 "WHERE hod.Username='$username' " .
		 "AND hod.DepartmentIndex = class.DepartmentIndex " .
		 "AND classlist.Username = '$studentusername' " .
		 "AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
		 "AND classterm.ClassIndex = class.ClassIndex";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_hod = true;
} else {
	$is_hod = false;
}

/* Check whether current user is student's guardian */
$query =	"SELECT familylist.Username FROM " .
		"    familylist INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
		"WHERE familylist.Username         = '$studentusername' " .
		"AND   familylist2.Username        = '$username' " .
		"AND   familylist2.Guardian        = 1 ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
	$is_guardian = true;
} else {
	$is_guardian = false;
}

/* See whether $username is teacher for this subject */
$res = & $db->query(
				"SELECT Username FROM subjectteacher " .
				 "WHERE subjectteacher.Username     = '$username' " .
				 "AND   subjectteacher.SubjectIndex = $subjectindex");
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
	
/* Make sure user has permission to view student's marks for subject */
if ($is_admin or $is_hod or $is_principal or $is_counselor or $is_guardian or
	 $studentusername == $username or $res->numRows() > 0) {
	$query = "SELECT subject.SubjectIndex, subject.AverageType, subject.AverageTypeIndex " .
	 "       FROM subject " . "WHERE subject.SubjectIndex = $subjectindex";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
	
	$subjectindex = $row['SubjectIndex'];
	$average_type = $row['AverageType'];
	$average_type_index = $row['AverageTypeIndex'];
	
	$query = "SELECT CategoryListIndex FROM assignment " .
			 "WHERE  SubjectIndex = $subjectindex " .
			 "AND    CategoryListIndex IS NOT NULL";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	if ($res->numRows() > 0) {
		$has_categories = True;
	} else {
		$has_categories = False;
	}
	
	$query = "SELECT Title, Date, DueDate, assignment.AssignmentIndex, Description, DescriptionData, " .
			 "       DescriptionFileType, AverageType, ShowAverage, Agenda, subject.Name AS SubjectName, " .
			 "       Uploadable, assignment.Weight, Score, Percentage, mark.Comment, " .
			 "       subjectstudent.Average AS StudentSubjectAverage, " .
			 "       CanModify, CategoryName, subject.SubjectIndex " .
			 "       FROM subject INNER JOIN assignment USING (SubjectIndex) INNER JOIN subjectstudent " .
			 "       ON (subjectstudent.SubjectIndex = subject.SubjectIndex) LEFT OUTER JOIN mark ON " .
			 "		(mark.Username = subjectstudent.Username AND mark.AssignmentIndex = assignment.AssignmentIndex) " .
			 "       LEFT OUTER JOIN categorylist USING (CategoryListIndex) LEFT OUTER JOIN category USING (CategoryIndex) " .
			 "WHERE mark.Username     = '$studentusername' " .
			 "AND   subject.SubjectIndex = $subjectindex " .
			 "AND   Hidden       = 0 " . "AND   YearIndex    = $yearindex " .
			 "AND   TermIndex    = $termindex " .
			 "ORDER BY Date DESC, AssignmentIndex DESC";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	$nrs = & $db->query(
					"SELECT YearIndex, TermIndex FROM subject " .
					 "WHERE subject.SubjectIndex = $subjectindex");
	if (DB::isError($nrs))
		die($nrs->getDebugInfo()); // Check for errors in query
		
	/* Print assignments and scores */
	if ($res->numRows() > 0) {
		$row = & $nrs->fetchRow(DB_FETCHMODE_ASSOC);
		$yearindex = $row['YearIndex'];
		$termindex = $row['TermIndex'];
		$nochangeyt = true;
		
		include "core/settermandyear.php";
		include "core/titletermyear.php";
		
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		if ($is_local and ($is_admin or $studentusername == $username)) {
			echo "            <th>&nbsp;</th>\n";
		}
		echo "            <th>Title</th>\n";
		if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
			 $has_categories) {
			echo "            <th>Category</th>\n";
		}
		echo "            <th>Date</th>\n";
		echo "            <th>Due Date</th>\n";
		echo "            <th>Score</th>\n";
		if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE)) {
			echo "            <th>Weight</th>\n";
		}
		echo "            <th>Comment</th>\n";
		echo "         </tr>\n";
		
		/* For each assignment, print a row with the title, date, score and comment */
		$alt_count = 0;
		$total = 0;
		$studentscore = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$can_modify = $row['CanModify'];
			
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			$alt = " class='$alt_step'";
			$aclass = "";
			
			if ($row['Agenda'] == 1) {
				$alt = " class='agenda-$alt_step'";
				$aclass = " class='agenda'";
			} else {
				if ($row['AverageType'] == $AVG_TYPE_PERCENT or
					 $row['AverageType'] == $AVG_TYPE_GRADE) {
					if ($row['Score'] == $MARK_LATE and $can_modify == 1) {
						$alt = " class='late-$alt_step'";
						$aclass = " class='late'";
					} elseif (is_null($row['Score']) and $can_modify == 1) {
						$alt = " class='unmarked-$alt_step'";
						$aclass = " class='unmarked'";
					}
				} elseif ($row['AverageType'] == $AVG_TYPE_INDEX) {
					if (is_null($row['Score']) and $can_modify == 1) {
						$alt = " class='unmarked-$alt_step'";
						$aclass = " class='unmarked'";
					}
				}
			}
			$dateinfo = date($dateformat, strtotime($row['Date']));
			if (isset($row['DueDate'])) {
				$duedateinfo = "<b>" .
							 date($dateformat, strtotime($row['DueDate'])) . "</b>";
			} else {
				$duedateinfo = "";
			}
			echo "         <tr$alt>\n";
			if ($is_local and ($is_admin or $studentusername == $username)) {
				if ($row['Uploadable'] == 1 and
					 (is_null($row['Score']) or $row['Score'] == $MARK_LATE) and
					 $currentterm == $termindex and $currentyear == $yearindex and
					 $can_modify) {
					$uploadlink = "index.php?location=" .
					 dbfuncString2Int("student/upload.php") . "&amp;key=" .
					 dbfuncString2Int($row['AssignmentIndex']) . "&amp;keyname=" .
					 dbfuncString2Int($name) . "&amp;key2=" .
					 dbfuncString2Int($studentusername) . "&amp;key2name=" .
					 dbfuncString2Int($subject);
				$uploadbutton = dbfuncGetButton($uploadlink, "U", "small", "", 
												"Upload homework onto server");
					echo "            <td>$uploadbutton</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
			}
			
			if (is_null($row['DescriptionFileType'])) {
				if (is_null($row['Description'])) {
					echo "            <td>{$row['Title']}</td>\n";
				} else {
					$newwin = "index.php?location=" .
							 dbfuncString2Int("student/descr.php") . "&amp;key=" .
							 dbfuncString2Int($row['AssignmentIndex']);
					echo "          <td><a$aclass href='javascript:popup(&quot;$newwin&quot;)'>{$row['Title']}</a></td>\n";
				}
			} else {
				$newwin = "index.php?location=" .
						 dbfuncString2Int("student/open_descr.php") . "&amp;key=" .
						 dbfuncString2Int($row['AssignmentIndex']);
				echo "          <td><a$aclass href='$newwin'>{$row['Title']}</a></td>\n";
			}
			if (($average_type == $AVG_TYPE_PERCENT or $average_type == $AVG_TYPE_GRADE) and
				 $has_categories) {
				if (is_null($row['CategoryName'])) {
					echo "<td><i>None</i></td>\n";
				} else {
					echo "<td>{$row['CategoryName']}</td>\n";
				}
			}
			echo "            <td>{$dateinfo}</td>\n";
			echo "            <td>{$duedateinfo}</td>\n";
			if ($row['Agenda'] == 1) {
				if (($average_type == $AVG_TYPE_PERCENT or
					 $average_type == $AVG_TYPE_GRADE)) {
					$colspan = "3";
				} else {
					$colspan = "2";
				}
				
				echo "            <td colspan='$colspan' align='center'><i>N/A</i></td>\n";
			} else {
				if ($average_type == $AVG_TYPE_PERCENT or
					 $average_type == $AVG_TYPE_GRADE) {
					if ($row['Score'] == $MARK_LATE) {
						if ($can_modify == 1) {
							echo "            <td>&nbsp;</td>\n";
						} else {
							echo "            <td>0%</td>\n";
						}
						echo "            <td>{$row['Weight']}</td>\n";
					} elseif ($row['Score'] == $MARK_ABSENT) {
						echo "            <td colspan='2' align='center'><i>Absent</i></td>\n";
					} elseif ($row['Score'] == $MARK_EXEMPT) {
						echo "            <td colspan='2' align='center'><i>Exempt</i></td>\n";
					} elseif (is_null($row['Score'])) {
						if ($can_modify == 1) {
							echo "            <td>&nbsp;</td>\n";
							echo "            <td>{$row['Weight']}</td>\n";
						} else {
							echo "            <td colspan='2' align='center'><i>Exempt</i></td>\n";
						}
					} else {
						$score = round($row['Percentage']);
						echo "            <td>$score%</td>\n";
						echo "            <td>{$row['Weight']}</td>\n";
					}
					if ($row['Score'] == $MARK_LATE) {
						if ($row['Comment'] == "" or is_null($row['Comment'])) {
							echo "            <td>Late</td>\n";
						} else {
							echo "            <td>{$row['Comment']}</td>\n";
						}
					} else {
						echo "            <td>{$row['Comment']}</td>\n";
					}
				} elseif ($average_type == $AVG_TYPE_INDEX) {
					if (! isset($average_type_index) or $average_type_index == "" or
							 ! isset($row['Score']) or $row['Score'] == "") {
						$score = "N/A";
					} else {
						$query = "SELECT Input, Display FROM nonmark_index " .
								 "WHERE NonmarkTypeIndex = $average_type_index " .
								 "AND   NonmarkIndex     = {$row['Score']}";
						$sres = & $db->query($query);
						if (DB::isError($sres))
							die($sres->getDebugInfo()); // Check for errors in query
						if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $srow['Display'];
						} else {
							$score = "N/A";
						}
					}
					echo "            <td>$score</td>\n";
					echo "            <td>{$row['Comment']}</td>\n";
				}
			}
			echo "         </tr>\n";
			$show_average = $row['ShowAverage'];
			$average = $row['StudentSubjectAverage'];
		}
		echo "      </table>\n"; // End of table
		
		/* Total percentage */
		if ($average_type == $AVG_TYPE_PERCENT and $show_average == 1) {
			if ($average > - 1) {
				$final = round($average) . "%";
			} else {
				$final = "N/A";
			}
			echo "      <p></p>\n";
			echo "      <h2 align='center'><b>Total score: <i>$final</i></b></h2>\n";
		}
	} else {
		echo "      <p>No assignments</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "student/subjectinfo.php", $LOG_STUDENT, 
			"Viewed $name's assignments for $subject.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "student/subjectinfo.php", $LOG_DENIED_ACCESS, 
			"Tried to access $name's marks for $subject.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
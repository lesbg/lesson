<?php
	/*****************************************************************
	 * teacher/report/class_list.php  (c) 2008 Jonathan Dieter
	 *
	 * View all students and basic report info
	 *****************************************************************/

	/* Get variables */
	$class         = dbfuncInt2String($_GET['keyname']);
	$title         = "Reports for " . $class;
	$classtermindex    = safe(dbfuncInt2String($_GET['key']));

	/* Check whether current user is principal */
	$res =&  $db->query("SELECT Username FROM principal " .
						"WHERE Username=\"$username\" AND Level=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_principal = true;
	} else {
		$is_principal = false;
	}

	/* Check whether current user is a hod */
	$res =&  $db->query("SELECT hod.Username FROM hod, class, classterm " .
						"WHERE hod.Username        = '$username' " .
						"AND   hod.DepartmentIndex = class.DepartmentIndex " .
						"AND   class.ClassIndex    = classterm.ClassIndex " .
						"AND   classterm.ClassTermIndex = $classtermindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT class.ClassIndex FROM class, classterm " .
					   "WHERE classterm.ClassTermIndex  = $classtermindex " .
					   "AND   classterm.ClassIndex = class.ClassIndex " .
					   "AND   class.ClassTeacherUsername = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	if($res->numRows() > 0) {
		$is_ct = true;
	} else {
		$is_ct = false;
	}
	$showyear = false;
	$showdeps = false;

	include "core/settermandyear.php";
	include "header.php";                                      // Show header
	include "core/titletermyear.php";

	if(!$is_ct and !$is_hod and !$is_principal and !$is_admin) {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_list.php", $LOG_DENIED_ACCESS,
					"Tried to get report information for $class.");

		include "footer.php";
		exit(0);
	}

	/* Check whether subject is open for report editing */
	$query =	"SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
				"       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
				"       classterm.ConductTypeIndex, classterm.CTCommentType, " .
				"       classterm.HODCommentType, classterm.PrincipalCommentType, " .
				"       classterm.CanDoReport, classterm.AbsenceType, " .
				"       MIN(classlist.ReportDone) AS ReportDone " .
				"       FROM classterm, classlist " .
				"WHERE classterm.ClassTermIndex    = $classtermindex " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"GROUP BY classterm.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or (!$row['CanDoReport'] and !$row['ReportDone'])) {
		/* Print error message */
		echo "      <p align='center'>No reports for this term.</p>\n";
		include "footer.php";
		exit(0);
	}

	$average_type       = $row['AverageType'];
	$effort_type        = $row['EffortType'];
	$conduct_type       = $row['ConductType'];
	$absence_type       = $row['AbsenceType'];
	$ct_comment_type    = $row['CTCommentType'];
	$hod_comment_type   = $row['HODCommentType'];
	$pr_comment_type    = $row['PrincipalCommentType'];
	$can_do_report      = $row['CanDoReport'];
	$average_type_index = $row['AverageTypeIndex'];
	$effort_type_index  = $row['EffortTypeIndex'];
	$conduct_type_index = $row['ConductTypeIndex'];

	$query =	"SELECT user.Gender, user.FirstName, user.Surname, user.Username, " .
				"       classlist.Average, classlist.Conduct, classlist.Effort, " .
				"       classlist.Rank, classlist.CTComment, classlist.HODComment, " .
				"       classlist.CTCommentDone, classlist.HODCommentDone, " .
				"       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
				"       classlist.PrincipalUsername, classlist.HODUsername, " .
				"       classlist.ReportDone, classlist.ReportProofread, " .
				"       classlist.ReportPrinted, classlist.Absences, " .
				"       classlist.ReportProofDone, " .
				"       average_index.Display AS AverageDisplay, " .
				"       effort_index.Display AS EffortDisplay, " .
				"       conduct_index.Display AS ConductDisplay " .
				"       FROM user, classlist " .
				"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
				"            classlist.Average = average_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
				"            classlist.Effort = effort_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
				"            classlist.Conduct = conduct_index.NonmarkIndex " .
				"WHERE user.Username            = classlist.Username " .
				"AND   classlist.ClassTermIndex = $classtermindex " .
				"ORDER BY user.FirstName, user.Surname, user.Username";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	$order = 1;
	if($res->numRows() == 0) {
		echo "          <p>No students in class list.</p>\n";
		include "footer.php";
		exit(0);
	}

	if($is_admin or $is_principal) {
		$link =	"index.php?location=" . dbfuncString2Int("report/show_class.php") .
				"&amp;key=" .               $_GET['key'] .
				"&amp;key3=" .              dbfuncString2Int($termindex);
		echo "          <p align='center'><a href='$link'>Print all reports</a></p>\n";
	}
	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>&nbsp;</th>\n";
	echo "               <th>Student</th>\n";
	if($average_type != $CLASS_AVG_TYPE_NONE) {
		echo "               <th>Average</th>\n";
	}
	if($effort_type != $CLASS_EFFORT_TYPE_NONE) {
		echo "               <th>Effort</th>\n";
	}
	if($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
		echo "               <th>Conduct</th>\n";
	}
	if($absence_type != $ABSENCE_TYPE_NONE) {
		echo "               <th>Absences</th>\n";
	}
	if($ct_comment_type != $COMMENT_TYPE_NONE) {
		echo "               <th>Class Teacher's Comment</th>\n";
		echo "               <th>Finished</th>\n";
	}
	if($hod_comment_type != $COMMENT_TYPE_NONE) {
		echo "               <th>Head of Department's Comment</th>\n";
		echo "               <th>Finished</th>\n";
	}
	if($pr_comment_type != $COMMENT_TYPE_NONE) {
		echo "               <th>Principal's Comment</th>\n";
		echo "               <th>Finished</th>\n";
	}
	echo "               <th>Report Status</th>\n";
	echo "            </tr>\n";
	
	/* For each student, print a row with the student's name and score on each report*/
	$alt_count   = 0;
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$alt_count   += 1;
		
		if($alt_count % 2 == 0) {
			$alt = " class='alt'";
		} else {
			$alt = " class='std'";
		}

		echo "            <tr$alt id='row_{$row['Username']}'>\n";
		echo "               <td>$order</td>\n";
		$order += 1;
		$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
		$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_modify.php") .
				"&amp;key=" .               $_GET['key'] .
				"&amp;key2=" .              dbfuncString2Int($row['Username']) .
				"&amp;keyname=" .           $_GET['keyname'] .
				"&amp;keyname2=" .          dbfuncString2Int($name);

		echo "               <td><a href='$link'>$name</a></td>\n";
		if($average_type != $CLASS_AVG_TYPE_NONE) {
			if($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC) {
				if($row['Average'] == -1) {
					$score = "N/A";
				} else {
					$score = round($row['Average']);
					$score = "$score%";
				}
			} elseif($average_type == $CLASS_AVG_TYPE_INDEX) {
				if(is_null($row['AverageDisplay'])) {
					$score = "&nbsp;";
				} else {
					$score = $row['AverageDisplay'];
				}
 			} else {
				$score = "N/A";
			}
			echo "               <td>$score</td>\n";
		}

		if($effort_type != $CLASS_EFFORT_TYPE_NONE) {
			if($effort_type == $CLASS_EFFORT_TYPE_PERCENT or $effort_type == $CLASS_EFFORT_TYPE_CALC) {
				if($row['Effort'] == -1) {
					$score = "&nbsp;";
				} else {
					$score = round($row['Effort']);
					$score = "$score%";
				}
			} elseif($effort_type == $CLASS_EFFORT_TYPE_INDEX) {
				if(is_null($row['EffortDisplay'])) {
					$score = "&nbsp;";
				} else {
					$score = $row['EffortDisplay'];
				}
			} else {
				$score = "N/A";
			}
			echo "               <td>$score</td>\n";
		}

		if($conduct_type != $CLASS_CONDUCT_TYPE_NONE) {
			if($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT or $conduct_type == $CLASS_CONDUCT_TYPE_CALC or $conduct_type == $CLASS_CONDUCT_TYPE_PUN) {
				if($row['Conduct'] == -1) {
					$score = "&nbsp;";
				} else {
					$score = round($row['Conduct']);
					$score = "$score%";
				}
			} elseif($conduct_type == $CLASS_CONDUCT_TYPE_INDEX) {
				if(is_null($row['ConductDisplay'])) {
					$score = "&nbsp;";
				} else {
					$score = $row['ConductDisplay'];
				}
			} else {
				$score = "N/A";
			}
			echo "               <td>$score</td>\n";
		}

		if($absence_type != $ABSENCE_TYPE_NONE) {
			if($absence_type == $ABSENCE_TYPE_NUM) {
				if($row['Absences'] == -1) {
					$score = "&nbsp;";
				} else {
					$score = round($row['Absences']);
					$score = "$score";
				}
			} elseif($absence_type == $ABSENCE_TYPE_CALC) {
				$absent    = 0;
				$late      = 0;
				$suspended = 0;

				$nquery =	"SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
							"       FROM attendance INNER JOIN subject USING (SubjectIndex) " .
							"       INNER JOIN period USING (PeriodIndex) " .
							"WHERE  attendance.Username = '{$row['Username']}' " .
							"AND    subject.YearIndex = $yearindex " .
							"AND    subject.TermIndex = $termindex " .
							"AND    period.Period = 1 " .
							"AND    attendance.AttendanceTypeIndex > 0 " .
							"GROUP BY AttendanceTypeIndex ";
				$cRes =&   $db->query($nquery);
				if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
				while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
					if($cRow['AttendanceTypeIndex'] == $ATT_ABSENT)    $absent    = $cRow['Count'];
					if($cRow['AttendanceTypeIndex'] == $ATT_LATE)      $late      = $cRow['Count'];
					if($cRow['AttendanceTypeIndex'] == $ATT_SUSPENDED) $suspended = $cRow['Count'];
				}
				$score = $absent + $suspended;
			} else {
				$score = "N/A";
			}
			echo "               <td>$score</td>\n";
		}

		if($ct_comment_type != $COMMENT_TYPE_NONE) {
			if($ct_comment_type == $COMMENT_TYPE_MANDATORY or $ct_comment_type == $COMMENT_TYPE_OPTIONAL) {
				if(is_null($row['CTComment'])) {
					echo "               <td>&nbsp;</td>\n";
				} else {
					$comment = $row['CTComment'];
					if(strlen($comment) > $SHOW_COMMENT_LENGTH) {
						$comment = trim(substr($comment, 0, $SHOW_COMMENT_LENGTH)) . "...";
					}
					$comment = htmlspecialchars($comment, ENT_QUOTES);
					echo "               <td>$comment</td>\n";
				}
				if($row['CTCommentDone']) {
					echo "               <td><i>Yes</i></td>\n";
				} else {
					echo "               <td><i><b>No</b></i></td>\n";
				}
			} else {
				echo "               <td colspan='2'>N/A</td>\n";
			}
			
		}

		if($hod_comment_type != $COMMENT_TYPE_NONE) {
			if($hod_comment_type == $COMMENT_TYPE_MANDATORY or $hod_comment_type == $COMMENT_TYPE_OPTIONAL) {
				if(is_null($row['HODComment'])) {
					echo "               <td>&nbsp;</td>\n";
				} else {
					$comment = $row['HODComment'];
					if(strlen($comment) > $SHOW_COMMENT_LENGTH) {
						$comment = trim(substr($comment, 0, $SHOW_COMMENT_LENGTH)) . "...";
					}
					$comment = htmlspecialchars($comment, ENT_QUOTES);
					echo "               <td>$comment</td>\n";
				}
				if($row['HODCommentDone']) {
					echo "               <td><i>Yes</i></td>\n";
				} else {
					echo "               <td><i><b>No</b></i></td>\n";
				}
			} else {
				echo "               <td colspan='2'>N/A</td>\n";
			}
			
		}

		if($pr_comment_type != $COMMENT_TYPE_NONE) {
			if($pr_comment_type == $COMMENT_TYPE_MANDATORY or $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
				if(is_null($row['PrincipalComment'])) {
					echo "               <td>&nbsp;</td>\n";
				} else {
					$comment = $row['PrincipalComment'];
					if(strlen($comment) > $SHOW_COMMENT_LENGTH) {
						$comment = trim(substr($comment, 0, $SHOW_COMMENT_LENGTH)) . "...";
					}
					$comment = htmlspecialchars($comment, ENT_QUOTES);
					echo "               <td>$comment</td>\n";
				}
				if($row['PrincipalCommentDone']) {
					echo "               <td><i>Yes</i></td>\n";
				} else {
					echo "               <td><i><b>No</b></i></td>\n";
				}
			} else {
				echo "               <td colspan='2'>N/A</td>\n";
			}
			
		}
		if($row['ReportDone'] and $row['ReportProofread'] and !$row['ReportProofDone']) {
			echo "               <td>Awaiting proofreading";
		} elseif($row['ReportDone'] and !$row['ReportPrinted']) {
			echo "               <td>Awaiting printing";
		} elseif($row['ReportDone'] and $row['ReportPrinted']) {
			echo "               <td><i>Finished</i>";
		} else {
			echo "               <td><b>Open</b>\n";
		}
		if($is_admin or $is_principal) {
			$link =	"index.php?location=" . dbfuncString2Int("report/show.php") .
					"&amp;key=" .               $_GET['key'] .
					"&amp;key2=" .              dbfuncString2Int($row['Username']) .
					"&amp;key3=" .              dbfuncString2Int($termindex);
			echo " <a href=$link>Print</a></td>\n";
		} else {
			echo "</td>\n";
		}

		echo "            </tr>\n";
	}
	echo "         </table>\n";               // End of table

	$show_finish = false;
	$query =	"SELECT MIN(CTCommentDone) AS CTCommentDone " .
				"       FROM classterm, classlist " .
				"WHERE classlist.ClassTermIndex     =  $classtermindex " .
				"AND   classterm.ClassTermIndex     =  $classtermindex " .
				"AND   classterm.CTCommentType     != $COMMENT_TYPE_NONE " .
				"GROUP BY classlist.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($is_ct and !$row['CTCommentDone']) $show_finish = true;
	}

	$query =	"SELECT MIN(HODCommentDone) AS HODCommentDone " .
				"       FROM classterm, classlist " .
				"WHERE classlist.ClassTermIndex     =  $classtermindex " .
				"AND   classterm.ClassTermIndex     =  $classtermindex " .
				"AND   classterm.HODCommentType    != $COMMENT_TYPE_NONE " .
				"GROUP BY classlist.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($is_hod and !$row['HODCommentDone']) $show_finish = true;
	}

	$query =	"SELECT MIN(PrincipalCommentDone) AS PrincipalCommentDone " .
				"       FROM classterm, classlist " .
				"WHERE classlist.ClassTermIndex        =  $classtermindex " .
				"AND   classterm.ClassTermIndex        =  $classtermindex " .
				"AND   classterm.PrincipalCommentType != $COMMENT_TYPE_NONE " .
				"GROUP BY classlist.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($is_principal and !$row['PrincipalCommentDone']) $show_finish = true;
	}

	$show_rpt_close = false;
	$query =	"SELECT MIN(classlist.ReportDone) AS ReportDone FROM classlist " .
				"WHERE classlist.ClassTermIndex     = $classtermindex " .
				"GROUP BY classlist.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if(($is_principal or $is_hod or $is_admin) and !$row['ReportDone']) $show_rpt_close = true;
	}

	if($show_finish or $is_hod or $is_admin or $is_principal) {
		$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_confirm.php") .
				"&amp;key=" .               $_GET['key'] .
				"&amp;keyname=" .           $_GET['keyname'];
	
		echo "         <form action='$link' method='post'>\n";
		echo "            <p align='center'>\n";
		if($show_finish) {
			echo "               <input type='submit' name='action' value='Finished with all comments'>&nbsp; \n";
		}
		if($show_rpt_close) {
			echo "               <input type='submit' name='action' value='Close all reports'>&nbsp; \n";
		}

		echo "            </p>\n";
		echo "         </form>\n";
	}
	include "footer.php";
?>
<?php
	/*****************************************************************
	 * teacher/report/modify.php  (c) 2008 Jonathan Dieter
	 *
	 * Edit conduct, effort and comment for report
	 *****************************************************************/

	$student_name     = "";
	$student_username = "";

	/* Get variables */
	if(!isset($_GET['next'])) $_GET['next'] = dbfuncString2Int($backLink);
	$subject       = dbfuncInt2String($_GET['keyname']);
	$title         = "Report for " . $subject;
	$subjectindex  = safe(dbfuncInt2String($_GET['key']));
	$link          = "index.php?location=" . dbfuncString2Int("teacher/report/modify_action.php") .
					 "&amp;key=" .               $_GET['key'] .
					 "&amp;keyname=" .           $_GET['keyname'] .
					 "&amp;next=" .              $_GET['next'];
	if(isset($_GET['key2'])) {
		$link .=	 "&amp;key2=" .               $_GET['key2'] .
					 "&amp;keyname2=" .           $_GET['keyname2'];
		$student_username = safe(dbfuncInt2String($_GET['key2']));
		$student_name     = dbfuncInt2String($_GET['keyname2']);
	}

	$use_extra_css = true;
	$extra_js      = "report.js";

	include "core/settermandyear.php";
	include "header.php";                                      // Show header

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
	$res =&  $db->query("SELECT hod.Username FROM hod, term, subject " .
						"WHERE hod.Username         = '$username' " .
						"AND   hod.DepartmentIndex  = term.DepartmentIndex " .
						"AND   term.TermIndex       = subject.TermIndex " .
						"AND   subject.SubjectIndex = $subjectindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	/* Check whether user is subject teacher */
	$query =		"SELECT subjectteacher.Username FROM subjectteacher " .
					"WHERE subjectteacher.SubjectIndex = $subjectindex " .
					"AND   subjectteacher.Username     = '$username' ";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_st = true;
	} else {
		$is_st = false;
	}

	/* Check whether user is proofreader */
	$query =	"SELECT department.ProofreaderUsername FROM department, subject " .
				"WHERE subject.SubjectIndex           = $subjectindex " .
				"AND   department.ProofreaderUsername = '$username' " .
				"AND   department.DepartmentIndex     = subject.DepartmentIndex ";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_proofreader = true;
	} else {
		$is_proofreader = false;
	}

	/* Check whether user is class teacher */
	$query =	"SELECT class.ClassTeacherUsername FROM class, classlist " .
				"WHERE  classlist.Username         = '$student_username' " .
				"AND    class.ClassIndex           = classlist.ClassIndex " .
				"AND    class.ClassTeacherUsername = '$username' " .
				"AND    class.YearIndex            = $yearindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_ct = true;
	} else {
		$is_ct = false;
	}

	if(!$is_st and !$is_admin and !$is_hod and !$is_principal and
	   ($student_username == "" or (!$is_ct and !$is_proofreader))) {
		/* Print error message */
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/modify.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	/* Check whether subject is open for report editing */
	$query =	"SELECT subject.AverageType, subject.EffortType, subject.ConductType, " .
				"       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
				"       subject.ConductTypeIndex, subject.CommentType, subject.CanDoReport, " .
				"       subject_info.ReportDone " .
				"       FROM subject LEFT OUTER JOIN " .
				"       (SELECT subjectstudent.SubjectIndex, " .
				"               MIN(subjectstudent.ReportDone) AS ReportDone " .
				"               FROM subjectstudent " .
				"        WHERE subjectstudent.SubjectIndex = $subjectindex " .
				"        GROUP BY subjectstudent.SubjectIndex) AS subject_info USING (SubjectIndex) " .
				"WHERE subject.SubjectIndex = $subjectindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
		/* Print error message */
		echo "      <p>Reports for this subject aren't open.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/modify.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	$average_type = $row['AverageType'];
	$effort_type  = $row['EffortType'];
	$conduct_type = $row['ConductType'];
	$comment_type = $row['CommentType'];
	$average_type_index = $row['AverageTypeIndex'];
	$effort_type_index  = $row['EffortTypeIndex'];
	$conduct_type_index = $row['ConductTypeIndex'];
	$report_done  = $row['ReportDone'];

	if($student_username != "") $report_done = 0;

	
	if(!is_null($effort_type_index)) {
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE  NonmarkTypeIndex=$effort_type_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$input = strtoupper($row['Input']);
			$einput_array   = "'$input'";
			$edisplay_array = "'{$row['Display']}'";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$einput_array   .= ", '{$row['Input']}'";
				$edisplay_array .= ", '{$row['Display']}'";
			}
		}
	} else {
		$einput_array   = "";
		$edisplay_array = "";
	}
	if(!is_null($conduct_type_index)) {
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE  NonmarkTypeIndex=$conduct_type_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$input = strtoupper($row['Input']);
			$cinput_array   = "'$input'";
			$cdisplay_array = "'{$row['Display']}'";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$cinput_array   .= ", '{$row['Input']}'";
				$cdisplay_array .= ", '{$row['Display']}'";
			}
		}
	} else {
		$cinput_array   = "";
		$cdisplay_array = "";
	}

	if($comment_type == $COMMENT_TYPE_MANDATORY or $comment_type == $COMMENT_TYPE_OPTIONAL) {
		$query = "SELECT CommentIndex, Comment, Strength FROM comment ORDER BY CommentIndex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$count = 0;
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$comment = str_replace("'", "\'", $row['Comment']);
			$comment = str_replace("\"", "\\\"", $comment);
			if($row['CommentIndex'] == $count) {
				$comment_array = "'$comment'";
				$cval_array    = "'{$row['Strength']}'";
			} else {
				$comment_array = "'($count)'";
				$cval_array    = "''";
				$count += 1;
				while($row['CommentIndex'] > $count) {
					$comment_array .= ", '($count)'";
					$cval_array    .= ", ''";
					$count += 1;
				}
				$comment_array .= ", '$comment'";
				$cval_array    .= ", '{$row['Strength']}'";
			}
			while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$comment = str_replace("'", "\'", $row['Comment']);
				$comment = str_replace("\"", "\\\"", $comment);
				$count += 1;
				while($row['CommentIndex'] > $count) {
					$comment_array .= ", '($count)'";
					$cval_array    .= ", ''";
					$count += 1;
				}
				$comment_array .= ", '$comment'";
				$cval_array    .= ", '{$row['Strength']}'";
			}
		}
	}

	$query =		"SELECT user.FirstName, user.Surname, user.Username, user.Gender, " .
					"       subjectstudent.Average, subjectstudent.Effort, subjectstudent.Conduct, " .
					"       nonmark_index.Display, subjectstudent.ReportDone, " .
					"       subjectstudent.Comment, subjectstudent.CommentValue, " .
					"       query.ClassOrder FROM user, subjectstudent LEFT OUTER JOIN " .
					"       (SELECT classlist.ClassOrder, classlist.Username " .
					"               FROM class, classlist, subject " .
					"        WHERE classlist.ClassIndex       = class.ClassIndex " .
					"        AND   class.YearIndex            = subject.YearIndex " .
					"        AND   subject.SubjectIndex       = $subjectindex) AS query " .
					"       ON subjectstudent.Username = query.Username " .
					"       LEFT OUTER JOIN nonmark_index ON " .
					"            subjectstudent.Average = nonmark_index.NonmarkIndex " .
					"WHERE user.Username               = subjectstudent.Username ";
	if($student_username != "") {
		$query .=	"AND   subjectstudent.Username     = '$student_username' ";
	}
	$query .=		"AND   subjectstudent.SubjectIndex = $subjectindex " .
					"ORDER BY user.FirstName, user.Surname, user.Username";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if($res->numRows() == 0) {
		echo "          <p>No students in class list.</p>\n";
		include "footer.php";
		exit(0);
	}

	if($report_done == 0) {
		echo "      <script language='JavaScript' type='text/javascript'>\n";
		echo "\n";
		echo "         var CONDUCT_TYPE_NONE      = $CONDUCT_TYPE_NONE;\n";
		echo "         var CONDUCT_TYPE_PERCENT   = $CONDUCT_TYPE_PERCENT;\n";
		echo "         var CONDUCT_TYPE_INDEX     = $CONDUCT_TYPE_INDEX;\n";
		echo "         var EFFORT_TYPE_NONE       = $EFFORT_TYPE_NONE;\n";
		echo "         var EFFORT_TYPE_PERCENT    = $EFFORT_TYPE_PERCENT;\n";
		echo "         var EFFORT_TYPE_INDEX      = $EFFORT_TYPE_INDEX;\n";
		echo "         var COMMENT_TYPE_NONE      = $COMMENT_TYPE_NONE;\n";
		echo "         var COMMENT_TYPE_MANDATORY = $COMMENT_TYPE_MANDATORY;\n";
		echo "         var COMMENT_TYPE_OPTIONAL  = $COMMENT_TYPE_OPTIONAL;\n";
		echo "\n";
		echo "         var effort_type            = $effort_type;\n";
		if($effort_type == $EFFORT_TYPE_INDEX) {
			echo "         var effort_input_array     = new Array($einput_array);\n";
			echo "         var effort_display_array   = new Array($edisplay_array);\n";
		}
		echo "\n";
		echo "         var conduct_type           = $conduct_type;\n";
		if($conduct_type == $CONDUCT_TYPE_INDEX) {
			echo "         var conduct_input_array    = new Array($cinput_array);\n";
			echo "         var conduct_display_array  = new Array($cdisplay_array);\n";
		}
		echo "\n";
		echo "         var comment_type           = $comment_type;\n";
		if($comment_type == $COMMENT_TYPE_MANDATORY or $comment_type == $COMMENT_TYPE_OPTIONAL) {
			echo "         var comment_array          = new Array($comment_array);\n";
			echo "         var cval_array             = new Array($cval_array);\n";
		}
	
		echo "      </script>\n";
	}
	echo "      <form action='$link' method='post' name='report'>\n";        // Form method
	
	$order = 1;

	echo "         <p align='center'>\n";
	if($report_done == 0) {
		echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
	}
	echo "            <input type='submit' name='action' value='Cancel'>\n";
	if($student_username == "" and $report_done == 0) {
		if($conduct_type != $CONDUCT_TYPE_NONE) {
			echo "            <input type='submit' name='action' value='Apply conduct to all my subjects'>\n";
		}
		echo "            <input type='submit' name='action' value='I&#039;m finished with these marks'>\n";
	}
	echo "         </p>\n";

	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>&nbsp;</th>\n";
	echo "               <th>Student</th>\n";
	if($is_st or $is_ct or $is_hod or $is_principal or $is_admin) {
		if($average_type != $AVG_TYPE_NONE) {
			echo "               <th>Average</th>\n";
		}
		if($effort_type != $EFFORT_TYPE_NONE) {
			echo "               <th>Effort</th>\n";
		}
		if($conduct_type != $CONDUCT_TYPE_NONE) {
			echo "               <th>Conduct</th>\n";
		}
	}
	if($comment_type != $COMMENT_TYPE_NONE) {
		echo "               <th>Comment</th>\n";
	}
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
		echo "               <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})<input type='hidden' name='firstname_{$row['Username']}' id='firstname_{$row['Username']}' value='{$row['FirstName']}'><input type='hidden' name='fullname_{$row['Username']}' id='fullname_{$row['Username']}' value='{$row['FirstName']} {$row['Surname']}'><input type='hidden' name='gender_{$row['Username']}' id='gender_{$row['Username']}' value='{$row['Gender']}'></td>\n";

		if($is_st or $is_ct or $is_hod or $is_principal or $is_admin) {
			if($average_type != $AVG_TYPE_NONE) {
				if($average_type == $AVG_TYPE_PERCENT) {
					if($row['Average'] == -1) {
						$score = "N/A";
					} else {
						$score = round($row['Average']);
						$score = "$score%";
					}
				} elseif($average_type == $AVG_TYPE_INDEX) {
					if(is_null($row['Display'])) {
						$score = "N/A";
					} else {
						$score = $row['Display'];
					}
				} else {
					$score = "N/A";
				}
				echo "               <td>$score</td>\n";
			}
	
			/* Check for type of effort mark and put in appropriate information */
			if($effort_type != $EFFORT_TYPE_NONE) {
				if($effort_type == $EFFORT_TYPE_PERCENT) {
					if(isset($_POST["effort_{$row['Username']}"])) {
						$scorestr = $_POST["effort_{$row['Username']}"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) > 100) {
							$score = "100%";
						} elseif(intval($scorestr) < 0) {
							$score = "0%";
						} else {
							$score = "$scorestr%";
						}
					} else {
						if($row['Effort'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = round($row['Effort']);
							$score = "$scorestr%";
						}
					}
				} elseif($effort_type == $EFFORT_TYPE_INDEX) {
					if(isset($_POST["effort_{$row['Username']}"])) {
						$scorestr = safe($_POST["effort_{$row['Username']}"]);
						$query =	"SELECT Display FROM nonmark_index " .
									"WHERE Input='$scorestr' " .
									"AND   NonmarkTypeIndex=$effort_type_index";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
	
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $nrow['Display'];
						} else {
							$score = "N/A";
						}
					} else {
						$scoreindex = $row['Effort'];
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex=$scoreindex";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
	
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$scorestr = $nrow['Input'];
							$score    = $nrow['Display'];
						} else {
							$scorestr = "";
							$score    = "N/A";
						}
					}
				} else {
					if(isset($_POST["effort_{$row['Username']}"])) {
						$scorestr = $_POST["effort_{$row['Username']}"];
					} else {
						$scorestr = "";
					}
					$score = "N/A";
				}
				if($row['ReportDone'] == 0 or $student_username != "") {
					echo "               <td><input type='text' name='effort_{$row['Username']}' " .
										"id='effort_{$row['Username']}' value='$scorestr' size='4' onChange='recalc_effort(&quot;{$row['Username']}&quot;);'> = <label name='eavg_{$row['Username']}' id='eavg_{$row['Username']}' for='effort_{$row['Username']}'>$score</label</td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
	
			/* Check for type of conduct mark&#039; and put in appropriate information */
			if($conduct_type != $CONDUCT_TYPE_NONE) {
				if($conduct_type == $CONDUCT_TYPE_PERCENT) {
					if(isset($_POST["conduct_{$row['Username']}"])) {
						$scorestr = $_POST["conduct_{$row['Username']}"];
						if(strval(intval($scorestr)) != $scorestr) {
							$score = "N/A";
						} elseif(intval($scorestr) > 100) {
							$score = "100%";
						} elseif(intval($scorestr) < 0) {
							$score = "0%";
						} else {
							$score = "$scorestr%";
						}
					} else {
						if($row['Conduct'] == -1) {
							$scorestr = "";
							$score = "N/A";
						} else {
							$scorestr = round($row['Conduct']);
							$score = "$scorestr%";
						}
					}
				} elseif($conduct_type == $CONDUCT_TYPE_INDEX) {
					if(isset($_POST["conduct_{$row['Username']}"])) {
						$scorestr = safe($_POST["conduct_{$row['Username']}"]);
						$query =	"SELECT Display FROM nonmark_index " .
									"WHERE Input='$scorestr' " .
									"AND   NonmarkTypeIndex=$conduct_type_index";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
	
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$score = $nrow['Display'];
						} else {
							$score = "N/A";
						}
					} else {
						$scoreindex = $row['Conduct'];
						$query =	"SELECT Input, Display FROM nonmark_index " .
									"WHERE NonmarkIndex=$scoreindex";
						$nres =& $db->query($query);
						if(DB::isError($nres)) die($nres->getDebugInfo());
	
						if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
							$scorestr = $nrow['Input'];
							$score    = $nrow['Display'];
						} else {
							$scorestr = "";
							$score    = "N/A";
						}
					}
				} else {
					if(isset($_POST["conduct_{$row['Username']}"])) {
						$scorestr = $_POST["conduct_{$row['Username']}"];
					} else {
						$scorestr = "";
					}
					$score = "N/A";
				}
				if($row['ReportDone'] == 0 or $student_username != "") {
					echo "               <td><input type='text' name='conduct_{$row['Username']}' " .
										"id='conduct_{$row['Username']}' value='$scorestr' size='4' onChange='recalc_conduct(&quot;{$row['Username']}&quot;);'> = <label name='cavg_{$row['Username']}' id='cavg_{$row['Username']}' for='conduct_{$row['Username']}'>$score</label></td>\n";
				} else {
					echo "               <td>$score</td>\n";
				}
			}
		}

		if($comment_type != $COMMENT_TYPE_NONE) {
			if($comment_type == $COMMENT_TYPE_MANDATORY or $comment_type == $COMMENT_TYPE_OPTIONAL) {
				if(isset($_POST["comment_{$row['Username']}"])) {
					$commentstr = htmlspecialchars($_POST["comment_{$row['Username']}"], ENT_QUOTES);
				} else {
					$commentstr = htmlspecialchars($row['Comment'], ENT_QUOTES);
				}
				if(isset($_POST["cval_{$row['Username']}"])) {
					$cvalstr = $_POST["cval_{$row['Username']}"];
				} else {
					if(is_null($row['CommentValue'])) {
						$cvalstr = "";
					} else {
						$cvalstr = $row['CommentValue'];
					}
				}
				if($row['ReportDone'] == 0 or $student_username != "") {
					echo "               <td><input type='text' name='comment_{$row['Username']}' " .
											"value='$commentstr' id='comment_{$row['Username']}' size='40' onChange='recalc_comment(&quot;{$row['Username']}&quot;);'><input type='hidden' name='cval_{$row['Username']}' id='cval_{$row['Username']}' value='$cvalstr'> <input type='submit' name='find_comment_{$row['Username']}' value='...'></td>\n";
				} else {
					echo "               <td>$commentstr</td>\n";
				}
			} else {
				echo "               <td>N/A</td>\n";
			}
			
		}
		echo "            </tr>\n";
	}
	echo "         </table>\n";               // End of table
	echo "         <p></p>\n";
	echo "         <p align='center'>\n";
	if($report_done == 0) {
		echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
	}
	echo "            <input type='submit' name='action' value='Cancel'>\n";
	if($student_username == "" and $report_done == 0) {
		if($conduct_type != $CONDUCT_TYPE_NONE) {
			echo "            <input type='submit' name='action' value='Apply conduct to all my subjects'>\n";
		}
		echo "            <input type='submit' name='action' value='I&#039;m finished with these marks'>\n";
	}
	echo "         </p>\n";
	echo "       </form>\n";
	include "footer.php";
?>
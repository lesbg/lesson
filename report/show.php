<?php
	/*****************************************************************
	 * report/show.php  (c) 2007, 2008 Jonathan Dieter
	 *
	 * Show report
	 *****************************************************************/
	function recursive_remove_directory($directory, $empty=FALSE) {
		if(substr($directory,-1) == '/') {
			$directory = substr($directory,0,-1);
		}
		if(!file_exists($directory) or !is_dir($directory)) {
			return FALSE;
		} elseif(is_readable($directory)) {
			$handle = opendir($directory);
			while (FALSE !== ($item = readdir($handle))) {
				if($item != '.' && $item != '..') {
					$path = $directory . '/' . $item;
					if(is_dir($path) and !is_link($path)) {
						recursive_remove_directory($path);
					} else {
						unlink($path);
					}
				}
			}
			closedir($handle);
			if($empty == FALSE) {
				if(!rmdir($directory)) {
					return FALSE;
				}
			}
		}
		return TRUE;
	}

	/* Get variables */
	if(!isset($_GET['next'])) $_GET['next'] = dbfuncString2Int($backLink);
	$classindex       = safe(dbfuncInt2String($_GET['key']));
	$student_username = safe(dbfuncInt2String($_GET['key2']));

	$MAX_SIZE = 10*1024*1024;

	include "core/settermandyear.php";
	if(isset($_GET['key3'])) $termindex = safe(dbfuncInt2String($_GET['key3']));

	/* Check whether subject is open for report editing */
	$query =	"SELECT class_term.AverageType, class_term.EffortType, class_term.ConductType, " .
				"       class_term.AverageTypeIndex, class_term.EffortTypeIndex, " .
				"       class_term.ConductTypeIndex, class_term.CTCommentType, " .
				"       class_term.HODCommentType, class_term.PrincipalCommentType, " .
				"       class_term.CanDoReport, class_term.AbsenceType, " .
				"       class_term.ReportTemplate, class_term.ReportTemplateType, " .
				"       class.ClassName, " .
				"       MIN(classterm.ReportDone) AS ReportDone " .
				"       FROM class_term, class, classterm, classlist " .
				"WHERE class_term.ClassIndex    = $classindex " .
				"AND   class_term.TermIndex     = $termindex " .
				"AND   classlist.ClassIndex     = $classindex " .
				"AND   classterm.ClassListIndex = classlist.ClassListIndex " .
				"AND   classterm.TermIndex      = $termindex " .
				"AND   class.ClassIndex         = classlist.ClassIndex " .
				"GROUP BY class_term.ClassIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or (!$row['CanDoReport'] and !$row['ReportDone'])) {
		/* Print error message */
		include "header.php";
		echo "      <p>Reports for this class aren't open.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	if(is_null($row['ReportTemplate'])) {
		/* Print error message */
		include "header.php";
		echo "      <p>There's no report template for this class.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);

	}

	$average_type         = $row['AverageType'];
	$absence_type         = $row['AbsenceType'];
	$effort_type          = $row['EffortType'];
	$conduct_type         = $row['ConductType'];
	$ct_comment_type      = $row['CTCommentType'];
	$hod_comment_type     = $row['HODCommentType'];
	$pr_comment_type      = $row['PrincipalCommentType'];
	$can_do_report        = $row['CanDoReport'];
	$average_type_index   = $row['AverageTypeIndex'];
	$effort_type_index    = $row['EffortTypeIndex'];
	$conduct_type_index   = $row['ConductTypeIndex'];
	$class_name           = $row['ClassName'];
	$report_template     =& $row['ReportTemplate'];
	$report_template_type = $row['ReportTemplateType'];

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
	$res =&  $db->query("SELECT hod.Username FROM hod, class " .
						"WHERE hod.Username        = '$username' " .
						"AND   hod.DepartmentIndex = class.DepartmentIndex " .
						"AND   class.ClassIndex    = $classindex");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	if(!$is_hod and !$is_principal and !$is_admin) {
		/* Print error message */
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Error";

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_modify.php", $LOG_DENIED_ACCESS,
					"Tried to modify class report for $student_name.");

		include "footer.php";
		exit(0);
	}

	$query =	"SELECT user.Gender, user.FirstName, user.Surname, term.TermName, " .
				"       huser.Title AS HODTitle, huser.FirstName AS HODFirstName, " .
				"       huser.Surname AS HODSurname, " .
				"       tuser.Title AS CTTitle, tuser.FirstName AS CTFirstName, " .
				"       tuser.Surname AS CTSurname, " .
				"       puser.Title AS PrincipalTitle, puser.FirstName AS PrincipalFirstName, " .
				"       puser.Surname AS PrincipalSurname, " .
				"       year.Year, " .
				"       classterm.Average, classterm.Conduct, classterm.Effort, " .
				"       classterm.Rank, classterm.CTComment, classterm.HODComment, " .
				"       classterm.CTCommentDone, classterm.HODCommentDone, " .
				"       classterm.PrincipalComment, classterm.PrincipalCommentDone, " .
				"       classterm.PrincipalUsername, classterm.HODUsername, " .
				"       classterm.ReportDone, classterm.ReportProofread, " .
				"       classterm.ReportProofDone, classterm.Absences, " .
				"       average_index.Display AS AverageDisplay, " .
				"       effort_index.Display AS EffortDisplay, " .
				"       conduct_index.Display AS ConductDisplay " .
				"       FROM user, term, year, " .
				"            (class INNER JOIN classlist ON class.ClassIndex = $classindex " .
				"                                        AND classlist.ClassIndex = $classindex) " .
				"             INNER JOIN classterm USING (ClassListIndex) " .
				"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
				"            classterm.Average = average_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
				"            classterm.Effort = effort_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
				"            classterm.Conduct = conduct_index.NonmarkIndex " .
				"       LEFT OUTER JOIN user AS huser ON " .
				"            classterm.HODUsername = huser.Username " .
				"       LEFT OUTER JOIN user AS puser ON " .
				"            classterm.PrincipalUsername = puser.Username " .
				"       LEFT OUTER JOIN user AS tuser ON " .
				"            class.ClassTeacherUsername = tuser.Username " .
				"WHERE classlist.Username       = '$student_username' " .
				"AND   user.Username            = '$student_username' " .
				"AND   classterm.TermIndex      = $termindex " .
				"AND   term.TermIndex           = $termindex " .
				"AND   year.YearIndex           = class.YearIndex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		/* Print error message */
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Error";
		include "header.php";                                      // Show header

		echo "      <p>$student_name is not in $class.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";

		include "footer.php";
		exit(0);
	}
	
	$student_info = $row;

	header("Content-type: $report_template_type");
	header("Content-disposition: attachment; filename=report.odt");

	// Extract odt template
	$retval = 0;
	$tempdir = tempnam("/tmp", "lesson");
	unlink($tempdir);

	$zip_handle = fopen("$tempdir.odt", "w");
	fwrite($zip_handle, $report_template);
	fclose($zip_handle);
	unset($report_template);  // Free up loads of RAM (hopefully)
	
	mkdir($tempdir, 0700);
	$output = array();
	$output = exec("/usr/bin/unzip $tempdir.odt -d $tempdir", $output, $retval);
	unset($output);

	// Read template
	$handle = fopen("$tempdir/content.xml", "r");
	$data = fread($handle, $MAX_SIZE);
	fclose($handle);

	/* Work out average string */
	if($average_type == $CLASS_AVG_TYPE_NONE) {
		$average = "N/A";
	} elseif($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC) {
		if($student_info['Average'] == -1) {
			$average = "N/A";
		} else {
			$scorestr = round($student_info['Average']);
			$average = "$scorestr%";
		}
	} elseif($average_type == $CLASS_AVG_TYPE_INDEX) {
		$scoreindex = $student_info['Average'];
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE NonmarkIndex=$scoreindex";
		$nres =& $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());

		if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$average = $nrow['Display'];
		} else {
			$average = "N/A";
		}
	} else {
		$average = "N/A";
	}

	if($effort_type == $CLASS_EFFORT_TYPE_NONE) {
		$effort = "N/A";
	} elseif($effort_type == $CLASS_EFFORT_TYPE_PERCENT or $effort_type == $CLASS_EFFORT_TYPE_CALC) {
		if($student_info['Effort'] == -1) {
			$effort = "N/A";
		} else {
			$scorestr = round($student_info['Effort']);
			$effort = "$scorestr%";
		}
	} elseif($effort_type == $CLASS_EFFORT_TYPE_INDEX) {
		$scoreindex = $student_info['Effort'];
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE NonmarkIndex=$scoreindex";
		$nres =& $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());

		if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$effort = $nrow['Display'];
		} else {
			$effort = "N/A";
		}
	} else {
		$effort = "N/A";
	}

	if($conduct_type == $CLASS_CONDUCT_TYPE_NONE) {
		$conduct = "N/A";
	} elseif($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT or $conduct_type == $CLASS_CONDUCT_TYPE_CALC) {
		if($student_info['Conduct'] == -1) {
			$conduct = "N/A";
		} else {
			$scorestr = round($student_info['Conduct']);
			$conduct = "$scorestr%";
		}
	} elseif($conduct_type == $CLASS_CONDUCT_TYPE_INDEX) {
		$scoreindex = $student_info['Conduct'];
		$query =	"SELECT Input, Display FROM nonmark_index " .
					"WHERE NonmarkIndex=$scoreindex";
		$nres =& $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());

		if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$conduct = $nrow['Display'];
		} else {
			$conduct = "N/A";
		}
	} elseif($conduct_type == $CLASS_CONDUCT_TYPE_PUN) {
		$query =	"SELECT Conduct FROM classlist, classterm, class " .
					"WHERE class.YearIndex = $yearindex " .
					"AND   classterm.ClassIndex = class.ClassIndex " .
					"AND   classterm.TermIndex = $termindex " .
					"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classlist.Username = '{$student_info['Username']}'";
		$nres =& $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());

		if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$scorestr = round($nrow['Conduct']);
			$conduct = "$scorestr%";
		} else {
			$conduct = "N/A";
		}
	} else {
		$conduct = "N/A";
	}

	if($absence_type == $ABSENCE_TYPE_NONE) {
		$absences = "N/A";
	} elseif($absence_type == $ABSENCE_TYPE_NUM) {
		if($student_info['Absences'] == -1) {
			$absences = "0";
		} else {
			$scorestr = $student_info['Absences'];
			$absences = "$scorestr";
		}
	} elseif($absence_type == $ABSENCE_TYPE_CALC) {
			$absent    = 0;
			$late      = 0;
			$suspended = 0;

			$nquery =   "SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
						"       FROM view_attendance " .
						"WHERE  Username = '$student_username' " .
						"AND    YearIndex = $yearindex " .
						"AND    TermIndex = $termindex " .
						"AND    Period = 1 " .
						"AND    AttendanceTypeIndex > 0 " .
						"GROUP BY AttendanceTypeIndex ";
			$cRes =&   $db->query($nquery);
			if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
			while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
				if($cRow['AttendanceTypeIndex'] == $ATT_ABSENT)    $absent    = $cRow['Count'];
				if($cRow['AttendanceTypeIndex'] == $ATT_LATE)      $late      = $cRow['Count'];
				if($cRow['AttendanceTypeIndex'] == $ATT_SUSPENDED) $suspended = $cRow['Count'];
			}
			$absences = $absent + $suspended;
	} else {
		$absences = "N/A";
	}

	if($ct_comment_type == $COMMENT_TYPE_NONE) {
		$ct_comment = "N/A";
	} elseif($ct_comment_type == $COMMENT_TYPE_MANDATORY or
		     $ct_comment_type == $COMMENT_TYPE_OPTIONAL) {
		$ct_comment = htmlspecialchars($student_info['CTComment'], ENT_QUOTES);
	}

	if($hod_comment_type == $COMMENT_TYPE_NONE) {
		$hod_comment = "N/A";
	} elseif($hod_comment_type == $COMMENT_TYPE_MANDATORY or
		     $hod_comment_type == $COMMENT_TYPE_OPTIONAL) {
		$hod_comment = htmlspecialchars($student_info['HODComment'], ENT_QUOTES);
	}

	if($pr_comment_type == $COMMENT_TYPE_NONE) {
		$pr_comment = "N/A";
	} elseif($pr_comment_type == $COMMENT_TYPE_MANDATORY or
		     $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
		$pr_comment = htmlspecialchars($student_info['PrincipalComment'], ENT_QUOTES);
	}

	// Replace obvious data points
	$student_name = "{$student_info['FirstName']} {$student_info['Surname']}";
	$ct_name  = "{$student_info['CTTitle']} {$student_info['CTFirstName']} {$student_info['CTSurname']}";
	$hod_name = "{$student_info['HODTitle']} {$student_info['HODFirstName']} {$student_info['HODSurname']}";
	$pr_name  = "{$student_info['PrincipalTitle']} {$student_info['PrincipalFirstName']} " .
	            "{$student_info['PrincipalSurname']}";
	$data = str_replace("&lt;&lt;name&gt;&gt;", "$student_name", $data);
	$data = str_replace("&lt;&lt;term&gt;&gt;", $student_info['TermName'], $data);
	$data = str_replace("&lt;&lt;year&gt;&gt;", $student_info['Year'], $data);
	$data = str_replace("&lt;&lt;class&gt;&gt;", $class_name, $data);
	$data = str_replace("&lt;&lt;average&gt;&gt;", $average, $data);
	$data = str_replace("&lt;&lt;conduct&gt;&gt;", $conduct, $data);
	$data = str_replace("&lt;&lt;effort&gt;&gt;", $effort, $data);
	$data = str_replace("&lt;&lt;absences&gt;&gt;", $absences, $data);
	$data = str_replace("&lt;&lt;class_teacher&gt;&gt;", $ct_name, $data);
	$data = str_replace("&lt;&lt;head_of_department&gt;&gt;", $hod_name, $data);
	$data = str_replace("&lt;&lt;principal&gt;&gt;", $pr_name, $data);
	$data = str_replace("&lt;&lt;class_teacher_comment&gt;&gt;", $ct_comment, $data);
	$data = str_replace("&lt;&lt;head_of_department_comment&gt;&gt;", $hod_comment, $data);
	$data = str_replace("&lt;&lt;principal_comment&gt;&gt;", $pr_comment, $data);

	// Grab table row for first table that contains <<subject>>
	$pos = strpos($data, "&lt;&lt;subject_name&gt;&gt;");
	if($pos === false) {
		$pos = strpos($data, "&lt;&lt;subject_shortname&gt;&gt;");
	}
	if($pos === false) {
		$pos = strpos($data, "&lt;&lt;subject_strippedname&gt;&gt;");
	}
	if ($pos > 0) {
		$startpos = strrpos(substr($data, 0, $pos), "<table:table-row");
		$endpos   = $pos + strpos(substr($data, $pos), "</table:table-row>") + strlen("</table:table-row>");
		$length = $endpos - $startpos;
		$data_row = substr($data, $startpos, $length);
		$rep = "";

		/* Get per-subject information */
		$query =	"SELECT subject.Name AS SubjectName, subject.ShortName, subject.SubjectIndex, " .
					"       subject.Average AS SubjectAverage, " .
					"       subjectstudent.Average, subjectstudent.Effort, subjectstudent.Conduct, " .
					"       average_index.Display AS AverageDisplay, " .
					"       effort_index.Display AS EffortDisplay, " .
					"       conduct_index.Display AS ConductDisplay, " .
					"       subject.AverageType, subject.EffortType, subject.ConductType, " .
					"       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
					"       subject.ConductTypeIndex, subject.CommentType, " .
					"       subjectstudent.Comment, subjectstudent.CommentValue, " .
					"       subjectstudent.ReportDone " .
					"       FROM subject, subjecttype, class, subjectstudent " .
					"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
					"            subjectstudent.Average = average_index.NonmarkIndex " .
					"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
					"            subjectstudent.Effort = effort_index.NonmarkIndex " .
					"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
					"            subjectstudent.Conduct = conduct_index.NonmarkIndex " .
					"WHERE subjectstudent.Username      = '$student_username' " .
					"AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
					"AND   subject.TermIndex            = $termindex " .
					"AND   subject.YearIndex            = class.YearIndex " .
					"AND   subject.ShowInList           = 1 " .
					"AND   class.ClassIndex             = $classindex " .
					"AND   subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
					"ORDER BY subject.AverageType DESC, subjecttype.Weight DESC, " .
					"         subjecttype.Title, subject.Name, subject.SubjectIndex";
	
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($row['AverageType'] == $AVG_TYPE_NONE) {
				$average = "N/A";
				$subject_average = "N/A";
			} elseif($row['AverageType'] == $AVG_TYPE_PERCENT) {
				if($row['Average'] == -1) {
					$average = "N/A";
				} else {
					$average = round($row['Average']);
					$average = "$average%";
				}
				if($row['SubjectAverage'] == -1) {
					$subject_average = "N/A";
				} else {
					$subject_average = round($row['SubjectAverage']);
					$subject_average = "$subject_average%";
				}
			} elseif($row['AverageType'] == $AVG_TYPE_INDEX) {
				if(is_null($row['AverageDisplay'])) {
					$average = "N/A";
				} else {
					$average = $row['AverageDisplay'];
				}
				$subject_average = "N/A";
			} else {
				$average = "N/A";
				$subject_average = "N/A";
			}
	
			if($row['EffortType'] == $EFFORT_TYPE_NONE) {
				$effort = "N/A";
			} elseif($row['EffortType'] == $EFFORT_TYPE_PERCENT) {
				if($row['Effort'] == -1) {
					$effort = "N/A";
				} else {
					$effort = round($row['Effort']);
					$effort = "$effort%";
				}
			} elseif($row['EffortType'] == $EFFORT_TYPE_INDEX) {
				if(is_null($row['EffortDisplay'])) {
					$effort = "N/A";
				} else {
					$effort = $row['EffortDisplay'];
				}
			} else {
				$effort = "N/A";
			}
		
			if($row['ConductType'] == $CONDUCT_TYPE_NONE) {
				$conduct = "N/A";
			} elseif($row['ConductType'] == $CONDUCT_TYPE_PERCENT) {
				if($row['Conduct'] == -1) {
					$conduct = "&nbsp;";
				} else {
					$conduct = round($row['Conduct']);
					$conduct = "$conduct%";
				}
			} elseif($row['ConductType'] == $CONDUCT_TYPE_INDEX) {
				if(is_null($row['ConductDisplay'])) {
					$conduct = "&nbsp;";
				} else {
					$conduct = $row['ConductDisplay'];
				}
			} else {
				$conduct = "N/A";
			}
	
			if($row['CommentType'] == $COMMENT_TYPE_NONE) {
				$comment = "N/A";
			} elseif($row['CommentType'] == $COMMENT_TYPE_MANDATORY or
					$row['CommentType'] == $COMMENT_TYPE_OPTIONAL) {
				if(!is_null($row['Comment'])) {
					$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				} else {
					$comment = "";
				}
			} else {
				$comment = "N/A";
			}

			$stripped_name = trim(str_replace($class_name, "", $row['SubjectName']));

			$reprow = str_replace("&lt;&lt;subject_name&gt;&gt;",         $row['SubjectName'], $data_row);
			$reprow = str_replace("&lt;&lt;subject_shortname&gt;&gt;",    $row['ShortName'],   $reprow);
			$reprow = str_replace("&lt;&lt;subject_strippedname&gt;&gt;", $stripped_name,      $reprow);
			$reprow = str_replace("&lt;&lt;subject_average&gt;&gt;",      $subject_average,    $reprow);
			$reprow = str_replace("&lt;&lt;subject_mark&gt;&gt;",         $average,            $reprow);
			$reprow = str_replace("&lt;&lt;subject_effort&gt;&gt;",       $effort,             $reprow);
			$reprow = str_replace("&lt;&lt;subject_conduct&gt;&gt;",      $conduct,            $reprow);
			$reprow = str_replace("&lt;&lt;subject_comment&gt;&gt;",      $comment,            $reprow);
			$rep .= $reprow;
		}
		$data = str_replace($data_row, $rep, $data);
	}

	// Write back to temporary odt
	$handle = fopen("$tempdir/content.xml", "w");
	$data = fwrite($handle, $data);
	fclose($handle);

	$output = array();
	$output = exec("cd $tempdir; /usr/bin/zip -DXr $tempdir.odt *", $output, $retval);
	unset($output);

	// Output temporary odt
	readfile("$tempdir.odt");

	// Remove temporary files
	unlink("$tempdir.odt");
	recursive_remove_directory($tempdir);
?>

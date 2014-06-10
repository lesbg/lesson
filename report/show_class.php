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
	$classtermindex       = safe(dbfuncInt2String($_GET['key']));

	$MAX_SIZE = 10*1024*1024;

	include "core/settermandyear.php";
	if(isset($_GET['key3'])) $termindex = safe(dbfuncInt2String($_GET['key3']));

	/* Check whether subject is open for report editing */
	$query =	"SELECT classterm.Average, classterm.Effort, classterm.Conduct, " .
				"       classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
				"       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
				"       classterm.ConductTypeIndex, classterm.CTCommentType, " .
				"       classterm.HODCommentType, classterm.PrincipalCommentType, " .
				"       classterm.CanDoReport, classterm.AbsenceType, " .
				"       classterm.ReportTemplate, classterm.ReportTemplateType, " .
				"       class.ClassName, class.Grade, class.ClassIndex, " .
				"       MIN(classlist.ReportDone) AS ReportDone " .
				"       FROM classterm, class, classlist " .
				"WHERE classterm.ClassTermIndex    = $classtermindex " .
				"AND   classlist.ClassTermIndex    = classterm.ClassTermIndex " .
				"AND   class.ClassIndex            = classterm.ClassIndex " .
				"GROUP BY classterm.ClassIndex";
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

	$class_average = $row['Average'];
	if($class_average == -1) {
		$class_average = "-";
	} else {
		$scorestr = round($class_average);
		$class_average = "$scorestr%";
	}
	$class_conduct = $row['Conduct'];
	if($class_conduct == -1) {
		$class_conduct = "-";
	} else {
		$scorestr = round($class_conduct);
		$class_conduct = "$scorestr%";
	}
	$class_effort = $row['Effort'];
	if($class_effort == -1) {
		$class_effort = "-";
	} else {
		$scorestr = round($class_effort);
		$class_effort = "$scorestr%";
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
	$class_grade          = $row['Grade'];
	$report_template     =& $row['ReportTemplate'];
	$report_template_type = $row['ReportTemplateType'];
	$class_index          = $row['ClassIndex'];

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

	if(!$is_hod and !$is_principal and !$is_admin) {
		/* Print error message */
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Error";

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "report/show_all.php", $LOG_DENIED_ACCESS,
					"Tried to display reports.");

		include "footer.php";
		exit(0);
	}

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
	$orig_data = fread($handle, $MAX_SIZE);
	fclose($handle);

	$header = substr($orig_data, 0, strpos($orig_data, "<office:body>") + 13);
	$footer = substr($orig_data, strpos($orig_data, "</office:body>"));
	$orig_data = substr($orig_data, strlen($header), strlen($orig_data) - (strlen($header)+strlen($footer)));

	$query =	"SELECT user.Username, user.Gender, user.FirstName, user.Surname, term.TermName, " .
				"       user.User1, user.User2, user.User3, " .
				"       huser.Title AS HODTitle, huser.FirstName AS HODFirstName, " .
				"       huser.Surname AS HODSurname, " .
				"       tuser.Title AS CTTitle, tuser.FirstName AS CTFirstName, " .
				"       tuser.Surname AS CTSurname, " .
				"       puser.Title AS PrincipalTitle, puser.FirstName AS PrincipalFirstName, " .
				"       puser.Surname AS PrincipalSurname, " .
				"       year.Year, term.DepartmentIndex, " .
				"       classlist.Average, classlist.Conduct, classlist.Effort, " .
				"       classlist.Rank, classlist.CTComment, classlist.HODComment, " .
				"       classlist.CTCommentDone, classlist.HODCommentDone, " .
				"       classlist.PrincipalComment, classlist.PrincipalCommentDone, " .
				"       classlist.PrincipalUsername, classlist.HODUsername, " .
				"       classlist.ReportDone, classlist.ReportProofread, " .
				"       classlist.ReportProofDone, classlist.Absences, " .
				"       COUNT(bclasslist.Username) AS ClassCount, " .
				"       average_index.Display AS AverageDisplay, " .
				"       effort_index.Display AS EffortDisplay, " .
				"       conduct_index.Display AS ConductDisplay " .
				"       FROM user, term, year, classlist AS bclasslist, " .
				"            (classterm INNER JOIN classlist ON classterm.ClassTermIndex = $classtermindex " .
				"                                            AND classlist.ClassTermIndex = $classtermindex) " .
				"             INNER JOIN class USING (ClassIndex) " .
				"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
				"            classlist.Average = average_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
				"            classlist.Effort = effort_index.NonmarkIndex " .
				"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
				"            classlist.Conduct = conduct_index.NonmarkIndex " .
				"       LEFT OUTER JOIN user AS huser ON " .
				"            classlist.HODUsername = huser.Username " .
				"       LEFT OUTER JOIN user AS puser ON " .
				"            classlist.PrincipalUsername = puser.Username " .
				"       LEFT OUTER JOIN user AS tuser ON " .
				"            class.ClassTeacherUsername = tuser.Username " .
				"WHERE bclasslist.ClassTermIndex = classlist.ClassTermIndex " .
				"AND   user.Username             = classlist.Username " .
				"AND   term.TermIndex            = classterm.TermIndex " .
				"AND   year.YearIndex            = class.YearIndex " .
				"GROUP BY classlist.Username " .
				"ORDER BY user.FirstName, user.Surname, user.Username";
	$sres =& $db->query($query);
	if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query

	$final_data = "";

	while ($student_info =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
		$data = $orig_data;

		/* Work out rank */
		$rank = "-";
		if($student_info['Rank'] != -1) {
			$rank = "{$student_info['Rank']}";
		}

		/* Work out average string */
		if($average_type == $CLASS_AVG_TYPE_NONE) {
			$average = "-";
		} elseif($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC) {
			if($student_info['Average'] == -1) {
				$average = "-";
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
				$average = "-";
			}
		} else {
			$average = "-";
		}
	
		if($effort_type == $CLASS_EFFORT_TYPE_NONE) {
			$effort = "-";
		} elseif($effort_type == $CLASS_EFFORT_TYPE_PERCENT or $effort_type == $CLASS_EFFORT_TYPE_CALC) {
			if($student_info['Effort'] == -1) {
				$effort = "-";
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
				$effort = "-";
			}
		} else {
			$effort = "-";
		}
	
		if($conduct_type == $CLASS_CONDUCT_TYPE_NONE) {
			$conduct = "-";
		} elseif($conduct_type == $CLASS_CONDUCT_TYPE_PERCENT or $conduct_type == $CLASS_CONDUCT_TYPE_CALC or $conduct_type == $CLASS_CONDUCT_TYPE_PERCENT or $conduct_type == $CLASS_CONDUCT_TYPE_CALC or $conduct_type == $CLASS_CONDUCT_TYPE_PUN) {
			if($student_info['Conduct'] == -1) {
				$conduct = "-";
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
				$conduct = "-";
			}
		} else {
			$conduct = "-";
		}
	
		$late = 0;
		$suspended = 0;
		if($absence_type == $ABSENCE_TYPE_NONE) {
			$absences = "-";
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

				$nquery =	"SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
							"       FROM attendance INNER JOIN subject USING (SubjectIndex) " .
							"       INNER JOIN period USING (PeriodIndex) " .
							"WHERE  attendance.Username = '{$student_info['Username']}' " .
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
				$absences = $absent + $suspended;
		} else {
			$absences = "-";
		}
	
		if($ct_comment_type == $COMMENT_TYPE_NONE) {
			$ct_comment = "-";
		} elseif($ct_comment_type == $COMMENT_TYPE_MANDATORY or
				 $ct_comment_type == $COMMENT_TYPE_OPTIONAL) {
			$ct_comment = htmlspecialchars($student_info['CTComment'], ENT_QUOTES);
		}
	
		if($hod_comment_type == $COMMENT_TYPE_NONE) {
			$hod_comment = "-";
		} elseif($hod_comment_type == $COMMENT_TYPE_MANDATORY or
				 $hod_comment_type == $COMMENT_TYPE_OPTIONAL) {
			$hod_comment = htmlspecialchars($student_info['HODComment'], ENT_QUOTES);
		}
	
		if($pr_comment_type == $COMMENT_TYPE_NONE) {
			$pr_comment = "-";
		} elseif($pr_comment_type == $COMMENT_TYPE_MANDATORY or
				 $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
			$pr_comment = htmlspecialchars($student_info['PrincipalComment'], ENT_QUOTES);
		}
	
		/* Get overall averages */
		$query =	"SELECT term.TermNumber, classlist.Rank, " .
					"       classlist.Average, classterm.Average AS ClassAverage, classterm.AverageType, " .
					"       classlist.Conduct, classterm.Conduct AS ClassConduct, classterm.ConductType, " .
					"       classlist.CTComment, classlist.HODComment, classlist.PrincipalComment, " .
					"       get_term_weight(term.TermIndex, classterm.ClassIndex, '{$student_info['Username']}') AS Weight FROM " .
					" (term INNER JOIN term AS depterm " .
					"       ON  term.DepartmentIndex = depterm.DepartmentIndex" .
					"       AND depterm.TermIndex = $termindex" .
					"       AND term.TermNumber <= depterm.TermNumber) " .
					" INNER JOIN " .
					" (classlist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
					"       ON  classlist.Username = '{$student_info['Username']}' " .
					"       AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"       AND class.YearIndex = $yearindex) " .
					" ON term.TermIndex = classterm.TermIndex " .
					"ORDER BY term.TermNumber";
					
		$cRes =&   $db->query($query);
		if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
		
		$ovl_average = 0;
		$ovl_average_max = 0;
		$cls_ovl_average = 0;
		$cls_ovl_average_max = 0;
	
		$ovl_conduct = 0;
		$ovl_conduct_max = 0;
		$cls_ovl_conduct = 0;
		$cls_ovl_conduct_max = 0;
		
		while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
			$term_weight = $cRow['Weight'];
	
			$term_average = "";
			$term_rank = "";
			$class_term_average = "";
			
			$term_conduct = "";
			$class_term_conduct = "";

			if($cRow['AverageType'] == $CLASS_AVG_TYPE_PERCENT or $cRow['AverageType'] == $CLASS_AVG_TYPE_CALC) {
				if($cRow['Average'] != -1 and !is_null($cRow['Average'])) {
					$term_average     = round($cRow['Average']);
					$ovl_average     += ($term_average * $term_weight);
					$ovl_average_max += 100 * $term_weight;
					
					$term_average     = "$term_average%";
					$term_rank        = $cRow['Rank'];
				}
				if($cRow['ClassAverage'] != -1 and !is_null($cRow['ClassAverage'])) {
					$class_term_average   = round($cRow['ClassAverage']);
					$cls_ovl_average     += ($class_term_average * $term_weight);
					$cls_ovl_average_max += 100 * $term_weight;
					
					$class_term_average   = "$class_term_average%";
				}
			} elseif($cRow['AverageType'] == $CLASS_AVG_TYPE_INDEX) {
				// TODO: Make this work
				$term_average = "";
				$class_term_average = "";
			} else {
				$term_average = "";
				$class_term_average = "";			
			}
			
			if($cRow['ConductType'] == $CLASS_CONDUCT_TYPE_PERCENT or $cRow['ConductType'] == $CLASS_CONDUCT_TYPE_CALC
			                                                       or $cRow['ConductType'] == $CLASS_CONDUCT_TYPE_PUN) {
				if($cRow['Conduct'] != -1 and !is_null($cRow['Conduct'])) {
					$term_conduct     = round($cRow['Conduct']);
					$ovl_conduct     += ($term_conduct * $term_weight);
					$ovl_conduct_max += 100 * $term_weight;
					
					$term_conduct     = "$term_conduct%";
				}
				if($cRow['ClassConduct'] != -1 and !is_null($cRow['ClassConduct'])) {
					$class_term_conduct   = round($cRow['ClassConduct']);
					$cls_ovl_conduct     += ($class_term_conduct * $term_weight);
					$cls_ovl_conduct_max += 100 * $term_weight;
					
					$class_term_conduct   = "$class_term_conduct%";
				}
			} elseif($cRow['ConductType'] == $CLASS_CONDUCT_TYPE_INDEX) {
				// TODO: Make this work
				$term_conduct = "";
				$class_term_conduct = "";
			} else {
				$term_conduct = "";
				$class_term_conduct = "";			
			}
			
			if($ct_comment_type == $COMMENT_TYPE_NONE) {
				$ct_comment = "-";
			} elseif($ct_comment_type == $COMMENT_TYPE_MANDATORY or
				     $ct_comment_type == $COMMENT_TYPE_OPTIONAL) {
				$ct_comment = htmlspecialchars($cRow['CTComment'], ENT_QUOTES);
			}
		
			if($hod_comment_type == $COMMENT_TYPE_NONE) {
				$hod_comment = "-";
			} elseif($hod_comment_type == $COMMENT_TYPE_MANDATORY or
				     $hod_comment_type == $COMMENT_TYPE_OPTIONAL) {
				$hod_comment = htmlspecialchars($cRow['HODComment'], ENT_QUOTES);
			}
		
			if($pr_comment_type == $COMMENT_TYPE_NONE) {
				$pr_comment = "-";
			} elseif($pr_comment_type == $COMMENT_TYPE_MANDATORY or
				     $pr_comment_type == $COMMENT_TYPE_OPTIONAL) {
				$pr_comment = htmlspecialchars($cRow['PrincipalComment'], ENT_QUOTES);
			}
			
			$termnum = $cRow['TermNumber'];
			$data = str_replace("&lt;&lt;average_t$termnum&gt;&gt;", htmlspecialchars($term_average, ENT_QUOTES), $data);
			$data = str_replace("&lt;&lt;class_average_t$termnum&gt;&gt;", htmlspecialchars($class_term_average, ENT_QUOTES), $data);
			$data = str_replace("&lt;&lt;conduct_t$termnum&gt;&gt;", htmlspecialchars($term_conduct, ENT_QUOTES), $data);
			$data = str_replace("&lt;&lt;class_conduct_t$termnum&gt;&gt;", htmlspecialchars($class_term_conduct, ENT_QUOTES), $data);
			$data = str_replace("&lt;&lt;rank_t$termnum&gt;&gt;", htmlspecialchars($term_rank, ENT_QUOTES), $data);
			$data = str_replace("&lt;&lt;class_teacher_comment_t$termnum&gt;&gt;", $ct_comment, $data);
			$data = str_replace("&lt;&lt;head_of_department_comment_t$termnum&gt;&gt;", $hod_comment, $data);
			$data = str_replace("&lt;&lt;principal_comment_t$termnum&gt;&gt;", $pr_comment, $data);	
		}
		
		$query =	"SELECT classlist.Username, term.TermNumber, term.TermIndex, " .
					"       ROUND(SUM(CONVERT(ROUND(classlist.Average * get_term_weight(term.TermIndex, class.ClassIndex, '{$student_info['Username']}')), DECIMAL)) / " .
					"                           SUM(get_term_weight(term.TermIndex, class.ClassIndex, '{$student_info['Username']}'))) AS Average FROM " .
					" (term INNER JOIN term AS depterm " .
					"  ON  term.DepartmentIndex = depterm.DepartmentIndex " .
					"  AND depterm.TermIndex = $termindex " .
					"  AND term.TermNumber <= depterm.TermNumber) " .
					" INNER JOIN " .
					" (classlist AS tclasslist INNER JOIN (classterm INNER JOIN class USING (ClassIndex)) " .
					"  ON  tclasslist.Username = '{$student_info['Username']}' " .
					"  AND tclasslist.ClassTermIndex = classterm.ClassTermIndex " .
					"  AND class.YearIndex = $yearindex) " .
					" ON term.TermIndex = classterm.TermIndex " .
					" INNER JOIN classlist " .
					"  ON classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"  AND classlist.Average > -1 " .
					" GROUP BY classlist.Username " .
					" ORDER BY Average DESC";
		$cRes =&   $db->query($query);
		if(DB::isError($cRes)) die($cRes->getDebugInfo());          // Check for errors in query
		
		$countrank = 0;
		$ovl_rank  = -1;
		$prevmark  = -1;
		$same      = 1;
		/* Student username may not show up if they don't have any marks in any subjects */
		while($cRow =& $cRes->fetchrow(DB_FETCHMODE_ASSOC)) {
			if($cRow['Average'] != $prevmark) {
				$countrank += $same;
				$same = 1;
			} else {
				$same += 1;
			}
			$prevmark = $cRow['Average'];
			
			if($cRow['Username'] == $student_info['Username']) {
				$ovl_rank = $countrank;
				break;
			}
		}
		if($ovl_rank == -1) {
			$ovl_rank = "-";
		} else { 
			$ovl_rank = "$ovl_rank";
		}
		
		# Remove fields for terms not used yet
		$query = "SELECT TermNumber FROM term WHERE DepartmentIndex=$depindex";
		$nres =&  $db->query($query);
		if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		while ($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$termnum = $nrow["TermNumber"];
			$data = str_replace("&lt;&lt;average_t$termnum&gt;&gt;",                    "", $data);
			$data = str_replace("&lt;&lt;rank_t$termnum&gt;&gt;",                       "", $data);
			$data = str_replace("&lt;&lt;class_average_t$termnum&gt;&gt;",              "", $data);
			$data = str_replace("&lt;&lt;conduct_t$termnum&gt;&gt;",                    "", $data);
			$data = str_replace("&lt;&lt;class_conduct_t$termnum&gt;&gt;",              "", $data);
			$data = str_replace("&lt;&lt;class_teacher_comment_t$termnum&gt;&gt;",      "", $data);
			$data = str_replace("&lt;&lt;head_of_department_comment_t$termnum&gt;&gt;", "", $data);
			$data = str_replace("&lt;&lt;principal_comment_t$termnum&gt;&gt;",          "", $data);
		}

	
		if($ovl_average_max > 0) {
			$scorestr = round($ovl_average * 100 / $ovl_average_max);
			$ovl_average = "$scorestr%";
		} else {
			$ovl_average = "-";
		}
		if($cls_ovl_average_max > 0) {
			$scorestr = round($cls_ovl_average * 100 / $cls_ovl_average_max);
			$cls_ovl_average = "$scorestr%";
		} else {
			$cls_ovl_average = "-";
		}
		if($ovl_conduct_max > 0) {
			$scorestr = round($ovl_conduct * 100 / $ovl_conduct_max);
			$ovl_conduct = "$scorestr%";
		} else {
			$ovl_conduct = "-";
		}
		if($cls_ovl_conduct_max > 0) {
			$scorestr = round($cls_ovl_conduct * 100 / $cls_ovl_conduct_max);
			$cls_ovl_conduct = "$scorestr%";
		} else {
			$cls_ovl_conduct = "-";
		}
		
		// Replace obvious data points
		$depindex = $student_info['DepartmentIndex'];
		$student_name = "{$student_info['FirstName']} {$student_info['Surname']}";
		$ct_name  = "{$student_info['CTTitle']} {$student_info['CTFirstName']} {$student_info['CTSurname']}";
		$hod_name = "{$student_info['HODTitle']} {$student_info['HODFirstName']} {$student_info['HODSurname']}";
		$pr_name  = "{$student_info['PrincipalTitle']} {$student_info['PrincipalFirstName']} " .
					"{$student_info['PrincipalSurname']}";
		$class_count = "{$student_info['ClassCount']}";
		$data = str_replace("&lt;&lt;name&gt;&gt;", htmlspecialchars($student_name, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;username&gt;&gt;", htmlspecialchars($student_info['Username'], ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;term&gt;&gt;", htmlspecialchars($student_info['TermName'], ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;year&gt;&gt;", htmlspecialchars($student_info['Year'], ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class&gt;&gt;", htmlspecialchars($class_name, ENT_QUOTES), $data);

		// Temporary class fix for LESL
		if($student_info['User2'] == 1) {
			if($student_info['User3'] == 1) {
				$grade = $class_grade + 1;
			} else {
				$grade = $class_grade;
			}
			$query = "SELECT GradeName FROM grade WHERE Grade=$grade";
			$nres =& $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());
	
			if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				$hacked_class = "Grade {$nrow['GradeName']}";
			} else {
				$hacked_class = "Unknown";
			}
		} else {
			$hacked_class = $class_name;
		}
		$data = str_replace("&lt;&lt;hacked_class&gt;&gt;", htmlspecialchars($hacked_class, ENT_QUOTES), $data);
		// End Temporary fix

		$data = str_replace("&lt;&lt;class_count&gt;&gt;", htmlspecialchars($class_count, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;average&gt;&gt;", htmlspecialchars($average, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_average&gt;&gt;", htmlspecialchars($class_average, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;overall_average&gt;&gt;", htmlspecialchars($ovl_average, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;overall_rank&gt;&gt;", htmlspecialchars($ovl_rank, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_overall_average&gt;&gt;", htmlspecialchars($cls_ovl_average, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;overall_conduct&gt;&gt;", htmlspecialchars($ovl_conduct, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_overall_conduct&gt;&gt;", htmlspecialchars($cls_ovl_conduct, ENT_QUOTES), $data);	
		$data = str_replace("&lt;&lt;rank&gt;&gt;", htmlspecialchars($rank, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;conduct&gt;&gt;", htmlspecialchars($conduct, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_conduct&gt;&gt;", htmlspecialchars($class_conduct, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;effort&gt;&gt;", htmlspecialchars($effort, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_effort&gt;&gt;", htmlspecialchars($class_effort, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;absences&gt;&gt;", htmlspecialchars($absences, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;late&gt;&gt;", htmlspecialchars($late, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;class_teacher&gt;&gt;", htmlspecialchars($ct_name, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;head_of_department&gt;&gt;", htmlspecialchars($hod_name, ENT_QUOTES), $data);
		$data = str_replace("&lt;&lt;principal&gt;&gt;", htmlspecialchars($pr_name, ENT_QUOTES), $data);
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
		if($pos === false) {
			$pos = strpos($data, "&lt;&lt;subject_type&gt;&gt;");
		}
		while ($pos != false) {
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
						"       subjectstudent.ReportDone, classterm.TermIndex, " .
						"       class.ClassIndex, subjecttype.SubjectTypeIndex, " .
						"       subjecttype.Title AS SubjectType, " .
						"       get_weight(subject.SubjectIndex, $class_index, '{$student_info['Username']}') AS Weight " .
						"       FROM subject, subjecttype, class, classterm, subjectstudent " .
						"       LEFT OUTER JOIN nonmark_index AS average_index ON " .
						"            subjectstudent.Average = average_index.NonmarkIndex " .
						"       LEFT OUTER JOIN nonmark_index AS effort_index ON " .
						"            subjectstudent.Effort = effort_index.NonmarkIndex " .
						"       LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
						"            subjectstudent.Conduct = conduct_index.NonmarkIndex " .
						"WHERE subjectstudent.Username      = '{$student_info['Username']}' " .
						"AND   subjectstudent.SubjectIndex  = subject.SubjectIndex " .
						"AND   subject.TermIndex            = classterm.TermIndex " .
						"AND   subject.YearIndex            = class.YearIndex " .
						"AND   subject.ShowInList           = 1 " .
						"AND   (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE) " .
						"AND   class.ClassIndex             = classterm.ClassIndex " .
						"AND   classterm.ClassTermIndex     = $classtermindex " .
						"AND   subjecttype.SubjectTypeIndex = subject.SubjectTypeIndex " .
						"ORDER BY subjecttype.HighPriority DESC, get_weight(subject.SubjectIndex, $class_index, '{$student_info['Username']}') DESC, " .
						"         subjecttype.Title, subject.Name, subject.SubjectIndex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($row['AverageType'] == $AVG_TYPE_PERCENT) {
					$weight = "${row['Weight']}";
				} else {
					$weight = "-";
				}
				if($row['AverageType'] == $AVG_TYPE_NONE) {
					$average = "-";
					$subject_average = "-";
				} elseif($row['AverageType'] == $AVG_TYPE_PERCENT) {
					if($row['Average'] == -1) {
						$average = "-";
					} else {
						$average = round($row['Average']);
						$average = "$average%";
					}
					if($row['SubjectAverage'] == -1) {
						$subject_average = "-";
					} else {
						$subject_average = round($row['SubjectAverage']);
						$subject_average = "$subject_average%";
					}
				} elseif($row['AverageType'] == $AVG_TYPE_INDEX or $row['AverageType'] == $AVG_TYPE_GRADE) {
					if(is_null($row['AverageDisplay'])) {
						$average = "-";
					} else {
						$average = $row['AverageDisplay'];
					}
					$subject_average = "-";
				} else {
					$average = "-";
					$subject_average = "-";
				}
		
				if($row['EffortType'] == $EFFORT_TYPE_NONE) {
					$effort = "-";
				} elseif($row['EffortType'] == $EFFORT_TYPE_PERCENT) {
					if($row['Effort'] == -1) {
						$effort = "-";
					} else {
						$effort = round($row['Effort']);
						$effort = "$effort%";
					}
				} elseif($row['EffortType'] == $EFFORT_TYPE_INDEX) {
					if(is_null($row['EffortDisplay'])) {
						$effort = "-";
					} else {
						$effort = $row['EffortDisplay'];
					}
				} else {
					$effort = "-";
				}
			
				if($row['ConductType'] == $CONDUCT_TYPE_NONE) {
					$conduct = "-";
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
					$conduct = "-";
				}
		
				if($row['CommentType'] == $COMMENT_TYPE_NONE) {
					$comment = "-";
				} elseif($row['CommentType'] == $COMMENT_TYPE_MANDATORY or
						$row['CommentType'] == $COMMENT_TYPE_OPTIONAL) {
					if(!is_null($row['Comment'])) {
						$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
					} else {
						$comment = "";
					}
				} else {
					$comment = "-";
				}
	
				if(strpos('0123456789', substr($row['SubjectName'], 0, 1)) !== false) {
					$stripped_name = trim(strstr($row['SubjectName'], " "));
				} else {
					$stripped_name = trim($row['SubjectName']);
				}

				$reprow = str_replace("&lt;&lt;subject_name&gt;&gt;",         htmlspecialchars($row['SubjectName'], ENT_QUOTES), $data_row);
				$reprow = str_replace("&lt;&lt;subject_shortname&gt;&gt;",    htmlspecialchars($row['ShortName'], ENT_QUOTES),   $reprow);
				$reprow = str_replace("&lt;&lt;subject_type&gt;&gt;",         htmlspecialchars($row['SubjectType'], ENT_QUOTES), $reprow);
				$reprow = str_replace("&lt;&lt;subject_strippedname&gt;&gt;", htmlspecialchars($stripped_name, ENT_QUOTES),      $reprow);
				$reprow = str_replace("&lt;&lt;subject_average&gt;&gt;",      htmlspecialchars($subject_average, ENT_QUOTES),    $reprow);
				$reprow = str_replace("&lt;&lt;subject_weight&gt;&gt;",       htmlspecialchars($weight, ENT_QUOTES),             $reprow);
				$reprow = str_replace("&lt;&lt;subject_mark&gt;&gt;",         htmlspecialchars($average, ENT_QUOTES),            $reprow);
				$reprow = str_replace("&lt;&lt;subject_effort&gt;&gt;",       htmlspecialchars($effort, ENT_QUOTES),             $reprow);
				$reprow = str_replace("&lt;&lt;subject_conduct&gt;&gt;",      htmlspecialchars($conduct, ENT_QUOTES),            $reprow);
				$reprow = str_replace("&lt;&lt;subject_comment&gt;&gt;",      $comment,                                          $reprow);

				$classindex = $row['ClassIndex'];
				$subjecttypeindex = $row['SubjectTypeIndex'];
				$subject_name = $row['SubjectName'];
				
				$query =	"SELECT subject.AverageType, subject.AverageTypeIndex, subjectstudent.Average, " .
							"       subject.EffortType, subject.EffortTypeIndex, subjectstudent.Effort, " .
							"       subject.ConductType, subject.ConductTypeIndex, subjectstudent.Conduct, " .
							"       subject.CommentType, subjectstudent.Comment, subjectstudent.CommentValue, " .
							"       subject.Average AS SubjectAverage, " .
							"       average_index.Display AS AverageDisplay, " .
							"       effort_index.Display AS EffortDisplay, " .
							"       conduct_index.Display AS ConductDisplay, " .
							"		subject.TermIndex, term.TermNumber, " .
							"       get_term_weight(term.TermIndex, $class_index, '{$student_info['Username']}') AS Weight " .
							" FROM " .
							" (term INNER JOIN term AS depterm " .
							"       ON  term.DepartmentIndex = depterm.DepartmentIndex " .
							"       AND depterm.TermIndex = $termindex " .
							"       AND term.TermNumber <= depterm.TermNumber) " .
							" INNER JOIN class ON (class.ClassIndex = $class_index) " .
							" LEFT OUTER JOIN " .
							" (subjectstudent INNER JOIN subject " .
							"       ON  subjectstudent.Username = '{$student_info['Username']}' " .
							"       AND subjectstudent.SubjectIndex = subject.SubjectIndex " .
							"       AND subject.YearIndex = $yearindex " .
							"       AND subject.Name = '$subject_name' " .
							"       AND subject.ShowInList = 1 " .
							"       AND (subject.AverageType != $AVG_TYPE_NONE OR subject.EffortType != $EFFORT_TYPE_NONE OR subject.ConductType != $CONDUCT_TYPE_NONE OR subject.CommentType != $COMMENT_TYPE_NONE)) " .
							" ON term.TermIndex = subject.TermIndex " .
							" LEFT OUTER JOIN nonmark_index AS average_index ON " .
							"       subjectstudent.Average = average_index.NonmarkIndex " .
							" LEFT OUTER JOIN nonmark_index AS effort_index ON " .
							"       subjectstudent.Effort = effort_index.NonmarkIndex " .
							" LEFT OUTER JOIN nonmark_index AS conduct_index ON " .
							"       subjectstudent.Conduct = conduct_index.NonmarkIndex " .
							"ORDER BY term.TermNumber ASC";
				$nres =&  $db->query($query);
				if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
			
				$std_average = 0;
				$std_average_max = 0;
				$subj_average = 0;
				$subj_average_max = 0;
				
				while ($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					$termnum = $nrow['TermNumber'];
					$term_weight = $nrow['Weight'];
						
					if($nrow['AverageType'] == $AVG_TYPE_NONE) {
						$average = "-";
						$subject_average = "-";
					} elseif($nrow['AverageType'] == $AVG_TYPE_PERCENT) {
						if($nrow['Average'] == -1) {
							$average = "-";
						} else {
							$average = round($nrow['Average']);
							$std_average     += $average * $term_weight;
							$std_average_max += 100 * $term_weight;
							
							$average = "$average%";
						}
						if($nrow['SubjectAverage'] == -1) {
							$subject_average = "-";
						} else {
							$subject_average = round($nrow['SubjectAverage']);
							$subj_average     += $subject_average * $term_weight;
							$subj_average_max += 100 * $term_weight;
							
							$subject_average = "$subject_average%";
						}
					} elseif($nrow['AverageType'] == $AVG_TYPE_INDEX or $nrow['AverageType'] == $AVG_TYPE_GRADE) {
						if(is_null($nrow['AverageDisplay'])) {
							$average = "-";
						} else {
							$average = $nrow['AverageDisplay'];
						}
						$subject_average = "-";
					} else {
						$average = "-";
						$subject_average = "-";
					}
			
					if($nrow['EffortType'] == $EFFORT_TYPE_NONE) {
						$effort = "-";
					} elseif($nrow['EffortType'] == $EFFORT_TYPE_PERCENT) {
						if($nrow['Effort'] == -1) {
							$effort = "-";
						} else {
							$effort = round($nrow['Effort']);
							$effort = "$effort%";
						}
					} elseif($nrow['EffortType'] == $EFFORT_TYPE_INDEX) {
						if(is_null($nrow['EffortDisplay'])) {
							$effort = "-";
						} else {
							$effort = $nrow['EffortDisplay'];
						}
					} else {
						$effort = "-";
					}
				
					if($nrow['ConductType'] == $CONDUCT_TYPE_NONE) {
						$conduct = "-";
					} elseif($nrow['ConductType'] == $CONDUCT_TYPE_PERCENT) {
						if($nrow['Conduct'] == -1) {
							$conduct = "&nbsp;";
						} else {
							$conduct = round($nrow['Conduct']);
							$conduct = "$conduct%";
						}
					} elseif($nrow['ConductType'] == $CONDUCT_TYPE_INDEX) {
						if(is_null($nrow['ConductDisplay'])) {
							$conduct = "&nbsp;";
						} else {
							$conduct = $nrow['ConductDisplay'];
						}
					} else {
						$conduct = "-";
					}
			
					if($nrow['CommentType'] == $COMMENT_TYPE_NONE) {
						$comment = "-";
					} elseif($nrow['CommentType'] == $COMMENT_TYPE_MANDATORY or
							$nrow['CommentType'] == $COMMENT_TYPE_OPTIONAL) {
						if(!is_null($nrow['Comment'])) {
							$comment = htmlspecialchars($nrow['Comment'], ENT_QUOTES);
						} else {
							$comment = "";
						}
					} else {
						$comment = "-";
					}
					$reprow = str_replace("&lt;&lt;subject_average_t$termnum&gt;&gt;",      htmlspecialchars($subject_average, ENT_QUOTES),    $reprow);
					$reprow = str_replace("&lt;&lt;subject_mark_t$termnum&gt;&gt;",         htmlspecialchars($average, ENT_QUOTES),            $reprow);
					$reprow = str_replace("&lt;&lt;subject_effort_t$termnum&gt;&gt;",       htmlspecialchars($effort, ENT_QUOTES),             $reprow);
					$reprow = str_replace("&lt;&lt;subject_conduct_t$termnum&gt;&gt;",      htmlspecialchars($conduct, ENT_QUOTES),            $reprow);
					$reprow = str_replace("&lt;&lt;subject_comment_t$termnum&gt;&gt;",      $comment,                                          $reprow);
				}
				
				# Year to date averages
				if($std_average_max > 0) {
					$scorestr = round($std_average * 100 / $std_average_max);
					$std_average = "$scorestr%";
				} else {
					$std_average = "-";
				}
				if($subj_average_max > 0) {
					$subjscore = round($subj_average * 100 / $subj_average_max);
					$subj_average ="$subjscore%";
				} else {
					$subj_average = "-";
				}
				$reprow = str_replace("&lt;&lt;overall_subject_average&gt;&gt;",      htmlspecialchars($subj_average, ENT_QUOTES), $reprow);
				$reprow = str_replace("&lt;&lt;overall_subject_mark&gt;&gt;",         htmlspecialchars($std_average, ENT_QUOTES),  $reprow);
		
				# Remove fields for terms not used yet	
				$query = "SELECT TermNumber FROM term WHERE DepartmentIndex=$depindex";
				$nres =&  $db->query($query);
				if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
				while ($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					$termnum = $nrow["TermNumber"];
					$reprow = str_replace("&lt;&lt;subject_average_t$termnum&gt;&gt;", "", $reprow);
					$reprow = str_replace("&lt;&lt;subject_mark_t$termnum&gt;&gt;",    "", $reprow);
					$reprow = str_replace("&lt;&lt;subject_effort_t$termnum&gt;&gt;",  "", $reprow);
					$reprow = str_replace("&lt;&lt;subject_conduct_t$termnum&gt;&gt;", "", $reprow);
					$reprow = str_replace("&lt;&lt;subject_comment_t$termnum&gt;&gt;", "", $reprow);
				}

				$rep .= $reprow;
			}
			$data = str_replace($data_row, $rep, $data);
			$old_pos = $pos + 1;
			$pos = strpos($data, "&lt;&lt;subject_name&gt;&gt;", $old_pos);
			if($pos === false) {
				$pos = strpos($data, "&lt;&lt;subject_shortname&gt;&gt;", $old_pos);
			}
			if($pos === false) {
				$pos = strpos($data, "&lt;&lt;subject_strippedname&gt;&gt;", $old_pos);
			}
			if($pos === false) {
				$pos = strpos($data, "&lt;&lt;subject_type&gt;&gt;", $old_pos);
			}
		}				
		$final_data .= $data;
	}
	// Write back to temporary odt
	$handle = fopen("$tempdir/content.xml", "w");
	$null = fwrite($handle, $header);
	$null = fwrite($handle, $final_data);
	$null = fwrite($handle, $footer);
	fclose($handle);

	$output = array();
	$output = exec("cd $tempdir; /usr/bin/zip -DXr $tempdir.odt *", $output, $retval);
	unset($output);

	// Output temporary odt
	header("Content-type: $report_template_type");
	header("Content-disposition: attachment; filename=reports.odt");

	readfile("$tempdir.odt");

	// Remove temporary files
	unlink("$tempdir.odt");
	recursive_remove_directory($tempdir);
?>
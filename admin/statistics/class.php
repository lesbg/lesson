<?php
	/*****************************************************************
	 * admin/statistics/class.php  (c) 2011 Jonathan Dieter
	 *
	 * View statistics about students in a class
	 *****************************************************************/

	/* Get variables */
	$class         = dbfuncInt2String($_GET['keyname']);
	$title         = "Statistics for " . $class;
	$classtermindex    = safe(dbfuncInt2String($_GET['key']));
	$cumulative    = false;
	
	if(isset($_GET['cumulative']) and safe(dbfuncInt2String($_GET['cumulative'])) == "1") {
		$cumulative = true;
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

	/* Check whether user is class teacher */
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
	
	/* Get current class term if someone has hit the term left or term right buttons */
	$query =	"SELECT classterm.ClassTermIndex FROM classterm, classterm AS old_classterm " .
				"WHERE old_classterm.ClassTermIndex = $classtermindex " .
				"AND   classterm.ClassIndex = old_classterm.ClassIndex " .
				"AND   classterm.TermIndex = $termindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if (!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "          <p>Unable to get class information.</p>\n";
		include "footer.php";
		exit(0);
	}
	$classtermindex = $row['ClassTermIndex'];
	

	if(!$is_ct and !$is_hod and !$is_principal and !$is_admin) {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "admin/statistics/class.php", $LOG_DENIED_ACCESS,
					"Tried to get statistics for $class.");

		include "footer.php";
		exit(0);
	}

	/* Get class information */
	$query =	"SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
				"       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
				"       classterm.ConductTypeIndex, classterm.Average, " .
				"       classterm.AbsenceType " .
				"       FROM classterm, classlist " .
				"WHERE classterm.ClassTermIndex    = $classtermindex " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"GROUP BY classterm.ClassTermIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if (!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		echo "          <p>Unable to get class information.</p>\n";
		include "footer.php";
		exit(0);
	}
		
	$class_average      = $row['Average'];
	$average_type       = $row['AverageType'];
	$effort_type        = $row['EffortType'];
	$conduct_type       = $row['ConductType'];
	$absence_type       = $row['AbsenceType'];
	$average_type_index = $row['AverageTypeIndex'];
	$effort_type_index  = $row['EffortTypeIndex'];
	$conduct_type_index = $row['ConductTypeIndex'];

	/* Get cumulative class information */
	if($cumulative and ($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC)) {
		$query =	"SELECT ROUND(AVG(ROUND(classterm.Average))) AS ClassAverage " .
					"       FROM classterm, classterm AS oldclassterm, term, term AS selected_term " .
					"WHERE oldclassterm.ClassTermIndex    = $classtermindex " .
					"AND   selected_term.TermIndex        = oldclassterm.TermIndex " .
					"AND   term.TermNumber               <= selected_term.TermNumber " .
					"AND   term.DepartmentIndex           = selected_term.DepartmentIndex " .
					"AND   classterm.TermIndex            = term.TermIndex " .
					"AND   classterm.ClassIndex           = oldclassterm.ClassIndex " .
					"AND   classterm.Average             != -1 " .
					"GROUP BY classterm.ClassIndex";
		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if (!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$class_average = -1;
		} else {
			$class_average = $row['ClassAverage'];
		}
	}

	$query =		"SELECT subject.Name, subjecttype.Title, subjecttype.ShortTitle, ROUND(AVG(ROUND(subject.Average))) AS Average, " .
					"       subject.SubjectIndex, subject.AverageType " .
					"       FROM subject, subjecttype, subjectstudent, classlist, class, classterm, " .
					"            term, term AS selected_term " .
					"WHERE classlist.ClassTermIndex = $classtermindex " .
					"AND   classterm.ClassTermIndex = $classtermindex " .
					"AND   class.ClassIndex         = classterm.ClassIndex ";
	if($cumulative) {
		$query .=	"AND   selected_term.TermIndex     = $termindex " .
					"AND   term.TermNumber            <= selected_term.TermNumber " .
					"AND   term.DepartmentIndex        = selected_term.DepartmentIndex " .
					"AND   term.DepartmentIndex        = class.DepartmentIndex " .
					"AND   subject.TermIndex           = term.TermIndex ";
	} else {
		$query .=	"AND   subject.TermIndex           = $termindex " .
					"AND   term.TermIndex              = subject.TermIndex " .
					"AND   selected_term.TermIndex     = subject.TermIndex ";	
	}
	$query .=		"AND   subject.YearIndex = $yearindex " .
					"AND   subject.SubjectTypeIndex = subjecttype.SubjectTypeIndex " .
					"AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
					"AND   subjectstudent.Username = classlist.Username " .
					"AND   subject.AverageType != $AVG_TYPE_NONE " .
					"AND   subjectstudent.Average != -1 " .
					"GROUP BY subject.Name " .
					"ORDER BY subject.YearIndex, subjecttype.HighPriority DESC, " .
					"         get_weight(subject.SubjectIndex, CURDATE()) DESC, " .
					"         subjecttype.Title, subject.Name, subject.TermIndex, subject.SubjectIndex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	if($res->numRows() == 0) {
		echo "          <p>No students in class list.</p>\n";
		include "footer.php";
		exit(0);
	}

	$cumlink = "";
	if(!$cumulative) {
		$cumlink =  "index.php?location=" .  dbfuncString2Int("admin/statistics/class.php") . // link to create a new subject 
					"&amp;key=" .            $_GET['key'] .
					"&amp;keyname=" .        $_GET['keyname'] .
					"&amp;cumulative=" .     dbfuncString2Int("1");
	}
	$indlink = "";
	if($cumulative) {
		$indlink =  "index.php?location=" .  dbfuncString2Int("admin/statistics/class.php") . // link to create a new subject 
					"&amp;key=" .            $_GET['key'] .
					"&amp;keyname=" .        $_GET['keyname'] .
					"&amp;cumulative=" .     dbfuncString2Int("0");
	}
	
	if($cumulative) {
		$cumbutton = dbfuncGetDisabledButton("Cumulative", "medium", "");
		$indbutton = dbfuncGetButton($indlink, "Individual", "medium", "", "Show each term's marks by themselves");
	} else {
		$cumbutton = dbfuncGetButton($cumlink, "Cumulative", "medium", "", "Show cumulative averages for each student");
		$indbutton = dbfuncGetDisabledButton("Individual", "medium", "");
	}
	
	

	echo "         <p align='center'>$indbutton $cumbutton</p>\n";
	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>&nbsp;</th>\n";
	echo "               <th>Student</th>\n";
	
	$subj_list = array();
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$subj_list[] = $row['Name'];
		if($row['Average'] == -1) {
			$subj_average[] = "-";
		} else {
			if($row['AverageType'] == $AVG_TYPE_PERCENT or $row['AverageType'] == $AVG_TYPE_CALC) {
				$subj_average[] = format_mark($row['Average'], $row['AverageType']);
			} elseif($row['AverageType'] == $AVG_TYPE_INDEX) {
				$subj_average[] = format_mark($row['Display'], $row['AverageType']);
			} else {
				$subj_average[] = "-";
			}
		}
		if(is_null($row['ShortTitle']) or $row['ShortTitle'] == "") {
			$subj_title = $row['Title'];
		} else {
			$subj_title = $row['ShortTitle'];
		}
		echo "               <th><a title='{$row['Name']}'>$subj_title</a></th>\n";
	}
	if ($average_type != $CLASS_AVG_TYPE_NONE) {
		echo "               <th>Average</th>\n";
	}
	echo "             </tr>\n";
	
	$query =		"SELECT user.FirstName, user.Surname, user.Username, ROUND(AVG(ROUND(subjectstudent.Average))) AS Average, " .
					"       average_index.Display, subject.SubjectIndex, subject.AverageType, subject.Name, " .
					"       classlist.Average AS ClassAverage, class_average_index.Display AS ClassDisplay, " .
					"       classterm.AverageType AS ClassAverageType " .
					"       FROM subjecttype, subject, user, term, term AS selected_term, class, classterm, " .
					"            classlist LEFT OUTER JOIN nonmark_index AS class_average_index " .
					"                       ON classlist.Average = class_average_index.NonmarkIndex, " .
					"            subjectstudent LEFT OUTER JOIN nonmark_index AS average_index " .
					"                       ON subjectstudent.Average = average_index.NonmarkIndex " .
					"WHERE classlist.ClassTermIndex    = $classtermindex " .
					"AND   classterm.ClassTermIndex    = classlist.ClassTermIndex " .
					"AND   class.ClassIndex            = classterm.ClassIndex ";
	if($cumulative) {
		$query .=	"AND   selected_term.TermIndex     = $termindex " .
					"AND   term.TermNumber            <= selected_term.TermNumber " .
					"AND   term.DepartmentIndex        = selected_term.DepartmentIndex " .
					"AND   term.DepartmentIndex        = class.DepartmentIndex " .
					"AND   subject.TermIndex           = term.TermIndex ";
	} else {
		$query .=	"AND   subject.TermIndex           = $termindex " .
					"AND   term.TermIndex              = subject.TermIndex " .
					"AND   selected_term.TermIndex     = subject.TermIndex ";	
	}
	$query .=		"AND   subject.YearIndex           = $yearindex " .
					"AND   subject.SubjectTypeIndex    = subjecttype.SubjectTypeIndex " .
					"AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
					"AND   subjectstudent.Username     = classlist.Username " .
					"AND   subject.AverageType        != $AVG_TYPE_NONE " .
					"AND   subjectstudent.Average     != -1 " .
					"AND   user.Username               = subjectstudent.Username " .
					"GROUP BY user.Username, subject.Name " .
					"ORDER BY user.FirstName, user.Surname, user.Username, subject.YearIndex, " .
					"         subjecttype.HighPriority DESC, get_weight(subject.SubjectIndex, CURDATE()) DESC, " .
					"         subjecttype.Title, subject.Name, subject.TermIndex, subject.SubjectIndex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	$alt_count   = 0;
	$prev_username = NULL;
	$prev_average = 0;

	$order = 1;
	while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($row['Username'] != $prev_username) {
			if(!is_null($prev_username)) {
				while($count < count($subj_list)) {
					echo "                <td>-</td>\n";
					$count += 1;		
				}
				if($average_type != $CLASS_AVG_TYPE_NONE) {
					echo "               <td><b>$prev_average</b></td>\n";
				}
				echo "            </tr>\n";
			}
			$alt_count   += 1;
			
			if($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
	
			$detaillink =	"index.php?location=" .  dbfuncString2Int("teacher/report/class_modify.php") . // link to create a new subject 
							"&amp;key=" .            $_GET['key'] .
							"&amp;keyname=" .        $_GET['keyname'] .
							"&amp;keyname2=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
							"&amp;key2=" .           dbfuncString2Int($row['Username']) .
							"&amp;showonly=" .       dbfuncString2Int("1");
	
			echo "            <tr$alt id='row_{$row['Username']}'>\n";
			echo "               <td>$order</td>\n";
			echo "               <td><a href='$detaillink'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
			
			$count = 0;
			$order += 1;
			$prev_username = $row['Username'];

			if($cumulative and ($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC)) {
			}
			
			if($average_type == $CLASS_AVG_TYPE_PERCENT or $average_type == $CLASS_AVG_TYPE_CALC) {
				if(!$cumulative) {
					$prev_average = format_mark($row['ClassAverage'], $average_type, 1);
				} else {
					$query =	"SELECT ROUND(AVG(ROUND(classlist.Average))) AS ClassAverage " .
								"       FROM classterm, classterm AS oldclassterm, term, term AS selected_term, classlist " .
								"WHERE oldclassterm.ClassTermIndex    = $classtermindex " .
								"AND   selected_term.TermIndex        = oldclassterm.TermIndex " .
								"AND   term.TermNumber               <= selected_term.TermNumber " .
								"AND   term.DepartmentIndex           = selected_term.DepartmentIndex " .
								"AND   classterm.TermIndex            = term.TermIndex " .
								"AND   classterm.ClassIndex           = oldclassterm.ClassIndex " .
								"AND   classlist.ClassTermIndex       = classterm.ClassTermIndex " .
								"AND   classlist.Username             = '$prev_username' " .
								"AND   classlist.Average             != -1 " .
								"GROUP BY classterm.ClassIndex";
					$nres =& $db->query($query);
					if(DB::isError($nres)) die($nres->getDebugInfo());         // Check for errors in query
					if (!$nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
						$prev_average = "-";
					} else {
						$prev_average = format_mark($nrow['ClassAverage'], $average_type, 1);
					}
				}
			} elseif($average_type == $CLASS_AVG_TYPE_INDEX) {
				$prev_average = format_mark($row['ClassAverageDisplay'], $average_type, 1);
			} else {
				$prev_average = "-";
			}
		}
		$found = array_search($row['Name'], $subj_list);
		if($found !== FALSE) {
			while($count < $found) {
				echo "                <td>-</td>\n";
				$count += 1;
			}
			if($row['Average'] == -1) {
				$average = "-";
			} else {
				if($row['AverageType'] == $AVG_TYPE_PERCENT or $row['AverageType'] == $AVG_TYPE_CALC) {
					$average = format_mark($row['Average'], $row['AverageType']);
				} elseif($row['AverageType'] == $AVG_TYPE_INDEX) {
					$average = format_mark($row['Display'], $row['AverageType']);
				} else {
					$average = "-";
				}
			}

			echo "                <td>$average</td>\n";
			$count += 1;			
		}
	}
	while($count < count($subj_list)) {
		echo "                <td>-</td>\n";
		$count += 1;		
	}
	if($average_type != $CLASS_AVG_TYPE_NONE) {
		echo "               <td><b>$prev_average</b></td>\n";
	}
	echo "            </tr>\n";

	$alt_count   += 1;
	
	if($alt_count % 2 == 0) {
		$alt = " class='alt'";
	} else {
		$alt = " class='std'";
	}

	echo "            <tr$alt id='average'>\n";
	echo "               <td colspan='2'><b>Average</b></td>\n";

	for($count = 0; $count < count($subj_list); $count += 1) {
		echo "               <td><b>{$subj_average[$count]}</b></td>\n";
	}
	if($average_type != $CLASS_AVG_TYPE_NONE) {
		if($class_average != -1) {
			$class_average = format_mark($class_average, $average_type, 1);
			echo "               <td><b>$class_average</b></td>\n";
		} else {
			echo "               <td>-</td>\n";
		}
	}
	echo "            </tr>\n";

	include "footer.php";
?>
<?php
	/*****************************************************************
	 * admin/marks/low.php  (c) 2007-2014 Jonathan Dieter
	 *
	 * Print student's marks that are below a certain criteria
	 *****************************************************************/
	if(isset($_GET["show"])) {
		$showtype = dbfuncInt2String($_GET["show"]);
	} else {
		$showtype = "a";
		$_GET["show"] = dbfuncString2Int("a");
	}
	
	if(isset($_GET['start'])) {
		$start = $_GET['start'];
	} else {
		$start = "0";
		$_GET['start'] = "0";
	}

	if($showtype == "l") {
		$title = "Late assignments";
	} else {
		$title = "Low marks";
	}

	/* Get filter type */
	if(isset($_POST['ftype'])) {
		$ftype = strval(intval(($_POST['ftype'])));
		$_GET['ftype'] = $ftype;
	} elseif(isset($_GET['ftype'])) {
		$ftype = strval(intval(($_GET['ftype'])));
	} else {
		$ftype = "0";
		$_GET['ftype'] = $ftype;
	}

	/* Get filter data */
	if(isset($_POST['fdata'])) {
		if($ftype == "0") {
			$fdata = strval(intval(($_POST['fdata'])));
			$_GET['fdata'] = $fdata;
		} else {
			$fdata = "20";
			$_GET['fdata'] = "20";
		}
	} elseif(isset($_GET['fdata'])) {
		if($ftype == "0") {
			$fdata = strval(intval(($_GET['fdata'])));
		} else {
			$fdata = "20";
			$_GET['fdata'] = "20";
		}
	} else {
		$fdata = "20";
		$_GET['fdata'] = $fdata;
	}

	/* Get starting date */
	if(isset($_POST['sdate'])) {
		$sdate = safe(dbfuncCreateDate($_POST['sdate']));
		$_GET['sdate'] = dbfuncString2Int($sdate);
	} elseif(isset($_GET['sdate'])) {
		$sdate = safe(dbfuncInt2String($_GET['sdate']));
	} else {
		$sdate = dbfuncCreateDate(date($dateformat, time() - 7*24*60*60));
		$_GET['sdate'] = dbfuncString2Int($sdate);
	}

	/* Get ending date */
	if(isset($_POST['edate'])) {
		$edate = safe(dbfuncCreateDate($_POST['edate']));
		$_GET['edate'] = dbfuncString2Int($edate);
	} elseif(isset($_GET['edate'])) {
		$edate = safe(dbfuncInt2String($_GET['edate']));
	} else {
		$edate = dbfuncCreateDate(date($dateformat));
		$_GET['edate'] = dbfuncString2Int($edate);
	}

	if($_POST['action'] == "Reset") {
		$ftype = "0";
		$_GET['ftype'] = $ftype;
		$fdata = "20";
		$_GET['fdata'] = $fdata;
		$sdate = dbfuncCreateDate(date($dateformat, time() - 7*24*60*60));
		$_GET['sdate'] = dbfuncString2Int($sdate);
		$edate = dbfuncCreateDate(date($dateformat));
		$_GET['edate'] = dbfuncString2Int($edate);
	}

	include "header.php";
	
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {  // Make sure user has permission to view
		$showalldeps = true;                              //  low marks
		include "core/settermandyear.php";
		include "core/titletermyear.php";

		if(!isset($_GET['sort'])) {
			$_GET['sort'] = '0';
		}
		
		for($a=0; $a < 18; $a++) {
			$sort[$a] = "sort";
		}
		
		$sort[intval($_GET['sort'])] = "bsort";
		
		if($_GET['sort'] == '1') {
			$sortorder = "ORDER BY Grade DESC, ClassName DESC, Username DESC, ShortName DESC, Date DESC, Title DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '2') {
			$sortorder = "ORDER BY Username, ShortName, Date, Title, AssignmentIndex";
		} elseif($_GET['sort'] == '3') {
			$sortorder = "ORDER BY Username DESC, ShortName DESC, Date DESC, Title DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '4') {
			$sortorder = "ORDER BY ShortName, Username, Date, Title, AssignmentIndex";
		} elseif($_GET['sort'] == '5') {
			$sortorder = "ORDER BY ShortName DESC, Username DESC, Date DESC, Title DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '6') {
			$sortorder = "ORDER BY Title, Date, AssignmentIndex, Username";
		} elseif($_GET['sort'] == '7') {
			$sortorder = "ORDER BY Title DESC, Date DESC, AssignmentIndex DESC, Username DESC";
		} elseif($_GET['sort'] == '8') {
			$sortorder = "ORDER BY Percentage, assignment.Average DESC, Username, AssignmentIndex";
		} elseif($_GET['sort'] == '9') {
			$sortorder = "ORDER BY Percentage DESC, assignment.Average, Username DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '10') {
			$sortorder = "ORDER BY assignment.Average, Percentage, Username, AssignmentIndex";
		} elseif($_GET['sort'] == '11') {
			$sortorder = "ORDER BY assignment.Average DESC, Percentage DESC, Username DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '12') {
			$sortorder = "ORDER BY Difference, Percentage, Username, AssignmentIndex";
		} elseif($_GET['sort'] == '13') {
			$sortorder = "ORDER BY Difference DESC, Percentage DESC, Username DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '14') {
			$sortorder = "ORDER BY Date, ShortName, Username, Title, AssignmentIndex";
		} elseif($_GET['sort'] == '15') {
			$sortorder = "ORDER BY Date DESC, ShortName DESC, Username DESC, Title DESC, AssignmentIndex DESC";
		} elseif($_GET['sort'] == '16') {
			$sortorder = "ORDER BY DueDate, ShortName, Username, Date, Title, AssignmentIndex";
		} elseif($_GET['sort'] == '17') {
			$sortorder = "ORDER BY DueDate DESC, ShortName DESC, Username DESC, Date DESC, Title DESC, AssignmentIndex DESC";
		} else {
			$sortorder = "ORDER BY Grade, ClassName, Username, ShortName, Date, Title, AssignmentIndex";
		}
		
		$classAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=0&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[0]}", "Sort ascending");
		$classDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=1&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[1]}", "Sort descending");
		$unameAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=2&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[2]}", "Sort ascending");
		$unameDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=3&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[3]}", "Sort descending");
		$subjectAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=4&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[4]}", "Sort ascending");
		$subjectDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=5&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[5]}", "Sort descending");
		$titleAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=6&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[6]}", "Sort ascending");
		$titleDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=7&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[7]}", "Sort descending");
		$scoreAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=8&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[8]}", "Sort ascending");
		$scoreDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=9&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[9]}", "Sort descending");
		$averageAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=10&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[10]}", "Sort ascending");
		$averageDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=11&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[11]}", "Sort descending");
		$diffAsc    = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=12&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[12]}", "Sort ascending");
		$diffDec    = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=13&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[13]}", "Sort descending");
		$dateAsc    = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=14&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[14]}", "Sort ascending");
		$dateDec    = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=15&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[15]}", "Sort descending");
		$duedateAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=16&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "A", "small", "{$sort[16]}", "Sort ascending");
		$duedateDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                              "&amp;sort=17&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;start={$_GET['start']}", "D", "small", "{$sort[17]}", "Sort descending");
	
		$query =		"SELECT COUNT(MarkIndex) AS Count " .
						"       FROM mark INNER JOIN assignment USING (AssignmentIndex) INNER JOIN subject USING (SubjectIndex) " .
						"WHERE subject.YearIndex = $yearindex " .
						"AND   subject.TermIndex = $termindex " .
						"AND   Date >= '$sdate' " .
						"AND   Date <= '$edate' " .
						"AND   Percentage < assignment.Average - $fdata " .
						"AND   (mark.Score >= 0 OR Score = $MARK_LATE)";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		$max     = intval($row['Count']) - 1;
		$fpstart = intval(intval($max)/$LOGS_PER_PAGE) * $LOGS_PER_PAGE;
		if($start > $max) $start = 0;

		$query =	"SELECT FirstName, Surname, mark.Username, " .
					"       assignment.Title, Date, DueDate, subject.ShortName, " .
					"       Marked, assignment.AssignmentIndex, assignment.Average, " .
					"       Description, SubjectIndex, CanModify, " .
					"       Score, assignment.Average - Percentage AS Difference, " .
					"       ClassName, class.Grade, " .
					"       Percentage, Comment " .
					"       FROM mark " .
					"         INNER JOIN assignment     USING (AssignmentIndex) " .
					"         INNER JOIN subject        USING (SubjectIndex) " .
					"         INNER JOIN user           USING (Username) " .
					"         INNER JOIN ( " .
					"           classlist INNER JOIN classterm " .
					"             ON  (classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"                  AND classterm.TermIndex = $termindex) " .
					"             INNER JOIN class " .
					"             ON  (class.ClassIndex = classterm.ClassIndex " .
					"                  AND class.YearIndex = $yearindex) " .
					"         ) ON mark.Username = classlist.Username " .
					"WHERE subject.YearIndex = $yearindex " .
					"AND   subject.TermIndex = $termindex " .
					"AND   Date >= '$sdate' " .
					"AND   Date <= '$edate' " .
					"AND   Percentage < assignment.Average - $fdata " .
					"AND   (Score >= 0 OR Score = $MARK_LATE) " .
					"$sortorder " .
					"LIMIT $start, $LOGS_PER_PAGE";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		$count = $res->numRows();

		if(intval($start) > 0) {
			$first_record = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                                    "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;sort={$_GET['sort']}&amp;start=0", "<<", "medium", "prevnext", "First page");
			$prev         = intval($start) - $LOGS_PER_PAGE;
			if($prev < 0)
				$prev     = 0;
			$prev         = strval($prev);
			$prev_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                                   "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;sort={$_GET['sort']}&amp;start=$prev", "<", "medium", "prevnext", "Previous page");
		} else {
			$first_record = dbfuncGetDisabledButton("<<", "medium", "prevnext");
			$prev_record  = dbfuncGetDisabledButton("<", "medium", "prevnext");
		}
		
		if(intval($start) < $fpstart) {
			$last_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                                    "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;sort={$_GET['sort']}&amp;start=$fpstart", ">>", "medium", "prevnext", "Last page");
			$next         = intval($start) + $LOGS_PER_PAGE;
			if($next > $fpstart)
				$next     = $fpstart;
			$next         = strval($next);
			$next_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		                                   "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;sort={$_GET['sort']}&amp;start=$next", ">", "medium", "prevnext", "Next page");
		} else {
			$last_record  = dbfuncGetDisabledButton(">>", "medium", "prevnext");;
			$next_record  = dbfuncGetDisabledButton(">", "medium", "prevnext");;
		}
		
		$startval = strval(intval($start) + 1);
		$endval   = strval(intval($start) + $count);
		$totalval = strval($max + 1);
		$link = "index.php?location=" . dbfuncString2Int("admin/marks/low.php") .
		        "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;edate={$_GET['edate']}&amp;sdate={$_GET['sdate']}&amp;sort={$_GET['sort']}&amp;start={$_GET['start']}";
		
		$sdate_show = date($dateformat, strtotime($sdate));
		$edate_show = date($dateformat, strtotime($edate));
		/* Print filter options */
		echo "      <form action='$link' method='post'>\n";  // Form method
		echo "         <table class='transparent' width='100%'>\n";
		echo "            <tr>\n";
		echo "               <td nowrap>Start date: <input type='text' name='sdate' value='$sdate_show'></td>\n";
		echo "               <td nowrap>End date: <input type='text' name='edate' value='$edate_show'></td>\n";
		echo "               <td nowrap>Min diff from average: <input type='text' name='fdata' value='$fdata'></td>\n";
		echo "               <td nowrap><input type='submit' name='action' value='Reset'> <input type='submit' name='action' value='Go'></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "      </form>\n";
		/* Print header with rows being shown and buttons to move */
		echo "      <table class='transparent' width='100%'>\n";
		echo "         <tr>\n";
		echo "            <td width='100px' nowrap>$first_record $prev_record</td>\n"; 
		echo "            <td align='center' nowrap>Showing records $startval-$endval " .
																		"of $totalval</td>\n";
		echo "            <td width='100px' align='right' nowrap>$next_record $last_record</td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";

		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th nowrap>Student $unameAsc $unameDec</th>\n";
		echo "            <th nowrap>Class $classAsc $classDec</th>\n";
		echo "            <th nowrap>Subject $subjectAsc $subjectDec</th>\n";
		echo "            <th nowrap>Teacher</th>\n";
		echo "            <th nowrap>Assignment $titleAsc $titleDec</th>\n";
		echo "            <th nowrap>Mark $scoreAsc $scoreDec</th>\n";
		echo "            <th nowrap>Avg $averageAsc $averageDec</th>\n";
		echo "            <th nowrap>Diff $diffAsc $diffDec</th>\n";
		echo "            <th nowrap>Date $dateAsc $dateDec</th>\n";
		echo "            <th nowrap>Due Date $duedateAsc $duedateDec</th>\n";
		echo "            <th nowrap>Comment</th>\n";
		echo "         </tr>\n";
		
		/* For each assignment, print subject, teacher, assignment title, date, score, and any comments */
		$alt_count = 0;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$can_modify = $row['CanModify'];
			
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			$alt = " class='$alt_step'";
			if($row['Score'] == $MARK_LATE and $can_modify == 1) {
				$alt = " class='late-$alt_step'";
			} else {
				$alt = " class='$alt_step'";
			}
			echo "         <tr$alt>\n";
			echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</th>\n";
			echo "            <td nowrap>{$row['ClassName']}</td>\n";
			echo "            <td nowrap>{$row['ShortName']}</td>\n";
			
			/* Print name(s) of teacher(s) */
			echo "            <td nowrap>";
			$teacherRes =& $db->query("SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
									"WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
			/*						  "AND   subjectteacher.ShowTeacher  = '1' " .*/
									"AND   user.Username               = subjectteacher.Username"); 
			if(DB::isError($teacherRes)) die($teacherRes->getMessage());          // Check for errors in query
			if($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				$teacherRow['Title']     = htmlspecialchars($teacherRow['Title']);
				$teacherRow['FirstName'] = htmlspecialchars($teacherRow['FirstName']);
				$teacherRow['Surname']   = htmlspecialchars($teacherRow['Surname']);
				echo "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
				
				/* If there's more than one teacher, separate with commas */
				while ($teacherRow =& $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					$teacherRow['Title']     = htmlspecialchars($teacherRow['Title']);
					$teacherRow['FirstName'] = htmlspecialchars($teacherRow['FirstName']);
					$teacherRow['Surname']   = htmlspecialchars($teacherRow['Surname']);
					echo "<br>{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
				}
			}
			echo "</td>\n";
			
			if(is_null($row['Description'])) {
				echo "            <td nowrap>{$row['Title']}</td>\n";
			} else {
				$newwin = "index.php?location=" . dbfuncString2Int("student/descr.php") . "&amp;key=" .
						dbfuncString2Int($row['AssignmentIndex']);
				$aclass = "";
				if($row['Score'] == $MARK_LATE and $can_modify == 1) {
					$aclass = " class='late'";
				} else {
					$aclass = "";
				}
				echo "            <td nowrap><a$aclass href='javascript:popup(\"$newwin\")'>{$row['Title']}</a></td>\n";
			}
			
			if($row['Score'] == $MARK_LATE) {
				if($can_modify == 1) {
					echo "            <td>&nbsp;</td>\n";
				} else {
					echo "            <td>0%</td>\n";
				}
			} else {
				$score = round($row['Percentage']);
				echo "            <td>$score%</td>\n";
			}
			$avgscore = round($row['Average']);
			echo "            <td>$avgscore%</td>\n";
			$diffscore = round($row['Difference']);
			echo "            <td>$diffscore%</td>\n";

			$dateinfo = date($dateformat, strtotime($row['Date']));
			if(isset($row['DueDate'])) {
				$duedateinfo = date($dateformat, strtotime($row['DueDate']));
			} else {
				$duedateinfo = "";
			}
			echo "            <td nowrap>$dateinfo</td>\n";
			echo "            <td nowrap>$duedateinfo</td>\n";
			echo "            <td nowrap>{$row['Comment']}</td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
		log_event($LOG_LEVEL_EVERYTHING, "admin/marks/low.php", $LOG_ADMIN,
					"Viewed low marks.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/marks/low.php", $LOG_DENIED_ACCESS,
					"Tried to access low marks.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
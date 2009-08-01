<?php
	/*****************************************************************
	 * teacher/conduct/modify.php  (c) 2008 Jonathan Dieter
	 *
	 * Allow modification of conduct and effort marks for students
	 *****************************************************************/

	/* Get variables */
	$title        = "Conduct and Effort for " . dbfuncInt2String($_GET['keyname']);
	$subjectindex = safe(dbfuncInt2String($_GET['key']));
	$link         = "index.php?location=" . dbfuncString2Int("teacher/conduct/modify_action.php") .
					"&amp;key=" .               $_GET['key'] .
					"&amp;next=" .              dbfuncString2Int($backLink);
	

	include "core/settermandyear.php";

	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher " .
					   "WHERE subjectteacher.SubjectIndex = $subjectindex " .
					   "AND   subjectteacher.Username     = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	if($res->numRows() > 0 or $is_admin) {
		$res =&  $db->query("SELECT user.FirstName, user.Surname, user.Username, subjectstudent.Conduct " .
							"       query.ClassOrder, subject. FROM subject, subjectstudent LEFT OUTER JOIN" .
							"       (SELECT classlist.ClassOrder, classlist.Username " .
							"               FROM class, classlist, subject " .
							"        WHERE classlist.ClassIndex       = class.ClassIndex " .
							"        AND   class.YearIndex            = subject.YearIndex " .
							"        AND   subject.SubjectIndex       = $subjectindex) AS query " .
							"       ON subjectstudent.Username = query.Username, " .
							"       user " .
							"WHERE subjectstudent.SubjectIndex = $subjectindex " .
							"AND   user.Username               = subjectstudent.Username " .
							"AND   subject.SubjectIndex        = subjectstudent.SubjectIndex " .
							"ORDER BY user.FirstName, user.Surname, user.Username");
#							"ORDER BY user.Username");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
		/* Get assignment info */
		$asr =&  $db->query("SELECT assignment.Title, assignment.Description, assignment.Max, " .
							"       assignment.TopMark, assignment.BottomMark, assignment.CurveType, " .
							"       assignment.Weight, assignment.Date, assignment.CategoryListIndex, " .
							"       assignment.DueDate, assignment.Hidden, " .
							"       assignment.Uploadable, assignment.UploadName, " .
							"       subject.Name FROM assignment, subject " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex");
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);
		
		/* Print assignment information table with fields filled in */
		$dateinfo      = date($dateformat, strtotime($aRow['Date']));
		if(isset($aRow['DueDate'])) {
			$duedateinfo = date($dateformat, strtotime($aRow['DueDate']));
		} else {
			$duedateinfo = "";
		}
		$aRow['Title'] = htmlspecialchars($aRow['Title'], ENT_QUOTES);
		$curve_type    = $aRow['CurveType'];

		if($curve_type == 1) {
			$curvetype1 = "checked";
		} elseif ($curve_type == 2) {
			$curvetype2 = "checked";
		} else {
			$curvetype0 = "checked";
		}

		if($aRow['Hidden'] == 1) {
			$hidden = "checked";
		} else {
			$hidden = "";
		}

		if($aRow['Uploadable'] == 1) {
			$uploadable = "checked";
		} else {
			$uploadable = "";
		}

		$subtitle = $aRow['Name'];
		
		log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/modify.php", $LOG_TEACHER,
					"Viewed assignment ($title) for {$aRow['Name']}.");
		
		include "header.php";                                      // Show header
		
		echo "      <script language='JavaScript' type='text/javascript'>\n";
		echo "         window.onload = recalc_all;\n";
		echo "      </script>\n";
		echo "      <form action='$link' method='post' name='assignment'>\n";        // Form method
		echo "         <table class='transparent' align='center'>\n";
		echo "            <tr>\n";
		echo "               <td>Title:</td>\n";
		echo "               <td colspan='2'><input type='text' name='title' value='{$aRow['Title']}' " .
														"tabindex='1' size='50'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Date:</td>\n";
		echo "               <td colspan='2'><input type='text' name='date' value='{$dateinfo}' " .
														"tabindex='2' size='50'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Due Date:</td>\n";
		echo "               <td colspan='2'><input type='text' name='duedate' value='{$duedateinfo}' " .
														"tabindex='3' size='50'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Maximum score:</td>\n";
		echo "               <td colspan='2'><input type='text' name='max' id='max' onChange='recalc_all();' " .
														"value='{$aRow['Max']}' tabindex='4' size='50'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Weight:</td>\n";
		echo "               <td colspan='2'><input type='text' name='weight' value='{$aRow['Weight']}' " .
														"tabindex='5' size='50'></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Assignment Options:</td>\n";
		echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='recalc_all();' $hidden> " .
								"<label for='hidden'>Hidden from students</label><br>\n";
		echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7' $uploadable> " .
								"<label id='uploadable_lbl' for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
		echo "            </tr>\n";

		/* Get category info */
		$bsr =&  $db->query("SELECT category.CategoryName, categorylist.CategoryListIndex, " .
							"       categorylist.Weight, categorylist.TotalWeight FROM category, " .
							"       categorylist, subject, assignment " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex " .
							"AND   categorylist.SubjectIndex   = subject.SubjectIndex " .
							"AND   category.CategoryIndex      = categorylist.CategoryIndex " .
							"ORDER BY category.CategoryName");
		if(DB::isError($bsr)) die($bsr->getDebugInfo());           // Check for errors in query
		if($bsr->numRows() > 0) {
			echo "            <tr>\n";
			echo "               <td>Category:</td>\n";
			echo "               <td colspan='2'>\n";
			echo "                  <select name='category' tabindex='8'>\n";
			$selected = "";
//			if(is_null($aRow['CategoryIndex'])) $selected = " selected";
//			echo "                     <option value='NULL'$selected>(None)\n";
			while ($bRow =& $bsr->fetchRow(DB_FETCHMODE_ASSOC)) {
				$percentage = sprintf("%01.1f", ($bRow['Weight'] * 100) / $bRow['TotalWeight']);
				$selected = "";
				if($aRow['CategoryListIndex'] == $bRow['CategoryListIndex']) $selected = " selected";
				echo "                     <option value='{$bRow['CategoryListIndex']}'$selected>" .
															"{$bRow['CategoryName']} - {$percentage}%</option>\n";
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
		}

		$aRow['Description'] = htmlspecialchars($aRow['Description'], ENT_QUOTES);
		
		echo "            <tr>\n";
		echo "               <td>Description:</td>\n";
		echo "               <td colspan='2'><textarea rows='10' cols='50' name='descr' " .
														"tabindex='9'>{$aRow['Description']}</textarea></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td>Curve Type:</td>\n";
		echo "               <td>\n";
		echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type0' " .
										"value='0' tabindex='10' $curvetype0><label for='curve_type0'>None</label><br>\n";
		echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type1' " .
										"value='1' tabindex='11' $curvetype1><label for='curve_type1'>Maximum score is 100%</label><br>\n";
		echo "                  <input type='radio' name='curve_type' onChange='recalc_all();' id='curve_type2' " .
										"value='2' tabindex='12' $curvetype2><label for='curve_type2'>Distributed scoring</label>\n";
		echo "               </td>\n";
		echo "               <td>\n";
		echo "                  <label id='top_mark_label' for='top_mark'>Top mark: \n";
		echo "                  <input type='text' name='top_mark' id='top_mark' onChange='recalc_all();' " .
										"value='$top_mark' size='5' tabindex='13' onChange='recalc_all();'>%</label><br>\n";
		echo "                  <label id='bottom_mark_label' for='bottom_mark'>Bottom mark: \n";
		echo "                  <input type='text' name='bottom_mark' id='bottom_mark' onChange='recalc_all();' " .
										"value='$bottom_mark' size='5' tabindex='14' onChange='recalc_all();'>%</label><br>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Update' tabindex='15' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Cancel' tabindex='16' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete' tabindex='17' />&nbsp; \n";
		if(!is_null($next_subjectindex)) {
			echo "            <input type='hidden' name='next_subject' value='$next_subjectindex' /><input type='submit' name='action' value='Move this assignment to next term' tabindex='18' />&nbsp; \n";
		}
		echo "         </p>\n";
		echo "         <p></p>\n";
		/* Print scores and comments */
		$tabC = 18;
		$order = 1;
		if($res->numRows() > 0) {
			echo "         <table align='center' border='1'>\n"; // Table headers
			echo "            <tr>\n";
			echo "               <th>&nbsp;</th>\n";
			echo "               <th>Student</th>\n";
			echo "               <th>Score</th>\n";
			echo "               <th>Comment</th>\n";
			echo "            </tr>\n";
			
			/* For each student, print a row with the student's name and score on each assignment*/
			$alt_count   = 0;
			if($res->numRows() > 0) {
				$tabS = 17;
				$tabC = 17 + $res->numRows();
			}
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$tabS        += 1;
				$tabC        += 1;
				$alt_count   += 1;
				
				if($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				if($row['Score'] == $MARK_ABSENT) {
					$row['Score'] = 'A';
					$avg = "N/A";
				} elseif($row['Score'] == $MARK_EXEMPT) {
					$row['Score'] = 'E';
					$avg = "N/A";
				} elseif($row['Score'] == $MARK_LATE) {
					$row['Score'] = 'L';
					$avg = "0%";
				} else {
					if($curve_type == 1) {
						$avg = round(($row['Score'] / $max_score) * 100) . "%";
					} elseif($curve_type == 2) {
						if($m == 0 && $b == 0) {
							$avg = "0%";
						} else {
							$avg = round(($m * $row['Score']) + $b) . "%";
						}
					} else {
						$avg = round(($row['Score'] / $aRow['Max']) * 100) . "%";
					}
				}
				$row['Comment'] = htmlspecialchars($row['Comment'], ENT_QUOTES);
				
				echo "            <tr$alt id='row_{$row['Username']}'>\n";
				/*echo "               <td>{$row['ClassOrder']}</td>\n";*/
				echo "               <td>$order</td>\n";
				$order += 1;
				echo "               <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "               <td><input type='text' name='score_{$row['Username']}' id='score_{$row['Username']}' " .
											"value='{$row['Score']}' size='5' tabindex='$tabS' " .
											"onChange='recalc_avg(&quot;{$row['Username']}&quot;);'>" .
											" = <label name='avg_{$row['Username']}' id='avg_{$row['Username']}' " .
											"for='score_{$row['Username']}'>$avg</label></td>\n";
				echo "               <td><input type='text' name='comment_{$row['Username']}' " .
											"value='{$row['Comment']}' size='50' tabindex='$tabC'></td>\n";
				echo "            </tr>\n";
			}
			echo "         </table>\n";               // End of table
			echo "         <p></p>\n";
		} else {
			echo "          <p>No students in class list.</p>\n";
		}
		$tabUpdate = $tabC + 1;
		$tabCancel = $tabC + 2;
		$tabDelete = $tabC + 3;
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Update' tabindex='$tabUpdate' \>&nbsp; \n";
		echo "            <input type='submit' name='action' value='Cancel' tabindex='$tabCancel' \>&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete' tabindex='$tabDelete' \>&nbsp; \n";
		echo "         </p>\n";
		
		echo "      </form>\n";
	} else {  // User isn't authorized to view or change scores.
		/* Get subject name and log unauthorized access attempt */
		$asr =&  $db->query("SELECT subject.Name FROM assignment, subject " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex");
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);		
		log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify.php", $LOG_DENIED_ACCESS, 
					"Tried to modify assignment for {$aRow['Name']}.");
		
		/* Print error message */
		include "header.php";
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
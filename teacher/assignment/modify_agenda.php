<?php
	/*****************************************************************
	 * teacher/assignment/modify_agenda.php  (c) 2004-2010 Jonathan Dieter
	 *
	 * Show marks for already created agenda item and allow teacher to
	 * change them.
	 *****************************************************************/

	/* Get variables */
	$title           = dbfuncInt2String($_GET['keyname']);
	$assignmentindex = safe(dbfuncInt2String($_GET['key']));
	$link            = "index.php?location=" . dbfuncString2Int("teacher/assignment/new_or_modify_action.php") .
					   "&amp;key=" .               $_GET['key'] .
					   "&amp;next=" .              dbfuncString2Int($backLink);
	$use_extra_css  = true;
	$extra_js        = "assignment.js";

	include "core/settermandyear.php";

	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher, assignment " .
					   "WHERE subjectteacher.SubjectIndex = assignment.SubjectIndex " .
					   "AND   assignment.AssignmentIndex  = $assignmentindex " .
					   "AND   subjectteacher.Username     = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	if($res->numRows() > 0 or $is_admin) {	
		/* Get assignment info */
		$query =	"SELECT assignment.Title, assignment.Description, assignment.Max, " .
					"       assignment.DescriptionFileType, assignment.DescriptionData, " .
					"       assignment.TopMark, assignment.BottomMark, assignment.CurveType, " .
					"       assignment.Weight, assignment.Date, assignment.CategoryListIndex, " .
					"       assignment.DueDate, assignment.Hidden, assignment.Agenda, " .
					"       assignment.Uploadable, assignment.UploadName, " .
					"       subject.Name, subject.AverageType, subject.AverageTypeIndex " .
					"       FROM assignment, subject " .
					"WHERE assignment.AssignmentIndex  = $assignmentindex " .
					"AND   subject.SubjectIndex        = assignment.SubjectIndex";
		$asr =&  $db->query($query);
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);

		/* Check whether this is the current term, and if it isn't, whether the next term is open */
		if($termindex != $currentterm) {
			$query =	"SELECT TermIndex FROM term WHERE DepartmentIndex = $depindex ORDER BY TermNumber";
			$sres =& $db->query($query);
			if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query
			while($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($srow['TermIndex'] == $termindex) {
					if($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
						$next_termindex = $srow['TermIndex'];
					} else {
						$next_termindex = NULL;
					}
				}
			}
			if(!is_null($next_termindex)) {
				$query =	"SELECT subject.SubjectIndex FROM subject, subjectteacher " .
							"WHERE subject.Name         = '{$aRow['Name']}' " .
							"AND   subject.SubjectIndex = subjectteacher.SubjectIndex " .
							"AND   subject.TermIndex    = $next_termindex " .
							"AND   subject.YearIndex    = $yearindex " .
							"AND   subject.CanModify    = 1 ";
				if(!$is_admin) {
					$query .= "AND subjectteacher.Username = '$username'";
				}
				$sres =& $db->query($query);
				if(DB::isError($sres)) die($sres->getDebugInfo());           // Check for errors in query
				if($srow =& $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
					$next_subjectindex = $srow['SubjectIndex'];
				} else {
					$next_subjectindex = NULL;
				}
			}
		} else {
			$next_subjectindex = NULL;
		}
		
		$average_type       = $aRow['AverageType'];
		$average_type_index = $aRow['AverageTypeIndex'];
		
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

		if(isset($aRow['DescriptionFileType']) and $aRow['DescriptionFileType'] != "") {
			$descrtype0 = "";
			$descrtype1 = "checked";
		} else {
			$descrtype0 = "checked";
			$descrtype1 = "";
		}

		if($curve_type == 1) {
			$curvetype0 = "";
			$curvetype1 = "checked";
			$curvetype2 = "";
		} elseif ($curve_type == 2) {
			$curvetype0 = "";
			$curvetype1 = "";
			$curvetype2 = "checked";
		} else {
			$curvetype0 = "checked";
			$curvetype1 = "";
			$curvetype2 = "";
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
		
		log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/modify_agenda.php", $LOG_TEACHER,
					"Viewed agenda item ($title) for {$aRow['Name']}.");
		
		include "header.php";                                      // Show header
		
		echo "      <script language='JavaScript' type='text/javascript'>\n";
		echo "         window.onload = check_style;\n";
		echo "      </script>\n";
		
		echo "      <form action='$link' enctype='multipart/form-data' method='post' name='assignment'>\n";        // Form method
		echo "         <input type='hidden' id='agenda' name='agenda' value='1'>\n";
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
		echo "               <td>Agenda Options:</td>\n";
		echo "               <td colspan='2'><input type='checkbox' name='hidden' id='hidden' tabindex='6' onchange='check_style();' $hidden> " .
								"<label for='hidden'>Hidden from students</label><br>\n";
		echo "                  <input type='checkbox' name='uploadable' id='uploadable' tabindex='7' $uploadable> " .
								"<label id='uploadable_lbl' for='uploadable'>Allow students to upload files so you can access them</label></td>\n";
		echo "            </tr>\n";

		$aRow['Description'] = htmlspecialchars(unhtmlize_comment($aRow['Description']), ENT_QUOTES);
		$currentdata = "None";
		if(isset($aRow['DescriptionFileType'])) {
			if($aRow['DescriptionFileType'] == "application/pdf") {
				$currentdata = "PDF Document";
			} elseif($aRow['DescriptionFileType'] != "") {
				$currentdata = "Unknown format";
			}
		}

		echo "            <tr>\n";
		echo "               <td>Description:</td>\n";
		echo "               <td colspan='2'>\n";
		echo "                  <input type='radio' name='descr_type' id='descr_type0' value='0' tabindex='9' onChange='descr_check();' $descrtype0>\n";
		echo "                  <textarea style='vertical-align: top' rows='10' cols='50' id='descr' name='descr' tabindex='10'>{$aRow['Description']}</textarea><br>\n";
		echo "                  <input type='radio' name='descr_type' id='descr_type1' value='1' tabindex='11' onChange='descr_check();' $descrtype1>\n";
		echo "                  <input type='file' name='descr_upload' id='descr_upload' tabindex='12' accept='application/pdf'><input type='hidden' name='MAX_FILE_SIZE' value='10240000'><br>\n";
		echo "                  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current file: <i>$currentdata</i>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Update' tabindex='18' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Cancel' tabindex='19' />&nbsp; \n";
		echo "            <input type='submit' name='action' value='Delete' tabindex='20' />&nbsp; \n";
		if($averagetype != $AVG_TYPE_NONE) {
			echo "            <input type='submit' name='action' value='Convert to assignment' tabindex='21' \>&nbsp; \n";
		}
		if(!is_null($next_subjectindex)) {
			echo "            <input type='hidden' name='next_subject' value='$next_subjectindex' /><input type='submit' name='action' value='Move this assignment to next term' tabindex='21' />&nbsp; \n";
		}
		echo "         </p>\n";
		echo "      </form>\n";
	} else {  // User isn't authorized to view or change scores.
		/* Get subject name and log unauthorized access attempt */
		$asr =&  $db->query("SELECT subject.Name FROM assignment, subject " .
							"WHERE assignment.AssignmentIndex  = $assignmentindex " .
							"AND   subject.SubjectIndex        = assignment.SubjectIndex");
		if(DB::isError($asr)) die($asr->getDebugInfo());           // Check for errors in query
		$aRow =& $asr->fetchRow(DB_FETCHMODE_ASSOC);		
		log_event($LOG_LEVEL_ERROR, "teacher/assignment/modify_agenda.php", $LOG_DENIED_ACCESS, 
					"Tried to modify agenda item for {$aRow['Name']}.");
		
		/* Print error message */
		include "header.php";
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>

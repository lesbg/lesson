<?php
	/*****************************************************************
	 * teacher/report/proofread_list.php  (c) 2008 Jonathan Dieter
	 *
	 * View all students and basic report info
	 *****************************************************************/

	/* Get variables */
	$title         = "Reports awaiting proofreading";

	include "core/settermandyear.php";
	include "header.php";                                      // Show header

	$query =	"SELECT user.Gender, user.FirstName, user.Surname, user.Username, " .
				"       class.ClassName, term.TermName, class.ClassIndex, " .
				"       classterm.CTComment, classterm.HODComment, " .
				"       classterm.CTCommentDone, classterm.HODCommentDone, " .
				"       classterm.PrincipalComment, classterm.PrincipalCommentDone, " .
				"       classterm.PrincipalUsername, classterm.HODUsername, " .
				"       classterm.ReportDone, classterm.ReportProofread, " .
				"       classterm.ReportProofDone, term.TermIndex, " .
				"       classterm.ReportPrinted, class_term.CTCommentType, " .
				"       class_term.HODCommentType, class_term.PrincipalCommentType " .
				"       FROM department, user, classlist, class, class_term, classterm, term " .
				"WHERE user.Username                  = classlist.Username " .
				"AND   classterm.TermIndex            = class_term.TermIndex " .
				"AND   classterm.ClassListIndex       = classlist.ClassListIndex " .
				"AND   classterm.ReportProofread      = 1 " .
				"AND   classterm.ReportProofDone      = 0 " .
				"AND   class_term.ClassIndex          = classlist.ClassIndex " .
				"AND   class_term.CanDoReport         = 1 " .
				"AND   class.ClassIndex               = classlist.ClassIndex " .
				"AND   department.DepartmentIndex     = class.DepartmentIndex " .
				"AND   department.ProofreaderUsername = '$username' " .
				"AND   term.TermIndex                 = class_term.TermIndex " .
				"ORDER BY term.TermNumber, class.Grade, class.ClassName, " .
				"         user.FirstName, user.Surname, user.Username";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

	$order = 1;
	if($res->numRows() == 0) {
		echo "          <p>No reports need to be proofread by you.</p>\n";
		include "footer.php";
		exit(0);
	}

	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>Term</th>\n";
	echo "               <th>Class</th>\n";
	echo "               <th>Student</th>\n";
	echo "               <th>Class Teacher's Comment</th>\n";
	echo "               <th>Finished</th>\n";
	echo "               <th>Head of Department's Comment</th>\n";
	echo "               <th>Finished</th>\n";
	echo "               <th>Principal's Comment</th>\n";
	echo "               <th>Finished</th>\n";
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

		echo "               <td>{$row['TermName']}</td>\n";
		echo "               <td>{$row['ClassName']}</td>\n";

		$name = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
		$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_modify.php") .
				"&amp;key=" .               dbfuncString2Int($row['ClassIndex']) .
				"&amp;key2=" .              dbfuncString2Int($row['Username']) .
				"&amp;key3=" .              dbfuncString2Int($row['TermIndex']) .
				"&amp;keyname=" .           dbfuncString2Int($row['ClassName']) .
				"&amp;keyname2=" .          dbfuncString2Int($name);

		echo "               <td><a href='$link'>$name</a></td>\n";

		
		if($row['CTCommentType'] == $COMMENT_TYPE_MANDATORY or $row['CTCommentType'] == $COMMENT_TYPE_OPTIONAL) {
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
			echo "               <td colspan='2' align='center'><i>N/A</i></td>\n";
		}

		if($row['HODCommentType'] == $COMMENT_TYPE_MANDATORY or $row['HODCommentType'] == $COMMENT_TYPE_OPTIONAL) {
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
			echo "               <td colspan='2' align='center'><i>N/A</i></td>\n";
		}

		if($row['PrincipalCommentType'] == $COMMENT_TYPE_MANDATORY or $row['PrincipalCommentType'] == $COMMENT_TYPE_OPTIONAL) {
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
			echo "               <td colspan='2' align='center'><i>N/A</i></td>\n";
		}

		if($row['ReportDone'] and $row['ReportProofread'] and !$row['ReportProofDone']) {
			echo "               <td>Awaiting proofreading</td>\n";
		} elseif($row['ReportDone'] and !$row['ReportPrinted']) {
			echo "               <td>Awaiting printing</td>\n";
		} elseif($row['ReportDone'] and $row['ReportPrinted']) {
			echo "               <td><i>Finished</i></td>\n";
		} else {
			echo "               <td><b>Open</b></td>\n";
		}
		echo "            </tr>\n";
	}
	echo "         </table>\n";               // End of table
	include "footer.php";
?>
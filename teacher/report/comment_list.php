<?php
	/*****************************************************************
	 * teacher/report/comment_list.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * Show available comments
	 * This should only be included from modify_action.php
	 *****************************************************************/
	
	/*if($is_admin) $showalldeps = true;
	include "core/settermandyear.php";*/

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

	/* Check whether user is authorized to change scores */
	$query =		"(SELECT subjectteacher.Username FROM subjectteacher " .
					" WHERE subjectteacher.SubjectIndex = $subjectindex " .
					" AND   subjectteacher.Username     = '$username') ";
	if($student_username != "") {
		$query .=	"UNION " .
					"(SELECT department.ProofreaderUsername FROM department, subject " .
					" WHERE subject.SubjectIndex        = $subjectindex " .
					" AND   department.DepartmentIndex  = subject.DepartmentIndex) " .
					"UNION " .
					"(SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
					" WHERE  classlist.Username         = '$student_username' " .
					" AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
					" AND    classterm.TermIndex        = $termindex " .
					" AND    classterm.ClassIndex       = class.ClassIndex " .
					" AND    class.ClassTeacherUsername = '$username' " .
					" AND    class.YearIndex            = $yearindex) ";
	}
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() == 0 and !$is_admin and !$is_hod and !$is_principal) {
		/* Print error message */
		include "header.php";                                      // Show header

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	/* Check whether subject is open for report editing */
	$query =	"SELECT subject.AverageType, subject.EffortType, subject.ConductType, " .
				"       subject.AverageTypeIndex, subject.EffortTypeIndex, " .
				"       subject.ConductTypeIndex, subject.CommentType, subject.CanDoReport " .
				"       FROM subject " .
				"WHERE subject.SubjectIndex = $subjectindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
		/* Print error message */
		include "header.php";                                      // Show header

		echo "      <p>Reports for this subject aren't open.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/comment_list.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	$st_username_click = safe($st_username_click);
	$query =	"SELECT user.FirstName, user.Surname, user.Gender FROM user " .
				"WHERE  user.Username = '$st_username_click'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		/* Print error message */
		include "header.php";                                      // Show header

		echo "      <p>Can't find $st_username_click.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}
	$student_name = "{$row['FirstName']} {$row['Surname']} ($st_username_click)";
	$student_firstname = $row['FirstName'];
	$student_fullname  = "{$row['FirstName']} {$row['Surname']}";
	$student_gender = $row['Gender'];

	$title = "Comment for $student_name";
	include "header.php";

	$link          = "index.php?location=" . dbfuncString2Int("teacher/report/modify_action.php") .
					 "&amp;key=" .               $_GET['key'] .
					 "&amp;keyname=" .           $_GET['keyname'] .
					 "&amp;next=" .              $_GET['next'];
	if(isset($_GET['key2'])) {
		$link .=	 "&amp;key2=" .               $_GET['key2'] .
					 "&amp;keyname2=" .           $_GET['keyname2'];
	}

	echo "      <form action='$link' method='post' name='report'>\n";        // Form method
	echo "         <input type='hidden' name='student_username' value='$st_username_click'>\n";
	foreach($_POST AS $postkey => $postval) {
		if(substr($postkey, 0, 8)  == "comment_") {
			$postval = htmlspecialchars($postval, ENT_QUOTES);
			echo "         <input type='hidden' name='$postkey' value='$postval'>\n";
		} elseif(substr($postkey, 0, 7) == "effort_" or substr($postkey, 0, 8) == "conduct_" or substr($postkey, 0, 5) == "cval_") {
			echo "         <input type='hidden' name='$postkey' value='$postval'>\n";
		}
	}

	echo "         <table align='center' border='1'>\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>#</th>\n";
	echo "               <th>Comment</th>\n";
	echo "               <th>&nbsp;</th>\n";
	echo "            </tr>\n";

	$query =	"SELECT comment.CommentIndex, comment.Comment " .
				"       FROM comment LEFT OUTER JOIN commenttype USING (CommentIndex), subject " .
				"WHERE (commenttype.SubjectTypeIndex IS NULL " .
				"       OR commenttype.SubjectTypeIndex = subject.SubjectTypeIndex) " .
				"AND   subject.SubjectIndex = $subjectindex " .
				"ORDER BY CommentIndex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	$alt_count   = 0;
	while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$alt_count   += 1;
		
		if($alt_count % 2 == 0) {
			$alt = " class='alt'";
		} else {
			$alt = " class='std'";
		}

		$comment_array = get_comment($st_username_click, $row['CommentIndex']);
		$comment = $comment_array[0];

		echo "            <tr$alt id='row_{$row['CommentIndex']}'>\n";
		echo "               <td>{$row['CommentIndex']}</td>\n";
		echo "               <td>$comment</td>\n";
		echo "               <td><input type='submit' name='cupdate_{$row['CommentIndex']}' value='Choose'></td>\n";
		echo "            </tr>\n";
	}
	echo "         </table>\n";
	echo "         <p align='center'><input type='submit' name='comment_action' value='Cancel'></p>\n";
	echo "      </form>\n";

	include "footer.php";
?>
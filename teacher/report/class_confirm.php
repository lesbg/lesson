<?php
	/*****************************************************************
	 * teacher/report/class_confirm.php  (c) 2008 Jonathan Dieter
	 *
	 * Confirm change report information for class at a time
	 *****************************************************************/

	/* Get variables */
	if(!isset($_GET['next'])) $_GET['next'] = dbfuncString2Int($backLink);
	$class            = dbfuncInt2String($_GET['keyname']);
	$classtermindex   = safe(dbfuncInt2String($_GET['key']));
	$nextLink         = dbfuncInt2String($_GET['next']);              // Link to next page
	$error            = false;    // Boolean to store any errors
	
	/* Check whether subject is open for report editing */
	$query =	"SELECT classterm.AverageType, classterm.EffortType, classterm.ConductType, " .
				"       classterm.AverageTypeIndex, classterm.EffortTypeIndex, " .
				"       classterm.ConductTypeIndex, classterm.CTCommentType, " .
				"       classterm.HODCommentType, classterm.PrincipalCommentType, " .
				"       classterm.CanDoReport, classterm.AbsenceType, class.DepartmentIndex, " .
				"       department.ProofreaderUsername " .
				"       FROM classterm, class, department " .
				"WHERE classterm.ClassTermIndex    = $classtermindex " .
				"AND   class.ClassIndex = classterm.ClassIndex " .
				"AND   department.DepartmentIndex = class.DepartmentIndex ";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC) or $row['CanDoReport'] == 0) {
		/* Print error message */
		include "header.php";
		echo "      <p>Reports for this class aren't open.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_action.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	$ct_comment_type    = $row['CTCommentType'];
	$hod_comment_type   = $row['HODCommentType'];
	$pr_comment_type    = $row['PrincipalCommentType'];
	$can_do_report      = $row['CanDoReport'];
	$depindex           = $row['DepartmentIndex'];
	$proof_username     = $row['ProofreaderUsername'];

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
					   "WHERE class.ClassIndex           = classterm.ClassIndex " .
					   "AND   classterm.ClassTermIndex   = $classtermindex " .
					   "AND   class.ClassTeacherUsername = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_ct = true;
	} else {
		$is_ct = false;
	}
	
	/* Check whether user is proofreader */
	if($proof_username == $username) {
		$is_proofreader = true;
	} else {
		$is_proofreader = false;
	}

	include "core/settermandyear.php";

	if(!$is_ct and !$is_hod and !$is_principal and !$is_admin and !$is_proofreader) {
		include "header.php";                                      // Show header

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		log_event($LOG_LEVEL_ERROR, "teacher/report/class_confirm.php", $LOG_DENIED_ACCESS,
					"Tried to modify report for $subject.");

		include "footer.php";
		exit(0);
	}

	$title         = "LESSON - Confirm";
	$noJS          = true;
	$noHeaderLinks = true;
	

	include "header.php";
	if($_POST['action'] == "Finished with all comments") {
		$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_action.php") .
				"&amp;key=" .               $_GET['key'] .
				"&amp;keyname=" .           $_GET['keyname'] .
				"&amp;next=" .              $_GET['next'];
		
		echo "      <p align='center'>Are you <b>sure</b> you are finished working on your comments for $class?</p>\n";
		echo "      <form action='$link' method='post'>\n";
		echo "         <p align='center'>";
		echo "            <input type='submit' name='action' value='Yes, I&#039;m finished' \>&nbsp; \n";
		echo "            <input type='submit' name='action' value='No, I changed my mind' \>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} elseif($_POST['action'] == "Close all reports") {
		$link =	"index.php?location=" . dbfuncString2Int("teacher/report/class_action.php") .
				"&amp;key=" .               $_GET['key'] .
				"&amp;keyname=" .           $_GET['keyname'] .
				"&amp;next=" .              $_GET['next'];
		
		echo "      <p align='center'>Are you <b>sure</b> you want to close all reports for $class?</p>\n";
		echo "      <form action='$link' method='post'>\n";
		echo "         <p align='center'>";
		echo "            <input type='submit' name='action' value='Yes, close reports'>&nbsp; \n";
		echo "            <input type='submit' name='action' value='No, I changed my mind'>&nbsp; \n";
		echo "         </p>";
		echo "      </form>\n";
	} else {
		echo "      <p align='center'>I'm not sure what you're trying to do.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	include "footer.php";
?>
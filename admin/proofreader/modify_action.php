<?php
	/*****************************************************************
	 * admin/proofreader/modify_action.php  (c) 2008 Jonathan Dieter
	 *
	 * Run query to update department proofreader to a certain value
	 *****************************************************************/
	 
	$choice_depindex   = safe(dbfuncInt2String($_GET['key']));
	$choice_department = dbfuncInt2String($_GET['keyname']);
	$nextLink     = dbfuncInt2String($_GET['next']);
	$error        = false;

	if($_POST['action'] == "Ok") {
		$title         = "LESSON - Saving changes...";
		$noJS          = true;
		$noHeaderLinks = true;
		
		include "header.php";
	
		echo "      <p align='center'>Saving changes...";
		/* Check whether user is authorized to change proofreader */
		if($is_admin or $is_principal) {
			if(isset($_POST['teacher']) and $_POST['teacher'] != "" and $_POST['teacher'] != "!none") {
				$_POST['teacher'] = safe($_POST['teacher']);
				$aRes =& $db->query("UPDATE department SET ProofreaderUsername='{$_POST['teacher']}' " .
									"WHERE  DepartmentIndex = $choice_depindex");
				if(DB::isError($aRes)) die($aRes->getDebugInfo());
			} else {
				$aRes =& $db->query("UPDATE department SET ProofreaderUsername=NULL " .
									"WHERE  DepartmentIndex = $choice_depindex");
				if(DB::isError($aRes)) die($aRes->getDebugInfo());
			}
			log_event($LOG_LEVEL_ADMIN, "admin/proofreader/modify_action.php", $LOG_ADMIN,
					"Changed proofreader for $choice_department department.");
		} else {
			/* Log unauthorized access attempt */
			log_event($LOG_LEVEL_ERROR, "admin/proofreader/modify_action.php", $LOG_DENIED_ACCESS,
					"Attempted to change proofreader for $choice_department.");
			echo "</p>\n      <p align='center'>You do not have permission to change this proofreader.</p>\n      <p align='center'>";
			$error = true;
		}
		if($error) {
			echo "failed.</p>\n";
		} else {
			echo "done.</p>\n";
		}
		echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n";  // Link to next page
	} else {
		$extraMeta     = "      <meta http-equiv='REFRESH' content='0;url=$nextLink'>\n";
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Cancelling...";
		
		include "header.php";
		
		echo "      <p align='center'>Cancelling and redirecting you to <a href='$nextLink'>$nextLink</a>." . 
					"</p>\n";
		
		include "footer.php";
	}

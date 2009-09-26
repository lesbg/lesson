<?php
	// FIX CLASS STUFF
	
	/*****************************************************************
	 * admin/class/modify_action.php  (c) 2005-2009 Jonathan Dieter
	 *
	 * Add or remove students from class
	 *****************************************************************/

	/* Get variables */
	$nextLink   = dbfuncInt2String($_GET['next']);              // Link to next page
	$classindex = dbfuncInt2String($_GET['key']);               // Index of class to add and remove students from
	$classname  = dbfuncInt2String($_GET['keyname']);
	
	include "core/settermandyear.php";
	
	/* Check whether user is authorized to change class */	
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		/* Update class year */
		$res =& $db->query("SELECT YearIndex FROM class " . 
						   "WHERE ClassIndex = $classindex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$classyear = $row['YearIndex'];
		} else {
			die("Unable to find class with index $classindex!");
		}

		/* Get classterm */
		$res =& $db->query("SELECT ClassTermIndex FROM classterm " . 
						   "WHERE ClassIndex = $classindex" .
						   "AND   TermIndex = $termindex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$classterm = $row['ClassTermIndex'];
		} else {
			die("Unable to find class term with index $classindex!");
		}
		
		/* Check which button was pressed */
		if($_POST["action"] == ">>") {                                   // If >> was pressed, remove students from
			foreach($_POST['removefromclass'] as $remUserName) {         //  class
				$res =& $db->query("DELETE FROM classlist " . 
								   "WHERE Username = '$remUserName' " .
								   "AND   ClassTermIndex = $classterm");
								   
				if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				log_event($LOG_LEVEL_ADMIN, "admin/class/modify_action.php", $LOG_ADMIN,
					"Removed $remUserName from $classname.");
			}
			update_classterm($classindex, $currentterm);

			include "admin/class/modify.php";
		} elseif($_POST["action"] == "<<") {                             // If << was pressed, add students to
			foreach($_POST['addtoclass'] as $addUserName) {              //  class
				$res =& $db->query("INSERT INTO classlist (ClassListIndex, Username, ClassIndex) VALUES " .
								   "                      (\"$addUserName$classyear\", \"$addUserName\", $classindex)");
				if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				//update_conduct_mark($addUserName // Where the flip do I find the term?
				log_event($LOG_LEVEL_ADMIN, "admin/class/modify_action.php", $LOG_ADMIN,
					"Added $addUserName to $classname.");
			}
			update_classterm($classindex, $currentterm);

			include "admin/class/modify.php";
		}  elseif($_POST["actiont"] == ">>") {       // If >> was pressed, remove teacher from
			$res =&  $db->query("SELECT ClassTeacherUsername FROM class " .
								"WHERE ClassIndex=$classindex");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$remUserName = $row['ClassTeacherUsername'];
				if($remUserName != "") {
					$res =&  $db->query("UPDATE class SET ClassTeacherUsername=NULL " .
										"WHERE ClassIndex=$classindex");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
					log_event($LOG_LEVEL_ADMIN, "admin/class/modify_action.php", $LOG_ADMIN,
							"Removed $remUserName from being class teacher for $classname.");
				}
			}
			include "admin/class/modify.php";
		} elseif($_POST["actiont"] == "<<") {
			$res =&  $db->query("SELECT ClassTeacherUsername FROM class " .
								"WHERE ClassIndex=$classindex");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$remUserName = $row['ClassTeacherUsername'];
				$addUserName = $_POST['addtoteacherlist'];
				$res =&  $db->query("UPDATE class SET ClassTeacherUsername=\"$addUserName\" " .
									"WHERE ClassIndex=$classindex");
				if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				if($remUserName != "") {
					log_event($LOG_LEVEL_ADMIN, "admin/class/modify_action.php", $LOG_ADMIN,
							"Removed $remUserName from being class teacher for $classname.");
				}	
				log_event($LOG_LEVEL_ADMIN, "admin/class/modify_action.php", $LOG_ADMIN,
							"Set $addUserName as class teacher for $classname.");
			}
			include "admin/class/modify.php";
		} elseif($_POST["action"] == "Done") {
			$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Redirecting...";
			
			include "header.php";
			
			echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";
			
			include "footer.php";
		} else {
			include "admin/class/modify.php";
		}
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/class/modify_action.php", $LOG_DENIED_ACCESS,
				"Attempted to modify class $classname.");
		
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Unauthorized access!";
		
		include "header.php";
		
		echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
		                               "\"$nextLink\">Click here to continue.</a></p>\n";
		
		include "footer.php";
	}
	
?>
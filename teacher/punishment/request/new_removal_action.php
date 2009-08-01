<?php
	/*****************************************************************
	 * teacher/punishment/request/new_removal_action.php  (c) 2006 Jonathan Dieter
	 *
	 * Insert new punishment removal request into database
	 *****************************************************************/

	 /* Get variables */
	$disciplineindex  = safe(dbfuncInt2String($_GET['key']));
	$link             = dbfuncInt2String($_GET['next']);

	include "core/settermandyear.php";
	
	$query =	"SELECT user.Username, user.FirstName, user.Surname FROM user, discipline " .
				"WHERE user.Username = discipline.Username " .
				"AND   discipline.DisciplineIndex = $disciplineindex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$student = "{$row['FirstName']} {$row['SurName']} ({$row['Username']})";
	} else {
		$student = "Unknown student";
	}

	$query =	"SELECT discipline.DisciplineIndex " .
				"       FROM disciplineweight, discipline " .
				"WHERE  discipline.WorkerUsername = \"$username\" " .
				"AND    discipline.DisciplineIndex = $disciplineindex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$is_teacher = true;
	} else {
		$is_teacher = false;
	}

	if($is_admin or $is_teacher) {
		/* Check which button was pressed */
		if($_POST["action"] == "Save" || $_POST["action"] == "Update") { // If update or save were pressed, print  
			$title         = "LESSON - Saving punishment removal request...";
			$noHeaderLinks = true;
			$noJS          = true;
			
			include "header.php";                                        // Print header
			
			echo "      <p align=\"center\">Saving punishment removal request...";
			
			$dateinfo = "'" . dbfuncCreateDate(date($dateformat)) . "'";
			
			/* Check whether or not a type was included and cancel if it wasn't */
			if($_POST['note'] == "" or is_null($_POST['note'])) {
				echo "failed</p>\n";
				echo "      <p align=\"center\">You must give a reason you want the punishment removed!</p>\n";
			} else {
				$reason = $db->escapeSimple($_POST['note']);

				$failed = 0;

				if($_POST["action"] == "Save") {
					$query =	"INSERT INTO disciplinebacklog (DisciplineIndex, WorkerUsername, " .
								"                               Date, RequestType, Comment) " .
								"       VALUES " .
								"       ($disciplineindex, '$username', $dateinfo, 2, '$reason')";
					$res =& $db->query($query);
					if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
					echo " done</p>\n";
					log_event($LOG_LEVEL_TEACHER, "teacher/punishment/request/new_removal_action.php", $LOG_TEACHER,
						"Created new punishment removal request for $student.");
				} else {
				}
			}
			
			echo "      <p align=\"center\"><a href=\"$link\">Continue</a></p>\n";  // Link to next page
			
			include "footer.php";
		}/* elseif($_POST["action"] == 'Delete') {          // If delete was pressed, confirm deletion
			include "teacher/confirmdelete";
		} */ else {
			$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$link\">\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Cancelling...";
			
			include "header.php";
			
			echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$link\">$link</a>." . 
						"</p>\n";
			
			include "footer.php";
		}
	} else {   // User isn't authorized to create punishment request
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/new_removal_action.php", $LOG_DENIED_ACCESS,
					"Tried to create a punishment removal request for $student.");
		$title         = "LESSON - Unauthorized access";
		$noHeaderLinks = true;
		$noJS          = true;
		
		include "header.php";                                        // Print header

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
	}
?>
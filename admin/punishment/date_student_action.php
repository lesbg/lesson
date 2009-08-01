<?php
	/*****************************************************************
	 * admin/punishment/date_student_action.php  (c) 2006 Jonathan Dieter
	 *
	 * Add students to discipline date
	 *****************************************************************/

	/* Get variables */
	$dtype    = intval($_POST['type']);
	$nextLink = dbfuncInt2String($_GET['next']);             // Link to next page
	
	include "core/settermandyear.php";
	
	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	/* Check whether user is authorized to issue mass punishment */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm >= $PUN_PERM_ALL) {
		/* Check which button was pressed */
		if($_POST["action"] == "Done" or $_POST["action"] == "Edit")  {
			
			/* Check whether or not a type was included and cancel if it wasn't */
			$tusername = $db->escapeSimple($_POST['teacher']);
			$dtype   = intval(dbfuncInt2String($_GET['type']));

			$query =	"SELECT DisciplineDateIndex, EndDate FROM disciplinedate WHERE DisciplineTypeIndex=$dtype AND Done=0";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$pindex = $row['DisciplineDateIndex'];
				$enddate = $row['EndDate'];
			} else {
				die("Error finding punishment date.\n");
			}
			$query =	"UPDATE discipline SET DisciplineDateIndex=NULL " .
						"WHERE DisciplineDateIndex=$pindex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			foreach($_POST['mass'] as $punusername) {
				$query =	"SELECT discipline.DisciplineIndex " .
							"       FROM disciplinetype, disciplineweight, " .
							"       discipline LEFT OUTER JOIN disciplinedate ON " .
							"       discipline.DisciplineDateIndex=disciplinedate.DisciplineDateIndex " .
							"WHERE  disciplineweight.YearIndex = $yearindex " .
							"AND    discipline.WorkerUsername IS NOT NULL " .
							"AND    disciplinedate.PunishDate IS NULL " .
							"AND    discipline.Date <= '$enddate' " .
							"AND    disciplinetype.DisciplineTypeIndex = $dtype " .
							"AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
							"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
							"AND    discipline.Username = '$punusername' ";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
				while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$dindex = $row['DisciplineIndex'];
					$query =	"UPDATE discipline SET DisciplineDateIndex=$pindex " .
								"WHERE DisciplineIndex=$dindex";
					$nres =&  $db->query($query);
					if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
				}
			}
			$query =	"UPDATE discipline SET ServedType=NULL " .
						"WHERE DisciplineDateIndex IS NULL";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			if($_POST["action"] == "Done") {
				log_event($LOG_LEVEL_ADMIN, "admin/punishment/date_student_action.php", $LOG_ADMIN,
							"Set up next punishment date.");

				$title         = "LESSON - Setting punishment date...";
				$noHeaderLinks = true;
				$noJS          = true;
				
				include "header.php";                                        // Print header
				
				echo "      <p align=\"center\">Setting punishment date...done</p>\n";
				
				echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";  // Link to next page
				
				include "footer.php";
			} else {
				$_GET['next'] = dbfuncString2Int("index.php?location=" . 
									dbfuncString2Int("admin/punishment/date_student.php") .
									"&amp;type=" .	$_GET['type'] .
									"&amp;next=" .  $_GET['next']);
				include "admin/punishment/set_date.php";
			}
		} else {
			include "admin/punishment/date_student.php";
		}
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/date_student_action.php", $LOG_DENIED_ACCESS,
				"Attempted to set punishment date.");
		
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Unauthorized access!";
		
		include "header.php";
		
		echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
		                               "\"$nextLink\">Click here to continue.</a></p>\n";
		
		include "footer.php";
	}
	
?>
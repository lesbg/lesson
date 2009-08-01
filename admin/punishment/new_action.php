<?php
	/*****************************************************************
	 * admin/punishment/new_action.php  (c) 2006 Jonathan Dieter
	 *
	 * Create a punishment
	 *****************************************************************/

	 /* Get variables */
	$studentusername  = dbfuncInt2String($_GET['key']);
	$student          = dbfuncInt2String($_GET['keyname']);
	$link             = dbfuncInt2String($_GET['next']);

	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	include "core/settermandyear.php";
	
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm >= $PUN_PERM_ISSUE) {
		/* Check which button was pressed */
		if($_POST["action"] == "Save") { // If update or save were pressed, print  
			$title         = "LESSON - Saving punishment...";
			$noHeaderLinks = true;
			$noJS          = true;
			
			include "header.php";                                        // Print header
			
			echo "      <p align=\"center\">Saving punishment...";
			
			if(!isset($_POST['date']) || $_POST['date'] == "") {         // Make sure date is in correct format.
				echo "</p>\n      <p>Date not entered, defaulting to today.</p>\n      <p>";       // Print error message
				$_POST['date'] =& dbfuncCreateDate(date($dateformat));
			} else {
				$_POST['date'] =& dbfuncCreateDate($_POST['date']);
			}
			$dateinfo = "'" . $db->escapeSimple($_POST['date']) . "'";
			$thisdateinfo = "'" . dbfuncCreateDate(date($dateformat)) . "'";
			
			/* Check whether or not a type was included and cancel if it wasn't */
			if($_POST['type'] == "" or is_null($_POST['type'])) {
				echo "failed</p>\n";
				echo "      <p align=\"center\">You must select a punishment type!</p>\n";
			} else {
				$weightindex = intval($_POST['type']);
				$query =	"SELECT DisciplineWeightIndex FROM disciplineweight " .
							"WHERE  disciplineweight.DisciplineWeightIndex = $weightindex " .
							"AND    disciplineweight.YearIndex = $currentyear " .
							"AND    disciplineweight.TermIndex = $currentterm ";
				$res =& $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
				$failed = 0;
				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					if($_POST['reason'] == "" or is_null($_POST['reason'])) {
						echo "failed</p>\n";
						echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
						$failed = 1;
					} elseif($_POST['reason'] == "other") {
						if($_POST['reasonother'] == "" or is_null($_POST['reasonother'])) {
							echo "failed</p>\n";
							echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
							$failed = 1;
						} else {
							$reason = $db->escapeSimple($_POST['reasonother']);
						}
					} else {
						$reasonindex = intval($_POST['reason']);
						$query =	"SELECT DisciplineReason FROM disciplinereason " .
									"WHERE  DisciplineReasonIndex = $reasonindex";
						$res =& $db->query($query);
						if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
						if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
							$reason = $row['DisciplineReason'];
						} else {
							echo "failed</p>\n";
							echo "      <p align=\"center\">You must explain why you want the student punished!</p>\n";
							$failed = 1;
						}
					}
					if($failed == 0) {
						if($_POST["action"] == "Save") {
							$query =	"INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
										"                        RecordUsername, DateRequested, DateIssued, " .
										"                        Date, Comment) " .
										"       VALUES " .
										"       ($weightindex, '$studentusername', '$username', '$username', " .
										"        $thisdateinfo, $thisdateinfo, $dateinfo, '$reason')";
							$res =& $db->query($query);
							if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
							update_conduct_mark($studentusername);
							echo " done</p>\n";
							log_event($LOG_LEVEL_ADMIN, "admin/punishment/new_action.php", $LOG_ADMIN,
								"Issued punishment for $student.");
						} else {
						}
					}
				} else {
					echo "failed</p>\n";
					echo "      <p align=\"center\">There is no punishment of selected type!</p>\n";
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
		log_event($LOG_LEVEL_ERROR, "admin/punishment/new_action.php", $LOG_DENIED_ACCESS,
					"Tried to create a punishment for $student.");
		$title         = "LESSON - Unauthorized access";
		$noHeaderLinks = true;
		$noJS          = true;
		
		include "header.php";                                        // Print header

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
	}
?>
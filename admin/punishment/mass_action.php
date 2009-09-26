<?php
	// FIX CLASS STUFF
	
	/*****************************************************************
	 * admin/punishment/mass_action.php  (c) 2006 Jonathan Dieter
	 *
	 * Do the actual issuing of punishments to many students at once
	 *****************************************************************/

	/* Get variables */
	$nextLink     = dbfuncInt2String($_GET['next']);             // Link to next page
	
	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}
	
	$showalldeps = true;
	include "core/settermandyear.php";
	
	/* Check whether user is authorized to issue mass punishment */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm >= $PUN_PERM_MASS) {
		if(isset($_POST["punished"])) {
			$punish_list = dbfuncString2Array($_POST["punished"]);
		} else {
			$punish_list = array();
		}
		
		/* Check which button was pressed */
		if($_POST["action"] == ">") {                                    // If > was pressed, remove selected students from
			foreach($_POST['removefrompunishment'] as $remUserName) {    //  punishment
				unset($punish_list[$remUserName]);
			}
			include "admin/punishment/mass.php";
		} elseif($_POST["action"] == ">>") {
			unset($punish_list);
			$punish_list = array();
			include "admin/punishment/mass.php";
		} elseif($_POST["action"] == "<") {                                   // If < was pressed, add selected students to
			$selected_classes_sql = "";
			foreach($punish_list as $studentusername=>$student) {
				if(substr($studentusername, 0, 1) == "!") {
					$classindex = intval(substr(strrchr($studentusername, "!"), 1));
					$selected_classes_sql .= "OR classlist.ClassIndex=$classindex ";
				}
			}
			foreach($_POST['addtopunishment'] as $addUserName) {
				$query =	"SELECT user.FirstName, user.Surname FROM user, class, classlist " .
							"WHERE user.Username = '$addUserName' " .
							"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
							"AND   classterm.TermIndex = $termindex " .
							"AND   classterm.ClassIndex = class.ClassIndex " .
							"AND   class.YearIndex = $yearindex " .
							"AND   classlist.Username = user.Username " .
							"AND NOT (1 = 0 " .                    // Will never be true, but OR'd with
							"         $selected_classes_sql " .    //  $selected_classes_sql
							"        )";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$punish_list[$addUserName] = "{$row['FirstName']} {$row['Surname']} ($addUserName)";
				}
			}
			include "admin/punishment/mass.php";
		} elseif($_POST["action"] == "<<") {
			$_POST["class"] = intval($_POST["class"]);
			$query =        "SELECT user.FirstName, user.Surname, user.Username FROM " .
							"       user, classlist " .
							"WHERE  user.Username = classlist.Username " .
							"AND    classlist.ClassIndex = {$_POST['class']} " .
							"ORDER BY user.Username";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$cuname = $row['Username'];
				if(isset($punish_list[$cuname])) unset($punish_list[$cuname]);
			}
			$res =&  $db->query("SELECT ClassIndex, ClassName, Grade FROM class " .
								"WHERE ClassIndex={$_POST['class']}");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($row['Grade'] < 0) {
					$row['Grade'] = 1000 + $row['Grade'];
					$grade = sprintf("0%03u", $row['Grade']);
				} else {
					$grade = sprintf("1%03u", $row['Grade']);
				}
				str_replace(" ", "_", $row['Classname']);
				$punish_list["!{$grade}!{$row['ClassName']}!{$row['ClassIndex']}"] = "Class {$row['ClassName']}";
			}
			include "admin/punishment/mass.php";
		} elseif($_POST["action"] == "Issue Punishment")  {
			$title         = "LESSON - Saving punishment request...";
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
						echo "      <p align=\"center\">You must explain why you want the students punished!</p>\n";
						$failed = 1;
					} elseif($_POST['reason'] == "other") {
						if($_POST['reasonother'] == "" or is_null($_POST['reasonother'])) {
							echo "failed</p>\n";
							echo "      <p align=\"center\">You must explain why you want the students punished!</p>\n";
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
							echo "      <p align=\"center\">You must explain why you want the students punished!</p>\n";
							$failed = 1;
						}
					}
					if($failed == 0) {
						foreach($punish_list as $studentusername=>$student) {
							if($student != "") {
								if(substr($studentusername, 0, 1) == "!") {
									$classindex = intval(substr(strrchr($studentusername, "!"), 1));
									$query =	"SELECT user.Username FROM user, classlist " .
												"WHERE  user.Username = classlist.Username " .
												"AND    classlist.ClassIndex = $classindex " .
												"ORDER BY user.Username";
									$pres =&  $db->query($query);
									if(DB::isError($pres)) die($pres->getDebugInfo());           // Check for errors in query
									while ($row =& $pres->fetchRow(DB_FETCHMODE_ASSOC)) {
										$query =	"INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
													"                        RecordUsername, DateRequested, DateIssued, " .
													"                        Date, Comment) " .
													"       VALUES " .
													"       ($weightindex, '{$row['Username']}', '$username', '$username', " .
													"        $thisdateinfo, $thisdateinfo, $dateinfo, '$reason')";
										$res =& $db->query($query);
										if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
										update_conduct_mark($row['Username']);
										log_event($LOG_LEVEL_ADMIN, "admin/punishment/mass_action.php", $LOG_ADMIN,
										"Issued mass punishment for $student.");	
									}
								} else {
									$query =	"INSERT INTO discipline (DisciplineWeightIndex, Username, WorkerUsername, " .
												"                        RecordUsername, DateRequested, DateIssued, " .
												"                        Date, Comment) " .
												"       VALUES " .
												"       ($weightindex, '$studentusername', '$username', '$username', " .
												"        $thisdateinfo, $thisdateinfo, $dateinfo, '$reason')";
									$res =& $db->query($query);
									if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
									update_conduct_mark($studentusername);
									log_event($LOG_LEVEL_ADMIN, "admin/punishment/mass_action.php", $LOG_ADMIN,
										"Issued mass punishment for $student.");
								}
							}
						}
						echo " done</p>\n";
					}
				} else {
					echo "failed</p>\n";
					echo "      <p align=\"center\">There is no punishment of selected type!</p>\n";
				}
			}
			
			echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n";  // Link to next page
			
			include "footer.php";
		} elseif($_POST["action"] == "Cancel")  {
			$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
			$noJS          = true;
			$noHeaderLinks = true;
			$title         = "LESSON - Redirecting...";
			
			include "header.php";
			
			echo "      <p align=\"center\">Redirecting you to <a href=\"$nextLink\">$nextLink</a></p>\n";
			
			include "footer.php";
		} else {
			include "admin/punishment/mass.php";
		}
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/mass_action.php", $LOG_DENIED_ACCESS,
				"Attempted to issue mass punishment.");
		
		$noJS          = true;
		$noHeaderLinks = true;
		$title         = "LESSON - Unauthorized access!";
		
		include "header.php";
		
		echo "      <p align=\"center\">You do not have permission to access this page. <a href=" .
		                               "\"$nextLink\">Click here to continue.</a></p>\n";
		
		include "footer.php";
	}
	
?>
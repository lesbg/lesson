<?php
	/*****************************************************************
	 * admin/punishment/pending.php  (c) 2006 Jonathan Dieter
	 *
	 * Approve or reject pending punishments
	 *****************************************************************/

	 /* Get variables */
	$nextLink        = dbfuncInt2String($_GET['next']);

	/* Get current user's punishment permissions */
	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	$title           = "LESSON - Approving punishments";
	$noJS            = true;
	$noHeaderLinks   = true;
	
	include "core/settermandyear.php";
	include "header.php";

	if($_POST['action'] == "Yes, approve") {
		$action = 1;
		$Did = "Approved";
		$do  = "approve";
	} elseif($_POST['action'] == "Yes, reject") {
		$action = 2;
		$Did = "Rejected";
		$do  = "reject";
	} else {
		$action = 0;
		$do  = "cancel";
	}

	/* Check whether current user is authorized to approve pending punishment */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm >= $PUN_PERM_APPROVE) {
		if($action > 0) {
			$title         = "LESSON - Approving punishments";
			$noJS          = true;
			$noHeaderLinks = true;
			
			include "header.php";
			
			foreach($_POST['mass'] as $backlogindex) {
				$query =	"SELECT RequestType FROM disciplinebacklog " .
							"WHERE  DisciplineBacklogIndex = $backlogindex";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$dtype = $row['RequestType'];

					if($dtype == 1) {
						$query =	"SELECT disciplinetype.DisciplineType, disciplinebacklog.WorkerUsername, " .
									"       user.Username, user.FirstName, user.Surname, disciplinebacklog.Date, " .
									"       disciplinebacklog.Comment, disciplinebacklog.DateOfViolation, " .
									"       disciplinebacklog.DisciplineTypeIndex " .
									"       FROM disciplinetype, disciplinebacklog, user " .
									"WHERE  disciplinebacklog.DisciplineTypeIndex = " .
									"       disciplinetype.DisciplineTypeIndex " .
									"AND    disciplinebacklog.DisciplineBacklogIndex = $backlogindex " .
									"AND    disciplinebacklog.Username = user.Username " .
									"AND    disciplinebacklog.RequestType = 1 ";
						$dowhat = "";
					} else {
						$query =	"SELECT disciplinetype.DisciplineType, discipline.WorkerUsername, " .
									"       user.Username, user.FirstName, user.Surname, " .
									"       discipline.Comment, discipline.Date AS DateOfViolation, " .
									"       disciplinebacklog.Date, discipline.DisciplineIndex " .
									"       FROM discipline, disciplinetype, disciplineweight, " .
									"       disciplinebacklog, user " .
									"WHERE  discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
									"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
									"AND    disciplinebacklog.DisciplineBacklogIndex = $backlogindex " .
									"AND    discipline.Username = user.Username " .
									"AND    disciplinebacklog.DisciplineIndex = discipline.DisciplineIndex " .
									"AND    disciplinebacklog.RequestType = 2 ";
						$dowhat = "removal of ";
					}
					$res =&  $db->query($query);
					if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
					if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
						$studentusername = $row['Username'];
						$workerusername  = $row['WorkerUsername'];
						$name            = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
						$dateinfo        = $row['Date'];
						$violdate        = $row['DateOfViolation'];
						$printdate       = date($dateformat, strtotime($row['DateOfViolation']));
						$thisdate        = date($dateformat);
						$reason          = safe($row['Comment']);
						$punishment      = "{$row['DisciplineType']} on $printdate";
						$log_pun         = "{$row['DisciplineType']} on {$row['DateOfViolation']}";
						
						if($action == 1 and $dtype == 1) {
							$res =&  $db->query("SELECT DisciplineWeightIndex FROM disciplineweight " .
												"WHERE  DisciplineTypeIndex={$row['DisciplineTypeIndex']} " .
												"AND    YearIndex=$yearindex " .
												"AND    TermIndex=$termindex ");
							if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
							if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
								$weight_index = $row['DisciplineWeightIndex'];
								$query =	"INSERT INTO discipline (DisciplineWeightIndex, Username, " .
											"                        WorkerUsername, RecordUsername, " .
											"                        DateRequested, DateIssued, " .
											"                        Date, Comment) " .
											"       VALUES " .
											"       ($weight_index, '$studentusername', '$workerusername', " .
											"        '$username', '$dateinfo', '$thisdate', '$violdate', " .
											"        '$reason')";
								$res =& $db->query($query);
								update_conduct_mark($studentusername);
								if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query	
							}  else {
								echo "      <p align=\"center\">$punishment has not been set up for this term.</p>\n";
							}
						} elseif($action == 1 and $dtype == 2) {
							$disciplineindex = $row['DisciplineIndex'];
							$query =	"DELETE FROM discipline WHERE DisciplineIndex=$disciplineindex";
							$res =& $db->query($query);
							if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query	
						}
						$res =& $db->query("DELETE FROM disciplinebacklog " .
											"WHERE DisciplineBacklogIndex = $backlogindex");
						if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
						
						echo "      <p align=\"center\">$Did $dowhat$punishment for $name.</p>\n";
						log_event($LOG_LEVEL_ADMIN, "admin/punishment/pending.php", $LOG_ADMIN,
								"$Did $dowhat$log_pun for $name.");
					}
				}
			}
			echo "      <p align=\"center\"><a href=\"$nextLink\">Click here to continue</a></p>\n";
		} else {
			$title         = "LESSON - Cancelling";
			$noJS          = true;
			$noHeaderLinks = true;
			$extraMeta     = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
			
			include "header.php";
			
			echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
						"</p>\n";
		}
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/punishment/pending.php", $LOG_DENIED_ACCESS,
				"Tried to $do punishments.");
		echo "      <p>You do not have the authority to $do these punishments.  <a href=\"$nextLink\">" .
					"Click here to continue</a>.</p>\n";
	}
	include "footer.php";
?>

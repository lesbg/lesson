<?php
	/*****************************************************************
	 * admin/punishment/review_pending.php  (c) 2006 Jonathan Dieter
	 *
	 * Review all pending punishments
	 *****************************************************************/

	/* Get variables */
	$title           = "Pending Punishments";
	
	include "header.php";

	$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm >= $PUN_PERM_APPROVE) {
		$link =	"index.php?location=" . dbfuncString2Int("admin/punishment/pending_confirm.php") .
				"&amp;next=" .          dbfuncString2Int("index.php?location=" .
										dbfuncString2Int("admin/punishment/review_pending.php") .
										"&next={$_GET['next']}");
		
		$query =	"(SELECT disciplinetype.DisciplineType, user.Username, disciplinebacklog.DateOfViolation, " .
					"       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment, " .
					"       class.ClassName, disciplinebacklog.DisciplineBacklogIndex, NULL AS Reason, " .
					"       tuser.Title AS TTitle, tuser.FirstName " .
					"       AS TFirstName, tuser.Surname AS TSurname, 'Punishment' AS Type " .
					"       FROM class, classlist, disciplinetype, " .
					"       disciplinebacklog, user, user AS tuser " .
					"WHERE  disciplinebacklog.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    classlist.Username = user.Username " .
					"AND    disciplinebacklog.Username = user.Username " .
					"AND    disciplinebacklog.WorkerUsername = tuser.Username " .
					"AND    class.ClassIndex = classlist.ClassIndex " .
					"AND    class.YearIndex = $yearindex " .
					"AND    disciplinebacklog.RequestType = 1) " .
					"UNION " .
					"(SELECT disciplinetype.DisciplineType, user.Username, discipline.Date AS DateOfViolation, " .
					"       user.FirstName, user.Surname, disciplinebacklog.Date, discipline.Comment, " .
					"       class.ClassName, disciplinebacklog.DisciplineBacklogIndex, " .
					"       disciplinebacklog.Comment AS Reason, tuser.Title AS TTitle, tuser.FirstName " .
					"       AS TFirstName, tuser.Surname AS TSurname, 'Removal' AS Type " .
					"       FROM class, classlist, disciplinetype, disciplineweight, " .
					"       discipline, user, disciplinebacklog, user AS tuser " .
					"WHERE  disciplinebacklog.DisciplineIndex = discipline.DisciplineIndex " .
					"AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
					"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    classlist.Username = user.Username " .
					"AND    discipline.Username = user.Username " .
					"AND    disciplinebacklog.WorkerUsername = tuser.Username " .
					"AND    class.ClassIndex = classlist.ClassIndex " .
					"AND    class.YearIndex = $yearindex " .
					"AND    disciplinebacklog.RequestType = 2) " .
					"ORDER BY Date DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		echo "      <form action=\"$link\" method=\"post\">\n";
		
		if($res->numRows() > 0) {
			echo "      <p align=\"center\" class=\"subtitle\">Punishments pending approval</p>\n";
			$alllink =  "index.php?location=" . dbfuncString2Int("admin/punishment/approve_all_pending_confirm.php") .
						"&amp;next=" .          dbfuncString2Int("index.php?location= " .
														dbfuncString2Int("admin/punishment/review_pending.php"));
			$allbutton = dbfuncGetButton($alllink, "Approve all", "medium", "", "Approve all pending punishments");
			echo "      <p align=\"center\">$allbutton</p>\n";
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th></th>\n";
			echo "            <th>Teacher</th>\n";
			echo "            <th>Request</th>\n";
			echo "            <th>Type</th>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Requested</th>\n";
			echo "            <th>Violation</th>\n";
			echo "            <th>Punishment Reason</th>\n";
			echo "            <th>Removal Reason</th>\n";
			echo "         </tr>\n";
			
			/* For each pending punishment, list relevant information */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt_step = "alt";
				} else {
					$alt_step = "std";
				}
				$alt = " class=\"$alt_step\"";

				$thisdateinfo = date($dateformat, strtotime($row['Date']));
				$dateinfo = date($dateformat, strtotime($row['DateOfViolation']));
				echo "         <tr$alt>\n";
				echo "            <td><input type='checkbox' name='mass[]' value='{$row['DisciplineBacklogIndex']}'></input></td>\n";
				echo "            <td>{$row['TTitle']} {$row['TFirstName']} {$row['TSurname']}</td>\n";
				echo "            <td>{$row['Type']}</td>\n";
				echo "            <td>{$row['DisciplineType']}</td>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td>{$row['ClassName']}</td>\n";
				echo "            <td>$thisdateinfo</td>\n";
				echo "            <td>$dateinfo</td>\n";
				echo "            <td>{$row['Comment']}</td>\n";
				echo "            <td>{$row['Reason']}</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
			echo "      <p align=\"center\">\n";
			echo "         <input type=\"submit\" name=\"action\" value=\"Approve checked\">&nbsp;\n";
			echo "         <input type=\"submit\" name=\"action\" value=\"Reject checked\">\n";
			echo "      </p>\n";
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">There are no punishments or removals pending approval.</p>\n";
		}
		echo "      </form>\n";
		log_event($LOG_LEVEL_EVERYTHING, "admin/punishment/review_pending.php", $LOG_ADMIN,
					"Viewed pending punishments and punishment removals.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/review_pending.php", $LOG_DENIED_ACCESS,
					"Tried to access pending punishments and punishment removals.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
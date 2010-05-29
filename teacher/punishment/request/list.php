<?php
	/*****************************************************************
	 * teacher/punishment/request/list.php  (c) 2006 Jonathan Dieter
	 *
	 * Print information about teacher's pending punishments
	 *****************************************************************/

	/* Get variables */
	$teacherusername = safe(dbfuncInt2String($_GET['key']));
	$name            = dbfuncInt2String($_GET['keyname']);
	$title           = "Pending Punishments issued by $name ($teacherusername)";
	
	include "core/settermandyear.php";
	include "header.php";

	/* Make sure user has permission to view student's marks for subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $teacherusername == $username) {
		include "core/settermandyear.php";

		$query =	"SELECT disciplinetype.DisciplineType, user.Username, disciplinebacklog.DateOfViolation, " .
					"       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment, " .
					"       class.ClassName, disciplinebacklog.DisciplineBacklogIndex " .
					"       FROM class, classterm, classlist, disciplinetype, " .
					"       disciplinebacklog, user " .
					"WHERE  disciplinebacklog.WorkerUsername   = '$teacherusername' " .
					"AND    disciplinetype.DisciplineTypeIndex = disciplinebacklog.DisciplineTypeIndex " .
					"AND    user.Username                      = disciplinebacklog.Username " .
					"AND    classlist.Username         = user.Username " .
					"AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
					"AND    classterm.TermIndex        = $termindex " .
					"AND    classterm.ClassIndex       = class.ClassIndex " .
					"AND    class.YearIndex            = $yearindex " .
					"AND    disciplinebacklog.RequestType = 1 " .
					"ORDER BY disciplinebacklog.Date, disciplinebacklog.DisciplineBacklogIndex DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		/* Print assignments and scores */
		include "core/titletermyear.php";

		if($res->numRows() > 0) {
			echo "      <p align=\"center\" class=\"subtitle\">Punishments pending approval</p>\n";
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th></th>\n";
			echo "            <th>Discipline Type</th>\n";
			echo "            <th>Date Requested</th>\n";
			echo "            <th>Date of Rule Violation</th>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Reason</th>\n";
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
				$alt = " class=\"almost-$alt_step\"";
				$thisdateinfo = date($dateformat, strtotime($row['Date']));
				$dateinfo = date($dateformat, strtotime($row['DateOfViolation']));
				echo "         <tr$alt>\n";
				$dellink =  "index.php?location=" . dbfuncString2Int("teacher/punishment/request/delete_confirm.php") .
							"&amp;key=" .           dbfuncString2Int($row['DisciplineBacklogIndex']) .
							"&amp;next=" .          dbfuncString2Int("index.php?location=" .
													dbfuncString2Int("teacher/punishment/request/list.php") .
													"&key={$_GET['key']}&keyname={$_GET['keyname']}");
				$delbutton = dbfuncGetButton($dellink,   "D", "small", "delete", "Delete pending punishment");
				echo "            <td nowrap>$delbutton</td>\n";
				echo "            <td nowrap>{$row['DisciplineType']}</td>\n";
				echo "            <td nowrap>$thisdateinfo</td>\n";
				echo "            <td nowrap>$dateinfo</td>\n";
				echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td nowrap>{$row['ClassName']}</td>\n";
				echo "            <td nowrap>{$row['Comment']}</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">You have no punishments pending approval.</p>\n";
		}
		echo "      <p>&nbsp;</p>\n";
		$query =	"SELECT disciplinetype.DisciplineType, user.Username,  " .
					"       user.FirstName, user.Surname, disciplinebacklog.Date, disciplinebacklog.Comment, " .
					"       class.ClassName, disciplinebacklog.DisciplineBacklogIndex, discipline.Comment AS Reason " .
					"       FROM class, classterm, classlist, disciplinetype, disciplineweight, discipline, " .
					"       disciplinebacklog, user " .
					"WHERE  disciplinebacklog.WorkerUsername   = '$teacherusername' " .
					"AND    discipline.DisciplineIndex         = disciplinebacklog.DisciplineIndex " .
					"AND    disciplineweight.DisciplineWeightIndex = discipline.DisciplineWeightIndex " .
					"AND    disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
					"AND    user.Username                      = discipline.Username " .
					"AND    classlist.Username         = user.Username " .
					"AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
					"AND    classterm.TermIndex        = $termindex " .
					"AND    classterm.ClassIndex       = class.ClassIndex " .
					"AND    class.YearIndex            = $yearindex " .
					"AND    disciplinebacklog.RequestType = 2 " .
					"ORDER BY disciplinebacklog.Date, disciplinebacklog.DisciplineBacklogIndex DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($res->numRows() > 0) {
			/* Print assignments and scores */
			echo "      <p align=\"center\" class=\"subtitle\">Punishment removals pending approval</p>\n";
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th></th>\n";
			echo "            <th>Discipline Type</th>\n";
			echo "            <th>Removal Request Date</th>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Original Reason for Punishment</th>\n";
			echo "            <th>Reason for Removal</th>\n";
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
				$alt = " class=\"almost-$alt_step\"";
				$dateinfo = date($dateformat, strtotime($row['Date']));
				echo "         <tr$alt>\n";
				$dellink =  "index.php?location=" . dbfuncString2Int("teacher/punishment/request/delete_removal_confirm.php") .
							"&amp;key=" .           dbfuncString2Int($row['DisciplineBacklogIndex']) .
							"&amp;next=" .          dbfuncString2Int("index.php?location=" .
													dbfuncString2Int("teacher/punishment/request/list.php") .
													"&key={$_GET['key']}&keyname={$_GET['keyname']}");
				$delbutton = dbfuncGetButton($dellink,   "D", "small", "delete", "Delete pending punishment removal");
				echo "            <td>$delbutton</td>\n";
				echo "            <td>{$row['DisciplineType']}</td>\n";
				echo "            <td>$dateinfo</td>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td>{$row['ClassName']}</td>\n";
				echo "            <td>{$row['Reason']}</td>\n";
				echo "            <td>{$row['Comment']}</td>\n";
				echo "         </tr>\n";
			}
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">You have no punishment removals pending approval.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/punishment/request/list.php", $LOG_TEACHER,
					"Viewed $name's pending punishments and punishment removals.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/list.php", $LOG_DENIED_ACCESS,
					"Tried to access $name's pending punishments and punishment removals.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
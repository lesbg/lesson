<?php
	/*****************************************************************
	 * teacher/punishment/request/new_removal.php  (c) 2006 Jonathan Dieter
	 *
	 * Create a punishment removal request
	 *****************************************************************/

	/* Get variables */
	$disciplineindex  = safe(dbfuncInt2String($_GET['key']));
	$nextLink         = dbfuncInt2String($_GET['next']);

	$link             = "index.php?location=" . dbfuncString2Int("teacher/punishment/request/new_removal_action.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;next=" .          $_GET['next'];
	
	include "core/settermandyear.php";
	
	$query =	"SELECT user.Username, user.FirstName, user.Surname " .
				"       FROM user, discipline " .
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
	
	$title = "Request for punishment removal";

	include "header.php";

	if($is_admin or $is_teacher) {
		$query =	"SELECT disciplinetype.DisciplineType, user.Username, " .
					"       user.FirstName, user.Surname, discipline.Date, discipline.Comment " .
					"       FROM disciplinetype, disciplineweight, " .
					"       discipline, user " .
					"WHERE  discipline.WorkerUsername = \"$username\" " .
					"AND    disciplineweight.YearIndex = $yearindex " .
					"AND    disciplineweight.TermIndex = $termindex " .
					"AND    discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
					"AND    disciplineweight.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    discipline.Username = user.Username " .
					"AND    discipline.DisciplineIndex = $disciplineindex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$name       = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
			$dateinfo   = date($dateformat, strtotime($row['Date']));
			$punishment = "{$row['DisciplineType']} issued on $dateinfo";
			$log_pun    = "{$row['DisciplineType']} issued on {$row['Date']}";

                                  // Show header
			log_event($LOG_LEVEL_EVERYTHING, "teacher/punishment/request/new_removal.php", $LOG_TEACHER,
						"Starting new punishment removal request of $log_pun for $student.");
			echo "      <form action=\"$link\" method=\"post\" name=\"casenote\">\n"; // Form method
			echo "         <table border=\"0\" class=\"transparent\" align=\"center\" width=\"600px\">\n";
			echo "            <tr>\n";
			echo "               <td>\n";
			echo "                  Why do you want to remove $name's $punishment?<br>\n";
			echo "                  <textarea rows=\"10\" cols=\"78\" name=\"note\">" .
											"</textarea>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "         </table>\n";
			echo "         <p align=\"center\">\n";
			echo "            <input type=\"submit\" name=\"action\" value=\"Save\">&nbsp; \n";
			echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\">&nbsp; \n";
			echo "         </p>\n";
			echo "      </form>\n";
		} else {
			echo "      <p>The punishment you want removed doesn't exist.  Perhaps it has already been removed?</p>\n";
			echo "      <p><a href=\"$nextLink\">Click here to continue</a></p>\n";
		}
	} else {  // User isn't authorized to create a punishment request
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/punishment/request/new_removal.php", $LOG_DENIED_ACCESS, 
					"Tried to create punishment removal request for $student.");

		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
<?php
	/*****************************************************************
	 * teacher/assignment/list_agenda.php  (c) 2010 Jonathan Dieter
	 *
	 * List all agenda items for this subject
	 *****************************************************************/
	 
	$title        = "Agenda Items for " . dbfuncInt2String($_GET['keyname']);
	$subjectindex = safe(dbfuncInt2String($_GET['key']));
	
	include "header.php";
	
	/* Check whether user is authorized to change scores */
	$res =& $db->query("SELECT subjectteacher.Username FROM subjectteacher " .
					   "WHERE subjectteacher.SubjectIndex = $subjectindex " .
					   "AND   subjectteacher.Username     = '$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
	
	if($res->numRows() > 0 or $is_admin) {
		include "core/settermandyear.php";
		$nochangeyt = true;
		include "core/titletermyear.php";

		/* Get whether marks can be modified */
		$res =& $db->query("SELECT AverageType, AverageTypeIndex, CanModify FROM subject " .
						   "WHERE subject.SubjectIndex = $subjectindex");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
		$row       =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
			$can_modify = 1;
		} else {
			$can_modify = $row['CanModify'];
		}

		$average_type       = $row['AverageType'];
		$average_type_index = $row['AverageTypeIndex'];

		$asmntlink =	"index.php?location=" . dbfuncString2Int("teacher/assignment/list.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
		$newlink =  	"index.php?location=" . dbfuncString2Int("teacher/assignment/new_agenda.php") .
						"&amp;key=" .           dbfuncString2Int($subjectindex) .
						"&amp;keyname=" .       $_GET['keyname'];
						
		$asmntbutton = dbfuncGetButton($asmntlink, "Assignments", "medium", "", "List assignments for this subject");
		if($can_modify==1 or $is_admin) {
			$newbutton = dbfuncGetButton($newlink, "New agenda item", "medium", "", "Create new agenda item for this subject");			
		} else {
			$newbutton = "";
		}
		
		echo "      <p align='center'>$asmntbutton $newbutton</p>\n";
		
		$query =		"SELECT Title, Date, DueDate, assignment.AssignmentIndex, Description, DescriptionData, " .
						"       DescriptionFileType, AverageType, ShowAverage, Agenda, subject.Name AS SubjectName, " .
						"       Uploadable, assignment.Weight, Hidden, " .
						"       CanModify, subject.SubjectIndex " .
						"       FROM subject INNER JOIN assignment USING (SubjectIndex) " .
						"WHERE subject.SubjectIndex = $subjectindex " .
						"AND   Agenda       = 1 " .
						"ORDER BY Date DESC, AssignmentIndex DESC";			
		

		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		/* Print assignments and scores */
		if($res->numRows() > 0) {
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>Title</th>\n";
			echo "            <th>Date</th>\n";
			echo "            <th>Due Date</th>\n";
			echo "         </tr>\n";
			
			/* For each assignment, print subject, teacher, assignment title, date, score, and any comments */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$can_modify = $row['CanModify'];
				
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt_step = "alt";
				} else {
					$alt_step = "std";
				}
	
				$alt = " class='agenda-$alt_step'";
				$aclass = " class='agenda'";
				
				if($row['Hidden'] == 1) {
					$alt = " class='hidden-$alt_step'";
				}

				echo "         <tr$alt>\n";
				$modifylink = 	"index.php?location=" . dbfuncString2Int("teacher/assignment/modify_agenda.php") .
								"&amp;key=" .           dbfuncString2Int($row['AssignmentIndex']) .
								"&amp;keyname=" .       dbfuncString2Int($row['Title']);
				echo "          <td><a$aclass href='$modifylink'>{$row['Title']}</a></td>\n";
				
				$dateinfo = date($dateformat, strtotime($row['Date']));
				$duedateinfo = date($dateformat, strtotime($row['DueDate']));
				echo "            <td>$dateinfo</td>\n";
				echo "            <td>$duedateinfo</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>No agenda items.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/assignment/list_agenda.php", $LOG_STUDENT,
					"Viewed all of " . dbfuncInt2String($_GET['keyname']) . "'s agenda items.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/assignment/list_agenda.php", $LOG_DENIED_ACCESS,
					"Tried to access " . dbfuncInt2String($_GET['keyname']) . "'s agenda items.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
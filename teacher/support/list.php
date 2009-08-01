<?php
	/*****************************************************************
	 * teacher/support/list.php  (c) 2006 Jonathan Dieter
	 *
	 * Show list of students for support teacher
	 *****************************************************************/

	/* Get variables */
	$title      = dbfuncInt2String($_GET['keyname']);
	$classindex = safe(dbfuncInt2String($_GET['key']));
	
	include "header.php";
	include "core/settermandyear.php";
	$nochangeyt = true;
	$showterm = false;	
	
	/* Get subject information for current teacher */
	$query =	"SELECT suser.Username, suser.FirstName, suser.Surname " .
				"       FROM user, support, classlist, user AS suser " .
				"WHERE support.WorkerUsername  = \"$username\" " .
				"AND   user.Username           = support.WorkerUsername " .
				"AND   user.ActiveTeacher      = 1 " .
				"AND   user.SupportTeacher     = 1 " .
				"AND   support.StudentUsername = classlist.Username " .
				"AND   suser.Username          = support.StudentUsername " .
				"AND   classlist.ClassIndex    = $classindex ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query	

	if($res->numRows() > 0 || dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$query =    "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
		$nres =&  $db->query($query);
		if(DB::isError($res)) die($nres->getDebugInfo());           // Check for errors in query
		if($row =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
			$perm = $row['Permissions'];
		} else {
			$perm = 0;
		}
		
		$order = 1;
		include "core/titletermyear.php";			
		
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Student</th>\n";
		echo "         </tr>\n";
			
		/* For each student, print a row with the student's name */
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			
			$alt = " class=\"$alt_step\"";
			echo "         <tr$alt>\n";

			if($currentyear == $yearindex) {
				$cnlink =   "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							"&amp;keyname2=" .      dbfuncSTring2Int($row['FirstName']);
				$cnbutton = dbfuncGetButton($cnlink,   "C", "small", "cn",   "Casenotes");
			} else {
				$cnbutton = "";
			}
			if($currentyear == $yearindex and $currentterm == $termindex and ($perm > 0 or dbfuncGetPermission($permissions, $PERM_ADMIN))) {
				if($perm == 1) {
					$punlink =  "index.php?location=" . dbfuncString2Int("teacher/punishment/request/new.php") .
								"&amp;key=" .           dbfuncString2Int($row['Username']) .
								"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
								"&amp;next=" .          dbfuncString2Int("index.php?location=" .
														dbfuncString2Int("teacher/support/list.php") .
														"&amp;key=" . $_GET['key'] .
														"&amp;keyname=" . $_GET['keyname']);
					$punbutton = dbfuncGetButton($punlink,   "P", "small", "delete",   "Request Punishment");
				} else {
					$punlink =  "index.php?location=" . dbfuncString2Int("admin/punishment/new.php") .
								"&amp;key=" .           dbfuncString2Int($row['Username']) .
								"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
								"&amp;next=" .          dbfuncString2Int("index.php?location=" .
															dbfuncString2Int("teacher/support/list.php") .
															"&amp;key=" . $_GET['key'] .
															"&amp;keyname=" . $_GET['keyname']);
					$punbutton = dbfuncGetButton($punlink,   "P", "small", "delete",   "Issue Punishment");
				}
			} else {
				$punbutton = "";
			}

			echo "            <td nowrap>$punbutton $cnbutton $order</td>\n";
			$order += 1;
			echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
		log_event($LOG_LEVEL_EVERYTHING, "teacher/support/list.php", $LOG_TEACHER,
				"Accessed list of support students for $title.");
	} else {  // User isn't authorized to view or change scores.
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/support/list.php", $LOG_DENIED_ACCESS, 
					"Tried to access support students for $title.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>

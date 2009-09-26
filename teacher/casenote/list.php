<?php
	/*****************************************************************
	 * teacher/casenote/list.php  (c) 2006 Jonathan Dieter
	 *
	 * Show list of casenotes for student
	 *****************************************************************/

	/* Get variables */
	$student          = dbfuncInt2String($_GET['keyname']);
	$studentfirstname = dbfuncInt2String($_GET['keyname2']);
	$studentusername  = safe(dbfuncInt2String($_GET['key']));

	$title            = "Casenotes for $student";
	
	include "core/settermandyear.php";
	include "header.php";
	
	/* Check whether student is in current user's watchlist */
	$res =&  $db->query("SELECT WorkerUsername FROM casenotewatch " .
						"WHERE StudentUsername='$studentusername' " .
						"AND   WorkerUsername='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_on_wl = true;
	} else {
		$is_on_wl = false;
	}

	/* Check whether current user is principal */
	$res =&  $db->query("SELECT Username FROM principal " .
						"WHERE Username='$username' AND Level=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_principal = true;
	} else {
		$is_principal = false;
	}

	/* Check whether current user is head of department for student */
	$query =	"SELECT hod.Username FROM hod, class, classterm, classlist " .
				"WHERE hod.Username = '$username' " .
				"AND   hod.DepartmentIndex = class.DepartmentIndex " .
				"AND   classlist.Username = '$studentusername' " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"AND   classterm.TermIndex = $currentterm " .
				"AND   class.ClassIndex = classterm.ClassIndex " .
				"AND   class.YearIndex = $currentyear";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_hod = true;
	} else {
		$is_hod = false;
	}

	/* Check whether current user is a counselor */
	$res =&  $db->query("SELECT Username FROM counselorlist " .
						"WHERE Username='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	/* Check whether current user is class teacher for this student this year */
	$query =	"SELECT class.ClassTeacherUsername FROM class, classterm, classlist " .
				"WHERE class.ClassTeacherUsername = '$username' " .
				"AND   classlist.Username = '$studentusername' " .
				"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				"AND   classterm.TermIndex = $currentterm " .
				"AND   class.ClassIndex = classterm.ClassIndex " .
				"AND   class.YearIndex = $currentyear";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_classteacher = true;
	} else {
		$is_classteacher = false;
	}

	/* Check whether current user is a support teacher for this student */
	$res =&  $db->query("SELECT user.Username FROM support, user " .
						"WHERE support.StudentUsername = '$studentusername' " .
						"AND   support.WorkerUsername = '$username' " .
						"AND   user.Username = support.WorkerUsername " .
						"AND   user.ActiveTeacher = 1 " .
						"AND   user.SupportTeacher = 1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_supportteacher = true;
	} else {
		$is_supportteacher = false;
	}
	
	/* Check whether current user is a teacher  */
	$res =&  $db->query("SELECT Username FROM user WHERE ActiveTeacher=1");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_teacher = true;
	} else {
		$is_teacher = false;
	}

	/* Check whether current user has ever written a casenote for this student  */
	$res =&  $db->query("SELECT WorkerUsername FROM casenote " .
						"WHERE WorkerUsername = '$username' " .
						"AND   StudentUsername = '$studentusername'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$prev_cn = true;
	} else {
		$prev_cn = false;
	}

	if($is_principal or $is_hod or $is_counselor or $is_classteacher or $is_supportteacher or $is_teacher or $prev_cn) {
		log_event($LOG_LEVEL_EVERYTHING, "teacher/casenote/list.php", $LOG_TEACHER,
					"Viewed casenotes for $student.");

		/* Build list of principals */
		$query =	"SELECT user.Title, user.FirstName, user.Surname " .
					"       FROM principal, user " .
					"WHERE Level = 1 " .
					"AND   principal.Username = user.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		$principal_list = array();
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$principal_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
		}

		/* Build list of relevant head of departments */
		$query = 	"SELECT user.Title, user.FirstName, user.Surname " .
					"       FROM hod, class, classterm, classlist, user " .
					"WHERE hod.DepartmentIndex = class.DepartmentIndex " .
					"AND   class.YearIndex = $currentyear " .
					"AND   class.ClassIndex = classterm.ClassIndex " .
					"AND   classterm.TermIndex = $currentterm " .
					"AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"AND   classlist.Username = '$studentusername' " .
					"AND   hod.Username = user.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		$hod_list = array();
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$hod_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
		}
		
		/* Build list of this student's class teacher for this year */
		$query = 	"SELECT user.Title, user.FirstName, user.Surname " .
					"       FROM class, classterm, classlist, user " .
					"WHERE class.ClassTeacherUsername = user.Username " .
					"AND   class.YearIndex = $currentyear " .
					"AND   class.ClassIndex = classterm.ClassIndex " .
					"AND   classterm.TermIndex = $currentterm " .
					"AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"AND   classlist.Username = '$studentusername' ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		$ct_list = array();
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$ct_list[] = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
		}

		$writable = true;
		if($is_principal) {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   (casenote.Level > 0 OR " .
						"       casenote.WorkerUsername = '$username')" .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
		} elseif($is_hod) {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   ((casenote.Level > 0 AND casenote.Level < 5) OR " .
						"       casenote.WorkerUsername = '$username')" .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
		} elseif($is_counselor) {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote LEFT OUTER JOIN casenotelist " .
						"       ON casenote.CaseNoteIndex = casenotelist.CaseNoteIndex " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   ((casenote.Level > 0 AND casenote.Level < 3) OR " .
						"       (casenote.Level = 3 AND " .
						"        (casenotelist.WorkerUsername = '$username' OR " .
						"         casenotelist.WorkerUsername IS NULL)) OR " .
						"       (casenote.WorkerUsername = '$username')) " .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"GROUP BY casenote.CaseNoteIndex " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
		} elseif($is_classteacher) {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   ((casenote.Level > 0 AND casenote.Level < 3) OR " .
						"       casenote.WorkerUsername = '$username')" .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
		} elseif($is_supportteacher or $is_teacher) {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title, casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   ((casenote.Level > 0 AND casenote.Level < 2) OR " .
						"       casenote.WorkerUsername = '$username')" .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
		} else {
			$query = 	"SELECT user.FirstName, user.Surname, user.Username, " .
						"       user.Title casenote.CaseNoteIndex, casenote.Note, " .
						"       casenote.Level, casenote.Date " .
						"       FROM user, casenote " .
						"WHERE user.Username = casenote.WorkerUsername " .
						"AND   casenote.WorkerUsername = '$username'" .
						"AND   casenote.StudentUsername = '$studentusername' " .
						"ORDER BY casenote.Date DESC, casenote.CaseNoteIndex DESC";
			$writable = false;
		}

		/* Clear new casenotes flag for current student */
		$res =&  $db->query("DELETE casenotenew.* FROM casenotenew, casenote " .
							"WHERE casenotenew.WorkerUsername='$username' " .
							"AND   casenote.CaseNoteIndex=casenotenew.CaseNoteIndex " .
							"AND   casenote.StudentUsername='$studentusername'");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if($writable) {
			/* Check to see if we are supposed to add someone to the watchlist */
			if(isset($_GET['key2'])) {
				if(dbfuncInt2String($_GET['key2']) == "add") {
					$abc =&  $db->query("INSERT INTO casenotewatch (CaseNoteWatchIndex, " .
										"            WorkerUsername, StudentUsername) " .
										"       VALUES " .
										"       ('{$username}{$studentusername}', '$username', " .
										"        '$studentusername')");
					$is_on_wl = true;
				} elseif(dbfuncInt2String($_GET['key2']) == "remove") {
					$abc =&  $db->query("DELETE FROM casenotewatch " .
										"WHERE CaseNoteWatchIndex='{$username}{$studentusername}'");
					$is_on_wl = false;
				}
			}

			$addLink  = "index.php?location=" . dbfuncString2Int("teacher/casenote/new.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;keyname2=" .      $_GET['keyname2'];

			$addbutton = dbfuncGetButton($addLink, "New casenote", "medium", "", "Create new casenote for $studentfirstname");
			if(!$is_on_wl) {
				if($is_counselor) {
					$wlnLink  = "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
								"&amp;key=" .           $_GET['key'] .
								"&amp;keyname=" .       $_GET['keyname'] .
								"&amp;keyname2=" .      $_GET['keyname2'] .
								"&amp;key2=" .          dbfuncString2Int("add");
					$wlbutton = dbfuncGetButton($wlnLink, "Add to my watchlist", "medium", "cn", "Add $studentfirstname to my casenote watchlist");
				} else {
					$res =&  $db->query("DELETE FROM casenotewatch " .
										"WHERE WorkerUsername='{$username}'");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
					$is_on_wl = false;
					$wlbutton = "";
				}
			} else {
				if($is_counselor) {
					$wlnLink  = "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
								"&amp;key=" .           $_GET['key'] .
								"&amp;keyname=" .       $_GET['keyname'] .
								"&amp;keyname2=" .      $_GET['keyname2'] .
								"&amp;key2=" .          dbfuncString2Int("remove");
					$wlbutton = dbfuncGetButton($wlnLink, "Remove from my watchlist", "medium", "delete", "Remove $studentfirstname from my casenote watchlist");
				} else {
					$wlbutton = "";
				}
			}
			echo "        <p align='center'>$addbutton $wlbutton</p>\n";

		} elseif($is_on_wl) {
			/* Clear new casenotes flag for current student */
			$res =&  $db->query("DELETE casenotenew.* FROM casenotenew, casenote " .
								"WHERE casenotenew.WorkerUsername='$username' " .
								"AND   casenote.CaseNoteIndex=casenotenew.CaseNoteIndex " .
								"AND   casenote.StudentUsername='$studentusername'");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			/* Remove student from watchlist because teacher is no longer able to see
			   anybody else's casenotes */
			$res =&  $db->query("DELETE FROM casenotewatch " .
								"WHERE CaseNoteWatchIndex='{$username}{$studentusername}'");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			echo "        <p align='center'>Removed $student from your watchlist because you are no longer able to see any other teacher's casenotes on this student.</p>\n";
			$is_on_wl = false;
		}
				

		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$author = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
			$date   = date(dbfuncGetDateFormat(), strtotime($row['Date']));
			$time   = date("g:iA", strtotime($row['Date']));
			$level  = "";
			if($row['Level'] == 0) {
				$level       = "Private";
				$level_title = "";
			} else {
				if($row['Level'] == 1) {
					$text = array("all $studentfirstname's teachers", "all counselors", $author);
					$name_list = array_unique(array_merge($text, $hod_list, $principal_list));
				} elseif($row['Level'] == 2) {
					$text = array_merge(array("all counselors", $author), $ct_list);
					$name_list = array_unique(array_merge($text, $hod_list, $principal_list));
				} elseif($row['Level'] == 3) {
					$query = 	"SELECT user.Title, user.FirstName, user.Surname " .
								"       FROM casenotelist, counselorlist, user " .
								"WHERE  CaseNoteIndex = {$row['CaseNoteIndex']} " .
								"AND    casenotelist.WorkerUsername = user.Username " .
								"AND    counselorlist.Username = casenotelist.WorkerUsername " .
								"ORDER BY casenotelist.WorkerUsername";
					$nrs =&  $db->query($query);
					if(DB::isError($nrs)) die($nrs->getDebugInfo());
					$counselor_list = array();
					if($nrs->numRows() > 0) {
						while($nrow =& $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
							$counselor_list[] = "{$nrow['Title']} {$nrow['FirstName']} {$nrow['Surname']}";
						}
					} else {
						$query = 	"SELECT user.Title, user.FirstName, user.Surname " .
									"       FROM counselorlist, user " .
									"WHERE  counselorlist.Username = user.Username " .
									"ORDER BY counselorlist.Username";
						$nrs =&  $db->query($query);
						if(DB::isError($nrs)) die($nrs->getDebugInfo());
						while($nrow =& $nrs->fetchRow(DB_FETCHMODE_ASSOC)) {
							$counselor_list[] = "{$nrow['Title']} {$nrow['FirstName']} {$nrow['Surname']}";
						}
					}
					$text = array($author);
					$name_list = array_unique(array_merge($counselor_list, $text, $hod_list, $principal_list));
				} elseif($row['Level'] == 4) {
					$text = array($author);
					$name_list = array_unique(array_merge($text, $hod_list, $principal_list));
				} elseif($row['Level'] == 5) {
					$text = array($author);
					$name_list = array_unique(array_merge($text, $principal_list));
				}
				$level       = "Level: {$row['Level']}";
				$level_title = "Viewable by " . getNamesFromList($name_list);
			}
			echo "         <table align='center' border='1' width='400px'>\n"; // Table headers
			echo "            <tr class='alt'>\n";
			echo "               <td style='border-style: none'>\n";
			echo "                  <span style='float: left'>{$row['Title']} {$row['FirstName']} {$row['Surname']} ({$row['Username']})</span><span style='float: right'>$date</span><br>\n";
			echo "                  <span style='float: left'><a title='$level_title' class='cn-level{$row['Level']}'>$level</a></span><span style='float: right'>$time</span>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "            <tr class='std'>\n";
			echo "               <td colspan='2'>\n";
			echo "                  {$row['Note']}\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "         </table>\n";
			echo "         <p></p>\n";
			/*print "<p>{$row['Username']}, {$row['Note']}</p>\n";*/
		}
	} else {  // User isn't authorized to view any casenotes.
		/* Log unauthorized access attempt */	
		log_event($LOG_LEVEL_ERROR, "teacher/casenote/list.php", $LOG_DENIED_ACCESS, 
					"Tried to access casenotes for $studentusername.");
		
		/* Print error message */
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
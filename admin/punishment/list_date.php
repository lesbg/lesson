<?php
	/*****************************************************************
	 * admin/punishment/list_date.php  (c) 2006-2013 Jonathan Dieter
	 *
	 * Show printable list of students who are punished on next date
	 *****************************************************************/

	/* Get variables */
	if(!isset($_GET['type'])) {
		if(!isset($_POST['type'])) {
			$link =		"index.php?location=" . dbfuncString2Int("admin/punishment/list_date.php") .
						"&amp;next=" .          $_GET['next'];
			include "admin/punishment/choose_type.php";
			exit(0);
		} else {
			$_GET['type'] = dbfuncString2Int($_POST['type']);
		}
	}
	$dtype = dbfuncInt2String($_GET['type']);
	
	$query =	"SELECT ActiveTeacher FROM user WHERE Username='$username' AND ActiveTeacher=1";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$is_teacher = true;
	} else {
		$is_teacher = false;
	}
	
	$query = "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = $DEFAULT_PUN_PERM;
	}

	$query =	"SELECT DisciplineType " .
				"       FROM disciplinetype " .
				"WHERE  disciplinetype.DisciplineTypeIndex = $dtype ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$disc = $row['DisciplineType'];
	} else {
		$disc = "unknown punishment";
	}
	
	$query =	"SELECT DisciplineDateIndex, PunishDate FROM disciplinedate " .
				"WHERE DisciplineTypeIndex = $dtype " .
				"AND   Done = 0";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$pindex  = $row['DisciplineDateIndex'];
		$pundate = date($dateformat, strtotime($row['PunishDate']));
	} else {
		$_GET['next'] = dbfuncString2Int("index.php?location=" .
							dbfuncString2Int("admin/punishment/date_student.php") .
							"&amp;type=" .	$_GET['type'] .
							"&amp;next=" .	$_GET['next']);
		include "admin/punishment/set_date.php";
		exit(0);
	}

	$title           = "{$disc} on $pundate";
	/* Make sure user has permission to view student's marks for subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or ($perm > $PUN_PERM_ALL and $is_teacher)) {
		if($_POST["action"] == "Check all") {
			$check_all = 1;
		} elseif($_POST["action"] == "Uncheck all") {
			$check_all = -1;
		} else {
			$check_all = 0;
		}
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" " .
			"\"http://www.w3.org/TR/html4/loose.dtd\">\n";
		echo "<html>\n";
		echo "   <head>\n";
		echo "      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
		echo "      <title>$title</title>\n";
		echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"screen\">\n";
		echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"print\">\n";
		echo "   </head>\n";
		echo "   <body>\n";
		echo "      <table class=\"transparent\" width=\"100%\">\n";
		echo "         <tr>\n";
		echo "            <td width=\"120px\" class=\"logo\"><img height=\"73\" width=\"75\" alt=\"LESB&G Logo\" src=\"images/lesbg-small.gif\"></td>\n"; 
		echo "            <td class=\"title\">$title</td>\n";
		echo "            <td width=\"120px\" class=\"home\">\n";
		echo "            </td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";

		$query =	"SELECT user.Username, user.FirstName, user.Surname, discipline.Date, " .
					"       discipline.Comment, class.ClassName, discipline.DisciplineIndex, " .
					"       disciplinedate.PunishDate, discipline.ServedType " .
					"       FROM class, classterm, classlist, disciplinetype, disciplinedate, discipline, " .
					"       user " .
					"WHERE  disciplinedate.DisciplineDateIndex = $pindex " .
					"AND    discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
					"AND    disciplinedate.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
					"AND    classlist.Username = user.Username " .
					"AND    discipline.Username = user.Username " .
					"AND    classterm.ClassTermIndex = classlist.ClassTermIndex " .
					"AND    classterm.TermIndex = $termindex " .
					"AND    class.ClassIndex = classterm.ClassIndex " .
					"AND    class.YearIndex = $yearindex " .
					"AND    class.DepartmentIndex = $depindex " .
					"GROUP BY user.Username " .
					"ORDER BY class.Grade, class.ClassName, user.Username ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		if($res->numRows() > 0) {
			/* Print punishments */
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
			echo "            <th>Teacher</th>\n";
			echo "            <th>Violation Date</th>\n";
			echo "            <th>Reason</th>\n";
			echo "         </tr>\n";
			
			/* For each assignment, print a row with the title, date, score and comment */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt_step = "alt";
				} else {
					$alt_step = "std";
				}
				$alt = " class=\"$alt_step\"";
				echo "         <tr$alt>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				echo "            <td>{$row['ClassName']}</td>\n";
				$query =	"SELECT discipline.Date, discipline.Comment, discipline.DisciplineIndex, " .
							"       tuser.FirstName AS TFirstName, tuser.Title AS TTitle, " .
							"       tuser.Surname AS TSurname, disciplinedate.PunishDate " .
							"       FROM discipline, disciplinedate, user AS tuser " .
							"WHERE  discipline.DisciplineDateIndex = disciplinedate.DisciplineDateIndex " .
							"AND    disciplinedate.DisciplineDateIndex = $pindex " .
							"AND    tuser.Username = discipline.WorkerUsername " .
							"AND    discipline.Username = '{$row['Username']}' " .
							"ORDER BY discipline.Date, discipline.DisciplineIndex";
				$nres =&  $db->query($query);
				if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
				echo "            <td nowrap>";
				if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo "{$nrow['TTitle']} {$nrow['TFirstName']} {$nrow['TSurname']}";
					while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "<br>{$nrow['TTitle']} {$nrow['TFirstName']} {$nrow['TSurname']}";
					}
				}
				echo "</td>\n";
				$nres =&  $db->query($query);
				if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
				echo "            <td nowrap>";
				if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					$dateinfo = date($dateformat, strtotime($nrow['Date']));
					echo "$dateinfo";
					while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
						$dateinfo = date($dateformat, strtotime($nrow['Date']));
						echo "<br>$dateinfo";
					}
				}
				echo "</td>\n";
				$nres =&  $db->query($query);
				if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
				echo "            <td nowrap>";
				if($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo "{$nrow['Comment']}";
					while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "<br>{$nrow['Comment']}";
					}
				}
				echo "</td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
		} else {
			echo "      <p align=\"center\" class=\"subtitle\">No punishments of this type have been issued and not punished yet up to {$_POST['date']}.</p>\n";
		}
	} else {
		include "header.php";
		
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/list_date.php", $LOG_DENIED_ACCESS,
					"Tried to view punishment date information.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
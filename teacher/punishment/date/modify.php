<?php
	/*****************************************************************
	 * teacher/punishment/date/modify.php  (c) 2006 Jonathan Dieter
	 *
	 * Check punishment attendance
	 *****************************************************************/

	/* Get variables */
	if(!isset($_GET['type'])) {
		if(!isset($_POST['type'])) {
			$link =		"index.php?location=" . dbfuncString2Int("teacher/punishment/date/modify.php") .
						"&amp;next=" .          $_GET['next'];
			include "admin/punishment/choose_type.php";
			exit(0);
		} else {
			$_GET['type'] = dbfuncString2Int($_POST['type']);
		}
	}
	$dtype = safe(dbfuncInt2String($_GET['type']));

	$query =	"SELECT Username, DisciplineDateIndex FROM disciplinedate " .
				"WHERE DisciplineTypeIndex=$dtype " .
				"AND   Done=0";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		if($row['Username'] == $username) {
			$perm = 1;
			$pindex = $row['DisciplineDateIndex'];
		} else {
			$perm = 0;
		}
	} else {
		include "header.php";
		echo "      <p>This punishment date no longer exists or is closed</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$query =	"SELECT DisciplineType " .
				"       FROM disciplinetype " .
				"WHERE  disciplinetype.DisciplineTypeIndex = $dtype ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$disc = strtolower($row['DisciplineType']);
	} else {
		$disc = "unknown punishment";
	}
	
	$title           = "Punishment attendance for $disc";
	/* Make sure user has permission to view student's marks for subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN) or $perm == 1) {
		if($_POST["action"] == "Check all") {
			$check_all = 1;
		} elseif($_POST["action"] == "Uncheck all") {
			$check_all = -1;
		} else {
			$check_all = 0;
		}
		include "header.php";
		
		$link             = "index.php?location=" . dbfuncString2Int("teacher/punishment/date/modify_action.php") .
							"&amp;type=" .          $_GET['type'] .
							"&amp;ptype=" .         dbfuncString2Int($pindex) .
							"&amp;next=" .          $_GET['next'];
		$query =	"SELECT view_discipline.Username, view_discipline.FirstName, view_discipline.Surname, view_discipline.Date, " .
					"       view_discipline.Comment, class.ClassName, view_discipline.DisciplineIndex, " .
					"       view_discipline.PunishDate, view_discipline.ServedType " .
					"       FROM class, classlist, view_discipline " .
					"WHERE  view_discipline.DisciplineDateIndex = $pindex " .
					"AND    classlist.Username         = view_discipline.Username " .
					"AND    classlist.ClassTermIndex   = classterm.ClassTermIndex " .
					"AND    classterm.TermIndex        = $termindex " .
					"AND    classterm.ClassIndex       = class.ClassIndex " .
					"AND    class.YearIndex            = $yearindex " .
					"AND    class.DepartmentIndex      = $depindex " .
					"GROUP BY view_discipline.Username " .
					"ORDER BY class.Grade, class.ClassName, view_discipline.Username ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		if($res->numRows() > 0) {
			/* Print punishments */

			echo "      <form action=\"$link\" method=\"post\" name=\"pundate\">\n"; // Form method
		
			echo "      <p align=\"center\">\n";
			echo "         <input type=\"submit\" name=\"action\" value=\"Check all\">&nbsp; \n";
			echo "         <input type=\"submit\" name=\"action\" value=\"Uncheck all\">&nbsp; \n";
			echo "         <input type=\"submit\" name=\"action\" value=\"Done\"> \n";
			echo "      </p>\n";
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Class</th>\n";
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
				if($check_all == 0) {
					if(isset($_POST['mass'][$row['Username']])) {
						if($_POST['mass'][$row['Username']] == "on") {
							$checked = "checked";
						} else {
							$checked = "";
						}
					} else {
						if(!is_null($row['ServedType']) and $row['ServedType'] == 1) {
							$checked = "checked";
						} else {
							$checked = "";
						}
					}
				} elseif($check_all == 1) {
					$checked = "checked";
				} else {
					$checked = "";
				}
				$alt = " class=\"$alt_step\"";
				echo "         <tr$alt>\n";
				echo "            <td><input type='checkbox' name='mass[]' value='{$row['Username']}' id=\"check{$row['Username']}\" $checked></input></td>\n";
				echo "            <td><label for=\"check{$row['Username']}\">{$row['FirstName']} {$row['Surname']} ({$row['Username']})</label></td>\n";
				echo "            <td><label for=\"check{$row['Username']}\">{$row['ClassName']}</label></td>\n";
				echo "         </tr>\n";
			}
			echo "      </table>\n";
			echo "      </form>\n";

		} else {
			echo "      <p align=\"center\" class=\"subtitle\">No students are punished in this list.</p>\n";
		}
	} else {
		include "header.php";
		
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/punishment/date/modify.php", $LOG_DENIED_ACCESS,
					"Tried to access punishment attendance.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
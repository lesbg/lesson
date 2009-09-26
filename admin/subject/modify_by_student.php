<?php
	/*****************************************************************
	 * admin/subject/modify_by_student.php  (c) 2006 Jonathan Dieter
	 *
	 * Add or remove subjects for a single student.
	 *****************************************************************/

	/* Get variables */
	if(!isset($nextLink)) $nextLink = $backLink;

	$studentusername = dbfuncInt2String($_GET['key']);
	$student         = dbfuncInt2String($_GET['keyname']);
	$title           = "$student's subjects";

	$link            = "index.php?location=" . dbfuncString2Int("admin/subject/modify_by_student_action.php") .
					   "&amp;key=" .           $_GET['key'] .
					   "&amp;keyname=" .       $_GET['keyname'] .
					   "&amp;next=" .          dbfuncString2Int($nextLink);
	
	include "header.php";                                   // Show header
	
	/* Check whether user is authorized to change subjects student is in */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$showalldeps = true;
		include "core/settermandyear.php";
		include "core/titletermyear.php";

		if(isset($errorlist)) {
			echo "      <p class=\"error\" align=\"center\">";
			foreach($errorlist as $error_subject) {            // If there were errors, print them.
				echo "         $student still has marks/comments in $error_subject.<br>\n";
			}
			echo "      </p>\n";
			echo "      <p class=\"error\" align=\"center\">If you want the marks deleted, " .
											"remove the subject again.</p>\n";
		}
		echo "      <form action=\"$link\" name=\"modSubj\" method=\"post\">\n";                  // Form method
		
		echo "         <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "            <tr>\n";
		echo "               <th>Subjects student is in</th>\n";
		echo "               <th>Subjects student is not in</th>\n";
		echo "            </tr>\n";
		echo "            <tr class=\"std\">\n";
		
		/* Get list of subjects for student and store in option list */
		echo "               <td>\n";
		echo "                  <select name=\"removesubject[]\" style=\"width: 200px;\" multiple size=19>\n";
		$res =&  $db->query("SELECT subject.Name, subject.SubjectIndex FROM subject, subjectstudent " .
							"WHERE subjectstudent.Username = \"$studentusername\" " .
							"AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
							"AND   subject.TermIndex = $termindex " .
							"AND   subject.YearIndex = $yearindex " .
							"ORDER BY subject.Name");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(isset($errorlist[$row['SubjectIndex']])) {
				$remSubject = "!{$row['SubjectIndex']}";
			} else {
				$remSubject = "{$row['SubjectIndex']}";
			}
			echo "                     <option value=\"$remSubject\">{$row['Name']}\n";
		}
		echo "                  </select>\n";
		echo "               </td>\n";
		
		/* Create listboxes with classes */
		$res =&  $db->query("SELECT class.ClassIndex FROM classlist, classterm, class " .
							"WHERE class.YearIndex = $yearindex " .
							"AND   classterm.ClassTermIndex = classlist.ClassTermIndex " .
							"AND   classterm.TermIndex = $termindex " .
							"AND   class.ClassIndex = classterm.ClassIndex " .
							"AND   Username = \"$studentusername\"");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$studentclass = $row['ClassIndex'];
		} else {
			$studentclass = NULL;
		}
		echo "               <td>\n";
		echo "                  List Class: <select name=\"class\" onchange=\"modSubj.submit()\">\n";
		echo "                     <option value=\"ALL\"";
		if(isset($_POST['class'])) {
			if($_POST['class'] == "ALL") echo " selected"; else $_POST['class'] = intval($_POST['class']);
		} else {
			if(is_null($studentclass)) {
				echo " selected";
				$_POST['class'] = "ALL";
			}
		}
		echo ">All classes\n";

		$res =&  $db->query("SELECT ClassIndex, Grade, ClassName FROM class " .
							"WHERE YearIndex       = $yearindex " .
							"AND   DepartmentIndex = $depindex " .
							"ORDER BY Grade, ClassName");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(!isset($_POST['class'])) {
				if(!is_null($studentclass)) {
					$_POST['class'] = $studentclass;
				} else {
					$_POST['class'] = $row['ClassIndex'];
				}
			}
			echo "                     <option value=\"{$row['ClassIndex']}\"";
			if($_POST['class'] == $row['ClassIndex']) echo " selected";
			echo ">{$row['ClassName']}\n";
		}
		echo "                  </select>\n";
		echo "                  <noscript>\n"; // No javascript compatibility
		echo "                     <input type=\"submit\" name=\"action\" value=\"Update\" \>\n";
		echo "                  </noscript><br>\n";
		
		/* Get list of subjects for active class */
		$query =	"SELECT subject.Name, subject.SubjectIndex FROM subject LEFT OUTER JOIN subjectstudent " .
					"       ON (subject.SubjectIndex = subjectstudent.SubjectIndex AND subjectstudent.Username " .
					"           = \"$studentusername\") " .
					"WHERE subject.TermIndex = $termindex " .
					"AND   subject.YearIndex = $yearindex " .
					"AND   subjectstudent.Username IS NULL ";
		if($_POST['class'] != "ALL") {
			$query .= "AND   subject.ClassIndex = {$_POST['class']} ";
		} else {
			$query .= "AND   subject.DepartmentIndex = $depindex ";
		}
		$query .=	"ORDER BY subject.Name";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		echo "                  <select name=\"addsubject[]\" style=\"width: 200px;\" multiple size=17>\n";
		
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			echo "                     <option value=\"{$row['SubjectIndex']}\">{$row['Name']}\n";
		}
		echo "                  </select>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr class=\"alt\">\n";
		echo "               <td align=\"center\"><input type=\"submit\" name=\"action\" value=\">>\" \></td>\n";
		echo "               <td align=\"center\"><input type=\"submit\" name=\"action\" value=\"<<\" \></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";               // End of table
		echo "         <p align=\"center\"><input type=\"submit\" name=\"action\" value=\"Done\" \></p>\n";
		echo "         <p></p>\n";
		echo "      </form>\n";
	} else {  // User isn't authorized to view or change scores.
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
<?php
	/*****************************************************************
	 * admin/subject/modify_list.php  (c) 2005, 2006 Jonathan Dieter
	 *
	 * Add or remove students from a subject.
	 *****************************************************************/

	/* Get variables */
	if(!isset($nextLink)) $nextLink = $backLink;
	if(!isset($_GET['key2'])) $_GET['key2'] = "";
	if(!isset($_GET['key3'])) $_GET['key3'] = "";

	$title           = "Add or remove students from " . dbfuncInt2String($_GET['keyname']);
	$subjectindex    = dbfuncInt2String($_GET['key']);
	$classindex      = dbfuncInt2String($_GET['key2']);
	$gradeindex      = dbfuncInt2String($_GET['key3']);
	if($classindex == "NULL" or $classindex=="") $classindex = NULL; else $classindex = intval($classindex);
	if($gradeindex == "NULL" or $gradeindex=="") $gradeindex = NULL; else $gradeindex = intval($gradeindex);

	$link            = "index.php?location=" . dbfuncString2Int("admin/subject/modify_list_action.php") .
					   "&amp;key=" .           $_GET['key'] .
					   "&amp;keyname=" .       $_GET['keyname'] .
					   "&amp;next=" .          dbfuncString2Int($nextLink);
	
	include "header.php";                                   // Show header
	
	/* Check whether user is authorized to change subject */
	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {
		$nochangeyt = true;
		
		$res =&  $db->query("SELECT YearIndex, TermIndex FROM subject " .
							"WHERE SubjectIndex = $subjectindex");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$yearindex = $row['YearIndex'];
			$termindex = $row['TermIndex'];
			
			include "core/titletermyear.php";

			if(isset($errorlist) and is_array($errorlist)) {
				echo "      <p class=\"error\" align=\"center\">";
				foreach($errorlist as $key=>$error_user) {            // If there were errors, print them.
 					echo "         $error_user still has marks/comments in this subject.<br>\n";
				}
				echo "      </p>\n";
				echo "      <p class=\"error\" align=\"center\">If you want the marks deleted, " .
												"remove the student(s) from the subject again.</p>\n";
			}
			echo "      <form action=\"$link\" name=\"modSubj\" method=\"post\">\n";                  // Form method
			
			$editlink        = "index.php?location=" . dbfuncString2Int("admin/subject/modify.php") .
							   "&amp;key=" .           $_GET['key'] .
							   "&amp;keyname=" .       $_GET['keyname'] .
							   "&amp;next=" .          dbfuncString2Int($nextLink);
			$editbutton = dbfuncGetButton($editlink, "Edit subject", "medium", "", "Edit subject specific data");
			echo "         <p align=\"center\">$editbutton</p>\n";
			echo "         <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "            <tr>\n";
			echo "               <th>Students in subject</th>\n";
			echo "               <th>Unassigned students</th>\n";
			echo "            </tr>\n";
			echo "            <tr class=\"std\">\n";
			
			/* Get list of students in subject and store in option list */
			echo "               <td>\n";
			echo "                  <select name=\"removefromsubject[]\" style=\"width: 200px;\" multiple size=19>\n";
			$res =&  $db->query("SELECT user.FirstName, user.Surname, user.Username FROM " .
								"       user, subjectstudent " .
								"WHERE subjectstudent.Username = user.Username " .
								"AND   subjectstudent.SubjectIndex = $subjectindex " .
								"ORDER BY user.Username");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(isset($errorlist[$row['Username']])) {
					$remUsername = "!{$row['Username']}";
				} else {
					$remUsername = "{$row['Username']}";
				}
				echo "                     <option value=\"$remUsername\">{$row['Username']} - {$row['FirstName']} " .
														"{$row['Surname']}\n";
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			
			/* Create listboxes with classes */
			echo "               <td>\n";
			echo "                  List Class: <select name=\"class\" onchange=\"modSubj.submit()\">\n";
			$res =&  $db->query("SELECT ClassIndex, Grade, ClassName FROM class " .
								"WHERE YearIndex = $yearindex " .
								"AND   DepartmentIndex = $depindex " .
								"ORDER BY Grade, ClassName");
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!isset($_POST['class'])) {
					if(!is_null($classindex)) {
						$_POST['class'] = $classindex;
					} elseif(!is_null($gradeindex)) {
						if($gradeindex == $row['Grade']) {
							$_POST['class'] = $row['ClassIndex'];
						}
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
			
			$showNew  = "";
			$showOld  = "";
			$showSpec = "";
			$showReg  = "";
			$showAll  = "";
			if(isset($_POST['show'])) {
				if    ($_POST['show'] == "new")  $showNew     = "checked";
				elseif($_POST['show'] == "old")  $showOld     = "checked";
				elseif($_POST['show'] == "spec") $showSpec    = "checked";
				elseif($_POST['show'] == "reg")  $showReg     = "checked";
				else                             $showAll     = "checked";
			} else {
				$showAll = "checked";
			}
			
			/* Get list of students who are in the active class */
			echo "                  <select name=\"addtosubject[]\" style=\"width: 200px;\" multiple size=10>\n";
			if ($_POST['class'] != "") {
				$query =        "SELECT user.FirstName, user.Surname, user.Username FROM " .
								"       user, classlist LEFT JOIN subjectstudent ON classlist.Username=subjectstudent.Username AND " .
								"       subjectstudent.SubjectIndex = $subjectindex " .
								"WHERE  user.Username = classlist.Username " .
								"AND    subjectstudent.Username IS NULL " .
								"AND    classlist.ClassIndex = {$_POST['class']} ";
				if    ($showNew  == "checked")            // Add appropriate filter according to radio button that has been selected
					$query .=   "AND user.User1 = 1 ";
				elseif($showOld  == "checked")
					$query .=   "AND (user.User1 IS NULL OR user.User1 = 0) ";
				elseif($showSpec == "checked") 
					$query .=   "AND user.User2 = 1 ";
				elseif($showReg  == "checked")
					$query .=   "AND (user.User2 IS NULL OR user.User2 = 0) ";
				$query .=       "ORDER BY user.Username";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
				
				while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					echo "                     <option value=\"{$row['Username']}\">{$row['Username']} - {$row['FirstName']} " .
															"{$row['Surname']}\n";
				}
			}
			echo "                  </select><br>\n";
			echo "                  <label for=\"showall\">\n";
			echo "                     <input type=\"radio\" name=\"show\" value=\"all\" onchange=\"modSubj.submit()\" " .
											"id=\"showall\" $showAll>All students<br>\n";
			echo "                  </label>\n";
			echo "                  <label for=\"shownew\">\n";
			echo "                     <input type=\"radio\" name=\"show\" value=\"new\" onchange=\"modSubj.submit()\" " . 
											"id=\"shownew\" $showNew>New students<br>\n";
			echo "                  </label>\n";
			echo "                  <label for=\"showold\">\n";
			echo "                     <input type=\"radio\" name=\"show\" value=\"old\" onchange=\"modSubj.submit()\" " . 
											"id=\"showold\" $showOld>Old students<br>\n";
			echo "                  </label>\n";
			echo "                  <label for=\"showspec\">\n";
			echo "                     <input type=\"radio\" name=\"show\" value=\"spec\" onchange=\"modSubj.submit()\" " . 
											"id=\"showspec\" $showSpec>Special students<br>\n";
			echo "                  </label>\n";
			echo "                  <label for=\"showreg\">\n";
			echo "                     <input type=\"radio\" name=\"show\" value=\"reg\" onchange=\"modSubj.submit()\" " . 
											"id=\"showreg\" $showReg>Regular students\n";
			echo "                  </label>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "            <tr class=\"alt\">\n";
			echo "               <td align=\"center\">\n";
			echo "                  <input type=\"submit\" name=\"action\" value=\"&gt;&gt;\">\n";
			echo "                  <input type=\"submit\" name=\"action\" value=\"&gt;\">\n";
			echo "               </td>\n";
			echo "               <td align=\"center\">\n";
			echo "                  <input type=\"submit\" name=\"action\" value=\"&lt;\">\n";
			echo "                  <input type=\"submit\" name=\"action\" value=\"&lt;&lt;\">\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "            <tr>\n";
			echo "               <th>Subject teacher(s)</th>\n";
			echo "               <th>Unassigned teachers</th>\n";
			echo "            </tr>\n";
			echo "            <tr class=\"std\">\n";
			
			/* Get list of teachers in subject and store in option list */
			echo "               <td>\n";
			echo "                  <select name=\"removefromteacherlist[]\" style=\"width: 200px;\" multiple size=10>\n";
			$res =&  $db->query("SELECT user.FirstName, user.Surname, user.Username FROM " .
								"       user, subjectteacher " .
								"WHERE subjectteacher.Username = user.Username " .
								"AND   subjectteacher.SubjectIndex = $subjectindex " .
								"ORDER BY user.Username");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "                     <option value=\"{$row['Username']}\">{$row['Username']} - {$row['FirstName']} " .
														"{$row['Surname']}\n";
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			
			/* Get list of unassigned teachers */
			echo "               <td>\n";
			echo "                  <select name=\"addtoteacherlist[]\" style=\"width: 200px;\" multiple size=10>\n";
			
			$query =        "SELECT user.FirstName, user.Surname, user.Username FROM " .
							"       user LEFT JOIN subjectteacher ON user.Username=subjectteacher.Username AND " .
							"       subjectteacher.SubjectIndex = $subjectindex " .
							"WHERE  user.ActiveTeacher = 1 " .
							"AND    subjectteacher.Username IS NULL " .
							"ORDER BY user.Username";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "                     <option value=\"{$row['Username']}\">{$row['Username']} - {$row['FirstName']} " .
														"{$row['Surname']}\n";
			}
			echo "                  </select><br>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "            <tr class=\"alt\">\n";
			echo "               <td align=\"center\">\n";
			echo "                  <input type=\"submit\" name=\"actiont\" value=\"&gt;\">\n";
			echo "               </td>\n";
			echo "               <td align=\"center\">\n";
			echo "                  <input type=\"submit\" name=\"actiont\" value=\"&lt;\">\n";
			echo "               </td>\n";			echo "            </tr>\n";
			echo "         </table>\n";               // End of table
			echo "         <p align=\"center\"><input type=\"submit\" name=\"action\" value=\"Done\" \></p>\n";
			echo "         <p></p>\n";
			echo "      </form>\n";
		} else {
			echo "      <p>The subject \"$title\" is no longer accessible.  Have you deleted it?</p>\n";
			echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		}
	} else {  // User isn't authorized to view or change scores.
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
<?php
	/*****************************************************************
	 * core/titletermyear.php  (c) 2005 Jonathan Dieter
	 *
	 * Print the term and year in <h2>.  $termindex, $yearindex, and
	 * $db must be set before invoking this page.
	 *****************************************************************/
	 
	$tty_oldyear = NULL;
	$tty_newyear = NULL;
	$tty_oldterm = NULL;
	$tty_newterm = NULL;
	if(!isset($nochangeyt))   $nochangeyt   = false;
	if(!isset($nochangeyear)) $nochangeyear = false;
	if(!isset($nochangeterm)) $nochangeterm = false;
	if(!isset($showterm))     $showterm     = true;
	if(!isset($showyear))     $showyear     = true;
	if(!isset($showdeps))     $showdeps     = true;
	if(!isset($showalldeps))  $showalldeps  = false;
	
	$tty_link = "index.php?location=" . $_GET['location'];
	
	foreach($_GET as $tty_getkey => $getval) {
		if($tty_getkey != 'year' && $tty_getkey != 'term' && $tty_getkey != 'location')
			$tty_link .= "&amp;$tty_getkey=$getval";
	}
	if($showyear) {
		$tty_res =&   $db->query("SELECT Year, YearIndex FROM year " .    // Run query to get year
								"ORDER BY YearNumber");
		if(DB::isError($tty_res)) die($tty_res->getDebugInfo());           // Check for errors in query
		while($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {        // If there is a year, print it
			if($tty_row['YearIndex'] == $yearindex) {
				$tty_yearname = $tty_row['Year'];
				if($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$tty_newyear = $tty_row['YearIndex'];
				}
				break;
			} else {
				$tty_oldyear = $tty_row['YearIndex'];
			}
		}
		
		if($nochangeyt == false and $nochangeyear == false) {
			if(!is_null($tty_oldyear)) {
				$tty_nlink   = $tty_link . "&amp;year=" . dbfuncString2Int($tty_oldyear);
				$tty_prevbutton = dbfuncGetButton($tty_nlink, "<", "small", "prevnext", "Previous school year");
			} else {
				$tty_prevbutton = dbfuncGetDisabledButton("<", "small", "prevnext");
			}
			if(!is_null($tty_newyear)) {
				$tty_nlink   = $tty_link . "&amp;year=" . dbfuncString2Int($tty_newyear);
				$tty_nextbutton = dbfuncGetButton($tty_nlink, ">", "small", "prevnext", "Next school year");
			} else {
				$tty_nextbutton = dbfuncGetDisabledButton(">", "small", "prevnext");
			}
			echo "      <h3>$tty_prevbutton $tty_yearname $tty_nextbutton</h3>\n";
		} else {
			echo "      <h3>$tty_yearname</h3>\n";
		}
	}

	if($nochangeyt == false and isset($depindex) and !is_null($depindex) and $showdeps) {
		if(!$showalldeps) {
			if(!$admin_page) {
				$query =	"(SELECT department.Department, class.DepartmentIndex FROM class, classlist, " .
							"        department " .
							" WHERE classlist.Username = \"$username\" " .
							" AND   class.ClassIndex   = classlist.ClassIndex " .
							" AND   class.YearIndex    = $yearindex " .
							" AND   department.DepartmentIndex = class.DepartmentIndex) " .
							"UNION " .
							"(SELECT department.Department, subject.DepartmentIndex FROM subject, subjectteacher, " .
							"        department " .
							" WHERE subjectteacher.Username = \"$username\" " .
							" AND   subject.SubjectIndex    = subjectteacher.SubjectIndex " .
							" AND   subject.YearIndex       = $yearindex " .
							" AND   department.DepartmentIndex = subject.DepartmentIndex) " .
	/*						"UNION " .
							"(SELECT department.Department, DepartmentIndex FROM hod " .
							" WHERE hod.Username = \"$username\") " .
							"UNION " .
							"(SELECT department.DepartmentIndex FROM department, principal " .
							" WHERE principal.Username=\"$username\") " .
							"UNION " .
							"(SELECT department.DepartmentIndex FROM department, counselorlist " .
							" WHERE counselorlist.Username=\"$username\") " .*/
							"UNION " .
							"(SELECT department.Department, user.DepartmentIndex FROM user, department " .
							" WHERE user.Username = \"$username\" " .
							" AND department.DepartmentIndex = user.DepartmentIndex) " .
							"ORDER BY DepartmentIndex";
			} else {
				$query =	"(SELECT department.Department, hod.DepartmentIndex FROM " .
							"        hod INNER JOIN department USING (DepartmentIndex) " .
							" WHERE hod.Username = '$username') " .
							"UNION " .
							"(SELECT department.DepartmentIndex, department.Department FROM " .
							"        department, principal " .
							" WHERE principal.Username='$username') " .
							"UNION " .
							"(SELECT department.DepartmentIndex, department.Department FROM " .
							"        department, counselorlist " .
							" WHERE counselorlist.Username='$username') " .
							"ORDER BY DepartmentIndex";
			}
		} else {
			$query =	"SELECT DepartmentIndex, Department FROM department " .
						"ORDER BY DepartmentIndex ";
		}
		$tty_res =&  $db->query($query);
		if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
		if($tty_res->numRows() > 1) {
			echo "      <h3><i>";
			while($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($tty_row['DepartmentIndex'] == $depindex) {
					$buttontype = "selected";
				} else {
					$buttontype = "prevnext";
				}
				$tty_nlink = $tty_link . "&amp;dept=" . dbfuncString2Int($tty_row['DepartmentIndex']);
				$tty_button = dbfuncGetButton($tty_nlink, $tty_row['Department'], "small", $buttontype, "{$tty_row['Department']} department");
				echo "$tty_button ";
			}
			echo "</i></h3>\n";
		}
	}
	if(isset($termindex) and !is_null($termindex) and $showterm) {
		$tty_res =&  $db->query("SELECT TermName, TermIndex FROM term " .  // Run query to get term
								"WHERE DepartmentIndex = $depindex " .
								"ORDER BY TermNumber");
		if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
		while($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {        // If there is a year, print it
			if($tty_row['TermIndex'] == $termindex) {
				$tty_termname = $tty_row['TermName'];
				if($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$tty_newterm = $tty_row['TermIndex'];
				}
				break;
			} else {
				$tty_oldterm = $tty_row['TermIndex'];
			}
		}
		
		if($nochangeyt == false and $nochangeterm == false) {
			if(!is_null($tty_oldterm)) {
				$tty_nlink = $tty_link . "&amp;term=" . dbfuncString2Int($tty_oldterm);
				$tty_prevbutton = dbfuncGetButton($tty_nlink, "<", "small", "prevnext", "Previous term");
			} else {
				$tty_prevbutton = dbfuncGetDisabledButton("<", "small", "prevnext");
			}
			if(!is_null($tty_newterm)) {
				$tty_nlink = $tty_link . "&amp;term=" . dbfuncString2Int($tty_newterm);
				$tty_nextbutton = dbfuncGetButton($tty_nlink, ">", "small", "prevnext", "Next term");
			} else {
				$tty_nextbutton = dbfuncGetDisabledButton(">", "small", "prevnext");
			}
			echo "      <h3><i>$tty_prevbutton $tty_termname $tty_nextbutton</i></h3>\n";
		} else {
			echo "      <h3><i>$tty_termname</i></h3>\n";
		}
	}
?>
<?php
/**
 * ***************************************************************
 * core/settermandyear.php (c) 2005, 2016 Jonathan Dieter
 *
 * Set the variables $termindex and $yearindex.
 * ***************************************************************
 */
if (isset($_GET['year'])) { // Check whether we're looking at a different year
	$yearindex = safe(dbfuncInt2String($_GET['year']));
	if($yearindex != $_SESSION['yearindex'])
		$termindex = NULL;
	$_SESSION['yearindex'] = $yearindex;
}

if (isset($_GET['dept'])) { // Check whether we're looking at a different term
	$depindex = safe(dbfuncInt2String($_GET['dept']));
}
if (isset($_GET['term'])) { // Check whether we're looking at a different term
	$termindex = safe(dbfuncInt2String($_GET['term']));
}

if (! isset($admin_page))
	$admin_page = false;
if (! isset($main_page))
	$main_page = false;

if (! isset($showalldeps)) {
	if ($is_admin and ! $main_page) {
		$showalldeps = true;
	} else {
		$showalldeps = false;
	}
}

$query = "SELECT YearIndex FROM currentinfo ORDER BY InputDate DESC LIMIT 1";
$tty_res = &  $db->query($query);
if (DB::isError($tty_res))
	die($tty_res->getDebugInfo()); // Check for errors in query
if ($tty_row = & $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$currentyear = $tty_row['YearIndex'];
}

if (! isset($depindex)) {
	/* If department hasn't been set, choose default department for user */
	$query = "SELECT DepartmentIndex FROM user " .
			 "WHERE Username = '$username' " . "AND DepartmentIndex IS NOT NULL ";
	$tty_res = &  $db->query($query);
	if (DB::isError($tty_res))
		die($tty_res->getDebugInfo()); // Check for errors in query
	if ($tty_row = & $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$depindex = $tty_row['DepartmentIndex'];
	} else {
		$depindex = 3;
	}
}

/* Verify that user is allowed to choose specified department */
if (! $showalldeps) {
	if (! $admin_page) {
		$query = "(SELECT class.DepartmentIndex FROM class, classterm, classlist " .
				 " WHERE classlist.Username         = '$username' " .
				 " AND   classlist.ClassTermIndex   = classterm.ClassTermIndex " .
				 " AND   classterm.ClassIndex       = class.ClassIndex " .
				 " AND   class.YearIndex            = $yearindex) " . "UNION " .
				 "(SELECT class.DepartmentIndex  " .
				 "         FROM user, support, class, classterm, classlist, currentterm " .
				 " WHERE support.WorkerUsername   = '$username' " .
				 " AND   user.Username            = support.WorkerUsername " .
				 " AND   user.ActiveTeacher       = 1 " .
				 " AND   user.SupportTeacher      = 1 " .
				 " AND   support.StudentUsername  = classlist.Username " .
				 " AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
				 " AND   classterm.TermIndex      = currentterm.TermIndex " .
				 " AND   classterm.ClassIndex     = class.ClassIndex " .
				 " AND   class.YearIndex          = $yearindex" .
				 " GROUP BY class.DepartmentIndex) " . "UNION " .
				 "(SELECT subject.DepartmentIndex FROM subject, subjectteacher " .
				 " WHERE subjectteacher.Username = '$username' " .
				 " AND   subject.SubjectIndex    = subjectteacher.SubjectIndex " .
				 " AND   subject.YearIndex       = $yearindex) " . "UNION ";
		if (! $main_page) {
			$query .= "(SELECT DepartmentIndex FROM hod " .
					 " WHERE hod.Username = '$username') " . "UNION " .
					 "(SELECT department.DepartmentIndex FROM department, principal " .
					 " WHERE principal.Username='$username') " . "UNION " .
					 "(SELECT department.DepartmentIndex FROM department, counselorlist " .
					 " WHERE counselorlist.Username='$username') " . "UNION ";
		}
		$query .= "(SELECT DepartmentIndex FROM user " .
				 " WHERE Username = '$username' " .
				 " AND DepartmentIndex IS NOT NULL) " .
				 "ORDER BY DepartmentIndex";
	} else {
		$query = "(SELECT DepartmentIndex FROM hod " .
				 " WHERE hod.Username = '$username') " . "UNION " .
				 "(SELECT department.DepartmentIndex FROM department, principal " .
				 " WHERE principal.Username='$username') " . "UNION " .
				 "(SELECT department.DepartmentIndex FROM department, counselorlist " .
				 " WHERE counselorlist.Username='$username') " .
				 "ORDER BY DepartmentIndex";
	}
} else {
	$query = "SELECT DepartmentIndex FROM department " .
			 "ORDER BY DepartmentIndex ";
}
$tty_res = &  $db->query($query);
if (DB::isError($tty_res))
	die($tty_res->getDebugInfo()); // Check for errors in query
if ($tty_res->numRows() > 0) {
	if (isset($depindex)) {
		while ( $tty_row = & $tty_res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			if (! isset($final))
				$final = $tty_row['DepartmentIndex'];
			if ($depindex == $tty_row['DepartmentIndex'])
				$final = $depindex;
		}
		$depindex = $final;
	} else {
		$tty_row = & $tty_res->fetchRow(DB_FETCHMODE_ASSOC);
		$depindex = $tty_row['DepartmentIndex'];
	}
} else {
	$depindex = NULL;
}
/* Store chosen department */
$_SESSION['depindex'] = $depindex;

$query = "SELECT TermIndex FROM currentterm " .
		 "WHERE DepartmentIndex=$depindex ";
$tty_res = &  $db->query($query);
if (DB::isError($tty_res))
	die($tty_res->getDebugInfo()); // Check for errors in query
if ($tty_row = & $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$currentterm = $tty_row['TermIndex'];
} else {
	$currentterm = NULL;
}

if (isset($termindex)) {
	$query =	"SELECT TermIndex FROM term " . 
				"WHERE DepartmentIndex=$depindex " .
				"AND   TermIndex=$termindex";
	$tty_res = &  $db->query($query);
	if (DB::isError($tty_res))
		die($tty_res->getDebugInfo()); // Check for errors in query
	if($tty_res->numRows() == 0)
		$termindex = NULL;
}

if(!isset($termindex) || is_null($termindex)) {
	$termindex = $currentterm;	
	
	$query = "SELECT YearIndex FROM currentinfo ORDER BY InputDate DESC LIMIT 1";
	$tty_res =& $db->query($query);
	if (DB::isError($tty_res))
		die($tty_res->getDebugInfo()); // Check for errors in query

	if ($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$current_year_index = $tty_row['YearIndex'];
		if($current_year_index != $yearindex) {
			$query =	"SELECT TermIndex FROM term " . 
						"WHERE DepartmentIndex=$depindex " .
						"AND TermNumber=1";
			$tty_res =& $db->query($query);
			if (DB::isError($tty_res))
				die($tty_res->getDebugInfo()); // Check for errors in query
			if ($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$termindex = $tty_row['TermIndex'];
			}
		}		
	}
}

$_SESSION['termindex'] = $termindex;
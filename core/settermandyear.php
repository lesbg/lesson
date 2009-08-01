<?php
	/*****************************************************************
	 * core/settermandyear.php  (c) 2005 Jonathan Dieter
	 *
	 * Set the variables $termindex and $yearindex.
	 *****************************************************************/
	
	
	if(isset($_GET['year'])) {  // Check whether we're looking at a different year
		$yearindex = safe(dbfuncInt2String($_GET['year']));
		$_SESSION['yearindex'] = $yearindex;
	}

	$currentyear = $yearindex;
	
	if(isset($_GET['dept'])) {  // Check whether we're looking at a different term
		$depindex = safe(dbfuncInt2String($_GET['dept']));
	}
	if(isset($_GET['term'])) {  // Check whether we're looking at a different term
		$termindex = safe(dbfuncInt2String($_GET['term']));
	}
	
	if(!isset($admin_page))   $admin_page   = false;
	if(!isset($main_page))    $main_page    = false;

	if(!isset($showalldeps)) {
		if($is_admin and !$main_page) {
			$showalldeps = true;
		} else {
			$showalldeps  = false;
		}
	}

	if(!isset($depindex)) {
		/* If department hasn't been set, choose default department for user */
		$query =	"SELECT DepartmentIndex FROM user " .
					"WHERE Username = '$username' " .
					"AND DepartmentIndex IS NOT NULL ";
		$tty_res =&  $db->query($query);
		if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
		if($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$depindex = $tty_row['DepartmentIndex'];
		}
	}

	/* Verify that user is allowed to choose specified department */
	if(!$showalldeps) {
		if(!$admin_page) {
			$query =		"(SELECT class.DepartmentIndex FROM class, classlist " .
							" WHERE classlist.Username = '$username' " .
							" AND   class.ClassIndex   = classlist.ClassIndex " .
							" AND   class.YearIndex    = $yearindex) " .
							"UNION " .
							"(SELECT subject.DepartmentIndex FROM subject, subjectteacher " .
							" WHERE subjectteacher.Username = '$username' " .
							" AND   subject.SubjectIndex    = subjectteacher.SubjectIndex " .
							" AND   subject.YearIndex       = $yearindex) " .
							"UNION ";
			if(!$main_page) {
				$query .=	"(SELECT DepartmentIndex FROM hod " .
							" WHERE hod.Username = '$username') " .
							"UNION " .
							"(SELECT department.DepartmentIndex FROM department, principal " .
							" WHERE principal.Username='$username') " .
							"UNION " .
							"(SELECT department.DepartmentIndex FROM department, counselorlist " .
							" WHERE counselorlist.Username='$username') " .
							"UNION ";
			}
			$query .=		"(SELECT DepartmentIndex FROM user " .
							" WHERE Username = '$username' " .
							" AND DepartmentIndex IS NOT NULL) " .
							"ORDER BY DepartmentIndex";
		} else {
			$query =	"(SELECT DepartmentIndex FROM hod " .
						" WHERE hod.Username = '$username') " .
						"UNION " .
						"(SELECT department.DepartmentIndex FROM department, principal " .
						" WHERE principal.Username='$username') " .
						"UNION " .
						"(SELECT department.DepartmentIndex FROM department, counselorlist " .
						" WHERE counselorlist.Username='$username') " .
						"ORDER BY DepartmentIndex";
		}
	} else {
		$query =	"SELECT DepartmentIndex FROM department " .
					"ORDER BY DepartmentIndex ";
	}
	$tty_res =&  $db->query($query);
	if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
	if($tty_res->numRows() > 0) {
		if(isset($depindex)) {
			while($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!isset($final)) $final = $tty_row['DepartmentIndex'];
				if($depindex==$tty_row['DepartmentIndex']) $final = $depindex;
			}
			$depindex = $final;
		} else {
			$tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC);
			$depindex = $tty_row['DepartmentIndex'];
		}
	} else {
		$depindex = NULL;
	}
	/* Store chosen department */
	$_SESSION['depindex'] = $depindex;

	$query =	"SELECT TermIndex FROM currentterm " .
				"WHERE DepartmentIndex=$depindex ";
	$tty_res =&  $db->query($query);
	if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
	if($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$currentterm = $tty_row['TermIndex'];
	} else {
		$currentterm = NULL;
	}

	if(isset($termindex)) {
		$query =	"SELECT TermIndex FROM term " .
					"WHERE DepartmentIndex=$depindex " .
					"ORDER BY TermNumber";
		$tty_res =&  $db->query($query);
		if(DB::isError($tty_res)) die($tty_res->getDebugInfo());             // Check for errors in query
		$final = $currentterm;
		while($tty_row =& $tty_res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($termindex==$tty_row['TermIndex']) $final = $termindex;
		}
		$termindex = $final;
	} else {
		$termindex = $currentterm;
	}

	$_SESSION['termindex'] = $termindex;
?>
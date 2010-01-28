<?php
	/*****************************************************************
	 * admin/open_reports.php  (c) 2009 Jonathan Dieter
	 *
	 * Open reports for all subjects and classes
	 *****************************************************************/

	$title           = "Open reports.";
	
	include "header.php";                                    // Show header
	
	$showalldeps = true;
	include "core/settermandyear.php";

	if(!$is_admin) {
		log_event($LOG_LEVEL_ERROR, "admin/open_reports.php", $LOG_DENIED_ACCESS,
					"Tried to open reports for this quarter.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	$query =	"UPDATE subject SET CanDoReport=1 " .
				"WHERE subject.TermIndex = $termindex " .
				"AND   subject.YearIndex = $yearindex";				
	$res =& $db->query($query);
	if(DB::isError($ttyres)) die($ttyres->getMessage());             // Check for errors in query
	
	$query =	"UPDATE classterm, class SET classterm.CanDoReport=1 " .
				"WHERE classterm.TermIndex = $termindex " .
				"AND   classterm.ClassIndex = class.ClassIndex " .
				"AND   class.YearIndex = $yearindex";
	$res =& $db->query($query);
	if(DB::isError($ttyres)) die($ttyres->getMessage());             // Check for errors in query

	echo "      <p><a href=\"$backLink\">Click here to continue</a></p>\n";
	
	include "footer.php";
?>
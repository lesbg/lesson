<?php
	/*****************************************************************
	 * student/open_descr.php  (c) 2008 Jonathan Dieter
	 *
	 * Show report
	 *****************************************************************/

	$assignmentindex       = safe(dbfuncInt2String($_GET['key']));

	$MAX_SIZE = 10*1024*1024;

	include "core/settermandyear.php";

	/* Check whether subject is open for report editing */
	$query =	"SELECT DescriptionData, DescriptionFileType FROM assignment " .
				"WHERE AssignmentIndex = $assignmentindex";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		/* Print error message */
		include "header.php";
		echo "      <p>Assignment doesn't exist. Perhaps it's been deleted?</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	if(is_null($row['DescriptionData'])) {
		/* Print error message */
		include "header.php";
		echo "      <p>There's no description for this assignment.</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	header("Content-type: {$row['DescriptionFileType']}");
	header("Content-disposition: inline; filename=description.pdf");

	echo $row['DescriptionData'];
?>

<?php
	/*****************************************************************
	 * student/doupload.php  (c) 2006 Jonathan Dieter
	 *
	 * Print information about how student is doing in a subject
	 *****************************************************************/

	/* Get variables */
	$assignmentindex = safe(dbfuncInt2String($_GET['key']));
	$name            = dbfuncInt2String($_GET['keyname']);
	$studentusername = safe(dbfuncInt2String($_GET['key2']));
	$subject         = dbfuncInt2String($_GET['key2name']);
	
	$query =	"SELECT subject.SubjectIndex, Score, Uploadable, UploadName FROM subject INNER JOIN assignment " .
				"       USING (SubjectIndex) INNER JOIN subjectstudent USING (SubjectIndex) " .
				"       LEFT OUTER JOIN mark ON (mark.Username = subjectstudent.Username " .
				"       AND mark.AssignmentIndex = assignment.AssignmentIndex) " .
				"WHERE subjectstudent.Username        = '$studentusername' " .
				"AND   assignment.AssignmentIndex = $assignmentindex ";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query	

	/* Make sure user has permission to upload file */
	if($is_admin or ($studentusername == $username and $res->numRows() > 0)) {
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
		$subjectindex = $row['SubjectIndex'];
		
		$title         = "LESSON - Uploading file...";               //  common info and go to the appropriate page.
		$noHeaderLinks = true;
		$noJS          = true;
		
		include "header.php";
		if($row['Uploadable'] == 1 and (is_null($row['Score']) or $row['Score'] == $MARK_LATE)) {
			$hwdata  = pathinfo($_FILES['hw']['name']);
			
			$tres =&  $db->query("SELECT subjectteacher.Username, subject.RenameUploads " .
								"FROM assignment, subject, subjectteacher " .
								"WHERE assignment.AssignmentIndex = $assignmentindex " .
								"AND   subject.SubjectIndex = assignment.SubjectIndex " .
								"AND   subjectteacher.SubjectIndex = subject.SubjectIndex");
			if(DB::isError($tres)) die($tres->getDebugInfo());         // Check for errors in query
			if($trow =& $tres->fetchRow(DB_FETCHMODE_ASSOC)) {
				$rename = $trow['RenameUploads'];
				if(isset($hwdata['extension']) and $hwdata['extension'] != "") {
					if($rename == 1) {
						$file = "$studentusername.{$hwdata['extension']}";
					} else {
						$file = $hwdata['basename'];
					}
				} else {
					if($rename == 1) {
						$file = "$studentusername";
					} else {
						$file = $hwdata['basename'];
					}
				}
				
				$dst_dir =& dbfuncGetDir($assignmentindex, $row['UploadName'], $trow['Username']);
				$link     = "index.php?location=" .  dbfuncString2Int("student/subjectinfo.php") .
							"&amp;key=" .            dbfuncString2Int($subjectindex) .
							"&amp;keyname=" .        dbfuncString2Int($subject) .
							"&amp;key2=" .           dbfuncString2Int($studentusername) .
							"&amp;key2name=" .       dbfuncString2Int($name);
				if (move_uploaded_file($_FILES['hw']['tmp_name'], "$dst_dir/$file")) {
					while($trow =& $tres->fetchRow(DB_FETCHMODE_ASSOC)) {
						$new_dir =& dbfuncGetDir($assignmentindex, $row['UploadName'], $trow['Username']);
						copy("$dst_dir/$file", "$new_dir/$file");
					}
					if($rename == 1) {
						$part2 = " as &quot;$file&quot;";
					} else {
						$part2 = "";
					}
					echo "      <p align=\"center\">Your file &quot;{$hwdata['basename']}&quot; has been " .
													" uploaded{$part2}.</p>\n";
					echo "      <p align=\"center\"><a href=\"$link\">Click here to continue.</a></p>\n";
					log_event($LOG_LEVEL_TEACHER, "student/doupload.php", $LOG_ASSIGNMENT, 
								"Uploaded {$hwdata['basename']} -> $file for $subject.");
				} else {
					echo "      <p align=\"center\"><b>There was an error uploading your file.</b>  It may have been too large.  Please try again with a smaller file. </p>\n";
					echo "      <p align=\"center\">Error code: {$_FILES['hw']['error']}</p>\n";
					echo "      <p align=\"center\"><a href=\"$link\">Click here to continue.</a></p>\n";
					log_event($LOG_LEVEL_TEACHER, "student/doupload.php", $LOG_ERROR,
								"Ran into an error while uploading assignment for $subject.");
				}
			}
		} else {
			echo "      <p>You are not allowed to upload your homework for this assignment.</p>\n";
		}
	} else {
		$title = "Error";

		include "header.php";

		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "student/doupload.php", $LOG_DENIED_ACCESS, 
					"Tried to upload homework for $name for $subject.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
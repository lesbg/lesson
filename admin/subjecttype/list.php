<?php
	/*****************************************************************
	 * admin/subjecttype/list.php  (c) 2004, 2005 Jonathan Dieter
	 *
	 * List all subject types
	 *****************************************************************/

	$title = "Subject Types List";      
	
	include "header.php";                                        // Show header

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {         // Make sure user has permission to view and
		/* Get subject type list */
		$res =&  $db->query("SELECT Title, Description, SubjectTypeIndex FROM subjecttype " .
							"ORDER BY Title");
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
		/* Print subjects and the teachers that teach them */
		$newlink =  "index.php?location=" .  dbfuncString2Int("admin/subjecttype/new.php");  // link to create a new subject 
		$newbutton = dbfuncGetButton($newlink, "New subject type", "medium", "", "Create new subject type");
		echo "      <p align=\"center\">$newbutton</p>\n";
		
		if($res->numRows() > 0) {			
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Title</th>\n";
			echo "            <th>Description</th>\n";
			echo "         </tr>\n";
			
			/* For each subject, print a row with the subject's name, # of students, and teacher name(s) */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class=\"alt\"";
				} else {
					$alt = " class=\"std\"";
				}
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/subjecttype/modify.php") .
							"&amp;key=" .            dbfuncString2Int($row['SubjectTypeIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Title']);      // Get link to subject
				
				echo "         <tr$alt>\n";
				
				/* Generate view and edit buttons */
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Edit subject type");
				echo "            <td>$editbutton</td>\n"; 
				echo "            <td>{$row['Title']}</td>\n";                // Print subject name
				echo "            <td>{$row['Description']}</td>\n";                    // Print number of students				
				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no subject types.</p>\n";
		}
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
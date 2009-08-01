<?php
	/*****************************************************************
	 * admin/proofreader/list.php  (c) 2008 Jonathan Dieter
	 *
	 * List all proofreaders
	 *****************************************************************/

	$title = "Proofreaders List";
	
	include "header.php";                                        // Show header

	if($is_admin or $is_principal) {
		/* Get proofreaders list */
		$query =	"SELECT user.Username, user.FirstName, user.Surname, department.Department, " .
					"       department.DepartmentIndex " .
					"       FROM department LEFT OUTER JOIN user ON " .
					"            user.Username = department.ProofreaderUsername " .
					"ORDER BY department.DepartmentIndex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			
		/* Print subjects and the teachers that teach them */
		if($res->numRows() > 0) {
			echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Department</th>\n";
			echo "            <th>Proofreader</th>\n";
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
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/proofreader/modify.php") .
							"&amp;key=" .            dbfuncString2Int($row['DepartmentIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['Department']) .
							"&amp;key2=" .            dbfuncString2Int($row['Username']);
				
				echo "         <tr$alt>\n";
				
				/* Generate view and edit buttons */
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Change proofreader for department");
				echo "            <td>$editbutton</td>\n"; 
				echo "            <td>{$row['Department']}</td>\n";                // Print subject name
				if(is_null($row['Username'])) {
					echo "            <td><i>None</i></td>\n";                // Print subject name
				} else {
					echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				}
				echo "         </tr>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>There are no departments.</p>\n";
		}
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
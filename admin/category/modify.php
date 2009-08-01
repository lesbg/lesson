<?php
	/*****************************************************************
	 * admin/category/modify.php  (c) 2005 Jonathan Dieter
	 *
	 * Change information about subject type
	 *****************************************************************/

	/* Get variables */
	$title            = "Change information for " . dbfuncInt2String($_GET['keyname']);
	$categoryindex    = dbfuncInt2String($_GET['key']);
	$link             = "index.php?location=" . dbfuncString2Int("admin/category/new_or_modify_action.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		/* Get subject information */
		$fRes =& $db->query("SELECT CategoryName FROM category " .
							"WHERE CategoryIndex = $categoryindex");
		if(DB::isError($fRes)) die($fRes->getDebugInfo());             // Check for errors in query
		if($fRow =& $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(isset($errorlist)) {
				echo $errorlist;
			}
			if(!isset($_POST['name'])) {
				$_POST['name'] = htmlspecialchars($fRow['CategoryName']);
			} else {
				$_POST['name'] = htmlspecialchars($_POST['name']);
			}

			if(!isset($values)) {
				$values = array();
				$query =	"SELECT SubjectTypeIndex FROM categorytype " .
							"WHERE CategoryIndex = $categoryindex";
				$res =&  $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());
	
				while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$subjecttypeindex = intval($row['SubjectTypeIndex']);
					$values[$subjecttypeindex] = $subjecttypeindex;
				}
			}
			echo "      <form action=\"$link\" name=\"modSubj\" method=\"post\">\n";         // Form method
			echo "         <input type='hidden' name='type' value='modify'>\n";
			echo "         <table class=\"transparent\" align=\"center\">\n";   // Table headers
			
			/* Show subject type name */
			echo "            <tr>\n";
			echo "               <td><b>Name of category type</b></td>\n";
			echo "               <td><input type=\"text\" name=\"name\" value=\"{$_POST['name']}\" size=20></td>\n";
			echo "            </tr>\n";
			echo "            <tr><td colspan='2'>&nbsp;</td></tr>\n";
			echo "            <tr><td colspan='2'><b>Allowed subject types</b>&nbsp;&nbsp; <i>If none are selected, all will be allowed</i></td></tr>\n";
			echo "            <tr>\n";
	
			/* Get list of subjects for student and store in option list */
			echo "               <td>\n";
			echo "                  <select name='removesubjecttype[]' style='width: 200px;' multiple size=15>\n";
			if(isset($values)) {
				foreach($values as $subject_type) {
					$subject_type = intval($subject_type);
					$query =	"SELECT Title FROM subjecttype " .
								"WHERE SubjectTypeIndex = $subject_type";
					$res =&  $db->query($query);
					if(DB::isError($res)) die($res->getDebugInfo());
		
					if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "                     <option value='{$subject_type}'>{$row['Title']}\n";
						$hidden_val .= "{$subject_type},";
					}
				}
			}		echo "                  </select>\n";
			echo "               </td>\n";
			echo "               <td>\n";
			$query =	"SELECT subjecttype.Title, subjecttype.SubjectTypeIndex FROM subjecttype " .
						"ORDER BY subjecttype.Title, subjecttype.SubjectTypeIndex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
			echo "                  <select name='addsubjecttype[]' style='width: 200px;' multiple size=15>\n";
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(!in_array($row['SubjectTypeIndex'], $values)) {
					echo "                     <option value='{$row['SubjectTypeIndex']}'>{$row['Title']}\n";
				}
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "            <tr>\n";
			echo "               <td align='center'><input type='submit' name='action' value='>' /></td>\n";
			echo "               <td align='center'><input type='submit' name='action' value='<' /></td>\n";
			echo "            </tr>\n";
			echo "         </table>\n";                                                      // End of table
			echo "         <p align=\"center\">\n";
			echo "            <input type=\"submit\" name=\"action\" value=\"Update\" \>\n";
			echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\" \>\n";
			echo "            <input type='hidden' name='value'  value='$hidden_val' />\n";
			echo "         </p>\n";
			echo "      </form>\n";
		} else {  // Couldn't find $subjecttypeindex in subjecttype table
			echo "      <p align=\"center\">Can't find subject type.  Have you deleted it?</p>\n";
			echo "      <p align=\"center\"><a href=\"$backLink\">Click here to go back</a></p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/category/modify.php", $LOG_ADMIN,
				"Opened category type $title for editing.");
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/category/modify.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about the category type $title.");
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
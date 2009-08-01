<?php
	/*****************************************************************
	 * admin/category/new.php  (c) 2005 Jonathan Dieter
	 *
	 * Create new category type
	 *****************************************************************/

	/* Get variables */
	$title            = "New Category Type";
	$link             = "index.php?location=" . dbfuncString2Int("admin/category/new_or_modify_action.php") .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		$hidden_val = "";

		if(isset($errorlist)) {
			echo $errorlist;
		}
		if(!isset($_POST['name'])) {
			$_POST['name'] = "";
		} else {
			$_POST['name'] = htmlspecialchars($_POST['name']);
		}

		echo "      <form action='$link' method='post'>\n";                          // Form method
		echo "         <input type='hidden' name='type' value='new'>\n";
		echo "         <table class='transparent' align='center'>\n";   // Table headers
		
		/* Show category type name */
		echo "            <tr>\n";
		echo "               <td><b>Name of category type</b></td>\n";
		echo "               <td><input type='text' name='name' value='{$_POST['name']}' size=20></td>\n";
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
		echo "         </table>\n";               // End of table
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Save' />\n";
		echo "            <input type='submit' name='action' value='Cancel' />\n";
		echo "            <input type='hidden' name='value'  value='$hidden_val' />\n";
		echo "         </p>\n";
		echo "      </form>\n";
	} else {  // User isn't authorized to view or change scores.
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
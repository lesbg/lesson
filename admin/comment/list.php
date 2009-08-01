<?php
	/*****************************************************************
	 * admin/comment/list.php  (c) 2008 Jonathan Dieter
	 *
	 * List all available comments
	 *****************************************************************/

	$title = "Comment List";
	
	include "header.php";

	if($is_admin) {
		/* Get comment list */
		$query =	"SELECT comment.CommentIndex, comment.Comment, comment.Strength " .
					"       FROM comment " .
					"ORDER BY comment.CommentIndex";
		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		$newlink =  "index.php?location=" .  dbfuncString2Int("admin/comment/new.php") .
					"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/comment/list.php"));
		$newbutton = dbfuncGetButton($newlink, "New comment", "medium", "", "Create new comment");
		echo "      <p align=\"center\">$newbutton</p>\n";

		if($res->numRows() > 0) {
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>#</th>\n";
			echo "            <th>Comment</th>\n";
			echo "            <th>Value</th>\n";
			echo "            <th>Subject Type</th>\n";
			echo "            <th>Delete</th>\n";
			echo "         </tr>\n";
			
			/* For each comment, print a row with the comment's name, # of subjects using it
			   and the subject types it's enabled for */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				$dellink =  "index.php?location=" .  dbfuncString2Int("admin/comment/delete_confirm.php") .
							"&amp;key=" .            dbfuncString2Int($row['CommentIndex']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/comment/list.php"));
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/comment/modify.php") .
							"&amp;key=" .            dbfuncString2Int($row['CommentIndex']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/comment/list.php"));

				echo "         <tr$alt>\n";

				/* Generate edit and delete buttons */
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Edit comment");
				$delbutton  = dbfuncGetButton($dellink,  "X", "small", "delete", "Delete comment");
				echo "            <td>$editbutton</td>\n";
				echo "            <td>{$row['CommentIndex']}</td>\n";
				echo "            <td>{$row['Comment']}</td>\n";
				if($row['Strength'] == 1) {
					echo "            <td>-</td>\n";
 				} elseif($row['Strength'] == 2) {
					echo "            <td>=</td>\n";
 				} elseif($row['Strength'] == 3) {
					echo "            <td>+</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
				$query =	"SELECT subjecttype.Title " .
							"       FROM view_comment LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
							"WHERE view_comment.CommentIndex = {$row['CommentIndex']} " .
							"ORDER BY subjecttype.Title, subjecttype.SubjectTypeIndex ";
				$aRes =& $db->query($query);
				if(DB::isError($aRes)) die($aRes->getDebugInfo());

				if($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
					if(is_null($aRow['Title'])) $aRow['Title'] = "<i>All subjects</i>";
					echo "            <td>{$aRow['Title']}";
					while($aRow =& $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo ", {$aRow['Title']}";
					}
					echo "</td>\n";
				}

				echo "            <td align='center'>$delbutton</td>\n";
			}
			echo "      </table>\n";               // End of table
			echo "      <p align='center'><b>Values</b><br>\n";
			echo "                        + Positive<br>\n";
			echo "                        = Neutral<br>\n";
			echo "                        - Negative</p>\n";
		} else {
			echo "      <p>No comments have been set up.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/comment/list.php", $LOG_ADMIN,
				"Viewed comments.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/comment/list.php", $LOG_DENIED_ACCESS,
				"Attempted to view comments.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
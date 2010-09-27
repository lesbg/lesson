<?php
	/*****************************************************************
	 * admin/book/modify_title.php  (c) 2010 Jonathan Dieter
	 *
	 * Change information about book title
	 *****************************************************************/

	/* Get variables */
	$title            = "Change title information for " . dbfuncInt2String($_GET['keyname']);
	$booktitleindex   = dbfuncInt2String($_GET['key']);
	$link             = "index.php?location=" . dbfuncString2Int("admin/book/new_or_modify_title_action.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;next=" .          $_GET['next'];
	
	include "header.php";                                              // Show header
	
	/* Check whether user is authorized to change subject */	
	if($is_admin) {
		/* Get subject information */
		$fRes =& $db->query("SELECT BookTitle, BookTitleIndex, Cost FROM book_title " .
							"WHERE BookTitleIndex = '$booktitleindex'");
		if(DB::isError($fRes)) die($fRes->getDebugInfo());             // Check for errors in query
		if($fRow =& $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(isset($errorlist)) {
				echo $errorlist;
			}

			if(!isset($_POST['title'])) {
				$_POST['title'] = htmlspecialchars($fRow['BookTitle']);
			} else {
				$_POST['title'] = htmlspecialchars($_POST['title']);
			}
			if(!isset($_POST['id'])) {
				$_POST['id'] = htmlspecialchars($fRow['BookTitleIndex']);
			} else {
				$_POST['id'] = htmlspecialchars($_POST['id']);
			}
			if(!isset($_POST['cost'])) {
				$_POST['cost'] = htmlspecialchars($fRow['Cost']);
			} else {
				$_POST['cost'] = floatval($_POST['id']);
			}
			
			echo "      <form action='$link' method='post'>\n";         // Form method
			echo "         <input type='hidden' name='type' value='modify'>\n";
			echo "         <table class='transparent' align='center'>\n";   // Table headers
			
			/* Show subject type name */
			echo "            <tr>\n";
			echo "               <td><b>Book Title</b></td>\n";
			echo "               <td><input type='text' name='title' value='{$_POST['title']}' size=20></td>\n";
			echo "            </tr>\n";
			echo "            <tr>\n";
			echo "               <td><b>Book ID</b></td>\n";
			echo "               <td><input type='text' name='id' value='{$_POST['id']}' size=20></td>\n";
			echo "            </tr>\n";
			echo "            <tr>\n";
			echo "               <td><b>Cost (\$)</b></td>\n";
			echo "               <td><input type='text' name='cost' value='{$_POST['cost']}' size=20></td>\n";
			echo "            </tr>\n";
			echo "            <tr>\n";
			echo "               <td><b>Owner</b></td>\n";
			echo "               <td>\n";
			$query =	"SELECT year.Year FROM year WHERE YearIndex = $yearindex";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$yearname = $row['Year'];
			} else {
				$yearname = "Unknown";
			}
			$query =	"(SELECT user.Username, user.Title, user.FirstName, user.Surname, " .
						"        year.Year, book_title_owner.YearIndex, year.YearNumber FROM " .
						"        book_title_owner, year, user " .
						" WHERE  user.Username = book_title_owner.Username " .
						" AND    year.YearIndex = book_title_owner.YearIndex " .
						" AND    book_title_owner.BookTitleIndex = '$booktitleindex') " .
						"UNION " .
						"(SELECT NULL AS Username, NULL AS Title, NULL AS FirstName, NULL AS Surname, " .
						"        year.Year, year.YearIndex, year.YearNumber FROM " .
						"        year LEFT OUTER JOIN book_title_owner ON " .
						"        (book_title_owner.BookTitleIndex = '$booktitleindex' " .
						"         AND book_title_owner.Yearindex = year.YearIndex) " .
						" WHERE year.YearIndex = $yearindex" .
						" AND   book_title_owner.Username IS NULL) " .
						" ORDER BY YearNumber DESC";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				echo "{$row['Year']}:\t";
				if($row['YearIndex'] == $yearindex) {
						echo "";
				} else {
					if(is_null($row['Username'])) {
						echo "<i>None</i><br>";
					} else {
						echo "{$row['Username']} - {$row['Title']} {$row['FirstName']} {$row['Surname']}<br>";
					}
				}
				
			}			
			echo "               </td>\n";
			echo "         </table>\n";                                                      // End of table
			echo "         <p align='center'>\n";
			echo "            <input type='submit' name='action' value='Update' \>\n";
			echo "            <input type='submit' name='action' value='Cancel' \>\n";
			echo "         </p>\n";
			echo "      </form>\n";
		} else {  // Couldn't find $booktitleindex in book_title table
			echo "      <p align='center'>Can't find book title.  Have you deleted it?</p>\n";
			echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/book/modify_title.php", $LOG_ADMIN,
				"Opened book title $title for editing.");
	} else {
		log_event($LOG_LEVEL_ERROR, "admin/book/modify_title.php", $LOG_DENIED_ACCESS,
				"Attempted to change information about the book title $title.");
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
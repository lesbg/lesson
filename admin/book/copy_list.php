<?php
	/*****************************************************************
	 * admin/book/copy_list.php  (c) 2010 Jonathan Dieter
	 *
	 * List copies of a book
	 *****************************************************************/

	$booktitle       = dbfuncInt2String($_GET['keyname']);
	$title           = "Copies of $booktitle";
	$booktitleindex  = dbfuncInt2String($_GET['key']);
	if(!isset($_GET['key2'])) {
		$_GET['key2'] = dbfuncString2Int("0");
	}
	$showall     = intval(dbfuncInt2String($_GET['key2']));
	

	include "header.php";

	if($is_admin) {
		/* Get category list */

		$newlink =  "index.php?location=" .  dbfuncString2Int("admin/book/new_copy.php") .
					"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/book/copy_list.php") .
															  "&amp;key=" . $_GET['key'] .
															  "&amp;keyname=" . $_GET['keyname'] .
															  "&amp;key2=" . $_GET['key2']);
		$newbutton = dbfuncGetButton($newlink, "New copy", "medium", "", "Add new copy of this book");
		if($showall) {
			$salink =	"index.php?location=" . dbfuncString2Int("admin/book/copy_list.php") .
						"&amp;key=" . $_GET['key'] .
						"&amp;keyname=" . $_GET['keyname'] .
						"&amp;key2=" . dbfuncString2Int("0");
			$sabutton = dbfuncGetButton($salink, "Show active copies", "medium", "", "Show only active copies and not retired copies");
		} else {
			$salink =	"index.php?location=" . dbfuncString2Int("admin/book/copy_list.php") .
						"&amp;key=" . $_GET['key'] .
						"&amp;keyname=" . $_GET['keyname'] .
						"&amp;key2=" . dbfuncString2Int("1");
			$sabutton = dbfuncGetButton($salink, "Show all copies", "medium", "", "Show all copies, including retired ones");
		}
		echo "      <p align='center'>$newbutton $sabutton</p>\n";

		$query =	"SELECT book.BookIndex, book.BookNumber, book.Retired, " .
					"       in_book_state.BookState AS InBookState, book_status.InDate, " .
					"       out_book_state.BookState AS OutBookState, book_status.Outdate, " .
					"       user.FirstName, user.Surname, user.Username " .
					"       FROM book LEFT OUTER JOIN (book_status INNER JOIN user USING (Username) " .
					"       INNER JOIN book_state AS out_book_state ON (book_status.OutState = out_book_state.BookStateIndex) " .
					"       LEFT OUTER JOIN book_state AS in_book_state ON (book_status.InState = in_book_state.BookStateIndex)) " .
					"       USING (BookIndex) ";
		if(!$showall) {
			$query .= "WHERE book.Retired=0 ";
		}
		$query .=	"ORDER BY book.BookNumber, book.BookIndex, book_status.OutDate DESC " .
					"LIMIT 1";

		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		if($res->numRows() > 0) {
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Number</th>\n";
			echo "            <th>Current State</th>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>Checkout Date</th>\n";
			echo "            <th>Retired</th>\n";
			echo "            <th>Delete/Retire</th>\n";
			echo "         </tr>\n";
			
			/* For each category, print a row with the category's name, # of subjects using it
			   and the subject types it's enabled for */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				$dellink  = "index.php?location=" .  dbfuncString2Int("admin/book/delete_copy_confirm.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/book/modify_copy.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);

				echo "         <tr$alt>\n";
				/* Generate view and edit buttons */
				$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View history of this copy");
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", "Edit copy information");
				$retbutton  = dbfuncGetButton($retlink,  "R", "small", "delete", "Retire copy");
				$unrbutton  = dbfuncGetButton($unrlink,  "U", "small", "msg", "Unretire copy");
				$delbutton  = dbfuncGetButton($dellink,  "X", "small", "delete", "Delete copy");
				echo "            <td>$viewbutton $editbutton</td>\n"; 
				echo "            <td>{$row['BookNumber']}</td>\n";
				if(is_null($row['InBookState'])) {
					echo "            <td>{$row['OutBookState']}</td>\n";
					echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
					echo "            <td>Not implemented</td>\n";
				} else {
					echo "            <td>{$row['InBookState']}</td>\n";
					echo "            <td colspan='2' align='center'>Not checked out</td>\n";
				}
				if($row['Retired']) {
					echo "            <td align='center'>X</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
				echo "            <td align='center'>$delbutton</td>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>No titles have been set up.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "admin/book/title_list.php", $LOG_ADMIN,
				"Viewed book titles.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/book/title_list.php", $LOG_DENIED_ACCESS,
				"Attempted to view book titles.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
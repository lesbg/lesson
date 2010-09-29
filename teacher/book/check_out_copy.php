<?php
	/*****************************************************************
	 * teacher/book/check_out_copy.php  (c) 2010 Jonathan Dieter
	 *
	 * Check out a copy of a book to a user
	 *****************************************************************/

	$title           = "Check out " . dbfuncInt2String($_GET['keyname']);
	$bookindex       = dbfuncInt2String($_GET['key']);

	include "header.php";

	$query =	"SELECT book_title_owner.Username FROM book_title_owner, book " .
				"WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
				"AND   book.BookIndex = $bookindex " .
				"AND   book_title_owner.Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if($is_admin or $res->numRows() > 0) {
		$link  =	"index.php?location=" . dbfuncString2Int("teacher/book/check_out_copy_action.php") .
					"&amp;key=" .			$_GET['key'] .
					"&amp;keyname=" .		$_GET['keyname'] .
					"&amp;next=" .          $_GET['next'];
				 
		$query =	"SELECT InState, OutState, Comment " .
					"       FROM book_status " .
					"WHERE book_status.BookIndex = $bookindex " .
					"ORDER BY book_status.InState IS NOT NULL, book_status.OutDate DESC, " .
					"         book_status.BookStatusIndex DESC";

		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		$ok = true;
		$oldstate = 10;
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if(is_null($row['InState'])) {
				$ok = false;
			} else {
				$oldstate = $row['InState'];
			}
		}
		
		if($ok) {
			if(isset($errorlist)) {
				echo $errorlist;
			}
			
			if(!isset($_POST['state'])) {
				$_POST['state'] = $oldstate;
			} else {
				$_POST['state'] = intval($_POST['state']);
			}
			
			if(!isset($_POST['class'])) {
				$query =	"(SELECT class.ClassIndex, book_status.* " .
							"        FROM book_status, class, classterm, classlist, currentterm " .
							" WHERE book_status.BookIndex=$bookindex " .
							" AND   book_status.Username=classlist.Username " .
							" AND   classlist.ClassTermIndex=classterm.ClassTermIndex " .
							" AND   classterm.ClassIndex=class.ClassIndex " .
							" AND   class.YearIndex=$yearindex " .
							" AND   classterm.TermIndex=currentterm.TermIndex) " .
							"UNION " .
							"(SELECT 'teacher' AS ClassIndex, book_status.* FROM book_status, user " .
							" WHERE book_status.Username=user.Username " .
							" AND   user.ActiveTeacher=1 " .
							") " .
							"ORDER BY OutDate DESC, BookStatusIndex DESC " .
							"LIMIT 1";
				$res =& $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());

				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$_POST['class'] = $row['ClassIndex'];
				} else {							
					$_POST['class'] = "teacher";
				}
			} else {
				if($_POST['class'] != "teacher") {
					$_POST['class'] = intval($_POST['class']);
				}
			}
			
			if(!isset($_POST['comment'])) {
				$query =	"SELECT Comment FROM book_status " .
							"WHERE BookIndex = $bookindex " .
							"ORDER BY OutDate DESC, BookStatusIndex DESC " .
							"LIMIT 1";
				$res =& $db->query($query);
				if(DB::isError($res)) die($res->getDebugInfo());
				
				if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$_POST['comment'] = htmlspecialchars($row['Comment'], ENT_QUOTES);
				} else {
					$_POST['comment'] = "";
				}
			} else {
				$_POST['comment'] = htmlspecialchars($_POST['comment'], ENT_QUOTES);
			}
			
			echo "      <form action='$link' method='post' name='checkout'>\n"; // Form method
			echo "         <table class='transparent' align='center'>\n";   // Table headers
			
			/* Show book type name */
			echo "         <tr>\n";
			echo "            <td><b>Outgoing state</b></td>\n";
			echo "            <td>\n";
			echo "               <select name='state'>\n";
			$query =	"SELECT BookStateIndex, BookState " .
						"FROM book_state " .
						"ORDER BY BookStateIndex DESC";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
			
			while($nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($nrow['BookStateIndex'] == $_POST['state']) {
					$default = "selected";
				} else {
					$default = "";
				}

				echo "                  <option value='{$nrow['BookStateIndex']}' $default>{$nrow['BookState']}</option>\n";
			}
			echo "               </select>\n";
			echo "            </td>\n";
			echo "         </tr>\n";
			echo "         <tr>\n";
			echo "            <td><b>Comment</b></td>\n";
			echo "            <td><textarea rows='3' cols='40' name='comment'>{$_POST['comment']}</textarea></td>\n";
			echo "         </tr>\n";
			
			
			/* Create listboxes with classes */
			echo "         <tr>\n";
			echo "            <td><b>Class</b></td>";
			echo "            <td><select name='class' onchange='checkout.submit()'>\n";
			echo "                        <option value='teacher'>Teachers\n";
			$res =&  $db->query("SELECT ClassIndex, ClassName FROM class " .
								"WHERE YearIndex = $yearindex " .
								"ORDER BY Grade, ClassName");
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if($row['ClassIndex'] == $_POST['class']) {
					$default = "selected";
				} else {
					$default = "";
				}

				echo "                     <option value='{$row['ClassIndex']}' $default>{$row['ClassName']}\n";
			}
			echo "                  </select>\n";
			echo "                  <noscript>\n"; // No javascript compatibility
			echo "                     <input type='submit' name='action' value='Update' \>\n";
			echo "                  </noscript><br>\n";
			echo "             </td>\n";
			echo "          </tr>\n";
			
			/* Get list of students who are in the active class */
			echo "          <tr>\n";
			echo "             <td colspan='2'>";
			echo "                  <select name='student' style='width: 398px;' size=14>\n";
			if ($_POST['class'] != "") {
				if($_POST['class'] == "teacher") {
					$query =	"SELECT user.Title, user.FirstName, user.Surname, user.Username " .
								"       FROM user " .
								"WHERE ActiveTeacher=1 " .
								"ORDER BY user.Username";
					$res =&  $db->query($query);
					if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
					
					while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['Title']} {$row['FirstName']} " .
																"{$row['Surname']}\n";
					}

				} else {
					$_POST['class'] = intval($_POST['class']);
					$query =        "SELECT user.FirstName, user.Surname, user.Username FROM " .
									"       user, classterm, classlist, currentterm " .
									"WHERE  user.Username = classlist.Username " .
									"AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
									"AND    classterm.TermIndex = currentterm.TermIndex " .
									"AND    classterm.ClassIndex = {$_POST['class']} " .
									"ORDER BY user.Username";
					$res =&  $db->query($query);
					if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
					
					while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
						echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['FirstName']} " .
																"{$row['Surname']}\n";
					}
				}
			}
			echo "                  </select>\n";
			echo "               </td>\n";
			echo "            </tr>\n";
			echo "         </table>\n";
			echo "         <p align='center'>\n";
			echo "            <input type='submit' name='action' value='Check out'>&nbsp; \n";
			echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
			echo "         </p>\n";
			echo "      </form>\n";
				
		} else {
			echo "      <p>This copy is already checked out</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/book/check_out_copy.php", $LOG_ADMIN,
				"Checked out copy of $title.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/check_out_copy.php", $LOG_DENIED_ACCESS,
				"Attempted to check out copy of $title.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
<?php
	// FIX CLASS STUFF
	
	/*****************************************************************
	 * core/dbfunc.php  (c) 2004-2007 Jonathan Dieter
	 *
	 * Functions for connecting to the database, plus miscellaneous
	 * functions for getting permissions and munging of location
	 * strings to ints
	 *****************************************************************/
	 
	/* Escape strings so they are safe to enter into a database */
	function safe($instring) {
		global $db;
		return $db->escapeSimple($instring);
	}
	
	/* Connect to database specified by global variable $dsn */
	function &dbfuncConnect() {
		/* Set global parameters */
		global $DSN;  // DSN to connect to database, stored in globals.php
		/* Connection to database */
		$db =& DB::connect($DSN);                     // Initiate connection
		if(DB::isError($db)) die($db->getDebugInfo());  // Check for errors in connection
		return $db;
	}
	
	/* Get code to generate a button-looking hyperlink to $link with ID of $type and tooltip of $tooltip around $text */
	function &dbfuncGetButton($link="", $text, $size="medium", $type="", $tooltip="", $buttonclass="button") {
		$button = "<span class=\"$size\"><a class=\"$buttonclass\" ";
		if($tooltip != "") {
			$button .= "title=\"$tooltip\" ";
		}
		if($link != "") {
			$button .= "href=\"$link\">";
		}
		if($type != "") {
			$button .= "<span class=\"$type\">";
		}
		$button .= "$text";
		if($type != "") {
			$button .= "</span>";
		}
		$button .= "</a></span>";
		 
		return $button;
	}
	
	/* Get code to generate a button-looking hyperlink to $link with ID of $type and tooltip of $tooltip around $text */
	function &dbfuncGetDisabledButton($text, $size="medium", $type="", $buttonclass="disabled-button") {
		$button = "<span class=\"$size\"><span class=\"$buttonclass\">";
		if($type != "") {
			$button .= "<span class=\"$type\">";
		}
		$button .= "$text";
		if($type != "") {
			$button .= "</span>";
		}
		$button .= "</span></span>";
		 
		return $button;
	}
	/* Return full username */
	function &dbfuncGetFullName() {
		/* Set global parameters */
		global $db;
		global $username;
		
		/* Run query to extract FirstName and Surname from "user" table */
		$res =& $db->query("SELECT FirstName, Surname FROM user WHERE Username = \"$username\"");
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {             // Get result of query
			$fullname = $row['FirstName'] . " " . $row['Surname'];  // Store result of query in $fullname,
			$fullname = htmlspecialchars($fullname);                     //   and return
			return $fullname;
		} else {
			return "";
		}
	}
	
	/* Get year index */
	function dbfuncGetYearIndex() {
		/* Set global parameters */
		global $db;
		
		/* Run query to extract YearIndex from "currentinfo" table */
		$res =& $db->query("SELECT YearIndex FROM currentinfo ORDER BY InputDate DESC");
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
		return $row['YearIndex'];                               //   and return
	}

	/* Check whether we are to print totals */
	function dbfuncGetPrintTotal() {
		/* Set global parameters */
		global $db;
		
		/* Run query to extract YearIndex from "currentinfo" table */
		$res =& $db->query("SELECT PrintTotal FROM currentinfo ORDER BY InputDate DESC");
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
		if($row['PrintTotal'] == 1)
			return true;
		else
			return false;
	}
	
	/* Get date format */
	function dbfuncGetDateFormat() {
		/* Set global parameters */
		global $db, $username;
		
		/* Run query to extract date format from "user" table */
		$userRes =& $db->query("SELECT DateType, DateSeparator FROM user WHERE Username=\"$username\"");
		if(DB::isError($userRes)) die($userRes->getDebugInfo());          // Check for errors in query
		$userRow =& $userRes->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
				
		/* Run query to extract date format from "currentinfo" table */
		$globRes =& $db->query("SELECT DateType, DateSeparator FROM currentinfo ORDER BY InputDate DESC");
		if(DB::isError($globRes)) die($globRes->getDebugInfo());          // Check for errors in query
		$globRow =& $globRes->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
		
		/* Get date format first from user table, then from currentinfo */
		if(is_null($userRow['DateType'])) {
			$dType = $globRow['DateType'];
		} else {
			$dType = $userRow['DateType'];
		}
		if(is_null($userRow['DateSeparator'])) {
			$dSeparator = $globRow['DateSeparator'];
		} else {
			$dSeparator = $userRow['DateSeparator'];
		}
		
		if($dType == 0) {
			return "m{$dSeparator}d{$dSeparator}Y";
		} else {
			return "d{$dSeparator}m{$dSeparator}Y";
		}
	}
	
	/* Create date in format yyyy-mm-dd from $dateformat */
	function dbfuncCreateDate($inputDate="") {
		global $dateformat;  // Globals
		
		if($inputDate=="") {
			return date("Y-m-d");
		} else {
			$dSeparator      = substr($dateformat, 1, 1);
			$firstSeparator  = strpos($inputDate, $dSeparator);
			$secondSeparator = strpos($inputDate, $dSeparator, $firstSeparator + 1);
			if($secondSeparator == "") {
				$year = date("Y");
				if(substr($dateformat, 0, 1) == 'd') {
					$month = substr($inputDate, $firstSeparator + 1);
					$day   = substr($inputDate, 0, $firstSeparator);
				} else {
					$month = substr($inputDate, 0, $firstSeparator);
					$day   = substr($inputDate, $firstSeparator + 1);
				}
			} else {
				$year  = substr($inputDate, $secondSeparator + 1);
				if(substr($dateformat, 0, 1) == 'd') {
					$month = substr($inputDate, $firstSeparator + 1, $secondSeparator - $firstSeparator - 1);
					$day   = substr($inputDate, 0, $firstSeparator);
				} else {
					$month = substr($inputDate, 0, $firstSeparator);
					$day   = substr($inputDate, $firstSeparator + 1, $secondSeparator - $firstSeparator - 1);
				}
			}
			return date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
		}
	}
	
	/* Get term index */
	function dbfuncGetTermIndex($depindex) {
		/* Set global parameters */
		global $db;
		
		$depindex = safe($depindex);

		if(!is_null($depindex) and $depindex != "") {
			/* Run query to extract YearIndex from "currentinfo" table */
			$res =& $db->query("SELECT TermIndex FROM currentterm WHERE DepartmentIndex=$depindex");
			if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
			
			$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
			return $row['TermIndex'];                               //   and return
		} else {
			return NULL;
		}
	}	

	function dbfuncGetDepIndex() {
		/* Set global parameters */
		global $db;
		global $username;
		global $yearindex;
		
		/* Run query to extract YearIndex from "currentinfo" table */
		$res =& $db->query("SELECT DepartmentIndex FROM user WHERE Username=\"$username\"");
		if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
		
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
		$depindex = $row['DepartmentIndex'];                               //   and return
		if(is_null($depindex)) {
			$res =&  $db->query("SELECT class.DepartmentIndex FROM class, classterm, classlist " .
								"WHERE classlist.Username='$username' " .
								"AND   classlist.ClassTermIndex=classterm.ClassTermIndex " .
								"AND   class.ClassIndex = classterm.ClassIndex " .
								"AND   class.YearIndex = $yearindex " .
								"ORDER BY classterm.TermIndex DESC " .
								"LIMIT 1");
			if(DB::isError($res)) die($res->getDebugInfo());          // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {             // Get result of query
				return $row['DepartmentIndex'];                               //   and return
			} else {
				return NULL;
			}
		} else {
			return $depindex;
		}
	}	
	
	function dbfuncIsActiveStudent() {
		/* Set global parameters */
		global $db;
		global $username;
		
		/* Run query to extract information from "user" table */
		$res =& $db->query("SELECT ActiveStudent FROM user WHERE Username = \"$username\"");
		if(DB::isError($res)) die($res->getDebugInfo());
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($row['ActiveStudent'] == 1) {
				return true;
			}
		}
		return false;
	}

	function dbfuncIsActiveTeacher() {
		/* Set global parameters */
		global $db;
		global $username;
		
		/* Run query to extract information from "user" table */
		$res =& $db->query("SELECT ActiveTeacher FROM user WHERE Username = \"$username\"");
		if(DB::isError($res)) die($res->getDebugInfo());
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($row['ActiveTeacher'] == 1) {
				return true;
			}
		}
		return false;
	}
	
	/* Return integer representing permissions */
	function dbfuncGetPermissions() {
		/* Set global parameters */
		global $db;
		global $username;
		
		/* Run query to extract permissions from "user" table */
		$res =& $db->query("SELECT Permissions FROM user WHERE Username = \"$username\"");
		if(DB::isError($res)) return 0;          // If there's an error, assume user has no permissions
		
		$row =& $res->fetchRow(DB_FETCHMODE_ASSOC);             // Get result of query
		return $row['Permissions'];                             //   and return
	}
	
	/* Get individual permission from permissions */
	function dbfuncGetPermission($permissions, $number) {
		for($count = 0; $count < $number; $count++) {        // Cycle through and remove all insignificant bits
			$permissions = floor($permissions / 2);          //  by dividing $permissions by two until we reach 
			                                                 //  $number
		}
		if(($permissions / 2) == floor($permissions / 2)) {  // Check least significant byte by determining if
			return false;                                    //  $permissions is even or odd.  If $permissions is
		} else {                                             //  even, the LSB is 0 or false, while if $permissions
			return true;                                     //  is even, the LSB is 1 or true.  Return appropriate
		}                                                    //  boolean.
	}
	
	/* Hash function to convert *any* string to a safe combination of numbers and multi-case letters */
	function &dbfuncString2Int($strValue) {
		$value     = "";
		$num_value = "0";
		for($loc = 0; $loc < strlen($strValue); $loc++) {          // Convert string to extremely large number
			$num_value = bcmul($num_value, "256");
			$num_value = bcadd($num_value, ord($strValue{$loc}));
		}
		while(bccomp($num_value, "0") == 1) {                      // Convert extremely large number to series of
			$val       = intval(bcmod($num_value, "62"));          //  numbers and upper- and lower-case letters.
			$num_value = bcdiv($num_value, "62");
			if($val < 10) {
				$sval = strval($val);
			} elseif($val < 36) {
				$sval = chr($val + 65 - 10);
			} else {
				$sval = chr($val + 97 - 36);
			}
			$value = $sval . $value;
		}
		return $value;
	}

	function dbfuncArray2String($Array) {
		$Return='';
		$NullValue="^^^";
		foreach ($Array as $Key => $Value) {
			if(is_array($Value)) {
				$ReturnValue='^^array^' . dbfuncArray2String($Value);
			} else {
				$ReturnValue=(strlen($Value)>0)?$Value:$NullValue;
			}
			$Return .= urlencode(base64_encode($Key)) . '|' . urlencode(base64_encode($ReturnValue)).'||';
		}
		return urlencode(substr($Return,0,-2));
	}

	function dbfuncString2Array($String) {
		$Return = array();
		$String = urldecode($String);
		$TempArray = explode('||',$String);
		$NullValue = urlencode(base64_encode("^^^"));
		foreach ($TempArray as $TempValue) {
			list($Key,$Value) = explode('|',$TempValue);
			$DecodedKey = base64_decode(urldecode($Key));
			if($Value != $NullValue) {
				$ReturnValue = base64_decode(urldecode($Value));
				if(substr($ReturnValue,0,8) == '^^array^') {
					$ReturnValue = dbfuncString2Array(substr($ReturnValue,8));
				}
				$Return[$DecodedKey] = $ReturnValue;
			} else {
				$Return[$DecodedKey] = NULL;
			}
		}
		return $Return;
	}

	function &dbfuncArray2Int($array) {
		$strValue = "";
		$strValue = dbfuncArray2String($array);
		return dbfuncString2Int($strValue);
	}
	
	function &dbfuncInt2Array($strValue) {
		$strValue = dbfuncInt2String($strValue);
		$array = dbfuncString2Array($strValue);
		return $array;
	}
		
	/* Hash function to a safe combination of numbers and multi-case letters into a string */
	function &dbfuncInt2String($strValue) {
		$value     = "";
		$num_value = "0";
		for($loc = 0; $loc < strlen($strValue); $loc ++) {         // Convert series of numbers and upper- and lower-case
			$val = ord($strValue{$loc});                           //  letters to extremely large number.
			if($val < 58) {
				$num_value = bcadd(bcmul($num_value, "62"), chr($val));
			} elseif($val < 91) {
				$num_value = bcadd(bcmul($num_value, "62"), strval($val - 65 + 10));
			} else {
				$num_value = bcadd(bcmul($num_value, "62"), strval($val - 97 + 36));
			}
		}
		while(bccomp($num_value, "0") == 1) {                      // Convert extremely large number to string
			$value = chr(bcmod($num_value, "256")) . $value;
			$num_value = bcdiv($num_value, "256");
		}
		return $value;
	}
	
	/* Function to setup logging */
	function start_log($page) {
		global $LOG_LEVEL;
		global $LOG_LOGIN;
		global $LOG_LEVEL_ACCESS;
		global $db;
		global $username;
		global $password_number;

		$page = safe($page);
		if(!isset($_SESSION['LogIndex']) && $LOG_LEVEL >= $LOG_LEVEL_ACCESS) {  // Login hasn't been logged yet, so log it
			if(isset($_SERVER['REMOTE_HOST'])) {
				$remote_host = $_SERVER['REMOTE_HOST'];
			} else {
				$remote_host = $_SERVER['REMOTE_ADDR'];
			}
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				if($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" and isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
				}
			}
			
			$today = date("Y-m-d H:i:s");
			$res =&  $db->query("INSERT INTO log (Username, Code, Level, Time, Page, RemoteHost, Comment) " .
								"VALUES (\"$username\", $LOG_LOGIN, $LOG_LEVEL_ACCESS, \"$today\", " .
										"\"$page\", \"$remote_host\", \"Password $password_number\")");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			$res =& $db->query("SELECT LogIndex FROM log WHERE LogIndex IS NULL");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Get new log index
				$_SESSION['LogIndex'] = $row['LogIndex'];
			} else {
				include "header.php";
				echo "     <p>Error appending to log!</p>\n";        // Somehow the login wasn't logged
				include "footer.php";
				exit();
			}
			$res =& $db->query("UPDATE log SET Session={$_SESSION['LogIndex']} WHERE LogIndex={$_SESSION['LogIndex']}");
			if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		}
	}

	/* Log event (must include either comment or code */
	function log_event($log_level, $page, $code=NULL, $comment=NULL, $set_log_index=1) {
		global $LOG_LEVEL;
		global $db;
		global $username;

		$log_level = safe($log_level);
		$page = safe($page);
		$code = safe($code);

		if($LOG_LEVEL >= $log_level) {
			$today = date("Y-m-d H:i:s");
			if($comment == NULL || $comment == '')  {   // If comment is blank, set to NULL
				$comment = "NULL";
			} else {
				$comment = "'" . $db->escapeSimple($comment) . "'"; // If comment is not blank, put quotes around it
			}
			
			if(isset($_SERVER['REMOTE_HOST'])) {
				$remote_host = $_SERVER['REMOTE_HOST'];
			} else {
				$remote_host = $_SERVER['REMOTE_ADDR'];
			}
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				if($_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$remote_host = "{$_SERVER['HTTP_X_FORWARDED_FOR']} through $remote_host";
				}
			}
			
			if($set_log_index == 1) {
				if(isset($_SESSION['LogIndex'])) {
					$res =&  $db->query("INSERT INTO log (Username, Code, Level, Comment, Time, Session, Page, RemoteHost) " .
										"VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
												"{$_SESSION['LogIndex']}, \"$page\", \"$remote_host\")");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				} else {
					$res =&  $db->query("INSERT INTO log (Username, Code, Level, Comment, Time, Page, RemoteHost) " .
										"VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
												"\"$page\", \"$remote_host\")");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
					$res =& $db->query("SELECT LogIndex FROM log WHERE LogIndex IS NULL");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
					if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Get new log index
						$_SESSION['LogIndex'] = $row['LogIndex'];
					} else {
						echo "     <p>Error appending to log!</p>\n";        // Somehow we were unable to add to log
						include "footer.php";
						exit();
					}
					$res =& $db->query("UPDATE log SET Session={$_SESSION['LogIndex']} WHERE LogIndex={$_SESSION['LogIndex']}");
					if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
				}
			} else {
				$res =&  $db->query("INSERT INTO log (Username, Code, Level, Comment, Time, Session, Page, RemoteHost) " .
									"VALUES (\"$username\", $code, $log_level, $comment, \"$today\", " .
											"NULL, \"$page\", \"$remote_host\")");
				if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
			}
		}
	}

	/* Find correct directory for $assignment_index */
	function &dbfuncGetDir($assignment_index, $dirname, $username) {
		global $UPLOAD_BASE_DIR;
		global $db;

		$assignment_index = safe($assignment_index);

		$remove_array = array("!", "#", ":", "/", "\\", "\"", "<", ">", "?", "*", "|", "&", "@", "`");
		
		$res =&  $db->query("SELECT year.Year, term.TermName, subject.Name, " .
							"       term.TermNumber " .
							"FROM  assignment, subject, year, term " .
							"WHERE assignment.AssignmentIndex = $assignment_index " .
							"AND   subject.SubjectIndex = assignment.SubjectIndex " .
							"AND   year.YearIndex = subject.YearIndex " .
							"AND   term.TermIndex = subject.TermIndex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Get assignment
			$uname   = str_replace($remove_array, "", $username);
			$year    = str_replace($remove_array, "", "{$row['Year']}");
			$term    = str_replace($remove_array, "", "{$row['TermNumber']}. {$row['TermName']}");
			$sname   = str_replace($remove_array, "", $row['Name']);
			$dirname = str_replace($remove_array, "", $dirname);
			$new_dirname = "$UPLOAD_BASE_DIR/$uname/$year/$term/$sname/$dirname";
			return $new_dirname;
		} else {
			print "<p>Assignment with index $assignment_index doesn't exist!</p>\n";
			exit(1);
		}
	}

	function dbfuncMkDir($assignment_index, $dirname) {
		global $LOG_ERROR;
		global $LOG_LEVEL_ERROR;
		global $db;

		$assignment_index = safe($assignment_index);

		$res =&  $db->query("SELECT subjectteacher.Username " .
							"FROM assignment, subject, subjectteacher " .
							"WHERE assignment.AssignmentIndex = $assignment_index " .
							"AND   subject.SubjectIndex = assignment.SubjectIndex " .
							"AND   subjectteacher.SubjectIndex = subject.SubjectIndex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Get username
			$dir =& dbfuncGetDir($assignment_index, $dirname, $row['Username']);
			if(is_dir($dir)) {
				return True;
			} else {
				$result = mkdir($dir, 0755, True);
				if($result == False) {
					log_event($LOG_LEVEL_ERROR, "core/dbfunc.php", $LOG_ERROR, "Unable to create $dir.");
					print "<p>Unable to create $dir.</p>\n";
				}
			}
		}
		return $result;
	}

	function dbfuncMoveDir($assignment_index, $old_dirname, $new_dirname) {
		global $LOG_ERROR;
		global $LOG_LEVEL_ERROR;
		global $db;
		
		$assignment_index = safe($assignment_index);

		$res =&  $db->query("SELECT subjectteacher.Username " .
							"FROM assignment, subject, subjectteacher " .
							"WHERE assignment.AssignmentIndex = $assignment_index " .
							"AND   subject.SubjectIndex = assignment.SubjectIndex " .
							"AND   subjectteacher.SubjectIndex = subject.SubjectIndex");
		if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {         // Get username
			$old_dir =& dbfuncGetDir($assignment_index, $old_dirname, $row['Username']);
			$new_dir =& dbfuncGetDir($assignment_index, $new_dirname, $row['Username']);
			if(is_dir($old_dir)) {
				$result = rename($old_dir, $new_dir);
			} else {
				return dbfuncMkDir($assignment_index, $new_dirname);
			}
			if($result == False) {
				log_event($LOG_LEVEL_ERROR, "core/dbfunc.php", $LOG_ERROR, "Unable to move $old_dir to $new_dir.");
				print "<p>Unable to move $old_dir to $new_dir.</p>\n";
			}
		}
		return $result;
	}

	function &getNamesFromList($namelist) {
		$total = count($namelist);
		$count = 0;
		$name_string = "";
		if($total == 0) {
			return "";
		} elseif($total == 1) {
			foreach($namelist as $name) {
				return $name;
			}
		} else {
			foreach($namelist as $name) {
				$count++;
				if($count == $total) {
					$name_string .= " and";
				} elseif($count > 1) {
					$name_string .= ",";
				}
				$name_string .= " $name";
			}
			return trim($name_string);
		}
	}

	function update_marks($assignment_index) {
		global $db;
		global $MARK_LATE;

		$assignment_index = safe($assignment_index);

		/* Update assignment max and min score */
		$query =	"UPDATE assignment, (SELECT MAX(Score) AS MaxScore, MIN(Score) AS MinScore " .
					"                    FROM mark " .
					"                    WHERE AssignmentIndex = $assignment_index " .
					"                    AND   mark.Score >= 0 " .
					"                    AND   mark.Score IS NOT NULL " .
					"                    GROUP BY AssignmentIndex) AS score " .
					"SET   assignment.StudentMax = score.MaxScore, " .
					"      assignment.StudentMin = score.MinScore " .
					"WHERE assignment.AssignmentIndex = $assignment_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Convert student's scores to percentages */
		$query =	"UPDATE mark SET Percentage = NULL " .
					"WHERE  AssignmentIndex = $assignment_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"UPDATE mark, " .
					" ((SELECT Score AS Percentage, Username " .
					"   FROM mark " .
					"   WHERE Score < 0 " .
					"   AND   Score IS NOT NULL " .
					"   AND   Score != $MARK_LATE " .
					"   AND   AssignmentIndex = $assignment_index) " .
					"  UNION " .
					"  (SELECT (mark.Score / assignment.Max) * 100 AS Percentage, mark.Username " .
					"   FROM mark, assignment " .
					"   WHERE mark.Score >= 0 " .
					"   AND   assignment.CurveType = 0 " .
					"   AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
					"   AND   assignment.AssignmentIndex = $assignment_index) " .
					"  UNION " .
					"  (SELECT (mark.Score / assignment.StudentMax) * 100 AS Percentage, mark.Username " .
					"   FROM mark, assignment " .
					"   WHERE mark.Score >= 0 " .
					"   AND   assignment.CurveType = 1 " .
					"   AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
					"   AND   assignment.AssignmentIndex = $assignment_index) " .
					"  UNION " .
					"  (SELECT (((assignment.TopMark - assignment.BottomMark) / (assignment.StudentMax - assignment.StudentMin)) * mark.Score) + ((assignment.TopMark * assignment.StudentMin - assignment.BottomMark * assignment.StudentMax) / (assignment.StudentMin - assignment.StudentMax)) AS Percentage, mark.Username " .
					"   FROM mark, assignment " .
					"   WHERE mark.Score >= 0 " .
					"   AND   assignment.CurveType = 2 " .
					"   AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
					"   AND   assignment.AssignmentIndex = $assignment_index) " .
					"  UNION " .
					"  (SELECT 0 AS Percentage, Username " .
					"   FROM mark " .
					"   WHERE Score = $MARK_LATE " .
					"   AND   AssignmentIndex = $assignment_index) " .
					" ) AS score " .
					"SET mark.Percentage = score.Percentage " .
					"WHERE mark.Username = score.Username " .
					"AND   mark.AssignmentIndex = $assignment_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Calculate subject average for assignment */
		$query =	"UPDATE assignment SET Average = -1 " .
					"WHERE AssignmentIndex = $assignment_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"UPDATE assignment, " .
					"   (SELECT (SUM(Percentage) / COUNT(AssignmentIndex)) AS Average, " .
					"           COUNT(AssignmentIndex) AS Count FROM mark " .
					"    WHERE AssignmentIndex = $assignment_index " .
					"    AND   Score >= 0 " .
					"    AND   Score IS NOT NULL " .
					"    GROUP BY AssignmentIndex) AS score " .
					"SET assignment.Average = score.Average " .
					"WHERE assignment.AssignmentIndex = $assignment_index " .
					"AND   (score.Count > 0 AND score.Count IS NOT NULL) ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Find subject index and update subject info */
		$query =	"SELECT SubjectIndex FROM assignment " .
					"WHERE AssignmentIndex = $assignment_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			update_subject($row['SubjectIndex']);
		}
		
		return true;
	}

	/*function update_classterm_from_user($username, $term_index, $year_index) {
		global $db;

		$query =	"SELECT classlist.ClassIndex FROM class, classlist " .
					"WHERE classlist.ClassIndex = class.ClassIndex " .
					"AND   class.YearIndex      = $year_index " .
					"AND   classlist.Username   = '$username'";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			update_classterm($row['ClassIndex'], $term_index);
		}
	}*/

	function update_classterm_from_subject($subject_index) {
		global $db;

		$query =	"SELECT classterm.ClassIndex, subject.TermIndex FROM " .
					"       classlist, classterm, class, subject, subjectstudent " .
					"WHERE subject.SubjectIndex = $subject_index " .
					"AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
					"AND   classlist.Username = subjectstudent.Username " .
					"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classterm.TermIndex = subject.TermIndex " .
					"AND   classterm.ClassIndex = class.ClassIndex " .
					"AND   class.YearIndex = subject.YearIndex " .
					"GROUP BY classterm.ClassIndex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			update_classterm($row['ClassIndex'], $row['TermIndex']);
		}
	}

	
	function update_classterm($class_index, $term_index) {
		global $db;
		global $AVG_TYPE_PERCENT;

		$query =	"UPDATE classlist, classterm " .
					"       SET classlist.Average=-1, classlist.Rank=-1 " .
					"WHERE classterm.TermIndex = $term_index " .
					"AND   classterm.ClassIndex = $class_index " .
					"AND   classterm.ClassTermIndex = classlist.ClassTermIndex ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"SELECT classlist.Username, classlist.ClassListIndex, " .
					"       classterm.ClassTermIndex, class.YearIndex " .
					"       FROM classlist, classterm, class " .
					"WHERE class.ClassIndex = $class_index " .
					"AND   classterm.ClassIndex = class.ClassIndex " .
					"ANd   classterm.TermIndex = $term_index " .
					"AND   classlist.ClassTermIndex = classterm.ClassTermIndex";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$query =	"UPDATE classlist, classterm, " .
						"  (SELECT SUM(subject_weight.DistWeight * subjectstudent.Average) * 100 / " .
						"          SUM(subject_weight.DistWeight * 100) AS Avg " .
						"          FROM subjectstudent, subject, " .
						"               (SELECT subjecttype.SubjectTypeIndex, " .
						"                       subjecttype.Weight / COUNT(subject.SubjectIndex) " .
						"                                                         AS DistWeight, " .
						"                       subjecttype.Weight FROM subjecttype, subject, subjectstudent " .
						"                WHERE subject.YearIndex            =  {$row['YearIndex']} " .
						"                AND   subject.TermIndex            =  $term_index " .
						"                AND   subject.AverageType          =  $AVG_TYPE_PERCENT " .
						"                AND   subjectstudent.subjectIndex  =  subject.SubjectIndex " .
						"                AND   subjectstudent.Average       >= 0 " .
						"                AND   subjectstudent.Username      =  '{$row['Username']}' " .
						"                AND   subjecttype.SubjectTypeIndex =  subject.SubjectTypeIndex " .
						"                AND   subjecttype.Weight           IS NOT NULL " .
						"                GROUP BY subjecttype.SubjectTypeIndex) AS subject_weight " .
						"   WHERE subject.YearIndex               =  {$row['YearIndex']} " .
						"   AND   subject.TermIndex               =  $term_index " .
						"   AND   subject.AverageType             =  $AVG_TYPE_PERCENT " .
						"   AND   subjectstudent.subjectIndex     =  subject.SubjectIndex " .
						"   AND   subjectstudent.Average          >= 0 " .
						"   AND   subjectstudent.Username         =  '{$row['Username']}' " .
						"   AND   subject_weight.SubjectTypeIndex =  subject.SubjectTypeIndex " .
						"   GROUP BY subjectstudent.Username) AS ctinfo " .
						"SET classlist.Average = ctinfo.Avg " .
						"WHERE classlist.Username  = '{$row['Username']}' " .
						"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
						"AND   classterm.TermIndex = $term_index " .
						"AND   classterm.ClassIndex = $class_index";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		}

		$query =	"SELECT classlist.ClassListIndex, classlist.Average FROM classterm, classlist " .
					"WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classterm.TermIndex      = $term_index " .
					"AND   classterm.ClassIndex     = $class_index";
					"AND   classterm.Average        >= 0 " .
					"ORDER BY Average DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Set subject ranking */
		$rank = 1;
		$prevmark = 0;
		$count = 0;
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($prevmark > round($row['Average'])) {
				$rank += $count;
				$count = 1;
			} else {
				$count += 1;
			}
			$prevmark = round($row['Average']);
			$query =	"UPDATE classlist SET Rank=$rank " .
						"WHERE ClassListIndex = {$row['ClassListIndex']}";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		}

	}

	function update_subject($subject_index) {
		global $db;
		global $MARK_LATE;
		global $AVG_TYPE_GRADE;
		
		$subject_index = safe($subject_index);

		$query =	"SELECT AverageType, AverageTypeIndex FROM subject WHERE SubjectIndex = $subject_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		if(!($row =& $res->fetchRow(DB_FETCHMODE_ASSOC))) {
			return false;
		}
		
		$avg_type = $row["AverageType"];
		$avg_type_index = $row["AverageTypeIndex"];
		
		/* Clear student averages for subject */
		$query =	"UPDATE subjectstudent " .
					"SET Average = -1 " .
					"WHERE SubjectIndex = $subject_index ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Calculate student's current average in subject */
		$query =	"UPDATE subjectstudent, " .
					"  (SELECT " .
					"   SUM(Average) AS Average, Username FROM " .
					"    (SELECT " .
					"       (SUM(mark.Percentage * assignment.Weight) / " .
					"        SUM(100 * assignment.Weight) * 100) * " .
					"       IF(categorylist.Weight IS NULL, " .
					"          1, " .
					"          (categorylist.Weight / total.TotalWeight)) AS Average, " .
					"       SUM(assignment.Weight) AS TotalWeight, " .
					"       mark.Username FROM " .
					"         assignment INNER JOIN subjectstudent USING (SubjectIndex) " .
					"         LEFT OUTER JOIN categorylist USING (CategoryListIndex) " .
					"         LEFT OUTER JOIN mark ON (subjectstudent.Username = mark.Username AND " .
					"                                 assignment.AssignmentIndex = mark.AssignmentIndex) " .
					"         LEFT OUTER JOIN " .
					"         (SELECT Username, SUM(TotalWeight) AS TotalWeight FROM " .
					"           (SELECT DISTINCT mark.Username, categorylist.CategoryListIndex, " .
					"                   categorylist.Weight AS TotalWeight FROM assignment " .
					"                   LEFT OUTER JOIN categorylist USING (CategoryListIndex), mark " .
					"            WHERE assignment.SubjectIndex=$subject_index " .
					"            AND   assignment.Agenda = 0 " .
					"            AND   mark.AssignmentIndex = assignment.AssignmentIndex " .
					"            AND   (mark.Score >= 0 OR mark.Score = $MARK_LATE) " .
					"            AND   assignment.Weight > 0 " .
					"            AND   mark.Score IS NOT NULL " .
					"           ) AS do_weight GROUP BY Username) AS total " .
					"         ON (subjectstudent.Username = total.username)" .
					"       WHERE assignment.SubjectIndex = $subject_index " .
					"       AND   assignment.Agenda = 0 " .
					"       AND   assignment.Hidden = 0 " .
					"       AND   (mark.Score >= 0 OR mark.Score = $MARK_LATE) " .
					"       AND   mark.Score IS NOT NULL " .
					"       GROUP BY assignment.CategoryListIndex, mark.Username) AS seg_score " .
					"   WHERE TotalWeight > 0 " .
					"   GROUP BY Username) AS score " .
					"SET subjectstudent.Average = score.Average " .
					"WHERE subjectstudent.SubjectIndex = $subject_index " .
					"AND   subjectstudent.Username = score.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"SELECT Username, Average FROM subjectstudent " .
					"WHERE SubjectIndex = $subject_index " .
					"AND   Average >= 0 " .
					"ORDER BY Average DESC";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		/* Set subject ranking */
		$rank = 1;
		$prevmark = 0;
		$count = 0;
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($prevmark > intval($row['Average'])) {
				$rank += $count;
				$count = 1;
			} else {
				$count += 1;
			}
			$prevmark = round($row['Average']);
			$query =	"UPDATE subjectstudent SET Rank=$rank " .
						"WHERE SubjectIndex = $subject_index " .
						"AND Username = '{$row['Username']}'";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		}


		/* Calculate subject average */
		$query =	"UPDATE subject SET Average = -1 " .
					"WHERE SubjectIndex = $subject_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"UPDATE subject, " .
					"   (SELECT AVG(subjectstudent.Average) AS Average, " .
					"           COUNT(subjectstudent.Average) AS Count " .
					"           FROM subjectstudent " .
					"    WHERE subjectstudent.SubjectIndex = $subject_index " .
					"    AND   subjectstudent.Average >= 0) AS score " .
					"SET subject.Average = score.Average " .
					"WHERE subject.SubjectIndex = $subject_index " .
					"AND   (score.Count > 0 AND score.Count IS NOT NULL)";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		update_classterm_from_subject($subject_index);
		
		if($avg_type == $AVG_TYPE_GRADE) {
			$query =	"UPDATE subjectstudent, " .
						"       (SELECT * FROM " .
						"          (SELECT Username, NonmarkIndex, MinScore, Display FROM nonmark_index, subjectstudent " .
						"           WHERE (nonmark_index.MinScore <= subjectstudent.Average OR nonmark_index.MinScore IS NULL) " .
						"           AND nonmark_index.NonMarkTypeIndex = $avg_type_index " .
						"           AND subjectstudent.SubjectIndex = $subject_index " .
						"           AND subjectstudent.Average != -1 " .
						"           ORDER BY MinScore DESC) AS score1 " .
						"        GROUP BY Username) AS score " .
						"SET subjectstudent.Average = score.NonMarkIndex " .
						"WHERE subjectstudent.SubjectIndex = $subject_index " .
						"AND   subjectstudent.Username = score.Username";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query						
		}
		return true;
	}

	/* Update all student conduct marks for year and term */
	
	function update_conduct_year_term($year, $term) {
		global $db;

		$query =	"SELECT classlist.Username FROM classlist, classterm, class " .
					"WHERE classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classterm.TermIndex = $term " .
					"AND   classterm.ClassIndex = class.ClassIndex " .
					"AND   class.YearIndex = $year ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());
		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			update_conduct_mark($row['Username'], $year, $term);
		}
	}

	/*
	function update_conduct_input($class_index, $term_index) {
		global $db;
		global $CONDUCT_TYPE_PERCENT;
		global $CLASS_CONDUCT_TYPE_CALC;

		$query =	"UPDATE classterm, classlist " .
					"       SET classterm.Conduct=-1 " .
					"WHERE classterm.TermIndex = $term_index " .
					"AND   classterm.ClassListIndex = classlist.ClassListIndex " .
					"AND   classlist.ClassIndex = $class_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		$query =	"SELECT classlist.Username, classlist.ClassListIndex, class.YearIndex, " .
					"       class_term.ConductType " .
					"       FROM classlist, class, class_term " .
					"WHERE classlist.ClassIndex = $class_index " .
					"AND   class.ClassIndex     = $class_index " .
					"AND   class_term.ClassIndex = $class_index " .
					"AND   class_term.TermIndex  = $term_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['ConductType'] == $CLASS_CONDUCT_TYPE_CALC) {
			$query =	"UPDATE classterm, " .
						"  (SELECT SUM(subject_weight.DistWeight * subjectstudent.Conduct) * 100 / " .
						"          SUM(subject_weight.DistWeight * 100) AS Avg " .
						"          FROM subjectstudent, subject, " .
						"               (SELECT subjecttype.SubjectTypeIndex, " .
						"                       subjecttype.Weight / COUNT(subject.SubjectIndex) " .
						"                                                         AS DistWeight, " .
						"                       subjecttype.Weight FROM subjecttype, subject, subjectstudent " .
						"                WHERE subject.YearIndex            =  {$row['YearIndex']} " .
						"                AND   subject.TermIndex            =  $term_index " .
						"                AND   subject.ConductType          =  $CONDUCT_TYPE_PERCENT " .
						"                AND   subjectstudent.subjectIndex  =  subject.SubjectIndex " .
						"                AND   subjectstudent.Conduct       >= 0 " .
						"                AND   subjectstudent.Username      =  '{$row['Username']}' " .
						"                AND   subjecttype.SubjectTypeIndex =  subject.SubjectTypeIndex " .
						"                AND   subjecttype.Weight           IS NOT NULL " .
						"                GROUP BY subjecttype.SubjectTypeIndex) AS subject_weight " .
						"   WHERE subject.YearIndex               =  {$row['YearIndex']} " .
						"   AND   subject.TermIndex               =  $term_index " .
						"   AND   subject.ConductType             =  $CONDUCT_TYPE_PERCENT " .
						"   AND   subjectstudent.subjectIndex     =  subject.SubjectIndex " .
						"   AND   subjectstudent.Conduct          >= 0 " .
						"   AND   subjectstudent.Username         =  '{$row['Username']}' " .
						"   AND   subject_weight.SubjectTypeIndex =  subject.SubjectTypeIndex " .
						"   GROUP BY subjectstudent.Username) AS ctinfo " .
						"SET classterm.Conduct = ctinfo.Avg " .
						"WHERE classterm.ClassListIndex = '{$row['ClassListIndex']}' " .
						"AND   classterm.TermIndex      = $term_index";
			$nres =&  $db->query($query);
			if(DB::isError($nres)) die($nres->getDebugInfo());           // Check for errors in query
		}
	}*/

	function update_conduct_mark($studentusername, $year=-1, $term=-1) {
		global $yearindex;
		global $termindex;
		global $db;

		if($year == -1) $year = $yearindex;
		if($term == -1) $term = $termindex;
		$query =	"SELECT class.HasConduct, term.HasConduct AS TermConduct, " .
					"       COUNT(subjectstudent.SubjectIndex) AS SubjectCount, " .
					"       classterm.ClassTermIndex FROM " .
					"       class, classterm, classlist, term, subject, subjectstudent " .
					"WHERE class.YearIndex = subject.YearIndex " .
					"AND   classterm.ClassIndex = class.ClassIndex " .
					"AND   classterm.TermIndex = term.TermIndex " .
					"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classlist.Username = '$studentusername' " .
					"AND   term.TermIndex = $term " .
					"AND   subject.YearIndex = $year " .
					"AND   subject.TermIndex = term.TermIndex " .
					"AND   subject.SubjectIndex = subjectstudent.SubjectIndex " .
					"AND   subjectstudent.Username = '$studentusername' " .
					"GROUP BY subjectstudent.Username";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query

		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['HasConduct'] == 1 and $row['TermConduct'] == 1) {
			$classterm = $row['ClassTermIndex'];
			
			$query =	"SELECT IF((sum(disciplineweight.DisciplineWeight) > 100), 0, " .
						"          (100 - sum(disciplineweight.DisciplineWeight))) AS Score " .
						"       FROM discipline, disciplineweight " .
						"WHERE discipline.DisciplineWeightIndex = disciplineweight.DisciplineWeightIndex " .
						"AND   disciplineweight.YearIndex = $year " .
						"AND   disciplineweight.TermIndex = $term " .
						"AND   discipline.Username        = '$studentusername' " .
						"GROUP BY discipline.Username";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
			if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$score = $row['Score'];
			} else {
				$score = 100;
			}
	
			$query =	"UPDATE classlist SET Conduct=$score " .
						"WHERE Username       = '$studentusername' " .
						"AND   ClassTermIndex = $classterm";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		} else {
			$classterm = $row['ClassTermIndex'];
			
			if(!isset($classterm) or is_null($classterm)){
				return;
			}
			$query =	"UPDATE classlist SET Conduct=-1 " .
						"WHERE Username       = '$studentusername' " .
						"AND   ClassTermIndex = $classterm";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		}
	}

	function dbfuncGetPhoneRLZ() {
		/* Set global parameters */
		global $db;
		
		/* Run query to extract information from "user" table */
		$res =& $db->query("SELECT PhoneRLZ FROM currentinfo ORDER BY InputDate DESC LIMIT 1");
		if(DB::isError($res)) die($res->getDebugInfo());
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			if($row['PhoneRLZ'] == 1) {
				return true;
			}
		}
		return false;
	}

	function dbfuncGetPhonePrefix() {
		/* Set global parameters */
		global $db;
		global $username;
		
		/* Run query to extract information from "user" table */
		$res =& $db->query("SELECT PhonePrefix FROM currentinfo ORDER BY InputDate DESC LIMIT 1");
		if(DB::isError($res)) die($res->getDebugInfo());
		
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			return $row['PhonePrefix'];
		}
		return "";
	}

	function &get_comment($username, $comment_index) {
		/* Set global parameters */
		global $db;
		global $yearindex;
		global $termindex;

		$query =	"SELECT user.FirstName, user.Surname, user.Gender, class.Grade, " .
					"       comment.Comment, comment.Strength FROM user, comment, class, classterm, classlist " .
					"WHERE user.Username            = '$username' " .
					"AND   comment.CommentIndex     = $comment_index " .
					"AND   classlist.Username       = user.Username " .
					"AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
					"AND   classterm.TermIndex      = $termindex " .
					"AND   classterm.ClassIndex     = class.ClassIndex " .
					"AND   class.YearIndex          = $yearindex ";
		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		if(!$row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			return false;
		}

		$grade = $row['Grade'];

		if(strtolower($row['Gender']) == 'm') {
			$heshe  = "he";
			$himher = "him";
			$hisher = "his";
		} else {
			$heshe  = "she";
			$himher = "her";
			$hisher = "her";
		}

		$comment = $row['Comment'];
		$comment = str_replace("[Name]", $row['FirstName'], $comment);
		$comment = str_replace("[NAME]", $row['FirstName'], $comment);
		$comment = str_replace("[name]", $row['FirstName'], $comment);
		$comment = str_replace("[FullName]", "{$row['FirstName']} {$row['Surname']}", $comment);
		$comment = str_replace("[FULLNAME]", "{$row['FirstName']} {$row['Surname']}", $comment);
		$comment = str_replace("[fullname]", "{$row['FirstName']} {$row['Surname']}", $comment);
		$comment = str_replace("[Fullname]", "{$row['FirstName']} {$row['Surname']}", $comment);
		$comment = str_replace("[him/her]", $himher, $comment);
		$comment = str_replace("[Him/her]", ucfirst($himher), $comment);
		$comment = str_replace("[Him/Her]", ucfirst($himher), $comment);
		$comment = str_replace("[he/she]", $heshe, $comment);
		$comment = str_replace("[He/she]", ucfirst($heshe), $comment);
		$comment = str_replace("[He/She]", ucfirst($heshe), $comment);
		$comment = str_replace("[his/her]", $hisher, $comment);
		$comment = str_replace("[His/her]", ucfirst($hisher), $comment);
		$comment = str_replace("[His/Her]", ucfirst($hisher), $comment);
		$comment = str_replace("[Grade]", strval($grade), $comment);
		$comment = str_replace("[grade]", strval($grade), $comment);
		$comment = str_replace("[GRADE]", strval($grade), $comment);
		$comment = str_replace("[NextGrade]", strval($grade+1), $comment);
		$comment = str_replace("[Nextgrade]", strval($grade+1), $comment);
		$comment = str_replace("[nextgrade]", strval($grade+1), $comment);
		$comment = str_replace("[NEXTGRADE]", strval($grade+1), $comment);
		
		return array($comment, $row['Strength']);
	}

	function &htmlize_comment($comment) {
		$comment = str_replace("\r\n", "<br>", $comment);
		return $comment;
	}
	
	function &unhtmlize_comment($comment) {
		$comment = str_replace("<br>", "\r\n", $comment);
		return $comment;
	}
?>

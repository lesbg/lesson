<?php
	/*****************************************************************
	 * admin/punishment/low.php  (c) 2007 Jonathan Dieter
	 *
	 * Print students whose conduct mark is below a certain criteria
	 *****************************************************************/
	if(isset($_GET["show"])) {
		$showtype = dbfuncInt2String($_GET["show"]);
	} else {
		$showtype = "a";
		$_GET["show"] = dbfuncString2Int("a");
	}
	
	if(isset($_GET['start'])) {
		$start = $_GET['start'];
	} else {
		$start = "0";
		$_GET['start'] = "0";
	}

	$title = "Low conduct marks";

	/* Get filter type */
	if(isset($_POST['ftype'])) {
		$ftype = strval(intval(($_POST['ftype'])));
		$_GET['ftype'] = $ftype;
	} elseif(isset($_GET['ftype'])) {
		$ftype = strval(intval(($_GET['ftype'])));
	} else {
		$ftype = "0";
		$_GET['ftype'] = $ftype;
	}

	/* Get filter data */
	if(isset($_POST['fdata'])) {
		if($ftype == "0") {
			$fdata = strval(intval(($_POST['fdata'])));
			$_GET['fdata'] = $fdata;
		} else {
			$fdata = "60";
			$_GET['fdata'] = "60";
		}
	} elseif(isset($_GET['fdata'])) {
		if($ftype == "0") {
			$fdata = strval(intval(($_GET['fdata'])));
		} else {
			$fdata = "60";
			$_GET['fdata'] = "60";
		}
	} else {
		$fdata = "60";
		$_GET['fdata'] = $fdata;
	}

	if($_POST['action'] == "Reset") {
		$ftype = "0";
		$_GET['ftype'] = $ftype;
		$fdata = "60";
		$_GET['fdata'] = $fdata;
	}

	$query =    "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	include "header.php";

	if($is_admin or $perm >= $PUN_PERM_SEE) {  // Make sure user has permission to view
		$showalldeps = true;                              //  low marks
		include "core/settermandyear.php";
		include "core/titletermyear.php";

		if(!isset($_GET['sort'])) {
			$_GET['sort'] = '0';
		}
		
		for($a=0; $a < 18; $a++) {
			$sort[$a] = "sort";
		}
		
		$sort[intval($_GET['sort'])] = "bsort";
		
		if($_GET['sort'] == '1') {
			$sortorder = "ORDER BY class.Grade DESC, class.ClassName DESC, user.Username DESC";
		} elseif($_GET['sort'] == '2') {
			$sortorder = "ORDER BY user.Username";
		} elseif($_GET['sort'] == '3') {
			$sortorder = "ORDER BY user.Username DESC";
		} elseif($_GET['sort'] == '8') {
			$sortorder = "ORDER BY classlist.Conduct, class.Grade, class.ClassName, user.Username";
		} elseif($_GET['sort'] == '9') {
			$sortorder = "ORDER BY classlist.Conduct DESC, class.Grade DESC, class.ClassName DESC, user.Username DESC";
		} else {
			$sortorder = "ORDER BY class.Grade, class.ClassName, user.Username";
		}
		
		$classAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=0&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "A", "small", "{$sort[0]}", "Sort ascending");
		$classDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=1&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "D", "small", "{$sort[1]}", "Sort descending");
		$unameAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=2&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "A", "small", "{$sort[2]}", "Sort ascending");
		$unameDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=3&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "D", "small", "{$sort[3]}", "Sort descending");
		$scoreAsc   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=8&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "A", "small", "{$sort[8]}", "Sort ascending");
		$scoreDec   = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                              "&amp;sort=9&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;start={$_GET['start']}", "D", "small", "{$sort[9]}", "Sort descending");
	
		$query =	"SELECT user.Username FROM classlist " .
					"         INNER JOIN user USING (Username) " .
					"         INNER JOIN classterm USING (ClassTermIndex) " .
					"         INNER JOIN class USING (ClassIndex) " .
					"WHERE class.YearIndex = $yearindex " .
					"AND   classterm.TermIndex = $termindex " .
					"AND   classlist.Conduct IS NOT NULL " .
					"AND   classlist.Conduct != -1 " .
					"AND   classlist.Conduct <= $fdata ";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		$count = $res->numRows();
		$max     = intval($count) - 1;
		$fpstart = intval(intval($max)/$LOGS_PER_PAGE) * $LOGS_PER_PAGE;
		if($start > $max) $start = 0;

		$query =	"SELECT user.FirstName, user.Surname, user.Username, class.ClassName, class.Grade, classlist.Conduct FROM classlist " .
					"         INNER JOIN user USING (Username) " .
					"         INNER JOIN classterm USING (ClassTermIndex) " .
					"         INNER JOIN class USING (ClassIndex) " .
					"WHERE class.YearIndex = $yearindex " .
					"AND   classterm.TermIndex = $termindex " .
					"AND   classlist.Conduct IS NOT NULL " .
					"AND   classlist.Conduct != -1 " .
					"AND   classlist.Conduct <= $fdata ";
					"$sortorder " .
					"LIMIT $start, $LOGS_PER_PAGE";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
		$count = $res->numRows();

		if(intval($start) > 0) {
			$first_record = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                                    "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;sort={$_GET['sort']}&amp;start=0", "<<", "medium", "prevnext", "First page");
			$prev         = intval($start) - $LOGS_PER_PAGE;
			if($prev < 0)
				$prev     = 0;
			$prev         = strval($prev);
			$prev_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                                   "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;sort={$_GET['sort']}&amp;start=$prev", "<", "medium", "prevnext", "Previous page");
		} else {
			$first_record = dbfuncGetDisabledButton("<<", "medium", "prevnext");
			$prev_record  = dbfuncGetDisabledButton("<", "medium", "prevnext");
		}
		
		if(intval($start) < $fpstart) {
			$last_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                                    "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;sort={$_GET['sort']}&amp;start=$fpstart", ">>", "medium", "prevnext", "Last page");
			$next         = intval($start) + $LOGS_PER_PAGE;
			if($next > $fpstart)
				$next     = $fpstart;
			$next         = strval($next);
			$next_record  = dbfuncGetButton("index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		                                   "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;sort={$_GET['sort']}&amp;start=$next", ">", "medium", "prevnext", "Next page");
		} else {
			$last_record  = dbfuncGetDisabledButton(">>", "medium", "prevnext");;
			$next_record  = dbfuncGetDisabledButton(">", "medium", "prevnext");;
		}
		
		$startval = strval(intval($start) + 1);
		$endval   = strval(intval($start) + $count);
		$totalval = strval($max + 1);
		$link = "index.php?location=" . dbfuncString2Int("admin/punishment/low.php") .
		        "&amp;show={$_GET['show']}&amp;fdata={$_GET['fdata']}&amp;ftype={$_GET['ftype']}&amp;sort={$_GET['sort']}&amp;start={$_GET['start']}";
		
		$sdate_show = date($dateformat, strtotime($sdate));
		$edate_show = date($dateformat, strtotime($edate));
		/* Print filter options */
		echo "      <form action='$link' method='post'>\n";  // Form method
		echo "         <table class='transparent' width='100%'>\n";
		echo "            <tr>\n";
		echo "               <td nowrap>Highest mark to show: <input type='text' name='fdata' value='$fdata'></td>\n";
		echo "               <td nowrap><input type='submit' name='action' value='Reset'> <input type='submit' name='action' value='Go'></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "      </form>\n";
		/* Print header with rows being shown and buttons to move */
		echo "      <table class='transparent' width='100%'>\n";
		echo "         <tr>\n";
		echo "            <td width='100px' nowrap>$first_record $prev_record</td>\n"; 
		echo "            <td align='center' nowrap>Showing records $startval-$endval " .
																		"of $totalval</td>\n";
		echo "            <td width='100px' align='right' nowrap>$next_record $last_record</td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";

		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th nowrap>Student $unameAsc $unameDec</th>\n";
		echo "            <th nowrap>Class $classAsc $classDec</th>\n";
		echo "            <th nowrap>Mark $scoreAsc $scoreDec</th>\n";
		echo "         </tr>\n";
		
		/* For each assignment, print subject, teacher, assignment title, date, score, and any comments */
		$alt_count = 0;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt_step = "alt";
			} else {
				$alt_step = "std";
			}
			$alt = " class='$alt_step'";

			echo "         <tr$alt>\n";
			
			if($is_admin) {
				$punlink =	"index.php?location=" . dbfuncString2Int("student/discipline.php") .
							"&amp;key=" .           dbfuncString2Int($row['Username']) .
							"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']}");
				echo "            <td nowrap><a href='$punlink'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
			} else {
				echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			}
			echo "            <td nowrap>{$row['ClassName']}</td>\n";
			echo "            <td nowrap>{$row['Conduct']}%</td>\n";
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
		log_event($LOG_LEVEL_EVERYTHING, "admin/punishment/low.php", $LOG_ADMIN,
					"Viewed low conduct marks.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "admin/punishment/low.php", $LOG_DENIED_ACCESS,
					"Tried to access low marks.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>
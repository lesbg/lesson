<?php
	/*****************************************************************
	 * teacher/casenote/watchlist/list.php  (c) 2005 Jonathan Dieter
	 *
	 * List all students on teacher's watchlist and which
	 * class they are in this year
	 *****************************************************************/

	$title = "Casenote Watchlist";
	
	include "core/settermandyear.php";
	include "header.php";                                          // Show header
	
	/* Check whether current user is a counselor */
	$res =&  $db->query("SELECT Username FROM counselorlist " .
						"WHERE Username='$username'");
	if(DB::isError($res)) die($res->getDebugInfo());         // Check for errors in query

	if($res->numRows() > 0) {
		$is_counselor = true;
	} else {
		$is_counselor = false;
	}

	if(!isset($_GET['sort'])) {
		$_GET['sort'] = '2';
	}
	
	if($_GET['sort'] == '1') {
		$sortorder = "Username DESC";
	} elseif($_GET['sort'] == '0') {
		$sortorder = "Username";
	} elseif($_GET['sort'] == '3') {
		$sortorder = "Grade DESC, ClassName DESC, Username DESC";
	} elseif($_GET['sort'] == '8') {
		$sortorder = "NewCount, Grade, ClassName, Username";
	} elseif($_GET['sort'] == '9') {
		$sortorder = "NewCount DESC, Grade DESC, ClassName DESC, Username DESC";
	} else {
		$sortorder = "Grade, ClassName, Username";
	}
	
	$nameAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=0", "A", "small", "sort", "Sort ascending");
	$nameDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=1", "D", "small", "sort", "Sort descending");
	$classAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=2", "A", "small", "sort", "Sort ascending");
	$classDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=3", "D", "small", "sort", "Sort descending");
	$newcnAsc = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=8", "A", "small", "sort", "Sort ascending");
	$newcnDec = dbfuncGetButton("index.php?location=" . dbfuncString2Int("teacher/casenote/watchlist/list.php") .
								"&amp;sort=9", "D", "small", "sort", "Sort descending");
	
	/* Get student list */
	$query =	"(SELECT user.FirstName, user.Surname, user.Username, " .
				"         class.ClassName, class.Grade, " .
				"         COUNT(casenote.StudentUsername) AS NewCount, casenotewatch.CaseNoteWatchIndex, " .
				"         classlist.ClassOrder " .
				"         FROM user, class, classterm, classlist, casenotenew, casenote LEFT OUTER JOIN " .
				"              casenotewatch ON (casenotewatch.StudentUsername=casenote.StudentUsername " .
				"              AND casenotewatch.WorkerUsername='$username') " .
				" WHERE  user.Username = casenote.StudentUsername " .
				" AND    casenotenew.CaseNoteIndex = casenote.CaseNoteIndex " .
				" AND    user.Username = classlist.Username " .
				" AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
				" AND    classterm.TermIndex = $currentterm " .
				" AND    class.ClassIndex = classterm.ClassIndex " .
				" AND    class.YearIndex = $currentyear " .
				" AND    casenotenew.WorkerUsername = '$username' " .
				" GROUP BY casenote.StudentUsername) " .
				"UNION " .
				"(SELECT user.FirstName, user.Surname, user.Username, " .
				"         class.ClassName, class.Grade, " .
				"         NULL AS NewCount, casenotewatch.CaseNoteWatchIndex, " .
				"         classlist.ClassOrder " .
				"         FROM user, class, classterm, classlist, casenote LEFT OUTER JOIN casenotenew " .
				"              ON (casenote.CaseNoteIndex=casenotenew.CaseNoteIndex AND " .
				"              casenotenew.WorkerUsername='$username'), casenotewatch " .
				" WHERE  user.Username = casenote.StudentUsername " .
				" AND    casenotenew.CaseNoteNewIndex IS NULL " .
				" AND    casenotewatch.StudentUsername = casenote.StudentUsername " .
				" AND    casenotewatch.WorkerUsername = '$username' " .
				" AND    user.Username = classlist.Username " .
				" AND    classlist.ClassTermIndex = classterm.ClassTermIndex " .
				" AND    classterm.TermIndex = $currentterm " .
				" AND    class.ClassIndex = classterm.ClassIndex " .
				" AND    class.YearIndex = $currentyear " .
				" GROUP BY casenote.StudentUsername )" .
				"ORDER BY $sortorder";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	
	/* Print students and their class */
	if($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n";  // Table headers
		echo "         <tr>\n";
		echo "            <th>Name $nameAsc $nameDec</th>\n";
		echo "            <th>Class $classAsc $classDec</th>\n";
		echo "            <th>New $newcnAsc $newcnDec</th>\n";
		if($is_counselor) echo "            <th>In Watchlist</th>\n";
		echo "         </tr>\n";
		
		/* For each student, print a row with the student's name and what class they're in */
		$alt_count = 0;
		while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$alt_count += 1;
			if($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			echo "         <tr$alt>\n";

			$cnlink =   "index.php?location=" . dbfuncString2Int("teacher/casenote/list.php") .
						"&amp;key=" .           dbfuncString2Int($row['Username']) .
						"&amp;keyname=" .       dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
						"&amp;keyname2=" .      dbfuncSTring2Int($row['FirstName']);

			echo "            <td><a href='$cnlink'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
			if($row['ClassName'] != NULL) {
				echo "            <td>{$row['ClassName']}</td>\n";
			} else {
				echo "            <td><i>None</i></td>\n";
			}
			if($row['NewCount'] == NULL) {
				echo "            <td>0</td>\n";
			} else {
				echo "            <td><b>{$row['NewCount']}</b></td>\n";
			}
			if($is_counselor) {
				if($row['CaseNoteWatchIndex'] == NULL) {
					echo "            <td>&nbsp;</td>\n";
				} else {
					echo "            <td>X</td>\n";
				}
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
	} else {
		echo "      <p align='center'>There are no students with new casenotes.</p>\n";
	}
	
	include "footer.php";
?>
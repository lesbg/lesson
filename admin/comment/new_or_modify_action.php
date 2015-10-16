<?php
/**
 * ***************************************************************
 * admin/comment/new_or_modify_action.php (c) 2005 Jonathan Dieter
 *
 * Change comment type information
 * ***************************************************************
 */

/* Get variables */
if (isset($_GET['key'])) {
	$key = dbfuncInt2String($_GET['key']); // Key
} else {
	$key = NULL;
}
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

if (! isset($_POST['strength']) or is_null($_POST['strength']) or
	 $_POST['strength'] == "") {
	$_POST['strength'] = "NULL";
} else {
	$_POST['strength'] = strval(intval($_POST['strength']));
}

if (! isset($_POST['number']) or is_null($_POST['number']) or
	 $_POST['number'] == "") {
	$_POST['number'] = "";
} else {
	$_POST['number'] = strval(intval($_POST['number']));
}

/* Check which button was pressed */
if ($_POST["action"] == "Save" or $_POST["action"] == "Update") { // If update or save were pressed, print
                                                                    // common info and go to the right page.
	$values = array();
	if (isset($_POST['value'])) {
		$tok = strtok($_POST['value'], ",");
		while ( $tok ) {
			if ($tok != "") {
				$values[intval($tok)] = intval($tok);
			}
			$tok = strtok(",");
		}
	}
	
	/* Check for input errors */
	$format_error = False;
	$errorlist = "";
	if (! isset($_POST['comment']) or is_null($_POST['comment']) or
		 $_POST['comment'] == "") { // Make sure name has been entered
		$errorlist .= "<p class='error' align='center'>You must type a comment!</p>\n";
		$format_error = True;
	}
	
	if ($_POST['number'] != "" and
		 ($_POST['action'] == "Save" or
		 ($_POST['action'] == "Update" and $key != $_POST['number']))) {
		$query = "SELECT CommentIndex FROM comment WHERE CommentIndex = {$_POST['number']}";
		if ($_POST["action"] == "Update") {
			$query .= " AND CommentIndex != $key";
		}
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		if ($res->numRows() > 0) {
			$errorlist .= "<p class='error' align='center'>A comment already has this number.  Please choose a different one.</p>\n";
			$format_error = True;
		}
	}
	
	if (! $format_error) {
		$_POST['comment'] = safe($_POST['comment']);
		$query = "SELECT CommentIndex FROM comment " .
				 "WHERE Comment = '{$_POST['comment']}'";
		if ($_POST["action"] == "Update")
			$query .= " AND CommentIndex != $key";
		$res = & $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		if ($res->numRows() > 0) {
			$errorlist .= "<p class='error' align='center'>This comment already exists.  Please write a different one.</p>\n";
			$format_error = True;
		}
	}
	
	if (! $format_error) {
		
		$errorlist = ""; // Clear error list. This list will now only contain database errors.
		
		$title = "LESSON - Saving changes...";
		$noHeaderLinks = true;
		$noJS = true;
		
		include "header.php";
		
		echo "      <p align='center'>Saving changes...";
		
		if ($_POST["action"] == "Save") { // Create new comment if "Save" was pressed
			include "admin/comment/new_action.php";
		} else {
			include "admin/comment/modify_action.php"; // Change comment if "Update" was pressed
		}
		
		if ($error) { // If we ran into any errors, print failed, otherwise print done
			echo "failed!</p>\n";
		} else {
			echo "done.</p>\n";
		}
		echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
		include "footer.php";
	} else {
		if ($_POST["action"] == "Save") {
			include "admin/comment/new.php";
		} else {
			include "admin/comment/modify.php";
		}
	}
} elseif ($_POST['action'] == "Cancel") {
	$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Cancelling...";
	
	include "header.php";
	
	echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
		 "</p>\n";
	
	include "footer.php";
} else {
	$values = array();
	if (isset($_POST['value'])) {
		$tok = strtok($_POST['value'], ",");
		while ( $tok ) {
			if ($tok != "") {
				$values[intval($tok)] = intval($tok);
			}
			$tok = strtok(",");
		}
	}
	if ($_POST['action'] == ">") {
		foreach ( $_POST['removesubjecttype'] as $remSubject ) {
			$remSubject = intval($remSubject);
			if (isset($values[$remSubject]))
				unset($values[$remSubject]);
		}
	} elseif ($_POST['action'] == "<") {
		foreach ( $_POST['addsubjecttype'] as $addSubject ) {
			$addSubject = intval($addSubject);
			$values[$addSubject] = $addSubject;
		}
	}
	
	if (is_null($key)) {
		include "admin/comment/new.php";
	} else {
		include "admin/comment/modify.php";
	}
}
?>
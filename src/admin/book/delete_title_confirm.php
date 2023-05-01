<?php
/**
 * ***************************************************************
 * admin/book/delete_title_confirm.php (c) 2010 Jonathan Dieter
 *
 * Confirm deletion of a book title from database
 * ***************************************************************
 */

/* Get variables */
$book = dbfuncInt2String($_GET['keyname']);

$title = "LESSON - Confirm to delete $book";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether user is authorized to delete book type */
if ($is_admin) {
	$link = "index.php?location=" .
			 dbfuncString2Int("admin/book/delete_title.php") . "&amp;key=" .
			 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
			 $_GET['next'];
	
	echo "      <p align=\"center\">Are you <b>sure</b> you want to delete $book?  All copies of this title will also be deleted.</p>\n";
	echo "      <form action=\"$link\" method=\"post\">\n";
	echo "         <p align=\"center\">";
	echo "            <input type=\"submit\" name=\"action\" value=\"Yes, delete title\" \>&nbsp; \n";
	echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
	echo "         </p>";
	echo "      </form>\n";
} else {
	log_event($LOG_LEVEL_ERROR, "admin/book/delete_title_confirm.php", 
			$LOG_DENIED_ACCESS, "Tried to delete book title $book.");
	$nextLink = dbfuncInt2String($_GET['next']);
	echo "      <p>You do not have the authority to remove this book type.  <a href=\"$nextLink\">" .
		 "Click here to continue</a>.</p>\n";
}

include "footer.php";
?>
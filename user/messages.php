<?php
/**
 * ***************************************************************
 * user/messages.php (c) 2006 Jonathan Dieter
 *
 * Show messages for user in folder
 * ***************************************************************
 */

/* Get variables */
if (isset($_GET['key'])) {
	$folder_index = dbfuncInt2String($_GET['key']);
}
$title = "Messages";

include "header.php";

/*
 * echo " <p><i>Folders:</i><br>\n";
 * // Get user's message folders
 * $query = "SELECT messagefolder.FolderName, messagestatus.NewStatus, " .
 * " COUNT(messagestatus.FolderIndex) AS NewCount, " .
 * " messagefolder.FolderIndex FROM " .
 * " messagefolder LEFT OUTER JOIN messagestatus ON " .
 * " (messagefolder.FolderIndex = messagestatus.FolderIndex " .
 * " AND messagestatus.NewStatus = 1) " .
 * "WHERE messagefolder.Username=\"$username\" " .
 * "GROUP BY messagestatus.FolderIndex";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
 * if($res->numRows() == 0) {
 * $query = "INSERT INTO messagefolder (Username, FolderName) " .
 * " VALUES (\"$username\", \"Inbox\")";
 * $res =& $db->query($query);
 * $query = "SELECT messagefolder.FolderName, messagestatus.NewStatus, " .
 * " COUNT(messagestatus.FolderIndex) AS NewCount, " .
 * " messagefolder.FolderIndex FROM " .
 * " messagefolder LEFT OUTER JOIN messagestatus ON " .
 * " (messagefolder.FolderIndex = messagestatus.FolderIndex " .
 * " AND messagestatus.NewStatus = 1) " .
 * "WHERE messagefolder.Username=\"$username\" " .
 * "GROUP BY messagestatus.FolderIndex";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo());
 * }
 * while($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
 * if(!isset($folder_index) && $row['FolderName'] == "Inbox") {
 * $folder_index = $row['FolderIndex'];
 * }
 *
 * if(is_null($row['NewStatus'])) {
 * echo " {$row['FolderName']} (0)<br>\n";
 * } else {
 * echo " <b>{$row['FolderName']} ({$row['NewCount']})</b><br>\n";
 * }
 * }
 * echo " </p>\n";
 */

/* Get messages in current folder */
$query = "SELECT message.MessageSubject, message.MessageDest, " .
		 "       message.MessageTime, user.Title, user.FirstName, " .
		 "       user.Surname, user.Username, user.ActiveTeacher, " .
		 "       messagestatus.NewStatus " .
		 "       FROM message, user, messagestatus " .
		 
		// "WHERE messagestatus.FolderIndex=$folder_index " .
		"WHERE message.MessageIndex = messagestatus.MessageIndex " .
		 "AND   user.Username = message.Username " .
		 "ORDER BY message.MessageTime DESC";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());
echo "            <td align=\"center\">\n";
if ($res->numRows() > 0) {
	echo "     <table align=\"center\" border=\"1\">\n";
	echo "        <tr>\n";
	echo "           <th>From:</th>\n";
	echo "           <th>Subject:</th>\n";
	echo "           <th>Date and Time:</th>\n";
	echo "        </tr>\n";
	$alt_count = 0;
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$alt_count += 1;
		
		if ($alt_count % 2 == 0) {
			$alt = " class=\"alt\"";
		} else {
			$alt = " class=\"std\"";
		}
		
		echo "         <tr$alt>\n";
		$date = date(dbfuncGetDateFormat(), strtotime($row['MessageTime']));
		$time = date("g:iA", strtotime($row['MessageTime']));
		
		if ($row['ActiveTeacher'] == 1) {
			$name = "{$row['Title']} {$row['FirstName']} {$row['Surname']}";
		} else {
			$name = "{$row['FirstName']} {$row['Surname']}";
		}
		if ($row['NewStatus'] == 1) {
			echo "            <td><b>$name</b></td>\n";
			echo "            <td><b>{$row['MessageSubject']}</b></td>\n";
			echo "            <td><b>$date - $time</b></td>\n";
		} else {
			echo "            <td>$name</td>\n";
			echo "            <td>{$row['MessageSubject']}</td>\n";
			echo "            <td>$date - $time</td>\n";
		}
		echo "         </tr>\n";
	}
	echo "     </table>\n";
} else {
	echo "     <p><i>No messages</i></p>\n";
}

include "footer.php";
?>
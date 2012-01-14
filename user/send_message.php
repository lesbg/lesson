<?php
	/*****************************************************************
	 * user/send_message.php  (c) 2006 Jonathan Dieter
	 *
	 * Send message
	 *****************************************************************/

	 /* Get variables */
	$to_index = $db->escapeSimple(dbfuncInt2String($_GET['key']));
	$to       = $db->escapeSimple(dbfuncInt2String($_GET['keyname']));
	$to_type  = $db->escapeSimple(dbfuncInt2String($_GET['key2']));
	$next     = $db->escapeSimple(dbfuncInt2String($_GET['next']));

	$title    = "Sending message...";
	$noHeaderLinks = true;
	$noJS          = true;

	include "header.php";

	$sent = false;
	$message  = $db->escapeSimple($_POST['message']);
	$subject  = $db->escapeSimple($_POST['subject']);

	/* Put message into message table */
	$query = 	"INSERT INTO message (Username, MessageSubject, MessageTime, Message) " .
				"       VALUES (\"$username\", \"$subject\", NOW(), \"$message\")";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());
	$query =	"SELECT LAST_INSERT_ID() AS MessageIndex";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC) and $row['MessageIndex'] != 0) {
		$message_index = $row['MessageIndex'];
	} else {
		echo "Error creating message";
		exit(1);
	}
	
	/* Put destination is a user, put in their inbox */
	if($to_type == $MSG_TYPE_USERNAME) {
		$query =	"SELECT FolderIndex FROM messagefolder " .
					"WHERE  Username = \"$to_index\" " .
					"AND    FolderName = \"Inbox\"";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());
		if($res->numRows() == 0) {
			$query =	"INSERT INTO messagefolder (Username, FolderName) " .
						"                VALUES    (\"$to_index\", \"Inbox\")";
			$res =&  $db->query($query);
			$query =	"SELECT FolderIndex FROM messagefolder " .
						"WHERE  Username = \"$to_index\" " .
						"AND    FolderName = \"Inbox\"";
			$res =&  $db->query($query);
			if(DB::isError($res)) die($res->getDebugInfo());
		}
		if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
			$folder_index = $row['FolderIndex'];
		} else {
			echo "Unable to create Inbox for $to.";
			exit(1);
		}
		$query =	"INSERT INTO messagestatus (MessageIndex, FolderIndex) " .
					"       VALUES             ($message_index, $folder_index)";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());
		$query =	"UPDATE message SET MessageDest=\"$to\" WHERE MessageIndex=$message_index";
		$res =&  $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());
		?>
		<p>Message sent to <?=$to?>.</p>
		<p><a href='<?=$next?>'>Click here to continue</a></p>
		<?php
	}

	include "footer.php";
?>
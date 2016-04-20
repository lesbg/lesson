<?php
/**
 * ***************************************************************
 * user/new_message.php (c) 2016 Jonathan Dieter
 *
 * Connect to Telegram bot
 * ***************************************************************
 */

$title = "Send message";

include "header.php"; // Show header

$db = & dbfuncConnectMaster(); // Connect to database and store in $db

/* Delete all old validation hashes */
$query = "DELETE FROM api_validate WHERE ValidUntil < NOW() ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

/* Generate new hashes */
$hash = bin2hex(openssl_random_pseudo_bytes(32));

$username=safe($username);

/* Delete all validation hashes for current user */
$query = "DELETE FROM api_validate WHERE Username='$username'";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

/* Add new hash for current user */
$query =	"INSERT INTO api_validate (Username, Hash, ValidUntil) " .
			"    VALUES ('$username', '$hash', ADDTIME(NOW(), '1:00'))";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

echo "<p><a href='https://telegram.me/lesworkbot?start=$hash'>Connect to Telegram</a></p>\n";

include "footer.php";

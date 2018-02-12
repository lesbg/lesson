<?php
/**
 * ***************************************************************
 * user/telegram.php (c) 2016, 2018 Jonathan Dieter
 *
 * Connect to Telegram bot
 * ***************************************************************
 */

$title = "Telegram";

include "header.php"; // Show header

$pdb = & dbfuncConnectMaster(); // Connect to database and store in $db

$pdb->prepare(
    "DELETE FROM api_validate WHERE ValidUntil < NOW() "
)->execute();

$query = $pdb->prepare(
    "SELECT Hash FROM api_validate WHERE Username=:username"
);
$query->execute(['username' => $username]);
$row = $query->fetch();

if ($row) {
    $hash = $row['Hash'];
} else {
    /* Generate new hashes */
    $hash = bin2hex(openssl_random_pseudo_bytes(32));

    /* Add new hash for current user */
    $pdb->prepare(
        "INSERT INTO api_validate (Username, Hash, ValidUntil) " .
        "    VALUES (:username, :hash, ADDTIME(NOW(), '1:00'))"
    )->execute(['username' => $username, 'hash' => $hash]);
}

echo "<p><a href='https://telegram.me/lesworkbot?start=$hash'>Connect to Telegram</a></p>\n";

include "footer.php";

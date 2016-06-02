<?php
/**
 * ***************************************************************
 * user/login_action.php (c) 2015 Jonathan Dieter
 *
 * Validate login
 * ***************************************************************
 */

if(!isset($_POST['username']) || !isset($_POST['password'])) {
	$error = True;
	
	include "user/login.php";
	exit(0);
}

$username = safe($_POST['username']);

$query = "SELECT Username, Password, Password2 FROM user " .
		 "WHERE Username = '$username' ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query

if (! $row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$error = True;
	
	$_SESSION['failcount'] += 1;
	
	log_event($LOG_LEVEL_ACCESS, "user/login_action.php",
		$LOG_ERROR,
		"Failed login (Unknown username).",
		0);
	
	include "user/login.php";
	exit(0);
}

/* Set username to canonical username */
$_POST['username'] = $row['Username'];

$good_pw = False;
if(password_verify($_POST['password'], $row['Password'])) {
	$good_pw = True;
	$_SESSION['password_number'] = 1;
} elseif(password_verify($_POST['password'], $row['Password2'])) {
	$good_pw = True;
	$_SESSION['password_number'] = 2;
} elseif(md5($_POST['password']) == $row['Password'] or md5($_POST['password']) == $row['Password2']) {
	$good_pw = True;
	if(md5($_POST['password']) == $row['Password']) {
		$_SESSION['password_number'] = 1;
		$pnum = "primary";
		$pval = "Password";
	} else {
		$_SESSION['password_number'] = 2;
		$pnum = "secondary";
		$pval = "Password2";
	}
	$phash = password_hash($_POST['password'], PASSWORD_DEFAULT, ['cost' => "15"]);
	$query = "UPDATE user SET $pval='$phash' WHERE Username='$username'";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	log_event($LOG_LEVEL_ACCESS, "user/login_action.php",
				$LOG_LOGIN,
				"Converted $pnum password from MD5 to salted Blowfish.",
				0);
}

if(!$good_pw) {
	$error = True;
	
	$_SESSION['failcount'] = 0;
	
	log_event($LOG_LEVEL_ACCESS, "user/login_action.php",
				$LOG_ERROR,
				"Failed login (Invalid password).",
				0);
	
	include "user/login.php";
	exit(0);
}

if($_POST['password'] == $_POST['username']) {
	$_SESSION['samepass'] = True;
	$change_pw = True;
}

if($_POST['password'] == "p{$_POST['username']}") {
	$_SESSION['samepass2'] = True;
	$_SESSION['samepass'] = True;
	$change_pw = True;
}

$_SESSION['username'] = safe($_POST['username']);
unset($_POST['password']);
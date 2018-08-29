<?php
/**
 * ***************************************************************
 * user/login_action.php (c) 2015, 2018 Jonathan Dieter
 *
 * Validate login
 * ***************************************************************
 */

if(!isset($_POST['username']) || !isset($_POST['password'])) {
    $error = True;

    include "user/login.php";
    exit(0);
}

$username = $_POST['username'];

$query = $pdb->query("SELECT YearIndex FROM currentinfo");
$row = $query->fetch();
$currentyear = $row['YearIndex'];

$query = $pdb->prepare(
    "SELECT Username, OriginalPassword, Password, Password2 FROM user " .
    "WHERE Username = :username "
);
$query->execute(['username' => $username]);
$row = $query->fetch();
if (!$row) {
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
$username = $_POST['username'];

$ldap = ldap_connect($LDAP_URI) or die("Unable to connect to $LDAP_URI");
ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
ldap_start_tls($ldap) or die("Unable to use TLS when connecting to $LDAP_URI");
$bind = @ldap_bind($ldap, "${LDAP_RDN}$username,${LDAP_SEARCH}", $_POST['password']);

$good_pw = False;

if ($bind) {
    $good_pw = True;
    $_SESSION['password_number'] = 3;
} else {
    echo "Unable to bind";
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
        $pdb->prepare(
            "UPDATE user SET $pval=:phash WHERE Username=:username"
        )->execute(['phash' => $phash, 'username' => $username]);

        log_event($LOG_LEVEL_ACCESS, "user/login_action.php",
                    $LOG_LOGIN,
                    "Converted $pnum password from MD5 to salted Blowfish.",
                    0);
    }
}

if(!$good_pw) {
    $error = True;
    $error_message = "Incorrect username or password.  Please try again!";

    $_SESSION['failcount'] = 0;

    log_event($LOG_LEVEL_ACCESS, "user/login_action.php",
                $LOG_ERROR,
                "Failed login (Invalid password).",
                0);

    include "user/login.php";
    exit(0);
}

if(check_group("disabled-$currentyear", $username)) {
    $error = True;
    $error_message = "Your account has been disabled.  Please contact the school " .
                     "administration for further information.";
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

if($_POST['password'] == $row['OriginalPassword']) {
    $_SESSION['samepass3'] = True;
    $_SESSION['samepass'] = True;
    $change_pw = True;
}

$_SESSION['username'] = safe($_POST['username']);
unset($_POST['password']);

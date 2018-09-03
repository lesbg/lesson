<?php
/**
 * ***************************************************************
 * user/changepassword.php (c) 2005, 2018 Jonathan Dieter
 *
 * Show form for user to change password.
 * ***************************************************************
 */

/* Get variables */
$title = "Change Password";
$link = "index.php?location=" . dbfuncString2Int("user/dochangepassword.php") .
         "&amp;next=" .
         dbfuncString2Int(
                        "index.php?location=" .
                         dbfuncString2Int("user/main.php"));

$query = $pdb->prepare(
    "SELECT user.FirstName, user.Surname, user.OriginalPassword FROM user " .
    "WHERE user.Username = :username"
);
$query->execute(['username' => $username ]);

include "header.php"; // Show header

include "user/wordlist.php";

$row = $query->fetch();
if ($row) {
    if (isset($error) and $error) {
        echo "      <p align='center' class='error'>You cannot choose your username as a password!</p>\n";
    }
    if (isset($samepass) and $samepass) {
        echo "      <p align='center' class='error'>Your password has expired.  You must change your password now.</p>\n";
        $pass = $_POST['password'];
    } else {
        $pass = "";
    }

    $suggestion = generate_password(4, $words);

    echo "      <form action='$link' method='post'>\n"; // Form method
    echo "         <table class='transparent' align='center'>\n";
    echo "            <tr>\n";
    echo "               <td>Old password:</td>\n";
    echo "               <td><input type='password' name='old' size=50 value='$pass'></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Password suggestion:<br>(Feel free to ignore)</td>\n";
    echo "               <td><strong>$suggestion</strong></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>New password:</td>\n";
    echo "               <td><input type='password' name='new' size=50></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td>Confirm new password:</td>\n";
    echo "               <td><input type='password' name='confirmnew' size=50></td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    echo "         <p></p>\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Ok' \>&nbsp; \n";
    echo "            <input type='submit' name='action' value='Cancel' \>&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>";
} else { // User isn't authorized to view or change scores.
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>

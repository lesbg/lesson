<?php
/**
 * ***************************************************************
 * header.php (c) 2004, 2005, 2016 Jonathan Dieter
 *
 * Sets title of page and prints header text. This can be changed
 * to whatever is appropriate for your school.
 * ***************************************************************
 */

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>\n";
echo "<html>\n";
echo "    <head>\n";
echo "        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>\n";

if (isset($extraMeta)) {
    echo $extraMeta; // Print any extra metatags.
}

echo "        <title>$title</title>\n";

if (! isset($noJS) || ! $noJS) {
    echo "        <script language='JavaScript' type='text/javascript' src='js/main.js'></script>\n";
    if (isset($extra_js)) {
        echo "        <script language='JavaScript' type='text/javascript' src='js/$extra_js'></script>\n";
    }
}

/* Link in CSS stylesheets */
echo "        <link rel='StyleSheet' href='css/standard.css' title='LES' type='text/css' media='screen'>\n";

if (isset($use_extra_css) && $use_extra_css == true) {
    echo "        <link rel='StyleSheet' href='css/standard.css' title='Regular' type='text/css' media='screen'>\n";
    echo "        <link rel='StyleSheet' href='css/standard-agenda.css' title='Agenda' type='text/css' media='screen'>\n";
    echo "        <link rel='StyleSheet' href='css/standard-hidden.css' title='Hidden' type='text/css' media='screen'>\n";
}
echo "        <link rel='StyleSheet' href='css/print.css' title='Printable colors' type='text/css' media='print'>\n";
echo "        <link rel='Alternate StyleSheet' href='css/basic.css' title='Basic' type='text/css' media='screen'>\n";
echo "    </head>\n";

if (isset($bodyClass)) {
    $alt = " class='$bodyClass'";
} else {
    $alt = "";
}

echo "    <body $alt>\n";
if (! isset($noHeaderLinks) || ! $noHeaderLinks) {
    if (! isset($homebutton)) {
        $homelink = "index.php?location=" . dbfuncString2Int("user/main.php");
        $homebutton = dbfuncGetButton($homelink, 'Home', 'small', 'home');
    }
    echo "        <table class='transparent' width='100%'>\n";
    echo "            <tr>\n";
    echo "                <td width='200px' class='logo'><img height='50' width='200' alt='LESSON Logo' src='images/lesson_logo_small.png'></td>\n";
    echo "                <td class='title'>$title";
    if (isset($subtitle)) {
        echo "<span class='subtitle'><br>$subtitle</span>";
    }
    echo "</td>\n";
    echo "                <td width='200px' class='home'>$homebutton</td>\n";
    echo "            </tr>\n";
    echo "        </table>\n";
}

$here = "index.php?{$_SERVER['QUERY_STRING']}";

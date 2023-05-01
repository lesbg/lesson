<?php
/**
 * ***************************************************************
 * admin/user/choose_user_action.php (c) 2016 Jonathan Dieter
 *
 * Select user
 * ***************************************************************
 */

if(isset($_POST['action']) and $_POST['action'] == "Add" and isset($_POST['uname']) and $_POST['uname'] != "") {
	if(!isset($_SESSION['post_family'])) {
		$_SESSION['post_family'] = array();
	}
	if(!isset($_SESSION['post_family']['uname'])) {
		$_SESSION['post_family']['uname'] = array();
	}
	$_SESSION['post_family']['uname'][] = array($_POST['uname'], 0);
}

unset($_POST);
$_POST = array();

/* Get variables */
$nextLink = parse_url(dbfuncInt2String($_GET['next']))['query']; // Link to next page
parse_str($nextLink, $temp);
$_GET['next'] = $temp['next'];

include "admin/family/modify.php";
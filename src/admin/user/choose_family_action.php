<?php
/**
 * ***************************************************************
 * admin/user/choose_family_action.php (c) 2015-2016 Jonathan Dieter
 *
 * Select family
 * ***************************************************************
 */

if(isset($_POST['action']) and $_POST['action'] == "Add" and isset($_POST['fcode']) and $_POST['fcode'] != "") {
	if(!isset($_SESSION['post'])) {
		$_SESSION['post'] = array();
	}
	if(!isset($_SESSION['post']['fcode'])) {
		$_SESSION['post']['fcode'] = array();
	}
	$_SESSION['post']['fcode'][] = array($_POST['fcode'], 0);
}

unset($_POST);
$_POST = array();

/* Get variables */
$nextLink = parse_url(dbfuncInt2String($_GET['next']))['query']; // Link to next page
parse_str($nextLink, $temp);
$_GET['next'] = $temp['next'];

include "admin/user/modify.php";
<?php
/**
 * ***************************************************************
 * admin/user/choose_family_action.php (c) 2015 Jonathan Dieter
 *
 * Select family
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

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

include "admin/user/modify.php";
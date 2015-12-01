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

foreach($_POST as $key => $value) {
	if(substr($key, 0, 7) == "select-") {
		$fadd = substr($key, 7);
		if(strlen($fadd) > 0 && $value="+") {
			if(!isset($_SESSION['post'])) {
				$_SESSION['post'] = array();
			}
			if(!isset($_SESSION['post']['fcode'])) {
				$_SESSION['post']['fcode'] = array();
			}
			$_SESSION['post']['fcode'][] = array($fadd, 0);
		}
	}
}

include "admin/user/modify.php";
<?php
defined('TYPO3_MODE') or die();

/** @uses \Sto\LogoutInfo\LoggerHooks::logoffPreProcessing() */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing']['tx_logout_info'] =
    \Sto\LogoutInfo\LoggerHooks::class . '->logoffPreProcessing';

/** @uses \Sto\LogoutInfo\LoggerHooks::postUserLookUp() */
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp']['tx_logout_info'] =
    \Sto\LogoutInfo\LoggerHooks::class . '->postUserLookUp';

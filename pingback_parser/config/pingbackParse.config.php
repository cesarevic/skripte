<?php
/**
 * Framework aplication configuration file
 * Copyright (c) 2005-2010 PKM Inc.
 */
return array(
	'pingbackParse' => array (
		'scriptMaxRunningTime'	=> 13*60*60, //12*60*60,
		'scriptSleepTime'		=> 5,
		'writeStackSize'		=> 50,
		'maxParseErrorCount'	=> 3,
	),
	'blogResource' => array (
		'lockTime' 				=> 900,
		'resultReturnCount'		=> 10,
		
	)
);

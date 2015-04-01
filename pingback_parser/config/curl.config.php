<?php
/**
 * Configuration file
 * Copyright (c) 2005-2009 ViewSource
 */
return array(
	'IPv4IPs' 			=> include(CONFIG_PATH . 	 "ip.config.php"),
	'shuffleIPv4' 		=> true,
	'userAgents'		=> include(FRAMEWORK_PATH . "lib/curl/useragents.php"),
	'shuffleUserAgents' => true,
	
	'CURLOPT_NOBODY' 			=> true,
	'CURLOPT_CONNECTTIMEOUT' 	=> 2,
	'CURLOPT_TIMEOUT' 			=> 4
);
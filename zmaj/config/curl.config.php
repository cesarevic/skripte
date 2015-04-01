<?php
/**
 * Configuration file
 * Copyright (c) 2005-2009 ViewSource
 */
return array(
	'ips' 		 => include(CONFIG_PATH . "ip.config.php"),
	'userAgents' => include(FWK_PATH . "lib/curl/useragents.php"),
);
#!/usr/bin/php
<?php

//include('/usr/lib/framework/0.3.3/Framework.php');
include('/usr/lib/framework/trunk/Framework.php');

define('SCRIPT_PATH', realpath(dirname(__FILE__)) . '/');
define('CONFIG_PATH', SCRIPT_PATH . 'config/');
define('LIB_PATH',    SCRIPT_PATH . 'lib/');

// Error Reporting /////////////////////////////////////////////////////////////////////////////////////////////////

error_reporting(E_ALL);
Framework::enableErrorToStandardError();
Framework::disableErrorToStandardOutput();
Framework::disableErrorToSystem();	

// AutoLoader //////////////////////////////////////////////////////////////////////////////////////////////////////////

AutoLoader::registerClass('cPingbackParse',    LIB_PATH . 'cPingbackParse.php');
AutoLoader::registerClass('cBlogResource',     LIB_PATH . 'cBlogResource.php');
AutoLoader::registerClass('cPingbackResource', LIB_PATH . 'cPingbackResource.php');
AutoLoader::registerClass('cLogWriterToFile',  LIB_PATH . 'cLogWriterToFile.php');

// Add object dependencies, configure lazy objects /////////////////////////////////////////////////////////////////////

Registry::configObject('oLogger', 'cLogger')
	->method('addWriter')->setComponent('oLogWriter');
Registry::configObject('oLogWriter', 'cLogWriterToFile');

Registry::configObject('oDb', 'cDb')
	->setPropertiesFromFile(CONFIG_PATH . 'db.config.php')
//	->method('attachObserver')
//		->setComponent('oLogger')
	;
Registry::configObject('oPingbackDb', 'cDb')
	->setPropertiesFromFile(CONFIG_PATH . 'dbPingback.config.php')
//	->method('attachObserver')
//		->setComponent('oLogger')
	;
		
Registry::configObject('oCurl', 'cCurlShuffler')
	->setPropertiesFromFile(CONFIG_PATH . 'curl.config.php');

Registry::configObject('oBlogResource', 'cBlogResource')
	->setComponent("oDb")
	->setPropertiesFromFile(CONFIG_PATH . 'pingbackParse.config.php', 'blogResource');	
	
Registry::configObject('oPingbackResource', 'cPingbackResource')
	->setComponent("oPingbackDb");
	
Registry::configObject('oPingbackParse', 'cPingbackParse')
	->setComponent("oBlogResource")
	->setComponent("oPingbackResource")
	->setComponent("oCurl")
	->setPropertiesFromFile(CONFIG_PATH . 'pingbackParse.config.php', 'pingbackParse');

// Run Script  /////////////////////////////////////////////////////////////////////////////////////////////////////////
	
Registry::getObject("oPingbackParse")->run();

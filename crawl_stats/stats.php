#!/usr/bin/php
<?php

include('/usr/lib/framework/0.3.5/Framework.php');
//include('/usr/lib/framework/trunk/Framework.php');

// Constants ///////////////////////////////////////////////////////////////////////////////////////////////////////

define('SCRIPT_PATH', realpath(dirname(__FILE__)) . '/');
define('CONFIG_PATH', SCRIPT_PATH . 'config/');
define('LIB_PATH',    SCRIPT_PATH . 'lib/');

// Error Reporting /////////////////////////////////////////////////////////////////////////////////////////////////

error_reporting(E_ALL);
Framework::enableErrorToStandardError();
Framework::disableErrorToStandardOutput();
Framework::disableErrorToSystem();	

// AutoLoader //////////////////////////////////////////////////////////////////////////////////////////////////////

AutoLoader::registerClass('cStats',            LIB_PATH . 'cStats.php');
AutoLoader::registerClass('cCSVLogResource',   LIB_PATH . 'cCSVLogResource.php');
AutoLoader::registerClass('cDBLogResource',    LIB_PATH . 'cDBLogResource.php');
AutoLoader::registerClass('cLogWriterToFile',  LIB_PATH . 'cLogWriterToFile.php');
AutoLoader::registerClass('cCSVParser',  	   LIB_PATH . 'cCSVParser.php');
AutoLoader::registerClass('cStatsResource',    LIB_PATH . 'cStatsResource.php');
AutoLoader::registerClass('cStatsPrint',       LIB_PATH . 'cStatsPrint.php');
AutoLoader::registerClass('cConsoleTable',     LIB_PATH . 'cConsoleTable.php');

// Add object dependencies, configure lazy objects /////////////////////////////////////////////////////////////////

Registry::configObject('oStats', 'cStats')
	->setComponent('oCSVLogResource')
	->setComponent('oDBLogResource')
	->setComponent('oStatsResource')
	->setPropertiesFromFile(CONFIG_PATH . 'stats.config.php');

Registry::configObject('oDb', 'cDb')
	->setPropertiesFromFile(CONFIG_PATH . 'db.config.php')
//	->method('attachObserver')
//		->setComponent('oLogger')
	;
	
Registry::configObject('oCSVLogResource', 'cCSVLogResource')
	->setComponent('oCSVParser');

Registry::configObject('oDBLogResource', 'cDBLogResource')
	->setComponent('oDb');

Registry::configObject('oStatsResource', 'cStatsResource')
	->setComponent('oDb');
	
Registry::configObject('oCSVParser', 'cCSVParser');	

Registry::configObject('oLogger', 'cLogger')
	->method('addWriter')->setComponent('oLogWriter');
Registry::configObject('oLogWriter', 'cLogWriterToFile');

Registry::configObject('oConsoleTable', 'cConsoleTable');

Registry::configObject('oStatsPrint', 'cStatsPrint')
	->setComponent('oDb')	
	->setComponent('oConsoleTable')
	->setComponent('oCSVParser');	


// Run Script  /////////////////////////////////////////////////////////////////////////////////////////////////////
	
if(Request::cli("r"))
	Registry::getObject("oStats")->run();
else 
	Registry::getObject("oStatsPrint")->run();

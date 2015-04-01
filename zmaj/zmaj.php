#!/usr/bin/php
<?php
/**
 * Cli Application
 * Copyright (c) 2005-2012 ViewSource
 */

// System requirements /////////////////////////////////////////////////////////////////////////////////////////////////
// Replace the path with your path to Framework.php

//include ('../framework/branches/0.3.6/Framework.php');
include ('../framework-0.3.6/Framework.php');

// Error handling & reporting //////////////////////////////////////////////////////////////////////////////////////////

//Report all php errors
error_reporting(E_ALL);

/** We log all errors to standard error output and disable printing of errors to standard output.
Usecase ./script_name.php > script_output.log 2> script_errors.log */ 

Framework::enableErrorToStandardError();
Framework::disableErrorToStandardOutput();

//Disable error loging to syslog (this is the default)
Framework::disableErrorToSystem();


// Constants ///////////////////////////////////////////////////////////////////////////////////////////////////////////

//Constants used to define the location of other components

define('SCRIPT_PATH', realpath(dirname(__FILE__)) . '/');
define('LIB_PATH',    SCRIPT_PATH . 'lib/');
define('CONFIG_PATH', SCRIPT_PATH . 'config/');
define('TEMP_PATH',   SCRIPT_PATH . 'temp/');
define('CTRL_PATH',   LIB_PATH .    'controller/');
define('MODEL_PATH',  LIB_PATH .    'model/');
define('LOG_PATH',    TEMP_PATH .   'logs/');

/** Autoloader, objasniti ili jos bolje linkovati ka nekom dokumentu koji objasnjava autoloader filozofiju? */

/** //////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

/** 
 * We notify the autoloader about our classes and the location of source code. 
 * Multiple classes can be contained in a single .php file, we ussually use
 * one file per class.   
 */

/** This is the help controler (usage guide) for the script */

//Ovo mora da se sredi nekako, helpcontroler je previse konfuzan, narocito ono stampanje
AutoLoader::registerClass('helpController',    CTRL_PATH . 'helpController.php');

//If the script is of a "single run" kind we use the "mainControler", in this case we will 
//use it to import some sitemap urls in to the database. 

AutoLoader::registerClass('mainController',    CTRL_PATH . 'mainController.php');

/** 
 * If we have a script that runs in a "daemon" style (waits for input, batch processing etc) 
 * we use the "loopController". This controler will do the sitemap ping.  
 */
AutoLoader::registerClass('loopController',    CTRL_PATH . 'loopController.php');


//This is used as an example of class inheritance and how to configure the framework so it can autoload
//class dependencies. class cDbModel extends cModelAbstract

//We define a database "Model" cDbModel and its parent cModelAbstract

AutoLoader::registerClass('cModelAbstract', MODEL_PATH . 'cModelAbstract.php');
AutoLoader::registerClass('cQueryModel',    MODEL_PATH . 'cQueryModel.php');
AutoLoader::registerClass('cCurlCache',     MODEL_PATH . 'cCurlCache.php');



AutoLoader::registerClass('cUrlModel',  MODEL_PATH . 'cUrlModel.php');
AutoLoader::registerClass('cParserModel',  MODEL_PATH . 'cParserModel.php');
AutoLoader::registerClass("cTidy", LIB_PATH . "cTidy.php");

//Ovo je pogresno
/*
 * Curl je isto kao i cDb, resurs je neko sranje koje radi sa curl-om, u ovom slucaju cCurlModel
 */

AutoLoader::registerClass('cCurlModel',		MODEL_PATH . 'cCurlModel.php');



AutoLoader::registerClass('cShuffler',		SCRIPT_PATH . 'shuffler.php');


//Ovo bi trebalo da bude jednostavno, autoloaderu se setuju imena klasa i njihove staze
//Help kontroler je definisan ali se nigde ne proziva ??
//cModelAbstract isto, kako objasniti ovu logiku ?? Objasnjeno gore, ko prvi put gleda bas je konfuzno. 



// Add class dependencies, configure classes ///////////////////////////////////////////////////////////////////////////


//metod mora da pocinje sa set, ostatak (lowercase) definise naziv resursa


/** Ovde se konfigurise main controler, preko 'magic metoda setSitesModel @see cControllerAbstract::__call() 
 *  upucava mu se komponenta oSitesModel */

Framework::configClass('mainController')->method('setQueryModel')->setComponent('oQueryModel');

/** 
 * Podesavanje kontrolera loopController
 * Array keys (and values) from loopController.config.php "automagicly" appear in 
 * loopController instance as object properties. 
 */

Framework::configClass('loopController')
			->setPropertiesFromFile(CONFIG_PATH . 'loopController.config.php')
			->method('setQueryModel')->setComponent('oQueryModel')
			->method('setUrlModel')->setComponent('oUrlModel')
			->method('setParserModel')->setComponent('oParserModel')
// 			->method('setCurl')->setComponent('oCurl')
// 			->method('setCurlMulti')->setComponent('oCurlMulti')
// 			->method('setCurlShuffler')->setComponent('oCurlShuffler')
			;

// Add object dependencies, configure lazy objects /////////////////////////////////////////////////////////////////////


/**
 * Konfiguracija objekata, sve sto se koristi u kontrolerima mora da ima Registry::configObject
 * za kontrolere nema potrebe. greska: "za kontrolere nema potrebe"
 */


/** 
 * Objasniti ovo bolje, tipa cDb je u frameworku i uvek je dostupan, mi ga ovde koristimo kao
 * konekciju na bazu ?? 
 */

/** Standard framework database object, db.config.php holds the database username, password etc. */

Registry::configObject('oDb', 'cDb')->setPropertiesFromFile(CONFIG_PATH . 'db.config.php');
	//->method('attachObserver')->setComponent('logger');

//Registry::configObject('oDb1', 'cDb')->setPropertiesFromFile(CONFIG_PATH . 'db1.config.php');

//Primeri za koniguraciju i koriscenje curl-a ??
Registry::configObject('oCurl', 'cCurl')->setPropertiesFromFile(CONFIG_PATH . 'curl.config.php');


Registry::configObject('oCurlMulti', 'cCurlMulti');
// Registry::configObject('oCurlShuffler', 'cCurlShuffler');


Registry::configObject('oCurlShuffler', 'cShuffler')->setPropertiesFromFile(CONFIG_PATH . 'curl.config.php');

/** 
 * Podesavamo resurs za bazu (oSitesModel) tako sto mu upucavamo oDb, za resurse ubacivanje
 * objekata idi kroz __construct resursa @see cSitesModel::__construct() tako da nema "magic" metoda kao kod kontrolera.
 */

Registry::configObject('oQueryModel', 'cQueryModel')->setComponent('oDb');
Registry::configObject('oCurlCache', 'cCurlCache')->setComponent('oDb');

Registry::configObject('oUrlModel', 'cUrlModel')->setPropertiesFromFile(CONFIG_PATH . 'url.config.php');


Registry::configObject('oTidy', 'cTidy')->setPropertiesFromFile(CONFIG_PATH . 'tidy.config.php');


/**
* Odakle oDb i cDb ovde, cDb je u framework-u pa je uvek dostupan?
* Cemu nam sluzi cModelAbstract? na prvi pogled deluje kao neki nevezan resurs, tek kada se udje u cDbModel
* vidi se da cDbModel nasledjuje cModelAbstract
*/

Registry::configObject('oCurlModel', 'cCurlModel')->setComponent('oCurlMulti')->setComponent('oCurlShuffler');

Registry::configObject('oParserModel', 'cParserModel');


//da li se negde koristi ime oApplication? Ovo je kao standard ili se moze nazvati bilo kojim imenom?
Registry::configObject('oApplication', 'cCliApplication');



/**
 *  Instanciranje rutera, i podesavanje ruta, naziv controlera je skracen, mainControler 
 *  se proziva samo sa main. Nije ocigledno promeniti ?? 
 *  Mozda da se dodaju svi parametri preko metoda kao ->addOption da bude ociglednije??
 *  Description u createRoute se koristi za help??
 *  ->addOption metod, prvi parametar je name, treba promeniti kao i u ruteru na nesto drugo?
 *  Opisi u addOption se koriste iz help kontrolera??
 *  cCliRoute::flag je sta ??? 
 */

/**
	kada ruta ima addoption to se testira u samom metodu u klasi

 */

/**
	cCliRoute::flag  odredjuje da li je parametar vrednost ili switch

 */


$oCliRouter = new cCliRouter();
$oCliRouter->createRoute('help', 'help', 'showHelp', 'Display help.')
	->addOption('version', 'v', 'Prints script version.', cCliRoute::flag);
$oCliRouter->createRoute('run', 'main', 'run', 'Runs a linear script.')
    ->addOption('import', 'i', "Import urls")
    ->addArgument('fajl', "File name")

;

$oCliRouter->setDefaultAction(new cAction('help', 'help'));




/**
 * Za ovo trebaju neki pametni primeri, kao i za helpControler kada se koristi switch -v
 * ne postoji mehanizam u samom kontroleru
 * sta su cCliCommandRoute::val ::flag itd, property iz controlera?
 * Ovo sluzi da se modifikuju parametri controlera pri pokretanju?
 * 
 * 
 * Sta je ->addArgument?? Argument za opciju? 
 * 
 */

$oCliRouter->createRoute('runloop', 'loop', 'run', 'Runs a linear script.');
  //->addOption('visina', 'y', 'Visina necega', cCliCommandRoute::val);
//  ->addOption('flag', 'f', 'Neki flag', cCliCommandRoute::flag)
//  ->addArgument('arg', 'Neki argument');


/*
 * Za loger, treba da se vidi u framework-u kako da se redirektuju sve greske u error_log
 */





/**
 * Sta je multiloger? Nema te klase
 * 
 * 
 */


// Logger
// $oMultiLogger = new cApplicationMultiLogger();
// Registry::setObject('logger', $oMultiLogger);
// $oMultiLogger->createLogger('file');

// $oLogWriter = new cLogWriterFile();
// $oLogWriter->setFilePath(LOG_PATH . 'error.log')
// 	->setFilter(cLogMessage::error|cLogMessage::warning);
// $oMultiLogger->getLogger('file')->addWriter($oLogWriter, 'error');

// $oLogWriter = new cLogWriterFile();
// $oLogWriter->setFilePath(LOG_PATH . 'debug.log')
// 	->setFilter(cLogMessage::info|cLogMessage::debug);
// $oMultiLogger->getLogger('file')->addWriter($oLogWriter, 'debug');

// $oMultiLogger->createLogger('memory');
// $oLogWriter = new cLogWriterMemory();
// $oMultiLogger->getLogger('memory')->addWriter($oLogWriter, 'all');



// Application
$oApp = Registry::getObject("oApplication");
$oApp->defaultController	= 'help';
$oApp->defaultAction    	= 'showHelp';
$oApp->name       		 	= 'Abstract CLI Application';
$oApp->description			= 'This is test CLI Application based on viewsource framework, and is only for test!';
$oApp->copyright  		 	= 'Copyright (c) 2005-2013 Viewsource';


/**
 * Kako radi loger na nivou cele aplikacije?
 * Kako se ubacuje loger u kontroler?
 *  
 */


$oApp->setRouter($oCliRouter);


// Run Script  /////////////////////////////////////////////////////////////////////////////////////////////////////


/**
 * Sta sa ovim ?
 */
// exception ovde moze da izleti od rutera, kontrolera, get action i tako ta sranja.... gde to treba da se privati ???
$oApp->run();







<?php
$start = microtime(true);
include ("../../framework/Framework.php");


// Create database object and configure it (add di if config is required)
$oDb = new cDb();

$oDb->username = "root";
$oDb->host = "192.168.1.30";
$oDb->charset = "latin1";

//$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_%'");
$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_3dsoftwares'");

foreach($aDatabases as $iNo => $dbName)
{
	$start1 = microtime(true);
//	print "Database #$iNo $dbName";
	$oDb->query("use $dbName");

	// Get dsg data
	$aDsgData = $oDb->fetchAllFromQuery("select keyword, se1, se2, se3 from Keywords");

	// Unserialize & struct & clean data
	$aStructDsgData['article'] = structData($aDsgData);

	// Build categories
	$aStructDsgData = BuildCategories($aStructDsgData);
	
	
	
	print "!";
}


/**
 * 
 * @param $aData
 * @param $iCategoryQuantity
 * @param $iMaxArticlePerCategory
 */
function BuildCategories(array $aData, $iCategoryQuantity = 10, $iMaxArticlePerCategory = 20)
{
	$aKwForCat = array();
	
	// Bild categories
	foreach($aData['article'] as $sArticleKw => $aVal)
	{
		$aArticleKwWords = explode(" ", $aVal['title']);
		foreach($aArticleKwWords as $sWord)
		{
			if(strlen($sWord) >= 2) // Only count words longer than
			{
				if(!isset($aKwForCat[$sWord]))
					$aKwForCat[$sWord] = 0;
				$aKwForCat[$sWord]++;
			}
		}
	}
	
	arsort($aKwForCat); // Sort an array in reverse order and maintain index association
	$aKwForCat = array_slice($aKwForCat, 0, $iCategoryQuantity, true);
	asort($aKwForCat); // Sort an array and maintain index association
	
	foreach($aKwForCat as $key => $val)
		$aKwForCat[$key] = 0;
		
	// Distribute categories to articles
	$aTmpCategory = array();
	
	// For propagation
	$iPropagator = 0;
	$aTmpKwForCatProp = array_keys($aKwForCat);
	
	foreach($aData['article'] as $sArticleCode => $aArticleData)
	{
		foreach($aKwForCat as $sCategory => $val)
		{
			if(strpos($sArticleCode, $sCategory) !== false &&  $aKwForCat[$sCategory] < $iMaxArticlePerCategory)
			{
				$aData['article'][$sArticleCode]['category'] = $sCategory;
				$aKwForCat[$sCategory]++;
				$aTmpCategory[$sCategory][] = $sArticleCode;
				break;
			}
		}
		
		// Set to last category if not assigned
		if(!isset($aData['article'][$sArticleCode]['category']))
		{
			$sCategoryToSet = $aTmpKwForCatProp[$iPropagator];
			
			$aData['article'][$sArticleCode]['category'] = $sCategoryToSet;
			$aKwForCat[$sCategoryToSet]++;
			$aTmpCategory[$sCategoryToSet][] = $sArticleCode;
			
			// Increse propagator, or reset
			$iPropagator++;
			if(!isset($aTmpKwForCatProp[$iPropagator]))
				$iPropagator = 0;
		}
	}
	
	// Loop through categories and add seconf word for category
	foreach($aTmpCategory as $sCategoryName => $aArticles)
	{
		$aKwForSubCat = array();
		foreach($aArticles as $sArticleCode)
		{
			$aArticleTitleWords = explode(" ", $aData['article'][$sArticleCode]['title']);
			foreach($aArticleTitleWords as $sWord)
			{
				if(strlen($sWord) >= 2 && $sWord !== $sCategoryName)
				{
					if(!isset($aKwForSubCat[$sWord]))
						$aKwForSubCat[$sWord] = 0;
					$aKwForSubCat[$sWord]++;
				}
			}
		}
		arsort($aKwForSubCat);
		$sSubKeyword = array_shift(array_flip($aKwForSubCat));
		
		$aTmp2Category["$sCategoryName $sSubKeyword"] = $aArticles;
	}
	$aTmpCategory = $aTmp2Category;
	
	// Add category code to articles, and add category to global array
	foreach($aTmpCategory as $sCategoryName => $aArticles)
	{
		$sCategoryCode = str_replace(" ", "-", $sCategoryName);

		// Add category code to article
		foreach($aData['article'] as $sArticleCode => $aArticleData)
			$aData['article'][$sArticleCode]['category'] = $sCategoryCode;
		
		$aData['category'][$sCategoryCode] = array(
			'title' => $sCategoryName,
			'keywords' => buildKws($sCategoryName)
		);
	}	
	
	return $aData;
}



/**
 * Unserialize data, clean results, create keywor
 * 
 * @param array $aData
 */
function structData(array $aData)
{
	$aReturnData = array();
	
	// Unserialize all articles
	foreach($aData as $aResult)
	{
		$sKeyword = $aResult['keyword'];
		$aSeResult = array();
		foreach(array(1,2,3) as $iSe)
		{
			$aSeUnserializedData = unserialize($aResult['se' . $iSe]);
			if(is_array($aSeUnserializedData))
			{
				$aSeResult = array_merge($aSeResult, $aSeUnserializedData);
				foreach ($aSeResult as $key => &$aSeData)
					foreach ($aSeData as &$aSeDataElement)
						if(!empty($aSeDataElement))
							true;//$aSeDataElement = cleanString($aSeDataElement);
			}
		}
		$sArticleCode = str_replace(" ", "-", $sKeyword);
		$aReturnData[$sArticleCode] = array(
			'se_results' => $aSeResult,
			'title' => $sKeyword, 
			'description' => "tralalal",
			'keywords' => buildKws($sKeyword)
		);
	}
		
	return $aReturnData;
}



function cleanString($sInput)
{
//	@todo Pogledati force i numeric(uses character entities instead of HTML entities) parametar, sta ako tidy ne uspe da vrati string ??

	$aTidyConfig = array(
	'clean' 					  => true, 
//	'output-xhtml' 				  => true,
	'output-xml' 				  => true,
	'show-body-only' 			  => true,
	'drop-proprietary-attributes' => true, 
	"drop-font-tags"			  => true, 
	"drop-empty-paras" 			  => true, 
	"hide-comments" 			  => true, 
	'word-2000' 			      => true, 
	"join-classes" 				  => true, 
	"join-styles" 				  => true,
//	"add-xml-space" => true,
	"enclose-text"=> true,
	'doctype' => 'strict',
	'logical-emphasis' => true,
	'quote-nbsp' => true,
	'numeric-entities'  => true,
    ); 
    
	$tidy = new tidy();
	$sTidyString = $tidy->repairString($sInput,$aTidyConfig,'utf8');
/*
	preg_match_all('/^(?:line (\d+) column (\d+) - )?(\S+): (?:\[((?:\d+\.?){4})]:)
	?(.*?)$/m', $tidy->errorBuffer, $tidy_errors, PREG_SET_ORDER);
	print_r($tidy_errors);
*/	

//	echo $sTidyString . "\n"; 
	
	
	$oXmlString = string2Xml($sTidyString);

	$oXml = $oXmlString->ownerDocument;
	$oXml->appendChild($oXmlString);
	
	$oXpath = new DOMXPath($oXml);
	
	$aQueryList = array('//script', '//style');
	
	$aE2Remove = array();
	
	foreach ($aQueryList as $sQuery)
	{
		$entries = $oXpath->query($sQuery);
		foreach($entries as $entry)
			$aE2Remove[] = $entry;
	}
	
	foreach($aE2Remove as $element)
		$element->parentNode->removeChild($element);
	
	$oBody = $oXml->getElementsByTagName("body")->item(0);
	$sResult = $oBody->textContent; 


	$aBadStrings = array("�","\n","\r");
	$sResult = str_replace($aBadStrings,' ',$sResult);

	$sResult = trim($sResult);

	if(substr($sResult, -3) == "...")
		$sResult = substr($sResult, 0, -3);
	if(substr($sResult, 0, 3) == "...")
		$sResult = substr($sResult, 3);				

	while(strpos($sResult, "  " ) !== false)
		$sResult = str_replace("  ", " ", $sResult);
	
	$sResult = trim($sResult);

	$sResult = ucfirst($sResult);
	
	return $sResult;
}


function string2Xml($sXml, $srootNode = 'root')
{
	$oXmlRoot = createElement($srootNode);	
	
	$oXmlImport = $oXmlRoot->ownerDocument->createDocumentFragment();
	$oXmlImport->appendXML($sXml);
	
	$oXmlRoot->appendChild($oXmlImport);

//	return result is DOMElement
	return $oXmlRoot;

/*
	echo $oXml->saveXML($oXml->documentElement);
	exit();
	
*/
}

function createElement($sName, $sValue = NULL)
{
	$oXml = new DomDocument('1.0', 'utf8'); 
	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;
	
	$oElement = $oXml->createElement($sName);
	if (!is_null($sValue))
	{
		$oElementText = $oXml->createTextNode($sValue);
		$oElement->appendChild($oElementText);
	}
	return $oElement; 
}

function buildKws($sKw)
{
	$sKeyword = "";
	$aKeywords = explode(" ", $sKw);
	while(!empty($aKeywords))
	{
		$sKeyword .= (implode(" ", $aKeywords) . ", ");
		array_pop($aKeywords);
	}
	$sKeyword = substr($sKeyword, 0, -2);

	return $sKeyword;
}



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function xxxDsgArray($oResultSet)
{
	$aDsgArray = array();
	
	while($aResults = $oResultSet->fetch_array(MYSQLI_ASSOC))
	{
		$sKeyword = $aResults['keyword'];
		$result = array();
		
		$aSe1Data = unserialize($aResults['se1']);
		$aSe2Data = unserialize($aResults['se2']);
		$aSe3Data = unserialize($aResults['se2']);

		if(is_array($aSe1Data))
			$result = array_merge($result, $aSe1Data);
		if(is_array($aSe2Data))
			$result = array_merge($result, $aSe2Data);
		if(is_array($aSe3Data))
			$result = array_merge($result, $aSe3Data);
		
		if(!empty($result))
			$aDsgArray[$sKeyword] = $result; 
		else
			print "...empty Dsg data for keyword: $sKeyword !!!";

		unset($result);
		unset($sKeyword);
	}

	foreach($aDsgArray as $sKeyword => &$aValue)
	{
		foreach($aValue as $key => &$val)
		{
			$val['description'] = cleanString($val['description']);
			$val['title'] = cleanString($val['title']);
			$val['url'] = cleanString($val['url']);
		}
	}
	
	
	
	
	
	
	
	
	
	
	return $aDsgArray;
}



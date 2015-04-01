<?php
$start = microtime(true);
include ("../../../framework/Framework.php");


// Create database object and configure it (add di if config is required)
$oDb = new cDb();

$oDb->username = "root";
$oDb->host = "192.168.1.30";
$oDb->charset = "latin1";

//$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_%'");
//$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_4wheelers'");
//$aDatabases = array ("sitegen_estatesales", "sitegen_3dsoftwares", "sitegen_12v");

$sConfigDir = "config";
$aDatabases = loadConfigs($sConfigDir);


foreach($aDatabases as $iNo => $sSiteName)
{
	$dbName = "sitegen_" . str_replace(" ", "", $sSiteName);
	$start1 = microtime(true);
	print "Database #$iNo $dbName";
	$oDb->query("use $dbName");

	// Get dsg data
	$aDsgData = $oDb->fetchAllFromQuery("select keyword, se1, se2, se3 from Keywords");

	// Unserialize & struct & clean data
	$aStructDsgData['article'] = structData($aDsgData);

	// Build categories
	$aStructDsgData = BuildCategories($aStructDsgData);

	$aDData = buildDArray($aStructDsgData);
	unset($aStructDsgData);
	
	buildXML($aDData, "../data/$dbName.xml", $sSiteName);
	unset($aDData);
	echo " Time: " . round((microtime(true) - $start1),3) . " Memory: " . number_format(memory_get_usage()) . "\n";
}

echo "Total Time: " . round((microtime(true) - $start),3) . " Memory: " . number_format(memory_get_usage()) . "\n";






/**
 * Vraca niz sa nazivima sajtova
 * @param string $sConfigPath
 */
function loadConfigs($sConfigPath)
{
	$aFileArray = cFileHelper::dirToArray($sConfigPath, true, false, false, true);

	foreach($aFileArray as $sFile)
	{
		$sFileName =  $sFile . "config.php";
		$sContent = file_get_contents($sFileName);
	
		preg_match('/\$site_keyword = "(.+?)";/', $sContent, $regs);
		$SKeyword = $regs[1];
	//	$sDbName = str_replace(" ", "", $SKeyword); 
		$aConfig[] = $SKeyword;
	}
	return 	$aConfig;
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
		foreach($aArticles as $sArticleCode)
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
	foreach($aData as $sKeyData => $aResult)
	{
		$sKeyword = $aResult['keyword'];
		
		// If keyword is longer than 60 chars skip
		if(strlen($sKeyword) > 60)
			continue;
		
		$sArticleCode = str_replace(" ", "-", $sKeyword);
		$aSeResult = array();
		foreach(array(1,2,3) as $iSe)
		{
			$aSeUnserializedData = unserialize($aResult['se' . $iSe]);
			if(is_array($aSeUnserializedData))
			{
				$aSeResult = array_merge($aSeResult, $aSeUnserializedData);
				foreach ($aSeResult as $key => &$aSeData)
				{
					try
					{
						$aSeData['title'] = normalizeString(cleanString($aSeData['title']));
						$aSeData['description'] = normalizeString(cleanString($aSeData['description']));
						$aSeData['url'] = normalizeString(cleanString($aSeData['url']), false);
					}
					catch(Exception $e)
					{
						print ("\n" . $e->getMessage() . "\n");
						print_r($aSeData);
						$sArray = cArrayHelper::arrayToPhpString($aSeData);
						file_put_contents("errors/" . $sKeyword . "_" . $key . ".php", "<?php\nreturn $sArray;");

						unset($aSeResult[$key]);
					}
				}
			}
		}
		
		$aReturnData[$sArticleCode] = array(
			'se_results' => $aSeResult,
			'title' => $sKeyword, 
			'description' => buildDescription($aSeResult),
			'keywords' => buildKws($sKeyword)
		);
	}
	return $aReturnData;
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

function buildDescription(array $sSeResults)
{
	$aStat = array();
	
	// Calculate results
	foreach($sSeResults as $key => $aSeResult)
	{
		$sDescription = $aSeResult['description'];
		$aStat[$key] = array(
			'nondef' => 0,
			'upperascii' => 0,
		);
		
		$aStat[$key]['comma'] = substr_count($sDescription, ",");
		$aStat[$key]['dot'] = substr_count($sDescription, ".");
		$aStat[$key]['sentence'] = substr_count($sDescription, ". ");
		
		$iDescLen = strlen($sDescription);
		for($i = 0; $i<$iDescLen; $i++)
		{
			if(!preg_match('/[a-zA-Z0-9 ]/', $sDescription[$i]))
				$aStat[$key]['nondef']++;
			if(preg_match('/[A-Z]/', $sDescription[$i]))
				$aStat[$key]['upperascii']++;
		}
	}

	// Try to find first minimal request	
	$aMatch = 0;
	foreach($sSeResults as $key => $aSeResult)
	{
		if($aStat[$key]['sentence'] >= 1 && substr($aSeResult['description'], -1) == ".")
			$aMatch = $key;
	}

	// Try to find better match
	foreach($sSeResults as $key => $aSeResult)
	{
		$calc = 0;
		if(!($aStat[$key]['sentence'] >= 1 && substr($aSeResult['description'], -1) == "." )) // con from minimal match
			break;
		
		$aStat[$key]['comma'] <=  $aStat[$aMatch]['comma']            ? $calc++ : $calc--;
		$aStat[$key]['dot'] <=  $aStat[$aMatch]['dot']                ? $calc++ : $calc--;
		$aStat[$key]['sentence'] >=  $aStat[$aMatch]['sentence']      ? $calc++ : $calc--;    
		$aStat[$key]['nondef'] <=  $aStat[$aMatch]['nondef']          ? $calc++ : $calc--;
		$aStat[$key]['upperascii'] <=  $aStat[$aMatch]['upperascii']  ? $calc++ : $calc--;
		if($calc > 0)
			$aMatch = $key;
	}
	
	return $sSeResults[$aMatch]['description'];
}


//------------------------------------------------------------------------------

function buildDArray(array $aDsgData)
{
	$aDData = array();
	
	foreach($aDsgData['article'] as $sArticleCode => $aArticleData)
	{
		$aDData['article'][$sArticleCode] = array(
				'Attributes' => array('code' => $sArticleCode),
				'Properties' => array(
					'text' => array(
						'title' => $aArticleData['title'],
						'keywords' => $aArticleData['keywords'],
						'description' => $aArticleData['description'],
									),
					'category' => array('category' => $aArticleData['category']),
					'xml' => array( 'se_result' => xml2string(dsgSe2Xml($aArticleData['se_results'])) ), 
									),
									);
	}


	foreach($aDsgData['category'] as $sCategoryCode => $aCategoryData)
	{
		$aDData['category'][$sCategoryCode] = array(
			'Attributes' => array('code' => $sCategoryCode, 'parent_id' => 0, 'category_of' => 'article'),
			'Properties' => array(
				'text' => array(
					'title' => $aCategoryData['title'],
					'keywords' => $aCategoryData['keywords'],
								),
								),
								);
	}
	return($aDData);
}





/**
 * Pravi finalni xml i cuva ga u fajl
 * 
 * @param $aDsgData array
 * @param $sFileName string
 */
function buildXML(array $aDsgData, $sFileName, $sSiteName)
{

	
	$oXmlRoot = createElement('root');	
	$oXmlRoot->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance", "xsi:noNamespaceSchemaLocation", "Item2.xsd");
	
	$oSiteNode = createElement('site');
	$oSiteNode = addAttributes($oSiteNode, array('name' => $sSiteName));
	
	
	
//	Pravimo entity nodove	
	foreach($aDsgData as $sEntityName => $aEntityArray)
	{
//		Pravim entity node i dodajem mu atribut name
		$oEntityNode = createElement('entity');
		$oEntityNode = addAttributes($oEntityNode, array('name' => $sEntityName));
		
//		Pravimo item nodove i vezujemo ih na entity node
		foreach($aEntityArray as $sItemCode => $aItemData)
		{
			$oItemNode = createItemNode($sEntityName, $aItemData);
			$ItemNodeImport = $oEntityNode->ownerDocument->importNode($oItemNode, true);
			$oEntityNode->appendChild($ItemNodeImport);
		}

//		Importujem entity node i kacim ga na root node documenta
		$EntityNodeImport = $oSiteNode->ownerDocument->importNode($oEntityNode, true);
		$oSiteNode->appendChild($EntityNodeImport);
		
		
//		echo $oEntityNode->ownerDocument->saveXML($oEntityNode);
		
//		$oXmlRoot->appendChild($oEntityNode);
	}

	$SiteNodeImport = $oXmlRoot->ownerDocument->importNode($oSiteNode, true);
	$oXmlRoot->appendChild($SiteNodeImport);
	
	$oXml = $oXmlRoot->ownerDocument; 	
	$oXml->appendChild($oXmlRoot);
	
	$oXml->save("compress.zlib://" . $sFileName . ".gz");
//	echo $oXml->saveXML();
}

/**
 * Pravi Item podstablo za xml
 * 
 * @param $sEntityName string
 * @param $aItemData array
 * @return DOMElement
 */
function createItemNode($sEntityName, array $aItemData)
{
	$oItem = createElement($sEntityName);
	$oItem = addAttributes($oItem, $aItemData['Attributes']);

	foreach($aItemData['Properties'] as $sPropertyType => $aProperties)
	{
		$oProperty = createPropertyNode($sPropertyType, $aProperties);
		$node = $oItem->ownerDocument->importNode($oProperty, true);
		$oItem->appendChild($node);
	}
	return $oItem;
}

/**
 * Pravi property podstablo za xml
 * 
 * @param $sPropertyType string
 * @param $aProperties array
 * @return DOMElement
 */
function createPropertyNode($sPropertyType = 'text', array $aProperties)
{
	$oProperty = createElement('property');
	$oProperty = addAttributes($oProperty, array('type' => $sPropertyType));

	if($sPropertyType == 'xml')
	{
		foreach($aProperties as $sProperty => $sValue)
		{
			$oElement = string2Xml($sValue, $sProperty);
			$node = $oProperty->ownerDocument->importNode($oElement, true);
			$oProperty->appendChild($node);
		}	
	}
	elseif($sPropertyType == 'binary') 
	{
//		@TODO Dokodirati funkcionalnost za pravljenje CDATA propertija 
		echo "Jebiga treba da se dokodira";
		exit();
	}
	else 
	{
		foreach($aProperties as $sProperty => $sValue)
		{
			$oElement = createElement($sProperty, $sValue);
			$node = $oProperty->ownerDocument->importNode($oElement, true);
			$oProperty->appendChild($node);
		}
	}
//	echo $oProperty->ownerDocument->saveXML($oProperty);
	return $oProperty;
}	

/**
 * Dodaje atribute na prosledjeni DOMElement 
 * 
 * @param $oXmlNode DOMElement
 * @param $aAttributes array
 * @return DOMElement
 */
function addAttributes(DOMElement $oXmlNode, array $aAttributes)
{
	foreach($aAttributes as $sAtrribute => $sValue)
	{
		$oXmlNode->setAttribute($sAtrribute, $sValue);	
	}
	return $oXmlNode;
}

/**
 * Pravi DOMElement i dodaje mu textnode ako je prosledjen
 * 
 * @param $sName string
 * @param $sValue string
 * @return DOMElement
 */
function createElement($sName, $sValue = NULL)
{
	$oXml = new DomDocument('1.0', 'utf8'); 
//	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;
	
	$oElement = $oXml->createElement($sName);
	if (!is_null($sValue))
	{
		$oElementText = $oXml->createTextNode($sValue);
		$oElement->appendChild($oElementText);
	}
	return $oElement; 
}

/**
 * Prevodi child nodove prosledjenog DOMElement-a u string 
 * 
 * @param $oXmlNode DOMElement 
 * @return string
 */
function xml2string(DOMElement $oXmlNode)
{
	$sResult = '';
	foreach($oXmlNode->childNodes as $node)
    	$sResult .= $oXmlNode->ownerDocument->saveXML($node);

	return $sResult;
}

/**
 * Vraca DOMElement za zadati validan XHTML ili XML string
 * $srootNode je opcionalni parametar sa default vrednoscu 'root'
 * 
 * @param $sXml string
 * @param $srootNode string
 * @return DOMElement
 */
function string2Xml($sXml, $srootNode = 'root')
{
	$oXmlRoot = createElement($srootNode);	
	
	$oXmlImport = $oXmlRoot->ownerDocument->createDocumentFragment();

	$bSucess = @$oXmlImport->appendXML($sXml);
	if(!$bSucess)
		throw new Exception ("Greska u xml-u" , 1);
	
	$oXmlRoot->appendChild($oXmlImport);

	return $oXmlRoot;
}

/**
 * Za zadati dsg Se_Results niz vraca xml stablo
 * 
 * @param $aSeResults array
 * @param $srootNode string
 * @return DOMElement
 */
function dsgSe2Xml(array $aSeResults, $srootNode = 'se_results')
{
	$oXmlRoot = createElement($srootNode);
	
	foreach($aSeResults as $aSeResult)
	{
		$oEntry = createElement('entry');
		$oTitle = createElement('title', $aSeResult['title']);
		$oLink = createElement('link', $aSeResult['url']);
		$oContent = createElement('content', $aSeResult['description']);

		$oEntry->appendChild($oEntry->ownerDocument->importNode($oTitle, true));
		$oEntry->appendChild($oEntry->ownerDocument->importNode($oLink, true));
		$oEntry->appendChild($oEntry->ownerDocument->importNode($oContent, true));

		$oXmlRoot->appendChild($oXmlRoot->ownerDocument->importNode($oEntry, true));	
		
		
		
/*		$oElement = string2Xml($sValue, $sProperty);
		$onode = $oProperty->ownerDocument->importNode($oElement, true);
		$oProperty->appendChild($node);
*/		
	}
	return $oXmlRoot; 
}




/**
 * Konvertuje djubre iz dsg rezutata u validan xml
 * i vraca 'textContent' kao string
 * 
 * @param $sInput string
 * @return string
 */
function cleanString($sInput)
{
//	@todo Pogledati force, sta ako tidy ne uspe da vrati string ?? show-warnings parametar ?? error handling, pogledati tidy klasu

	
	$aTidyConfig = array(
	'clean' 					  => true, 
	'output-xhtml' 				  => true,
//	'output-xml' 				  => true,
	'show-body-only' 			  => true,
	'drop-proprietary-attributes' => true, 
	"drop-font-tags"			  => true, 
	"drop-empty-paras" 			  => true, 
	"hide-comments" 			  => true, 
	'word-2000' 			      => true, 
	"join-classes" 				  => true, 
	"join-styles" 				  => true,
	"add-xml-space" 			  => true,
	"enclose-text"				  => true,
	'doctype' 					  => 'strict',
	'logical-emphasis' 			  => true,
	'quote-nbsp' 				  => true,
	'numeric-entities'  		  => true,
	'assume-xml-procins'		  => true,
	'hide-comments'		          => true,
	'quote-ampersand'	          => true,
	); 
	
	$tidy = new tidy();
	
	$sResult = $sInput;

	$sResult = cStringHelper::cleanScriptLangTags($sResult);
	
	$aBadStrings = array("�","\n","\r","\0","\x0B");
	$sResult = str_replace($aBadStrings, " ", $sResult);

//	$sInput = stripControlChars($sInput);

	$sResult = htmlentities($sResult);
	
	$tidy->parseString($sResult, $aTidyConfig, 'utf8');

	
	$tidy->cleanRepair();

	$sResult = $tidy->body()->value;


	try 
	{
		$oXmlString = string2Xml($sResult);
	
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
	}
	
	catch(Exception $e)
	{
		echo $tidy->errorBuffer . "\n";
		echo "$sResult";
		throw new Exception ("Tidy: Greska u xml-u \n" , 1);
	}
	
	return $sResult;
}
/**
 * Iz zadatog input stringa brise specijalne karaktere
 * skida '...' trimuje itd
 * 
 * @param string $sInput
 * @return string
 */
function normalizeString($sInput, $sUcfirst = true)
{
	$aBadStrings = array("�","\n","\r");
	$sResult = str_replace($aBadStrings, ' ', $sInput);

	$sResult = trim($sResult);
	
 	if(substr($sResult, -3) == "...")
		$sResult = substr($sResult, 0, -3);
	
	if(substr($sResult, 0, 3) == "...")
		$sResult = substr($sResult, 3);				

	$sResult = str_replace(' ...' , '.', $sResult);
	
	while(strpos($sResult, "  " ) !== false)
		$sResult = str_replace("  ", " ", $sResult);
		
	$sResult = trim($sResult);

	if($sUcfirst)
		$sResult = ucfirst($sResult);
	
	return $sResult;
}

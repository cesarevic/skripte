<?php
/**
 * Dsg data to xml 
 * 
 * Copyright (c) 2005-2009 ViewSource
 */
$start = microtime(true);
include ("../../framework/Framework.php");


// Create database object and configure it (add di if config is required)
$oDb = new cDb();

$oDb->username = "root";
$oDb->host = "192.168.1.30";
$oDb->charset = "latin1";

//$start = microtime(true);

//$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_%'");
//$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_3dsoftwares'");
$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_12v'");
//$aDatabases = array ("sitegen_estatesales");
//$aDatabases = array ("sitegen_12v");

//$iTime_Baza = microtime(true);	
//echo "Konektovao sam se na bazu " . round(($iTime_Baza - $start),3) . "\n";


//Primer kako treba da izgleda niz
/*
	[article] => Array
        (
            [12v-timer] => Array
                (
                    [title] => 12v timer
                    [se_results] => Array 
	                [keyword] => 12v timer, 12v
                    [category] => 12v-adapter
                )
	[category] => Array
        (
            [12v-adapter] => Array
                (
                    [title] => 12v adapter
					[keyword] => 12v adapter, 12v
                )
*/











foreach($aDatabases as $iNo => $dbName)
{
	$start1 = microtime(true);
	print "Database #$iNo $dbName";
	$oDb->query("use $dbName");
	
	
//	$oResultSet = $oDb->query("select keyword, se1, se2, se3, comments from Keywords");
	$oResultSet = $oDb->query("select keyword, se1, se2, se3 from Keywords");

//	$iTime_Query = microtime(true);
//	echo "Prvi query " . round(($iTime_Query - $iTime_Baza),3) . "\n";
	
	$aDarray = buildDArray($oResultSet);
	$aDsgData = arrayTidy($aDarray);

	print_r($aDsgData);
	exit();
	
	
	unset($aDarray);

//	$iTime_Nizovi = microtime(true);
//	echo "Pravljenje nizova " . round(($iTime_Nizovi - $iTime_Query),3) . "\n";

	buildXML($aDsgData, $dbName);
	
//	$iTime_XML = microtime(true);
//	echo "Pravljenje XML-a " . round(($iTime_XML - $iTime_Nizovi),3) . "\n";
	echo " Time: " . round((microtime(true) - $start1),3) . " Memory: " . number_format(memory_get_usage()) . "\n";
}



echo "\n\nTotal time: " . round((microtime(true) - $start),3) . "\n";


function buildXML(array $aDsgData, $sFileName)
{
//	Pocetak je debilan, trebalo bi transformisati niz direktno iz dsg_data skripte
//	Ovako ova fukcija prima niz koji je generisan od strane arrayTidy funkcije

$oXml = new DomDocument('1.0', 'utf8'); 
$oXml->formatOutput = true;
$oXml->preserveWhiteSpace = false;

$oXmlRoot = $oXml->createElement('root');	
$oXmlRoot->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance", "xsi:noNamespaceSchemaLocation", "Item2.xsd");
$oXml->appendChild($oXmlRoot);
	
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
		$EntityNodeImport = $oXmlRoot->ownerDocument->importNode($oEntityNode, true);
		$oXmlRoot->appendChild($EntityNodeImport);
		
//		echo $oEntityNode->ownerDocument->saveXML($oEntityNode);
		
//		$oXmlRoot->appendChild($oEntityNode);
	}
$oXml->save("data/$sFileName.xml");	
}

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
//	echo $oItem->ownerDocument->saveXML($oItem);
	return $oItem;
}

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
	elseif($sPropertyType == 'xml') 
	{
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

function addAttributes(DOMElement $oXmlNode, array $aAttributes)
{
	foreach($aAttributes as $sAtrribute => $sValue)
	{
		$oXmlNode->setAttribute($sAtrribute, $sValue);	
	}
	return $oXmlNode;
}
	
function buildArticleArray(array $aData)
{
	$aDData = array();
	
	foreach($aData as $sItemCode => $aItemProperties)
	{
		$sXml = '';
		$oXmlImport = string2Xml($aItemProperties['html']);
		$oDXml = dSeResult($oXmlImport);
		foreach($oDXml->childNodes as $oNode)
    		$sXml .= $oDXml->ownerDocument->saveXML($oNode);
		
		$aDData[$sItemCode]  = array(
				'Attributes' => array('code' => $sItemCode),
				'Properties' => array(
					'text' => array(
						'title' => $aItemProperties['title'],
						'keyword' => $aItemProperties['keyword'],
									),
					'category' => array('category' => $aItemProperties['category']),
					'xml' => array('se_result' => $sXml), 
									),
									);
	}
	return $aDData;
}	

function buildCategoryArray(array $aData)
{
	$aDData = array();
	
	foreach($aData as $sItemCode => $aItemProperties)
	{
		$sXml = '';
		$oXmlImport = string2Xml($aItemProperties['html']);
		$oDXml = dSeResult($oXmlImport);
		foreach($oDXml->childNodes as $oNode)
    		$sXml .= $oDXml->ownerDocument->saveXML($oNode);
		
		$aDData[$sItemCode]  = array(
				'Attributes' => array('code' => $sItemCode, 'parent_id' => 0, 'category_of' => 'article'),
				'Properties' => array(
					'text' => array(
						'title' => $aItemProperties['title'],
						'keyword' => $aItemProperties['keyword'],
									),
					'xml' => array('se_result' => $sXml), 
									),
									);
	}
	return $aDData;
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

function xml2string(DOMElement $oXmlNode)
{
//	echo $oXmlNode->ownerDocument->saveXML($oXmlNode);

	foreach($oXmlNode->childNodes as $node)
    	$result .= $oXmlNode->ownerDocument->saveXML($node)."\n";

	return $result;
}

function dSeResult(DOMElement $oXmlNode, $sRootNode = 'root')
{
	$oXml = new DomDocument('1.0', 'utf8'); 
	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;
	
	$oXmlRoot = $oXml->createElement($sRootNode);	
	$oXml->appendChild($oXmlRoot);
		
	foreach ($oXmlNode->getElementsByTagName('p') as $paragraph)
	{

		foreach ($paragraph->childNodes as $node)
		{
			if (($node->nodeType == 3) && (strlen(trim($node->wholeText)) >= 1))
				$text = trim($node->nodeValue);		
		}	

		$aTag = $paragraph->getElementsByTagName('a');
		$anchor = trim($aTag->item(0)->nodeValue);
		$href = $aTag->item(0)->getAttribute('href');
				
		if(substr($text, -3) == "...")
			$text = substr($text, 0, -3);

		if(substr($anchor, -3) == "...")
			$anchor = substr($anchor, 0, -3);
			
		$text = trim($text);
		$anchor = trim($anchor);
		

//		Pravimo elemente 
		$entry = $oXml->createElement('entry');
		$title = $oXml->createElement('title');
		$link = $oXml->createElement('link');
		$content = $oXml->createElement('content');

//		Pravimo text nodove
		$title_text = $oXml->createTextNode($anchor);
		$link_text = $oXml->createTextNode($href);
		$content_text = $oXml->createTextNode($text);

//		Dodajemo text nodove na njihove elemente

		$title->appendChild($title_text);
		$link->appendChild($link_text);
		$content->appendChild($content_text);
		
		$entry->appendChild($title);
		$entry->appendChild($link);
		$entry->appendChild($content);

		
		$oXmlRoot->appendChild($entry);

	}

//	echo $oXml->saveXML($oXml->documentElement);
	return $oXml->documentElement;
}	

/**
 * Vraca xml Element za zadati input string
 * 
 * @param $sXml string
 * @return DOMElement
 */
function string2Xml($sXml, $srootNode = 'root')
{
	$oXml = new DomDocument('1.0', 'utf8'); 
	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;
	
	$oXmlRoot = $oXml->createElement($srootNode);	
	$oXml->appendChild($oXmlRoot);
	
	$oXmlImport = $oXml->createDocumentFragment();
	$oXmlImport->appendXML($sXml);
	
	$oXmlRoot->appendChild($oXmlImport);

//	return result is DOMElement
	return $oXml->documentElement;

/*
	echo $oXml->saveXML($oXml->documentElement);
	exit();
	
*/
}











//-------------------------------------------------------------------------


function xxxxbuildDArray($oResultSet)
{
	$aKwData = array();
	$aKwForCat = array();
	
	while($aResults = $oResultSet->fetch_array(MYSQLI_ASSOC))
	{
		$result = array();
		$sKw = $aResults['keyword'];
		$aResultCategory = array();
		
		$aSe1Data = unserialize($aResults['se1']);
		$aSe2Data = unserialize($aResults['se2']);
		$aSe3Data = unserialize($aResults['se2']);

		if(is_array($aSe1Data))
			$result = array_merge($result, $aSe1Data);
		if(is_array($aSe2Data))
			$result = array_merge($result, $aSe2Data);
		if(is_array($aSe3Data))
			$result = array_merge($result, $aSe3Data);
			
			
		$aKwData[$sKw] = $result;
		if(empty($result))
			print "...empty data !!!";
		unset($result);
		
		$aKwWords = explode(" ", $sKw);
		foreach($aKwWords as $sWord)
		{
			if(strlen($sWord) >= 2)
			{
				if(!isset($aKwForCat[$sWord]))
					$aKwForCat[$sWord] = 0;
				$aKwForCat[$sWord]++;
			}
		}
	}	

	arsort($aKwForCat);
	$aKwForCat = array_slice($aKwForCat, 0, 10, true);
	asort($aKwForCat);
	$aCategories = array_flip(array_keys($aKwForCat));
	unset($aKwForCat);

	foreach($aKwData as $sKw => $aData)
	{
		$bStat = false;
		foreach($aCategories as $sCategory => $val)
		{
			if(strpos($sKw, $sCategory) !== false)
			{
				$aResultCategory[$sCategory][$sKw] = $aData;
				$bStat = true;
				break;
			}
		}
		if(!$bStat)
			$aResultCategory['other'][$sKw] = $aData;
	}

	// Ovo moze i gore, ali namerno je razdvojeno da bi moglo da se ukljuci/iskljuci
	$aTmpResultCategory = array();
	foreach($aResultCategory as $sCategoryName => $aCatData)
	{
		$aKwForSubCat = array();
		foreach($aCatData as $sKw => $val)
		{
			$aKwWords = explode(" ", $sKw);
			foreach($aKwWords as $sWord)
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
		$sSubCat = array_shift(array_flip($aKwForSubCat));
		$aTmpResultCategory["$sCategoryName $sSubCat"] = $aCatData;
	}
	$aResultCategory = $aTmpResultCategory;

	/*
	Trenutno nismo u mogucnosti da vam isporucimo XML tako da ide php niz
	Niz koji se salje izgleda po sledecem sablonu

	array(
		'category-code-1' => array(
			'title' => "Title 1",
			'keyword' => "Kws",
			'article' => array(
				'article-code-1' => array(
					'title' => "Title1",
					'keyword' => "Kws",
					'html' => "html"
				),
			),
		),
		'category-code-1' => array(
		......
	*/
	$aData = array();
	$sIndexHtml = "";
	$aCategoryTitles = array();
	foreach($aResultCategory as $key => $val)
	{		
		$sCategoryHtml = "";
		$sCategoryCode = str_replace(" ", "-", $key);
		$sCategoryKeyword = buildKws($key);
		
		$aArticles = array();
		
		$aArticlesToTake = range(1, count($val));
		shuffle($aArticlesToTake);
		$aArticlesToTake = array_slice($aArticlesToTake, 0, 5);
		$sCategoryHtml = "";
				
		$iCategoryHtmlCounter = 0;
		
		// Uzmi samo prvi po kategoriji
		$iCounterIndexHtml = 1;
		
		foreach($val as $key1 => $val1)
		{
			
			if(! (strlen($key1) > 60))  // Ignore articles where code is longer than 60 chars
			{
				$iCategoryHtmlCounter++;
	
				$sArticleCode = str_replace(" ", "-", $key1);
				$sArticleTitle = ucfirst($key1);
				$sKeyword = buildKws($sArticleTitle);
				
				$sHtml = "";
				$iArticleHtmlCounter = 0;
				foreach($val1 as $val2)
				{
					$iArticleHtmlCounter++;
					
					$val2['description'] = preg_replace("/[^[:alnum:][:punct:] ]/","",$val2['description']); 
					$val2['title'] = preg_replace("/[^[:alnum:][:punct:] ]/","",$val2['title']);
					
					$sHtml .= "<p><a href=\"{$val2['url']}\" rel=\"nofollow\">{$val2['title']}</a> {$val2['description']}</p>";
					
					if(in_array($iCategoryHtmlCounter, $aArticlesToTake) && $iArticleHtmlCounter <= 2)
						$sCategoryHtml .= "<p>{$val2['description']} <a href=\"/$sCategoryCode/{$sArticleCode}.html\">{$val2['title']}</a></p>";

					if($iCounterIndexHtml <= 2 && (rand(1, 8) == 1)) // rand sluzi da malo zamesa
					{
						$sIndexHtml .= "<p>{$val2['description']} <a href=\"/$sCategoryCode/{$sArticleCode}.html\">{$val2['title']}</a></p>";
						$iCounterIndexHtml++;
					}
				}
		
				$aArticles[$sArticleCode] = array(
					"title" => $sArticleTitle,
					"html" => $sHtml,
					"keyword" => $sKeyword, 
				);
			}
		}
		$aCategoryTitles[] = $key;
		$aData[$sCategoryCode] = array(
			'title'   => $key,
			'keyword' => $sCategoryKeyword,
			'html'    => $sCategoryHtml,
			'article' => $aArticles,
		);
	}
/*	
	// Add false home category
	$aData['home'] = array(
		'keyword' => implode(", ", $aCategoryTitles),
		'html' => $sIndexHtml,
		'article' => array(),
	);
*/	
/*
	$sArray = cArrayHelper::arrayToPhpString($aData);
	unset($aData);
	
*/

/*	
//	Convert string to utf-8
	$sArray = utf8_encode($sArray);
	file_put_contents("data/$dbName.php", "<?php\nreturn $sArray;");
	unset($sArray);
*/

	return $aData;

}	

function arrayTidy($aDarray)
{
//	echo "<root>";
	$aCategory = array();
	$aArticle = array();
	
	foreach($aDarray as $sCategoryCode => $val)
	{
		$aCategory[$sCategoryCode] = array(
			'title'   => $val['title'],
			'html' => tidyCleanString($val['html']),
			'keyword'    => $val['keyword']
		);
				
		foreach($val['article'] as $sArticleCode => $val1)
		{
			$aArticle[$sArticleCode] = array(
			'title'    => $val1['title'],
			'html'     => tidyCleanString($val1['html']),
			'keyword'  => $val1['keyword'],
			'category' => $sCategoryCode
			);
		}
	}
			
//	echo "</root>";
	
/*	
//	Privremeno iskljuceno vrati posle a obrisi blok ispod
	$aDsgData = array(
	'article' =>  buildArticleArray($aArticle),
	'category' => buildCategoryArray($aCategory)
					);

*/
	
	$aDsgData = array(
	'article' =>  $aArticle,
	'category' => $aCategory
					);
	
	
	
	return $aDsgData; 					
}

/**
 * Cisti input string i od njega pravi validan xhtml
 * 
 * @param $sInput string
 * @return string
 */
function tidyCleanString($sInput)
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
	"join-styles" 				  => true
    ); 
    
	$tidy = new tidy();
	
	$sTidyString = $tidy->repairString($sInput,$aTidyConfig,'utf8');
//	echo $tidy->errorBuffer;
/*
	preg_match_all('/^(?:line (\d+) column (\d+) - )?(\S+): (?:\[((?:\d+\.?){4})]:)
	?(.*?)$/m', $tidy->errorBuffer, $tidy_errors, PREG_SET_ORDER);
	print_r($tidy_errors);
	
*/	
//	echo $sTidyString;
	return $sTidyString;
}

/**
 * Napravimo keyword od title po sistemu 123, 12, 1
 * 
 * @param $sKw string
 * @return string
 */
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
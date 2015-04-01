<?php

$aArticle = include 'article.php';
$aCategory = include 'category.php';

$sXml = $aArticle['12v-timer']['html'];



//
//	$test = string2Xml($sXml);
//	$test1 = dSeResult($test);
//	xml2string($test1);
//
	
	
//	print_r(buildArticleArray($aArticle));
	
//	print_r(buildCatalogArray($aArticle));
	
	
/*	
[12v-timer] => Array(
	[Attributes] => Array(
		[code] => 12v-timer
		[parent_id] => 0
		[category_of] => article
							)
	[Properties] => Array(
		[text] => Array(
			[title] => 12v timer
			[keyword] => 12v timer, 12v
						)
		[xml] => Array(
					[se_result]
*/

/*
	$oXmlNode=createElement('Test','Value');
	$aAttributes = array('visina' => 50);
	$oXmlNode=addAttributes($oXmlNode,$aAttributes);
	echo $oXmlNode->ownerDocument->saveXML($oXmlNode);
*/

	$oXmlNode=createElement('root');
	$oXmlNode->setAttributeNS('namespaceURI', 'qualifiedName', 'value');
	
	

//$aDData = buildArticleArray($aArticle);
//$aDData = buildCategoryArray($aCategory);
//$aProperties = $aDData['12v-adapter']['Properties']['xml'];
//createPropertyNode('xml', $aProperties);

//$aItemData = $aDData['12v-timer'];

//print_r($aItemData);

//createItemNode('article', $aItemData);
/*
$aDsgData = array(
	'article' =>  buildArticleArray($aArticle),
	'category' => buildCategoryArray($aCategory)
);

*
///print_r($aDsgData);
//buildXML($aDsgData);


function buildXML(array $aDsgData)
{
//	Pocetak je debilan, trebalo bi transformisati niz direktno iz dsg_data skripte
//	Ovako ova fukcija prima niz koji je generisan od strane arrayTidy funkcije

	$oXml = new DomDocument('1.0', 'utf8'); 
	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;
	
	$oXmlRoot = $oXml->createElement('root');	
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
	echo $oXml->saveXML();	
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












//		Listanje DOMElement-a
//		echo $oXmlNode->ownerDocument->saveXML($oXmlNode);




//	$test1 = string2Xml($sXml,'se_result');

//dSeResult($test, 'se_result');

//	$test1 = createTextElement('element', 'Vrednost');






/*
	$oXml = new DomDocument('1.0', 'utf8'); 
	$oXml->formatOutput = true;
	$oXml->preserveWhiteSpace = false;

	$node = $oXml->importNode($test1, true);
	$oXml->appendChild($node);
//	echo $oXml->saveXML();

*/

/*
	$aProperties = array( 
		'type' => array(
					'property_name' => 'Value',
						),
						);
*/
/*
	'attributes' => array(
			'attribute_name1' => 'attribute_value1',
			'attribute_name2' => 'attribute_value2'
							),
						
*/						
						
//	print_r($aProperties);






















/*			  
	foreach ($html->documentElement->childNodes as $paragraph)
	{
//		Prolazimo sve subnodove, ako je subnode tipa text i prazan string to postaje text
		foreach ($paragraph->childNodes as $node)
		{
			if (($node->nodeType == 3) && (strlen(trim($node->wholeText)) >= 1))
				$text = $node->nodeValue;		
		}	

		$aTag = $paragraph->getElementsByTagName('a');
		$anchor = $aTag->item(0)->nodeValue;
		$href = $aTag->item(0)->getAttribute('href');
	}

*/
	
/*	
		foreach ($paragraph->childNodes as $node)
		{
			echo $node->nodeName . ":";
			echo $node->nodeValue . "\n"; 
		}
	
*/
/*
		echo "Paragraph Type: ". $paragraph->nodeType . " Name: " . $paragraph->nodeName . " Value: " . $paragraph->nodeValue . "\n";
		foreach ($paragraph->childNodes as $node)
		{
			echo "Type: ". $node->nodeType . " Name: " . $node->nodeName . " Value: " . $node->nodeValue . "\n";
			
		}
	
*/	
	
?> 
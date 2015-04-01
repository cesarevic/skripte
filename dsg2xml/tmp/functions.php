<?php




/**
 * Pravi finalni xml i cuva ga u fajl
 * 
 * @param $aDsgData array
 * @param $sFileName string
 */
function buildXML(array $aDsgData, $sFileName)
{
//	Pocetak je debilan, trebalo bi transformisati niz direktno iz dsg_data skripte
//	Ovako ova fukcija prima niz koji je generisan od strane arrayTidy funkcije

$oXmlRoot = $oXml->createElement('root');	
$oXmlRoot->setAttributeNS("http://www.w3.org/2001/XMLSchema-instance", "xsi:noNamespaceSchemaLocation", "Item2.xsd");
	
	
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
	
$oXml = $oXmlRoot->ownerDocument; 	
$oXml->appendChild($oXmlRoot);
	
$oXml->save("$sFileName.xml");	
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

/**
 * Prevodi child nodove prosledjenog DOMElement-a u string 
 * 
 * @param $oXmlNode DOMElement 
 * @return string
 */
function xml2string(DOMElement $oXmlNode)
{
	foreach($oXmlNode->childNodes as $node)
    	$result .= $oXmlNode->ownerDocument->saveXML($node)."\n";

	return $result;
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
	$oXmlImport->appendXML($sXml);
	
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

		$oEntry->appendChild($oTitle);
		$oEntry->appendChild($oLink);
		$oEntry->appendChild($oContent);

		$oXmlRoot->appendChild($oEntry);	
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
//	@todo Pogledati force, sta ako tidy ne uspe da vrati string ?? show-warnings parametar ??

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
	"enclose-text"				  => true,
	'doctype' 					  => 'strict',
	'logical-emphasis' 			  => true,
	'quote-nbsp' 				  => true,
	'numeric-entities'  		  => true,
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




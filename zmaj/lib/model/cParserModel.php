<?php

class cParserModel extends cModelAbstract
{
	/**
	 * 
	 * @var cTidy
	 */
	protected $_tidy;
	
	/**
	 * 
	 * @var cDOMDocument
	 */
	protected $_xml;
	
	protected $_queue = array();

	protected $_results = array();
	
	protected $_callback = NULL;
	
	protected $_callbackMethod = NULL;
	
	protected $_interrupt = FALSE;

	public $queueLimit = 100;
	
	public $iTestCount = 0; 
	
	public $UA = array();
	
	protected $_xpath;
	
	protected $_result;
	
	
	public function getData()
	{
		if (empty($this->_results))
			return FALSE;
		else
			return array_pop($this->_results);
	}
	
	
	public function is_Available()
	{
		$iCount = count($this->_queue);
		 
		if ($iCount >= $this->queueLimit)
			return FALSE;
		 
		return TRUE;
	}
	
	
	public function add($aItem)
	{
 		$this->_queue[] = $aItem;
	}
		
	
	public function run()
	{
		do
		{
			$this->_results[] = $this->parse(array_pop($this->_queue));
			
			if(count($this->_results) > 0 && !is_null($this->_callback))
				$this->_callCallback();
		}
		while (count($this->_queue) > 0 && !$this->_interrupt);
		
		die();
		$this->disableInterrupt();
	}

	public function enableInterrupt()
	{
		$this->_interrupt = TRUE;
	}
	
	public function disableInterrupt()
	{
		$this->_interrupt = FALSE;
	}
	
 
	
	public function parse($aItem)
	{
		$oCurlResult = $aItem['result'];
		$sService = $aItem['service'];
		$sQuery = $aItem['query'];
		
		if ($sService == 'google')
		{
			$aResult = $this->parseGoogle($aItem);	
		} 
					
		

		
		
// 		throw new cException("Parser: unknown service: $sPingService");
			

// 		$aReturn = array_merge($aItem, array('string' => $sResult, 'status' => $iStatus));
		$aReturn = array_merge($aItem, array('parser' => $aResult));
		
		return $aReturn;
	}	

	protected function parseGoogle($aItem)
	{
		$oCurlResult = $aItem['result'];
		$sQuery = $aItem['query'];
		$sHtml = $oCurlResult->getBody();
		
		$oHtml = $this->_xml;
		$oHtml->loadHTML($sHtml);
		 
		unset($sHtml);
		
// 		$sHtmlTest = $oHtml->saveXML();
		
		$this->_xpath = new DOMXPath($oHtml);
		$oXpath = $this->_xpath;
		
		
		$this->_result = new cDOMDocument(); 
		$this->_result->formatOutput = TRUE;
		$oResult = $this->_result;

/* 		
		$oResultRoot = $oResult->buildElement('root');
		$oResult->appendChild($oResultRoot);
		
		$oResultRoot->appendChild($oResult->buildElement('se'));
		$oResultRoot->appendChild($oResult->buildElement('video'));
		$oResultRoot->appendChild($oResult->buildElement('maps'));
		$oResultRoot->appendChild($oResult->buildElement('related'));
		$oResultRoot->appendChild($oResult->buildElement('shopping'));
		$oResultRoot->appendChild($oResult->buildElement('images'));
		$oResultRoot->appendChild($oResult->buildElement('adwords'));
		
		$sXmlTest = $oResult->saveXML();
		
		$oResultSe = $oResultRoot->getElementsByTagName('se')->item(0); 
		
		$sXmlTest = $oResult->saveXML($oResultSe);
		
 */		
		$aResultNodes = array(
							'se' => $oResult->buildElement('se'),	
							'video' => $oResult->buildElement('video'),
							'maps' => $oResult->buildElement('maps'),
							'related' => $oResult->buildElement('related'),
							'shopping' => $oResult->buildElement('shopping'),
							'images' => $oResult->buildElement('images'),
							'adwords' => $oResult->buildElement('adwords')
						);
		
		
		
		
		
		$oEntries = $oXpath->query('//div[@id="ires"]/ol/li[count(*)=2]');
		$iEntriesCount = $oEntries->length; 
		
		foreach($oEntries as $oEntry)
		{
			$sXmlTest = $oEntry->ownerDocument->saveXML($oEntry);

			$sLink = $this->parseLink($oEntry);		

			if(strpos($sLink, '/interstitial?url=') !== FALSE)
			{
				continue;
			}
			
			$sLink = $this->fixLink($sLink);
				
			if (empty($sLink))
			{
				continue;
			}
			
			
			$sTitle = $this->parseTitle($oEntry);
			
			if (empty($sTitle))
			{
				continue;				
			}
			
			$oRes = $oXpath->query('div', $oEntry);
				
			if ($oRes->item(0) instanceof DOMElement)
			{
				$iResElements = $oRes->item(0)->childNodes->length;
				
				if($iResElements == 1)
				{

					//Prepravi da koristi $this_result
					$oVideoRes = $this->parseVideo($oEntry);

					if ($oVideoRes instanceof DOMElement)
					{
						$oVideo = $oResult->importNode($oVideoRes, TRUE);
						unset($oVideoRes);
						
						$oVideo->appendChild($oResult->buildElement('title', $sTitle));
						$oVideo->appendChild($oResult->buildElement('link', $sLink));
						$aResultNodes['video']->appendChild($oVideo);
					} 
					else
						continue;					
				}
					
				if($iResElements >= 3)
				{
					$sContent = $this->parseContent($oRes->item(0));
					
					if (empty($sContent)) 
						continue;
					
					if ($iResElements == 4)
						$aContentLinks = $this->parseContentLinks($oRes->item(0));
				}
			}
			else //Google maps  
			{
				$sMapLink = $this->parseMaps($oEntry);
				
				if (empty($sMapLink))
					continue;
			}			

/* 			
			//Ovo treba da stoji negde gore u foreach-u ? 
			//Sredjuje content da se ne pojavlju html entities, prebaciti u formatSEResultString ?
			if ($sResType == "SE" || "Youtube") 
			{
				$sContent = html_entity_decode($sContent);
			}
			
			//Sranje u slucaju mape
			//marginalni slucaj (kada je content prazan) je rezultat bez opisa na google-u 
			if (empty($sContent))
			{
				continue;
			}
			
 */			
			
/* 			
			$oResultEntry = $oResult->buildElement('entry');
			$oResultEntry->appendChild($oResult->buildElement('title', $sTitle));
			$oResultEntry->appendChild($oResult->buildElement('link', $sLink));
				
			$oResultData = $oResult->buildElement('data');
			$oResult->addAttributes($oResultData, array('type' => $sResType));  

			$oResultData->appendChild($oResult->buildElement('content', $sContent));

			if ($sResType == 'YouTube') 
				$oResultData->appendChild($oResult->buildElement('image', $sYouTubeImage));
			
			$oResultEntry->appendChild($oResultData);
			
			$oResultRoot->appendChild($oResultEntry);
 */
		}
		
		//Related Keywords
		$aKeywords = $this->parseRelated();
		
		//Shopping
		$aShopping = $this->parseShopping();
		
		//Adwords
		$aAdwords = $this->parseAdwords();
		
		//Images
		$aImages = $this->parseImages();
		
			
		
		
		
/* 		
		
		
		$iResultCount = $oResultRoot->childNodes->length; 

		if ($iResultCount == 0 || $iEntriesCount == 0)
		{
// 			echo "\n\n\n" . $oCurlResult->getUserAgent() . "\n\n\n";
			$this->UA['sjebani'][] = $oCurlResult->getUserAgent();
		}
		else 
			$this->UA['ok'][] = $oCurlResult->getUserAgent();

		//Division by zero kada je parsiranje neuspesno, hendluj 		
		$iSuccessRate = $iResultCount/$iEntriesCount*100;
		
		if ($iSuccessRate >= 90) 
			$iStatus = 0;
		else 
			$iStatus = 1;

		$aResult = array('string' => $oResult->xml2string($oResultRoot), 'status' => $iStatus);
		
// 		$sResult = $oResult->xml2string($oResultRoot); 
		return $aResult;
		
 */		
		return null;

	}

	protected function parseLink(DOMElement $oEntry)
	{
		$oXpath = $this->_xpath;
		$sLink = NULL;
		
		//Standard result
		$oRes = $oXpath->query('h3/a/@href', $oEntry);
			
		if ($oRes->item(0) instanceof DOMAttr)
			$sLink = $oRes->item(0)->textContent;

		//Video
		if(is_null($oRes->item(0)))
		{
			$oRes = $oXpath->query('table//h3/a/@href', $oEntry);

			if ($oRes->item(0) instanceof DOMAttr)
				$sLink = $oRes->item(0)->textContent;
		}
		
		if(is_null($oRes->item(0)) || strpos($sLink, '/interstitial?url=') == 0)
		{
			//Ovde bi trebalo staviti neki echo ili callback da ispise koji je rezultat u pitanju.
			
		}

		return $sLink;
	}
	
	
	protected function parseTitle(DOMElement $oEntry)
	{
		$oXpath = $this->_xpath;
		$sTitle = NULL;
		
		$oRes = $oXpath->query('h3/a', $oEntry);
		if ($oRes->item(0) instanceof DOMElement)
		{
			$sTitle = cStringHelper::formatSEResultString($oRes->item(0)->textContent);
		}
			
		//Youtube ?
		if(is_null($oRes->item(0)))
		{
			$oRes = $oXpath->query('table//h3/a', $oEntry);

			if ($oRes->item(0) instanceof DOMElement)
				$sTitle = cStringHelper::formatSEResultString($oRes->item(0)->textContent);
		}
		
		if (!empty($sTitle)) 
			$sTitle = html_entity_decode($sTitle);
		
		return $sTitle;
	}	
	
	/**
	 * @param DOMElement $oEntry
	 * @return Ambigous <NULL, DOMElement>
	 */
	protected function parseVideo(DOMElement $oEntry)
	{
		$oXpath = $this->_xpath;
		$oVideo = NULL;
				
		$oRes = $oXpath->query('table//span', $oEntry);
		$sContent = cStringHelper::formatSEResultString($this->getXHTMLContent($oRes->item(0)));
		
		if (!empty($sContent)) 
		{
			$oRes = $oXpath->query('table//img/@src', $oEntry);
			$sImage = $oRes->item(0)->textContent;
			$sImage = substr($sImage, 0, strpos($sImage, '?h='));
				
			if (strpos($sImage, "youtube.com"))
				$sImage = str_replace("/default", "/mqdefault", $sImage);
			
			if (!empty($sImage))
			{
				$oVideo = $this->_xml->buildElement('entry');
				$oVideo->appendChild($this->_xml->buildElement('content', $sContent));
				$oVideo->appendChild($this->_xml->buildElement('image', $sImage));
			}
		}
		
		return $oVideo;		
	}
	
	protected function parseContent(DOMElement $oElement)
	{
		$oXpath = $this->_xpath;
		$sContent = NULL;
		
		//string-length(text()) > 0 sluzi da isfiltrira specijalne rezultate koji nemaju desc.
		$oResInner = $oXpath->query('span[string-length(text()) > 0]', $oElement);
		
		if ($oResInner->item(0) instanceof DOMElement)
			$sContent = cStringHelper::formatSEResultString($this->getXHTMLContent($oResInner->item(0)));
		
		return $sContent;
	}
	
	
	protected function parseContentLinks(DOMElement $oElement)
	{
		$oXpath = $this->_xpath;
		$aLinks = array();
		
		$oResInner = $oXpath->query('div[@class="osl"]', $oElement);
		
		if ($oResInner->item(0) instanceof DOMElement)
		{
			$oRes = $oXpath->query('a', $oResInner->item(0));
		
			foreach ($oRes as $oLink)
			{
				$sAnchor = cStringHelper::formatSEResultString($oLink->textContent);
				$sLink = $this->fixLink($oLink->getAttribute('href'));
		
				if(!empty($sAnchor) && !empty($sLink))
					$aLinks[] = array('anchor' => $sAnchor, 'url' => $sLink);
			}
		}
		
		return $aLinks;
	}
	
	protected function parseMaps(DOMElement $oEntry)
	{
		$oXpath = $this->_xpath;
		$sMapLink = NULL;
		
		$oRes = $oXpath->query('table//a[contains(@href, "maps.google.com")]/img', $oEntry);
		
		if ($oRes->item(0) instanceof DOMElement)
		{
			$sMapLink = $oRes->item(0)->getAttribute('src');
			$sMapLink = "https://www.google.com" . $sMapLink;
		}
		
		return $sMapLink;
	}
	
	protected function parseRelated()
	{
		$oXpath = $this->_xpath;
		$aKeywords = array();
		
		//Ovaj xpath radi tako sto nadje div ciji text pocinje sa "Searches related to", i ima table kao next
		//sibling. Iz tog siblinga povadi sve /a elemente gde god da se nalaze
		$oEntries = $oXpath->query('//div[text()[starts-with(.,"Searches related to")]]/following-sibling::table//a');
		
		foreach ($oEntries as $oEntry)
		{
			if ($oEntry instanceof DOMElement)
			{
				$sKeyword = cStringHelper::formatSEResultString($oEntry->textContent);
				
				if (!empty($sKeyword))
					$aKeywords[] = $sKeyword;
			}
		}
		
		return $aKeywords;
	}
	
	protected function parseShopping()
	{
		$oXpath = $this->_xpath;
		$aShopping = array();
		
		$oEntries = $oXpath->query('//div[@class="pla_image_container"]');
		
		foreach ($oEntries as $oEntry)
		{
			$sLink = $sImage = $sPrice = NULL;
			
			$oRes = $oXpath->query('a/@href', $oEntry);
			if ($oRes->item(0) instanceof DOMAttr)
			{
				$sLink = $oRes->item(0)->textContent;
		
				$sLink = substr($sLink, strpos($sLink, '&adurl='));
				$sLink = str_replace('&adurl=', '', $sLink);
				$sLink = urldecode(urldecode($sLink));
			}
		
			if (empty($sLink))
				continue;
			
			
			$oRes = $oXpath->query('//img/@src', $oEntry);
			if ($oRes->item(0) instanceof DOMAttr)
				$sImage = $oRes->item(0)->textContent;
		
			if (empty($sImage))
				continue;

			
			$oRes = $oXpath->query('div/*[text()[starts-with(.,"$")]]', $oEntry);
			if ($oRes->item(0) instanceof DOMElement)
				$sPrice = $oRes->item(0)->textContent;

			if (empty($sPrice))
				continue;

			
			$aShopping[] = array('link' => $sLink, 'image' => $sImage, 'price' => $sPrice);
		}
		
		return $aShopping;
	}
	
	protected function parseAdwords()
	{
		$oXpath = $this->_xpath;
		$aAdwords = array();
		
		$oEntries = $oXpath->query('//h2[text()[starts-with(.,"Ad")]]/following-sibling::ol/li');
		
		foreach ($oEntries as $oEntry)
		{
			$sTitle = $sAdLink = $sContent = NULL;
			$aLinks = array();
			
			//First child that has an <a> should always be the title and url of the ad
			$oRes = $oXpath->query('*[1]/a', $oEntry);
			$oLink = $oRes->item(0);
				
			$sTitle = cStringHelper::formatSEResultString($oLink->textContent);

			if (empty($sTitle))
				continue;
			
			$sAdLink = $oLink->getAttribute('href');
				
			$sAdLink = substr($sAdLink, strpos($sAdLink, '&adurl='));
			$sAdLink = str_replace('&adurl=', '', $sAdLink);
			$sAdLink = urldecode(urldecode($sAdLink));

			if (empty($sAdLink))
				continue;
			
			//Ad Content
			if ($oEntry->childNodes->length >= 3)
			{
				$oRes = $oXpath->query('*[3]', $oEntry);
				if ($oRes->item(0) instanceof DOMElement)
				{
					$sContent = cStringHelper::formatSEResultString($this->getXHTMLContent($oRes->item(0)));
					$sContent = html_entity_decode($sContent);
				}

				if (empty($sContent))
					continue;
				
				if ($oEntry->childNodes->length == 4)
				{
					$oRes = $oXpath->query('*[4]', $oEntry);
						
// 					$sXmlTest = $oEntry->ownerDocument->saveXML($oEntry);
						
					if ($oRes->item(0) instanceof DOMElement)
					{
						$oLinks = $oXpath->query('.//a', $oRes->item(0));
						foreach ($oLinks as $oLink)
						{
							$sAnchor = cStringHelper::formatSEResultString($oLink->textContent);
							
							if (empty($sAnchor))
								continue;
							
							$sLink = $oLink->getAttribute('href');
		
							$sLink = substr($sLink, strpos($sLink, '&adurl='));
							$sLink = str_replace('&adurl=', '', $sLink);
							$sLink = urldecode(urldecode($sLink));

							if (empty($sLink))
								continue;
							
							$aLinks[] = array('anchor' => $sAnchor, 'url' => $sLink);
						}
					}
				}
			}

			if (empty($sContent))
				continue;
				
			$aAdwords[] = array('title' => $sTitle, 'link' => $sAdLink, 'content' => $sContent);
				
			if (!empty($aLinks))
			{
				$iKey = key($aAdwords);
				$aAdwords[$iKey] = array_merge($aAdwords[$iKey], array('links' => $aLinks));
			}
		}
		
		return $aAdwords;
	}
	
	protected function parseImages()
	{
		$oXpath = $this->_xpath;
		$aImages = array();
		
		//Trazim rezultat koji u nekom <a> ima tekst koji pocinje sa Images. Od tog elementa biram parent koji za sibling
		//ima div u kojem se pojavljuje <a>
		$oEntries = $oXpath->query('//div[@id="ires"]/ol/li//a[text()[starts-with(.,"Images")]]/parent::*/following-sibling::div/a');
			
		if ($oEntries->item(0) instanceof DOMElement)
		{
			foreach ($oEntries as $oEntry)
			{
				$sThumbUrl = $sImageUrl = $sUrl = $sHref = NULL;
				
				$sHref = $oEntry->getAttribute('href');
					
				$sThumbUrl = $oEntry->firstChild->getAttribute('src');
				
				if (empty($sThumbUrl))
					continue;
					
				$sImageUrl = substr($sHref, 0, strpos($sHref, '&'));
				$sImageUrl = substr($sImageUrl, ( strpos($sImageUrl, '?imgurl=') + strlen('?imgurl=') ) );

				if (empty($sImageUrl))
					continue;
				
				$sUrl = substr($sHref, strpos($sHref, '&imgrefurl='));
				$sUrl = str_replace('&imgrefurl=', '', $sUrl);
				$sUrl = substr($sUrl, 0, strpos($sUrl, '&h='));
				
				if (empty($sUrl))
					continue;
					
				$sImageUrl = urldecode(urldecode($sImageUrl));
				$sUrl = urldecode(urldecode($sUrl));
					
				$aImages[] = array('thumbnail' => $sThumbUrl, 'image' => $sImageUrl, 'link' => $sUrl);
			}
		}
		
		return $aImages;
	}
	
	public function fixLink($sLink)
	{

		if (strpos($sLink, 'http://maps.google.com') === 0)
		{
			$sQuery = substr($sLink, strpos($sLink, '&q='));
			$sQuery = str_replace('&q=', '', $sQuery);
			$sQuery = substr($sQuery, 0, strpos($sQuery, '&'));
			$sLink  = 'https://www.google.com/maps/preview#!q=' . $sQuery;
			
		}
		else
		{
			$sLink = str_replace('/url?q=', '', $sLink);
			$sLink = substr($sLink, 0, strpos($sLink, '&'));
			$sLink = urldecode(urldecode($sLink));
		}
		
		return $sLink;
	}
	
	
	
	protected function parseBing($sHtml)
	{
		$sTidyResult = $this->_tidy->clean($sHtml);
		$oXmlNode =	$this->_xml->string2Xml($sTidyResult);
		$oMessage =	$oXmlNode->getElementsByTagName("body")->item(0);
		$sResult = trim($oMessage->textContent);
		
		return $sResult; 
	}
	

	public function getXHTMLContent(DOMElement $oXmlNode)
	{
		$sResult = '';
		foreach($oXmlNode->childNodes as $node)
		{
			if ($node->nodeType === XML_TEXT_NODE)
				$sResult .= $oXmlNode->ownerDocument->saveXML($node);
				
			if ($node->nodeType === XML_ELEMENT_NODE && $node->childNodes->length === 1 && $node->childNodes->item(0)->nodeType === XML_TEXT_NODE)
				$sResult .= $oXmlNode->ownerDocument->saveXML($node->childNodes->item(0));
		}
		$sResult = trim($sResult);

		return $sResult;
	}
	
	
	
	/**
	 * Configures the callback functionality.
	 *
	 * @param mixed $callback
	 * @param string $sMethod
	 * @return boolean
	 */
	public function setCallBack($callback, $sMethod = "callback")
	{
		//if $callback is an object
		if (is_callable(array($callback, $sMethod)))
		{
			$this->_callback = $callback;
			$this->_callbackMethod = $sMethod;
		}
		//if $callback is a function
		elseif (is_callable($callback))
		$this->_callback = $callback;
		else
			return FALSE;
	
		return TRUE;
	}
	
	/**
	 * Disables the callback functionality
	 */
	public function disableCallback()
	{
		$this->_callback = NULL;
		$this->_callbackMethod = NULL;
	}
	
	/**
	 * Executes the callback
	 *
	 * @return boolean
	 */
	protected function _callCallback()
	{
		if (is_callable(array($this->_callback, $this->_callbackMethod)))
			call_user_func(array($this->_callback, $this->_callbackMethod));
	
		elseif (is_callable($this->_callback))
		call_user_func($this->_callback);
		else
			return FALSE;
	
		return TRUE;
	}
	
	public function __construct()
	{
		$this->_tidy = Registry::getObject("oTidy");
		$this->_xml = new cDOMDocument();
		$this->_xml->formatOutput = TRUE;
		
	}
	
	
}
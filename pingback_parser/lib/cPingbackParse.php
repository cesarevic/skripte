<?php
class cPingbackParse extends cCliLoopApplication
{
	/**
	 * Scipt name
	 * 
	 * @var string
	 */
	public $name = "Pingback Parser";
	
	/**
	 * Script description
	 * 
	 * @var string
	 */
	public $description = "Script for parsing and crawling pingback urls.";

	/**
	 * Posle koliko odradjenih domena ide write
	 * 
	 * @var integer
	 */
	public $writeStackSize = 50;

	/**
	 * Posle koliko errora domen se disejbluje
	 * 
	 * @var unknown_type
	 */
	public $maxParseErrorCount = 3;
	
	/**
	 * Instance of cBlogResource object
	 *
	 * @var cBlogResource
	 */
	protected $_blogResource;
	
	/**
	 * Instance of cPingbackResource object
	 * 
	 * @var cPingbackResource
	 */
	protected $_pingbackResource;

	/**
	 * Instance of cCurl object
	 * 
	 * @var cCurl
	 */
	protected $_curl;

	
	/**
	 * Main run Loop
	 * 
	 */
	protected function _runLoop()
	{
		print $this->getLoopHeader();
		
		// Get blog data
		print "Get urls from blog resource... ";
		$this->_blogResource->resultReturnCount = 100;
		$aBlogData = $this->_blogResource->getData();
		if(empty($aBlogData))
			throw new cSleepException(0, 'No Results to parse!', $this);
			
		// Get domains
		print "Get domains... \n";
		$aBlogDomains = $this->_pingbackResource->getDomain(array_keys($aBlogData));

		// Main domain loop
		$aProcessedDomains = array();
		$sLastDomain = $this->_getLastDomain($aBlogData);
		foreach($aBlogData as $sDomainName => $aBlogUrls)
		{
			// Populate domain data
			if(isset($aBlogDomains[$sDomainName]))
			{
				$aDomain = $aBlogDomains[$sDomainName];
				
				// Check if domain is inactive. If is, skip
				if($aDomain['active'] == 0)
				{
					print "[Domain is not active, skip!]\n";
					continue;
				}
				// Assign done urls
				$aDomain['_urls'] = $this->_pingbackResource->getUrls($aDomain['id']);
			}
			else 
			{
				$aDomain = array(
					'id' => null,
					'domain' => $sDomainName,
					'active' => 1,
					'urlCount' => 0,
					'pingbackUrlCount' => 0,
					'parseErrorCount' => 0,
					'_urls' => array()
				);
			}
			$aDomain['urls'] = $aBlogUrls;
			$aDomain['_trusted'] = null;
			
			// Process domain urls
			print "[Domain: $sDomainName] [Id: " . (is_null($aDomain['id']) ? 'NULL' : $aDomain['id']) . "] ";
			$this->_processDomainUrls($aDomain);
			$aProcessedDomains[$sDomainName] = $aDomain;
			
			// Write data, if is time to write
			if(count($aProcessedDomains) >= $this->writeStackSize || $sLastDomain == $sDomainName)
			{
				print "\n[Writing processed data...]";
				$this->_pingbackResource->insertData($aProcessedDomains);
				$this->_blogResource->ping();
				
				$aProcessedDomains = array();
			}
			print "\n";
		}
		
		// All is done, update blog resource
		print "Done with result set, Update blog resource...\n";
		$this->_blogResource->updateData();
	}

	
	/**
	 * 
	 * 
	 * Sranja:
	 * 	ako je domen vec obradjen salje se ceo, sto nije optimalno, potrebno je samo updejtovati kws
	 * 
	 * @param array $aDomain
	 */
	protected function _processDomainUrls(array &$aDomain)
	{
		foreach($aDomain['urls'] as $sUrl => $aUrlData)
		{
			print "\n\t[Url: $sUrl] [urls: {$aDomain['urlCount']}] [pb urls: {$aDomain['pingbackUrlCount']}] ";
			
			if($this->_trustedDomain($aDomain) !== null) 
			{
				// Trusted domain, dodeli trusted pb link i preskaci
				$aDomain['urls'][$sUrl]['pbLink'] = $this->_trustedDomain($aDomain);
				$aDomain['urlCount']++;
				print '[trusted]';
			}
			elseif(isset($aDomain['_urls'][$sUrl])) 
			{
				// Url je vec obradjen, dodeli mu pbLink i preskaci (sjebana logika)
				$aDomain['urls'][$sUrl]['pbLink'] = $aDomain['_urls'][$sUrl];
				print '[url already processed]';
			}
			else
			{
				// Nije trusted, nije radjen, onda Curl
				try
				{
					$oResult = $this->_curl->doCurl($sUrl);
					if($oResult->getFirstHttpStatus() !== 200)
						throw new Exception("Curl http status " . $oResult->getFirstHttpStatus());

					// Parse pingback url from header
					$sPingbackUrl = $this->_getPingbackUrlFromHeader($oResult->getHeader(), $aDomain['domain']);
					if($sPingbackUrl == null)
						throw new Exception("Unable to find Pingback Url");
						
					$aDomain['urls'][$sUrl]['pbLink'] = $sPingbackUrl;

					// Update pingbackUrlCount
					$aPbLinks = array();
					foreach($aDomain['urls'] as $sTempUrl => $aTempUrlData)
						if(isset($aTempUrlData['pbLink']))
							$aPbLinks[$aTempUrlData['pbLink']] = null;
					foreach($aDomain['_urls'] as $sTempUrl => $sPbLink)
						$aPbLinks[$sPbLink] = null;
					$aDomain['pingbackUrlCount'] = count($aPbLinks);
					
					// Update urlCount
					$aDomain['urlCount']++;
				}
				catch (Exception $e)
				{
					print "[Error: " . $e->getMessage() . "] ";
	
					// ovde nema tb-a, ubijamo ovaj url
					unset($aDomain['urls'][$sUrl]);
					$aDomain['parseErrorCount']++;
					
					// Ako je domen imao previse error counta, a nema ni jedan ok url, deaktiviramo domen
					if($aDomain['parseErrorCount'] >= $this->maxParseErrorCount && $aDomain['urlCount'] == 0)
					{
						print "[Parse error maximum, deactivate domain!]";
						$aDomain['active'] = 0;
						$aDomain['urls'] = array(); //@todo dali ovo treba da resetujem?!?!
						break;
					}
				}
			}
		}
	}  
	
	/**
	 * Parsira header i trazi u njemu pingback url
	 * 
	 * @param string $sHeader
	 * @param string $sDomainName
	 * @return string|null
	 */
	protected function _getPingbackUrlFromHeader($sHeader, $sDomainName)
	{
		$sPingbackUrl = null;
		$aHeader = explode("\n", $sHeader);
		foreach ($aHeader as $sHeader)
		{
			$sHeader = trim($sHeader);
			if(stripos($sHeader, 'X-Pingback:') !== false)
			{
				$sPingbackUrl = trim(substr($sHeader, stripos($sHeader, 'X-Pingback:') + 11));
				if(substr($sPingbackUrl, 0, 1) == "/")
					$sPingbackUrl = 'http://' . $sDomainName . $sPingbackUrl;

				break;
			}
		}
		
		return $sPingbackUrl;
	}
	
	/**
	 * 
	 * 
	 * @param array $aDomain
	 */
	protected function _trustedDomain(array &$aDomain)
	{
		$sTrustedUrl = null;
		if(!empty($aDomain['_trusted']))
		{
			$sTrustedUrl = $aDomain['_trusted'];
		}
		elseif($aDomain['urlCount'] >= 3 && $aDomain['pingbackUrlCount'] == 1)
		{
			// Uslov zadovoljen, nabavi mi trusted url, prvo kroz ono sto imamo
			foreach($aDomain['urls'] as $sUrl => $aUrlData)
				if(isset($aUrlData['pbLink']))
				{
					$sTrustedUrl = $aUrlData['pbLink'];
					break;
				}

			if($sTrustedUrl === null && !empty($aDomain['_urls']))
				$sTrustedUrl = current($aDomain['_urls']);
				
			$aDomain['_trusted'] = $sTrustedUrl;
		}
		
		return $sTrustedUrl;
	}	
		
	/**
	 * Helper, returns key of last element in array
	 * 
	 * @param array $aArray
	 * @return string
	 */
	protected function _getLastDomain(array $aArray)
	{
		end($aArray);
		return key($aArray);	
	}

	/**
	 * Public class constructor
	 * 
	 * @param cBlogResource $oBlogResource
	 * @param cPingbackResource $oPingbackResource
	 * @param cCurl $oCurl
	 */
	public function __construct(cBlogResource $oBlogResource, cPingbackResource $oPingbackResource, cCurl $oCurl)
	{
		parent::__construct();
		$this->addDispatch('p', 'print_xml_result', 'Print xml-a za zadati kw');
		
		$this->_blogResource = $oBlogResource;
		$this->_pingbackResource = $oPingbackResource;
		$this->_curl = $oCurl;
		
	}
	
}

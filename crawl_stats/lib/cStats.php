<?php
/**
 * cStats class
 * Copyright (c) 2005-2011 PKM-Inc
 */
class cStats extends cCliLoopApplication
{
	/**
	 * Scipt name
	 * 
	 * @var string
	 */
	public $name = "Crawl Stats";

	/**
	 * Script description
	 * 
	 * @var string
	 */
	public $description = "Crawl Stats";

	public $botUA = array();
	
	/**
	 * Instance of cCSVLogResource class  
	 *
	 * @var cCSVLogResource
	 */
	public $_log;
	
	/**
	 * Instance of cDBLogResource class  
	 *
	 * @var cDBLogResource
	 */
	public $_logDb;

	/**
	 * Instance of cStatsResource class  
	 *
	 * @var cStatsResource
	 */
	public $_stats;

	protected $_state = 1;
	
	protected function _runLoop()
	{
		print $this->getLoopHeader();

		if($this->_state == 0) 
		{
			echo "Get log data \n";
			$aLogData = $this->_log->getData();
			if(!empty($aLogData))
			{
				echo "Process log data \n";
				$aData = $this->processLogs($aLogData);
				echo "Write log data \n";
				$this->_logDb->writeData($aData);
			}
			else
			{ 
				echo "No log data \n";
				$this->_state = 1;
			}
		}
		
		if($this->_state == 1) 
		{
			$this->_stats->warmUp();
			echo "Get sites \n";
			$aSites = $this->_stats->getSites();
			if(!empty($aSites))
			{
				echo "Update sites \n";
				$this->_stats->updateSites($aSites);
			}
			else
			{ 
				echo "No sites \n";
				$this->_state = 2;
			}		
		}
		
		if($this->_state == 2) 
		{
			$this->_stats->resultLimit = 1;			
			echo "Get domains \n";
			$aDomains = $this->_stats->getDomains();
	
			if(!empty($aDomains))
			{
				echo "Update domains \n";
				$this->_stats->updateDomains($aDomains);				
			}
			else 
			{
				echo "Done\n";
				die();
			}			
		}
	}
	
	public function processLogs($aLogData)
	{
		$aData = array();
		
		foreach ($aLogData as $aRecord) 
		{
			$sHost = @parse_url($aRecord['url'], PHP_URL_HOST);
			$iTLDDot = strrpos($sHost, '.');
			$iDomainDot = strrpos($sHost, '.', ($iTLDDot - strlen($sHost)) -1);
			$sDomain = substr($sHost, $iDomainDot+1);
			
			if(is_null($sHost) || $iTLDDot === FALSE || $iDomainDot === FALSE || $sDomain === FALSE || empty($sDomain))
				continue;
			
			$iFirstSlash = strpos($aRecord['url'], '/', strpos($aRecord['url'], '.'));
			
			//Sta u ako je url root domena ??
			//zameniti sa / ??
			$sUrl = substr($aRecord['url'], $iFirstSlash);
						
			$iTimeStamp = strtotime($aRecord['time']);
			
//			$sTest = date("H:i:s j F Y", $iTimeStamp);
			
			$sBot = "unknown";
			$sBotUA = "unknown";
			$iUAMatch = 0;
			
			foreach($this->botUA as $sBotName => $aUA) 
			{
				foreach($aUA as $sUAString) 
				{
					if(strpos($aRecord['ua'], $sUAString) !== FALSE)
					{
						$sBot = $sBotName;
						$sBotUA = $aRecord['ua'];
						$iUAMatch = 1;
						break;	
					}
				}
				if($iUAMatch == 1)
					break;
			}
			
			if(substr($aRecord['url'], -4) == ".rss")
				$sPageType = "rss";
			elseif(substr($aRecord['url'], -4) == ".xml")
				$sPageType = "xml";				
			elseif(substr($aRecord['url'], -4) == ".css")
				$sPageType = "css";				
			elseif(substr($aRecord['url'], -11) == "/robots.txt")
				$sPageType = "robots";				
			elseif(substr($aRecord['url'], -5) == ".html")
				$sPageType = "html";
			elseif( array_search(substr($aRecord['url'], -4), array(".png", ".gif", ".jpg")) !== FALSE)				
				$sPageType = "image";				
			elseif(substr( rtrim($aRecord['url'], "/"), -strlen($sHost)) == $sHost)
				$sPageType = "root";				
			//Redosled je bitan, mora da ide posle root tipa	
			elseif(substr($aRecord['url'], -1) == "/")
				$sPageType = "category";
			else
				$sPageType = "unknown";

				
			$aData[] = array(
				'bot' 		=> $sBot,
				'botua' 	=> $sBotUA,
				'domain' 	=> $sDomain,
				'site' 		=> $sHost,
				'type' 		=> $sPageType,
				'url' 		=> $sUrl,
				'timestamp' => $iTimeStamp,
			);
			
			
		}
		
		return $aData;
	}
	
	public function __construct(cCSVLogResource $oCSVLogResource, cDBLogResource $oDBLogResource, cStatsResource $oStatsResource)
	{
		parent::__construct();
		$this->_log = $oCSVLogResource;
		$this->_logDb = $oDBLogResource;
		$this->_stats = $oStatsResource;
	}
}	
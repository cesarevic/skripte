<?php
/**
 * cStatsPrint class
 * Copyright (c) 2005-2011 PKM-Inc
 */
class cStatsPrint extends cCliApplication
{
	
	/**
	 * Instance of cConsoleTable class  
	 *
	 * @var cConsoleTable
	 */
	protected $_table;
	
	/**
	 * Instance of cDb class  
	 *
	 * @var cDb
	 */
	protected $_db;
	
	/**
	 * Instance of cCSVParser class  
	 *
	 * @var cCSVParser
	 */
	public $_parser;
	
	public $limitDays = 30;
	
	protected $_pageTypes = array();
	
	protected $_statsArray = array();
	
	public function printSiteStats()
	{
		$aSites = $this->getSites();
		$aSitesStatsData = $this->getSitesStatsData($aSites);
		
//		$aCSV = $this->getSitesCSV($aSitesStatsData);
		$aTable = $this->getSitesTable($aSitesStatsData);		

		foreach($aTable as $sSite => $aSiteData) 
		{
			foreach($aSiteData as $sBot => $aBotData) 
			{
				echo $aBotData['sum'];
				echo $aBotData['daily'];
			}
			echo "\n\n\n\n";
		}		
	} 

	public function getSitesStatsData($aSites)
	{
		$aData = array();
		$aSitesSum = $this->getSitesSum($aSites);		
		$aSitesDaily = $this->getSitesDaily($aSites);

		foreach($aSitesSum as $sSite => $aSiteData) 
		{
			foreach($aSiteData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sTimestamp => $aPageTypes) 
				{
					$aDataRow = (array_merge(array($sSite, $sBot, $sTimestamp), array_values($aPageTypes)));
					$aData[$sSite][$sBot]['sum'][] = $aDataRow;
				}
			}
		}
		
		$iLastTimestamp = 0;
		
		foreach($aSitesDaily as $sSite => $aSiteData) 
		{
			foreach($aSiteData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sTimestamp => $aPageTypes) 
				{
					//Zakrpa za slucaj kada nema rezultata za neke dane
					if($iLastTimestamp && ($iLastTimestamp - strtotime($sTimestamp)) > 86400)
					{
						$aMissigDates = $this->getMissingDates($iLastTimestamp, strtotime($sTimestamp));
						foreach($aMissigDates as $sDate) 
						{
							$aFirst = array($sSite, $sBot, $sDate);
							$aSecond = array_fill(0, count($aPageTypes), 0);
							$aDataRow = array_merge($aFirst, $aSecond);
							$aData[$sSite][$sBot]['daily'][] = $aDataRow;
						}
					}
					
					$aDataRow = array_merge(array($sSite, $sBot, $sTimestamp), array_values($aPageTypes));
					$aData[$sSite][$sBot]['daily'][] = $aDataRow;
					$iLastTimestamp = strtotime($sTimestamp);			
				}
			}
		}
		return $aData;
	}	
	
	public function getSitesDaily($aSites)
	{
		$aData = array();
		foreach($aSites as $aSite) 
		{

			// Moraju da se prave intervali, moguce je da za neki dan nema rezultata po nekom botu ili uopste	
			//Daily	
			$sSql = "				
				SELECT 
					site,
					bot,
				  	page_type,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	SUM(value) AS value
				FROM
					site
				JOIN 
				  	stats_site_daily ON (site.id = site_id)
				JOIN 
				  	bot ON (bot.id = bot_id)
				JOIN 
				  	page_type ON (page_type.id = ptype_id)
				WHERE
				  	site.id = {$aSite['id']} AND
				  	stats_site_daily.timestamp >= ({$aSite['stats_timestamp']} - ($this->limitDays * 86400))		            
				GROUP BY
				  	site, timestamp desc, bot, page_type      
									";				
				
				$aResult = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aResult as $value) 
				{
					$sSite = $value['site'];
					$sBot = $value['bot'];
					$sTimestamp = $value['timestamp'];
					
					$aData[$sSite][$sBot][$sTimestamp] = $this->_pageTypes;
				}
				
				foreach($aResult as $value)
				{
					$sSite = $value['site'];
					$sBot = $value['bot'];
					$sPageType = $value['page_type'];
					$sTimestamp = $value['timestamp'];
					$sValue = $value['value'];

					$aData[$sSite][$sBot][$sTimestamp][$sPageType] = $sValue; 
				}
		}
		return $aData;
	}
	
	
	public function getSitesSum($aSites)
	{
		$aData = array();
		foreach($aSites as $aSite) 
		{
			$sSql = "
				SELECT 
					site,
				  	bot,
				  	page_type,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	value
				FROM
					site
				JOIN 
					stats_site_sum ON (site.id = site_id)
				JOIN 
					bot ON (bot.id = bot_id)
				JOIN 
					page_type ON (page_type.id = ptype_id)
				WHERE
					site.id = {$aSite['id']} AND
					stats_site_sum.timestamp >= ({$aSite['stats_timestamp']} - ($this->limitDays * 86400))										
				ORDER BY
					site, timestamp desc, bot, page_type			
					";
				
				$aResult = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aResult as $value) 
				{
					$sSite = $value['site'];
					$sBot = $value['bot'];
					$sTimestamp = $value['timestamp'];
					
					$aData[$sSite][$sBot][$sTimestamp] = $this->_pageTypes;
				}
				
				foreach($aResult as $value)
				{
					$sSite = $value['site'];
					$sBot = $value['bot'];
					$sPageType = $value['page_type'];
					$sTimestamp = $value['timestamp'];
					$sValue = $value['value'];

					$aData[$sSite][$sBot][$sTimestamp][$sPageType] = $sValue; 
				}
		}
		return $aData;
	}
	
	
	
	public function getSites()
	{
    	$sSql = "SELECT id, site, stats_timestamp FROM site where active = 1";
    	$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		return $aResult;
	}
	
	
	
	
	
	
	public function printDomainStats()
	{
		$aDomainStatsData = $this->getDomainStatsData();
		
		$aTable = $this->getDomainTable($aDomainStatsData);		

		foreach($aTable as $sDomain => $aDomainData) 
		{
			foreach($aDomainData as $sBot => $aBotData) 
			{
				echo $aBotData['sum'];
				echo $aBotData['daily'];
			}
			echo "\n\n\n\n";
		}		
	}

	public function getSitesCSV($aData)
	{
		$aCSV = array();
		$aHeaders = array_merge(array('domain', 'bot', 'date'), array_keys($this->_pageTypes));
				
		foreach($aData as $sSite => $aSiteData) 
		{
			foreach($aSiteData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sType => $aTableData) 
				{
					$aCSV[$sSite][$sBot][$sType] = $this->_parser->unparse($aTableData, $aHeaders);
				}
			}
		}
		return $aCSV;		
	}
	
	
	
	
	
	public function getSitesTable($aData)
	{
		$aTable = array();
		$aTableHeaders = array_merge(array('domain', 'bot', 'date'), array_keys($this->_pageTypes));
		$this->_table->setHeaders($aTableHeaders);
		
		foreach($aData as $sSite => $aSiteData) 
		{
			foreach($aSiteData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sType => $aTableData) 
				{
					$this->_table->addData($aTableData);
					$aTable[$sSite][$sBot][$sType] = $this->_table->getTable();
					$this->_table->_data = array();
				}
			}
		}
		return $aTable;		
	}
	
	public function getDomainTable($aData)
	{
		$aTable = array();
		$aTableHeaders = array_merge(array('domain', 'bot', 'date', 'sites'), array_keys($this->_pageTypes));
		$this->_table->setHeaders($aTableHeaders);
		
		foreach($aData as $sDomain => $aDomainData) 
		{
			foreach($aDomainData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sType => $aTableData) 
				{
					$this->_table->addData($aTableData);
					$aTable[$sDomain][$sBot][$sType] = $this->_table->getTable();
					$this->_table->_data = array();
				}
			}
		}
		return $aTable;		
	}
		
	public function getDomainStatsData()
	{
		$aData = array();
		
		$aDomains = $this->getDomains();

		$aDomainSum = $this->getDomainSum($aDomains);
		$aDomainSumCount = $this->getDomainSumCount($aDomains);
		$aDomainDaily = $this->getDomainDaily($aDomains);
		$aDomainDailyCount = $this->getDomainDailyCount($aDomains);
		
		foreach($aDomainSum as $sDomain => $aDomainData) 
		{
			foreach($aDomainData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sTimestamp => $aPageTypes) 
				{
					$sCount = $aDomainSumCount[$sDomain][$sBot][$sTimestamp];
					$aDataRow = (array_merge(array($sDomain, $sBot, $sTimestamp, $sCount), array_values($aPageTypes)));
					$aData[$sDomain][$sBot]['sum'][] = $aDataRow;
				}
			}
		}
		
		$iLastTimestamp = 0;
		
		foreach($aDomainDaily as $sDomain => $aDomainData) 
		{
			foreach($aDomainData as $sBot => $aBotData) 
			{
				foreach($aBotData as $sTimestamp => $aPageTypes) 
				{
					//Zakrpa za slucaj kada nema rezultata za neke dane
					if($iLastTimestamp && ($iLastTimestamp - strtotime($sTimestamp)) > 86400)
					{
						$aMissigDates = $this->getMissingDates($iLastTimestamp, strtotime($sTimestamp));
						foreach($aMissigDates as $sDate) 
						{
							$aFirst = array($sDomain, $sBot, $sDate, 0);
							$aSecond = array_fill(0, count($aPageTypes), 0);
							$aDataRow = array_merge($aFirst, $aSecond);
							$aData[$sDomain][$sBot]['daily'][] = $aDataRow;
						}
					}
					
					$sCount = $aDomainDailyCount[$sDomain][$sBot][$sTimestamp];
					$aDataRow = array_merge(array($sDomain, $sBot, $sTimestamp, $sCount), array_values($aPageTypes));
					$aData[$sDomain][$sBot]['daily'][] = $aDataRow;
					$iLastTimestamp = strtotime($sTimestamp);			
				}
			}
		}
		return $aData;
	}
	
	public function getDomainSum($aDomains)
	{
		$aData = array();
		foreach($aDomains as $aDomain) 
		{
			$sSql = "
				SELECT 
					domain,
				  	bot,
				  	page_type,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	value
				FROM
					domain
				JOIN 
					stats_domain_sum ON (domain.id = domain_id)
				JOIN 
					bot ON (bot.id = bot_id)
				JOIN 
					page_type ON (page_type.id = ptype_id)
				WHERE
					domain.id = {$aDomain['id']} AND
					stats_domain_sum.timestamp >= ({$aDomain['stats_timestamp']} - ($this->limitDays * 86400))										
				ORDER BY
					domain, timestamp desc, bot, page_type			
					";
				
				$aResult = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aResult as $value) 
				{
					$sDomain = $value['domain'];
					$sBot = $value['bot'];
					$sTimestamp = $value['timestamp'];
					
					$aData[$sDomain][$sBot][$sTimestamp] = $this->_pageTypes;
				}
				
				foreach($aResult as $value)
				{
					$sDomain = $value['domain'];
					$sBot = $value['bot'];
					$sPageType = $value['page_type'];
					$sTimestamp = $value['timestamp'];
					$sValue = $value['value'];

					$aData[$sDomain][$sBot][$sTimestamp][$sPageType] = $sValue; 
				}
		}
		return $aData;
	}
	
	public function getDomainSumCount($aDomains)
	{
		$aData = array();
		foreach($aDomains as $aDomain) 
		{
			//Sum site count				
			$sSql = "
				SELECT 
					domain,
				  	bot,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	value
				FROM
					domain
				JOIN 
					stats_domain_sum_count ON (domain.id = domain_id)
				JOIN 
					bot ON (bot.id = bot_id)
				WHERE
					domain.id = {$aDomain['id']} AND
					stats_domain_sum_count.timestamp >= ({$aDomain['stats_timestamp']} - ($this->limitDays * 86400))										
				ORDER BY
					domain, timestamp desc, bot			
					";
				
			$aResult = $this->_db->fetchAllFromQuery($sSql);
				
			foreach($aResult as $value) 
			{
				$sDomain = $value['domain'];
				$sBot = $value['bot'];
				$sTimestamp = $value['timestamp'];
				$sValue = $value['value'];
				
				$aData[$sDomain][$sBot][$sTimestamp] = $sValue;
			}
		}
		return $aData;
	}
	
	public function getDomainDaily($aDomains)
	{
		$aData = array();
		foreach($aDomains as $aDomain) 
		{

			// Moraju da se prave intervali, moguce je da za neki dan nema rezultata po nekom botu ili uopste	
			//Daily	
			$sSql = "				
				SELECT 
					domain,
					bot,
				  	page_type,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	SUM(value) AS value
				FROM
					domain
				JOIN 
				  	stats_domain_daily ON (domain.id = domain_id)
				JOIN 
				  	bot ON (bot.id = bot_id)
				JOIN 
				  	page_type ON (page_type.id = ptype_id)
				WHERE
				  	domain.id = {$aDomain['id']} AND
				  	stats_domain_daily.timestamp >= ({$aDomain['stats_timestamp']} - ($this->limitDays * 86400))		            
				GROUP BY
				  	domain, timestamp desc, bot, page_type      
									";				
				
				$aResult = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aResult as $value) 
				{
					$sDomain = $value['domain'];
					$sBot = $value['bot'];
					$sTimestamp = $value['timestamp'];
					
					$aData[$sDomain][$sBot][$sTimestamp] = $this->_pageTypes;
				}
				
				foreach($aResult as $value)
				{
					$sDomain = $value['domain'];
					$sBot = $value['bot'];
					$sPageType = $value['page_type'];
					$sTimestamp = $value['timestamp'];
					$sValue = $value['value'];

					$aData[$sDomain][$sBot][$sTimestamp][$sPageType] = $sValue; 
				}
		}
		return $aData;
	}
	
	public function getDomainDailyCount($aDomains)
	{
		$aData = array();
		foreach($aDomains as $aDomain) 
		{
			$sSql = "
				SELECT 
					domain,
				  	bot,
				  	FROM_UNIXTIME(timestamp, '%Y-%m-%d') AS timestamp,
				  	value
				FROM
					domain
				JOIN 
					stats_domain_daily_count ON (domain.id = domain_id)
				JOIN 
					bot ON (bot.id = bot_id)
				WHERE
					domain.id = {$aDomain['id']} AND
					stats_domain_daily_count.timestamp >= ({$aDomain['stats_timestamp']} - ($this->limitDays * 86400))										
				ORDER BY
					domain, timestamp desc, bot			
					";
				
				$aResult = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aResult as $value) 
				{
					$sDomain = $value['domain'];
					$sBot = $value['bot'];
					$sTimestamp = $value['timestamp'];
					$sValue = $value['value'];
					
					$aData[$sDomain][$sBot][$sTimestamp] = $sValue;
				}
		}
		return $aData;
	}
	
	public function getMissingDates($iMax, $iMin)
	{
		$aDates = array();
		$iMax -= 86400; 
		
		for ($i = $iMax; $i > $iMin; $i-= 86400) 
		{
			$sDate = date("Y-m-d", $i);
			$aDates[] = $sDate; 
		}
		return $aDates;
	}
	
	public function getDomains()
	{
    	$sSql = "SELECT id, domain, stats_timestamp FROM domain";
    	$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		return $aResult;
	}
	
	protected function getPageTypes()
	{
		$aData = array();
		$sSql = "SELECT * FROM page_type";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach ($aResult as $key => $value) 
			$aData[ $value['page_type'] ] = 0; 
		
		return $aData; 
	}
	
	public function __construct(cDb $oDb, cConsoleTable $oConsoleTable, cCSVParser $oCSVParser)
	{
		parent::__construct();
		$this->addDispatch('pd', 'printDomainStats', 'Print domain results');
		$this->addDispatch('ps', 'printSiteStats', 'Print site results');
		$this->_table = $oConsoleTable;
		$this->_db = $oDb;
		$this->_parser = $oCSVParser;
		
		$this->_pageTypes = $this->getPageTypes();
	}
	
	
	
	
	
	
	
	
}

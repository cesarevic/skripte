<?php
/**
 * cStatsResource class
 * Copyright (c) 2005-2011 PKM-Inc
 */
class cStatsResource
{
	/**
	 * Instance of cDb class  
	 *
	 * @var cDb
	 */
	protected $_db;
	
	protected $_bots = array();
	protected $_pageTypes = array();
	protected $_dbTime = 0;
	
	public $resultLimit = 10;

	public function getDomains()
	{
    	$sSql = "SELECT domain.id, domain, stats_timestamp FROM domain WHERE stats_timestamp < $this->_dbTime LIMIT $this->resultLimit";
    	$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		return $aResult;
	}
	
	public function updateDomains($aDomains)
	{
		foreach ($aDomains as $aDomain) 
		{
			try 
			{
				$sSql = "
					SELECT 
						DISTINCT timestamp
					FROM 
						domain
					JOIN 
						site ON (site.domain_id = domain.id)
					JOIN
						stats_site_daily ON (stats_site_daily.site_id = site.id)   
					WHERE 
						domain.id = {$aDomain['id']} AND
						stats_site_daily.timestamp > {$aDomain['stats_timestamp']} /*Optimizacija*/
					ORDER BY
						timestamp ASC					
						";
				
				$aIntervals = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aIntervals as $iTimestamp) 
				{
					$sSql = "
						SELECT 
						 	domain.id,
						 	domain,
						 	bot,
						 	page_type,
						 	SUM(value) AS value,
							stats_site_daily.timestamp
						FROM 
							domain
						JOIN 
							site ON (site.domain_id = domain.id)
						JOIN 
							stats_site_daily ON (stats_site_daily.site_id = site.id)
						JOIN 
							bot ON (stats_site_daily.bot_id = bot.id)
						JOIN 
							page_type ON (stats_site_daily.ptype_id = page_type.id)
	          			WHERE 
						  domain.id = {$aDomain['id']} AND
						  stats_site_daily.timestamp = $iTimestamp
						GROUP BY 
						  domain, bot, page_type
						  	";				
					
					$aResult = $this->_db->fetchAllFromQuery($sSql);
	
					$this->updateDomainStatsDaily($aResult);
					
					$sSql = "					
						SELECT
						    domain.id,
						    domain,
						    bot,
						    COUNT(distinct site_id) AS value,
						    stats_site_daily.timestamp
						FROM
						    domain
						JOIN
						    site ON (site.domain_id = domain.id)
						JOIN
						    stats_site_daily ON (site_id = site.id)
						JOIN
						    bot ON (bot_id = bot.id)
						JOIN
						    page_type ON (ptype_id = page_type.id)
						WHERE
						    domain.id = {$aDomain['id']} AND
						    stats_site_daily.timestamp = $iTimestamp
						GROUP BY
						    domain, bot
						  	";				
    
					$aResult = $this->_db->fetchAllFromQuery($sSql);
					$this->updateDomainStatsDailyCount($aResult);
				}
	
				$sSql = "
					SELECT 
						DISTINCT timestamp
					FROM 
						domain
					JOIN 
						site ON (site.domain_id = domain.id)
					JOIN
						stats_site_sum ON (stats_site_sum.site_id = site.id)   
					WHERE 
						domain.id = {$aDomain['id']} AND
						stats_site_sum.timestamp > {$aDomain['stats_timestamp']} /*Optimizacija*/
					ORDER BY
						timestamp ASC					
							";
				
				$aIntervals = $this->_db->fetchAllFromQuery($sSql);
				
				foreach($aIntervals as $iTimestamp)
				{			
					$sSql =	"
						SELECT 
						 	domain.id,
						 	domain,
						 	bot,
						 	page_type,
						 	SUM(value) AS value,
							stats_site_sum.timestamp
						FROM 
							domain
						JOIN 
							site ON (site.domain_id = domain.id)
						JOIN 
							stats_site_sum ON (stats_site_sum.site_id = site.id)
						JOIN 
							bot ON (stats_site_sum.bot_id = bot.id)
						JOIN 
							page_type ON (stats_site_sum.ptype_id = page_type.id)
	          			WHERE 
						  domain.id = {$aDomain['id']} AND
						  stats_site_sum.timestamp = $iTimestamp
						GROUP BY 
						  domain, bot, page_type
								";				
					
					$aResult = $this->_db->fetchAllFromQuery($sSql);
					$this->updateDomainStatsSum($aResult);

					$sSql = "					
						SELECT
							domain.id,
						  	domain,
						  	bot,
						  	COUNT(distinct site_id) AS value,
						  	stats_site_sum.timestamp
						FROM
						  	domain
						JOIN
						  	site ON (site.domain_id = domain.id)
						JOIN
						  	stats_site_sum ON (stats_site_sum.site_id = site.id)
						JOIN
						  	bot ON (stats_site_sum.bot_id = bot.id)
						JOIN
						  	page_type ON (stats_site_sum.ptype_id = page_type.id)
						WHERE
						  	domain.id = {$aDomain['id']} AND
						  	stats_site_sum.timestamp = $iTimestamp
						GROUP BY
						  	domain, bot
								";

					$aResult = $this->_db->fetchAllFromQuery($sSql);
					$this->updateDomainStatsSumCount($aResult);
				}
	
				$sSql = "UPDATE domain SET stats_timestamp = $this->_dbTime WHERE id = {$aDomain['id']}";
				$this->_db->query($sSql);
			}
			
			catch (cException $oException) 
			{
				echo $oException->getMessage();
				throw $oException;
			}
		}
	} 	

	protected function updateDomainStatsSumCount($aData)
	{
		foreach($aData as $aStats) 
		{
			$iId = $aStats['id'];
			$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
			$iTimestamp = $aStats['timestamp'];
			$iValue = $aStats['value'];
			
			$aInsertValues[] = "($iId, $iBotId, $iTimestamp, $iValue)";
		}
		
		$sValues = implode(', ', $aInsertValues);		
		$sSql = "INSERT INTO stats_domain_sum_count (domain_id, bot_id, timestamp, value) VALUES $sValues";
		$this->_db->query($sSql);
	}
	
	protected function updateDomainStatsSum($aData)
	{
		foreach($aData as $aStats) 
		{
			$iId = $aStats['id'];
			$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
			$iPageTypeId = $this->_pageTypes[ $aStats['page_type'] ]['id'];
			$iTimestamp = $aStats['timestamp'];
			$iValue = $aStats['value'];
			
			$aInsertValues[] = "($iId, $iBotId, $iPageTypeId, $iTimestamp, $iValue)";
		}
		
		$sValues = implode(', ', $aInsertValues);		
		$sSql = "INSERT INTO stats_domain_sum (domain_id, bot_id, ptype_id, timestamp, value) VALUES $sValues";
		$this->_db->query($sSql);
	}

	protected function updateDomainStatsDailyCount($aData)
	{
		foreach($aData as $aStats) 
		{
			$iId = $aStats['id'];
			$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
			$iTimestamp = $aStats['timestamp'];
			$iValue = $aStats['value'];
			
			$aInsertValues[] = "($iId, $iBotId, $iTimestamp, $iValue)";
		}
		
		$sValues = implode(', ', $aInsertValues);		
		$sSql = "INSERT INTO stats_domain_daily_count (domain_id, bot_id, timestamp, value) VALUES $sValues";
		$this->_db->query($sSql);
		
	}
	
	protected function updateDomainStatsDaily($aData)
	{
		foreach($aData as $aStats) 
		{
			$iId = $aStats['id'];
			$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
			$iPageTypeId = $this->_pageTypes[ $aStats['page_type'] ]['id'];
			$iTimestamp = $aStats['timestamp'];
			$iValue = $aStats['value'];
			
			$aInsertValues[] = "($iId, $iBotId, $iPageTypeId, $iTimestamp, $iValue)";
		}
		
		$sValues = implode(', ', $aInsertValues);		
		$sSql = "INSERT INTO stats_domain_daily (domain_id, bot_id, ptype_id, timestamp, value) VALUES $sValues";
		$this->_db->query($sSql);
		
	}
	
	public function getSites()
    {
    	$sSql = "SELECT site.id, site, stats_timestamp FROM site WHERE stats_timestamp < $this->_dbTime LIMIT $this->resultLimit";
    	$aResult = $this->_db->fetchAllFromQuery($sSql);

    	return $aResult;
	}
		
	public function updateSites($aSites)
	{
		foreach ($aSites as $aSite) 
		{
			try 
			{
				$sSql = "
					SELECT 
						DISTINCT 
						  UNIX_TIMESTAMP(FROM_UNIXTIME(log.timestamp, '%Y-%m-%d')) AS start,
						  UNIX_TIMESTAMP(FROM_UNIXTIME(log.timestamp, '%Y-%m-%d')) + (24*60*60-1) AS end
					FROM 
						site
					JOIN 
						url ON (url.site_id = site.id)
					JOIN 
						log ON (log.url_id = url.id)
					WHERE 
						site.id = {$aSite['id']} AND
						log.timestamp > {$aSite['stats_timestamp']} AND
						log.timestamp <= $this->_dbTime
					ORDER BY
						start ASC
					  		";
				
				$aIntervals = $this->_db->fetchAllFromQuery($sSql);
					
				foreach($aIntervals as $value) 
				{
					$sSql = "
						SELECT 
						  site.id,
						  site,
						  bot,
						  page_type,
						  count(distinct url.id) AS count,
						  {$value['end']} AS timestamp
						FROM 
							site
						JOIN 
							url ON (url.site_id = site.id)
						JOIN 
							page_type ON (url.ptype_id = page_type.id)
						JOIN 
							log ON (log.url_id = url.id)
						JOIN 
							bot_ua ON (log.botua_id = bot_ua.id)
						JOIN 
							bot ON (bot_ua.bot_id = bot.id)          
						WHERE 
						  site.id = {$aSite['id']} AND
						  log.timestamp >= {$value['start']} AND
						  log.timestamp <= {$value['end']}
						GROUP BY 
						  bot, site, page_type
						  		";				
					
					$aResult = $this->_db->fetchAllFromQuery($sSql);

					$this->updateSiteStatsDaily($aResult);
				}
				
				$iMin = 0;
				if($aSite['stats_timestamp'] > 0)
					$iMin = $aSite['stats_timestamp'] + 1;	
				elseif($aSite['stats_timestamp'] == 0 && !empty($aIntervals))	
					$iMin = $aIntervals[0]['start'];

				//Ovaj slucaj je moguc kada sajt ima par log entry-ja koji spadaju u dan posle $this->_dbTime 	
				if($iMin > 0)	
					$aIntervals = $this->getIntervals($iMin, $this->_dbTime);
				
				foreach($aIntervals as $value) 
				{
					$sSql = " 								
							SELECT 
							  site.id,
							  site,
							  bot,
							  page_type,
							  count(distinct url.id) AS count,
							  {$value['end']} AS timestamp
							FROM 
								site
							JOIN 
								url ON (url.site_id = site.id)
							JOIN 
								page_type ON (url.ptype_id = page_type.id)
							JOIN 
								log ON (log.url_id = url.id)
							JOIN 
								bot_ua ON (log.botua_id = bot_ua.id)
							JOIN 
								bot ON (bot_ua.bot_id = bot.id)          
							WHERE 
							  site.id = {$aSite['id']} AND
							  log.timestamp <= {$value['end']}
							GROUP BY 
							  bot, site, page_type";				
		
						$aResult = $this->_db->fetchAllFromQuery($sSql);
						$this->updateSiteStatsSum($aResult);
				}
				
				$sSql = "UPDATE site SET stats_timestamp = $this->_dbTime WHERE id = {$aSite['id']}";
				$this->_db->query($sSql);
			}
			
			catch (cException $oException) 
			{
				echo $oException->getMessage();
				throw $oException;
			}
				
		}
	} 	

	public function getIntervals($iMin, $iMax)
	{
		$iStart = mktime(0, 0, 0, date("n", $iMin), date("j", $iMin), date("Y", $iMin));
		$iEnd = mktime(23, 59, 59, date("n", $iMax), date("j", $iMax), date("Y", $iMax));
		
		$iDays = ceil(($iEnd - $iStart) / (24*60*60)); 

		$iDayStart = $iStart;

		for($i = 1; $i <= $iDays; $i++) 
		{
			$iDayEnd = mktime(23, 59, 59, date("n", $iDayStart), date("j", $iDayStart), date("Y", $iDayStart));
			$aDays[$i] = array('start' => $iDayStart, 'end' => $iDayEnd);
			$iDayStart = $iDayEnd + 1;
		}
/*
		foreach ($aDays as $key => $value) 
		{
			$iDayStart = date("c", $value['start']);
			$iDayEnd =  date("c", $value['end']);
			$aTest[$key] = array('start' => $iDayStart, 'end' => $iDayEnd);
		}
*/		
/*		if(isset($aDays))
		{
			$aDays[1]['start']		= $iMin;
			$aDays[$iDays]['end']	= $iMax;
		}
*/
		return $aDays;
	}
	
	protected function updateSiteStatsDaily($aData)
	{
			foreach($aData as $aStats) 
			{
				$iId = $aStats['id'];
				$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
				$iPageTypeId = $this->_pageTypes[ $aStats['page_type'] ]['id'];
				$iTimestamp = $aStats['timestamp'];
				$iValue = $aStats['count'];
				
				$aInsertValues[] = "($iId, $iBotId, $iPageTypeId, $iTimestamp, $iValue)";
			}
			
			$sValues = implode(', ', $aInsertValues);		
			$sSql = "INSERT INTO stats_site_daily (site_id, bot_id, ptype_id, timestamp, value) VALUES $sValues";
			$this->_db->query($sSql);
	}

	protected function updateSiteStatsSum($aData)
	{
		foreach($aData as $aStats) 
		{
			$iId = $aStats['id'];
			$iBotId = $this->_bots[ $aStats['bot'] ]['id'];
			$iPageTypeId = $this->_pageTypes[ $aStats['page_type'] ]['id'];
			$iTimestamp = $aStats['timestamp'];
			$iValue = $aStats['count'];
			
			$aInsertValues[] = "($iId, $iBotId, $iPageTypeId, $iTimestamp, $iValue)";
		}
		
		$sValues = implode(', ', $aInsertValues);		
		$sSql = "INSERT INTO stats_site_sum (site_id, bot_id, ptype_id, timestamp, value) VALUES $sValues";
		$this->_db->query($sSql);
	}
	
	public function warmUp()
	{
		$this->updateStatsTime();
		$this->getBots();
		$this->getPageTypes();
	}
	
	protected function updateStatsTime()
	{
		$iDate = 1;
		$sSql = "SELECT MAX(timestamp) AS time FROM log";
		$iDbTime = (int) $this->_db->fetchOneFromQuery($sSql, "time");
				
		$iTimestamp = $iDbTime - 24*60*60;
		$iDate = mktime(23, 59, 59, date("n", $iTimestamp), date("j", $iTimestamp), date("Y", $iTimestamp));
		
		$this->_dbTime = $iDate;
	}
		
	protected function getBots()
	{
		$aData = array();
		$sSql = "SELECT * FROM bot";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach ($aResult as $key => $value) 
		{
			$aData[ $value['bot'] ]['id'] = $value['id']; 
		}
		
		$this->_bots = $aData;
	}
	
	protected function getPageTypes()
	{
		$aData = array();
		$sSql = "SELECT * FROM page_type";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach ($aResult as $key => $value) 
		{
			$aData[ $value['page_type'] ]['id'] = $value['id']; 
		}
		
		$this->_pageTypes = $aData; 
	}
	
	public function __construct(cDb $oDb)
	{
		$this->_db = $oDb;
		$this->warmUp();
	}
}
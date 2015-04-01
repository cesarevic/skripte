<?php
/**
 * Cli Application controller
 * Copyright (c) 2005-2012 ViewSource
 */
class cQueryModel extends cModelAbstract
{
	/**
	 * Database connection
	 *
	 * @var cDb
	 */
	protected $_db;

	public $queueLimit = 1000;
	
	public $lockTime = 1;  // 30*60
 	
	public $pingTime = 1; // 12*60*60
	
	protected $_queue = array();
	
	protected $_results = array();
	
	protected $_data = array();
	
	

	public function getData()
	{
		if (empty($this->_results) && !$this->select()) 
			return FALSE;
		else
			return array_pop($this->_results);
	}

	public function add($aItem)
	{
		if ($this->is_Available())
			$this->_queue[] = $aItem;
		
		if (!$this->is_Available())
			$this->update();
	}

	public function update()
	{
		
		while (!empty($this->_queue))
		{
			$aItem = array_pop($this->_queue);
			$sUrl = $aItem['url'];
			$iId = $this->_data[$sUrl]['id'];
			$aIds[] = $iId;
				
			unset($this->_data[$sUrl]);
		}
		
		if (!empty($aIds)) 
		{
			$sIds = implode(", ", $aIds);
			
			$sQuery = "
				UPDATE
					urls
				SET
					timestamp_sitemap_ping = '" . time() . "',
					count_sitemap_ping = count_sitemap_ping + 1,
					pid = 0
				WHERE
					id in ($sIds)";
			
			$this->_db->query($sQuery);
		}
		
	}
	
	
	public function is_Available()
	{
		if (count($this->_queue) >= $this->queueLimit)
			return FALSE;
			
		return TRUE;
	}
	
	protected function select()
	{
		$iPid = getmypid();
		$iTimeStamp = time();
		$iLockTime = $iTimeStamp - $this->lockTime;
		
		$sQuery = "
			UPDATE
				queue
			SET
				pid = $iPid,
				timestamp = $iTimeStamp
			WHERE
				queue.timestamp < $iLockTime AND
				(queue.pid = 0 OR queue.pid <> $iPid)
			ORDER BY
				id										
			LIMIT
				$this->queueLimit";
		
		$this->_db->query($sQuery);
		
		$sQuery = "
			SELECT
				queue.id as id,
				queue.keyword as keyword
			FROM
				queue
			WHERE
				queue.timestamp = $iTimeStamp AND
				queue.pid = $iPid
			";
		
		$aResults = $this->_db->fetchAllFromQuery($sQuery);
		
		if (!empty($aResults)) 
		{
			foreach ($aResults as $aResult)
			{
				$sKeyword = $aResult['keyword'];
				$this->_data[$sKeyword] = $aResult;
				$this->_results[$sKeyword]= $sKeyword;
			}
			return TRUE;
		}
		else 
			return FALSE;
	}
	
	
	
	/**
	 * Insert sitemap urls
	 * @param resoure $sFile
	 */
	public function importSitemapUrls($sFile)
	{

		$aUrls = file($sFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$iCount = count($aUrls);
		
		shuffle($aUrls);
		
		
		foreach ($aUrls as &$sUrl)
			if (strpos($sUrl, 'http://') === FALSE)
				$sUrl = "http://" . $sUrl;
		
		foreach ($aUrls as $sUrl) 
		{
           $sUrl = $this->_db->realEscapeString($sUrl);
           $this->_db->query("INSERT INTO urls SET url = '$sUrl', active = 1");
		}  		
	}
	
	public function importKeywords($sFile)
	{
		$aKeywords = file($sFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		$aValues = array();
		$sValues = NULL;

		foreach ($aKeywords as $sKeyword) 
		{
			$sValues .= "('$sKeyword'), ";

			if (strlen($sValues) > 20000) 
			{
				$sValues = trim($sValues, ", ");
				$aValues[] = $sValues;
				$sValues = NULL;
			}
		}

		if (!empty($sValues)) 
		{
			$sValues = trim($sValues, ", ");
			$aValues[] = $sValues;
		}
		
		foreach ($aValues as $sValues) 
		{
			$sQuery = "INSERT IGNORE INTO queue(keyword) VALUES $sValues";
				
			$this->_db->query($sQuery);
			
		}
	}
	
	

	public function deleteAll()
	{
	    $this->_db->query("TRUNCATE TABLE urls");
	}

	
	// parametre treba da vuce iz property-ja
	public function run()
	{
	}
	
	public function updateSitemapUrl($aSitemapUrls)
	{
		if(!empty($aSitemapUrls))
		{
			$aIds = array();
			foreach ($aSitemapUrls as $aUrl)
				$aIds[] = $aUrl['id'];
			$sIds = implode(", ", $aIds);
	
			$sQuery = "UPDATE
				url_dsg
			SET
				stamp_sitemap_ping = '" . time() . "', count_sitemap_ping = count_sitemap_ping + 1
					WHERE
					id in ($sIds)";
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * Class constructor
	 *
	 * @param cDb $oDb
	 */
	public function __construct(cDb $oDb)
	{
		$this->_db = $oDb;
	}
}
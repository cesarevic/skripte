<?php

class cCurlCache extends cModelAbstract
{
	/**
	 * Database connection
	 *
	 * @var cDb
	 */
	protected $_db;

	public $queueLimit = 90;
	
	protected $_queue = array();
	
	protected $_results = array();
	
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
			$this->insert();
	}

	public function insert()
	{
		while (!empty($this->_queue))
		{
			$sSQLValues = "";
				
			$aItem = array_pop($this->_queue);
			$sQuery = $aItem['query'];
			$sService = $aItem['service'];
			$oCurlResult = $aItem['result'];
		    
			$aInfo = array('info' => $oCurlResult->getInfo(), 'opt' => $oCurlResult->getOpt());
			
			$sSQLBody = $this->_db->realEscapeString($oCurlResult->getBody(TRUE));
		    $sSQLHeader = $this->_db->realEscapeString($oCurlResult->getHeader(TRUE));

		    //$aInfo contains a resource, json_encode can't encode resources 
		    $sSQLInfo = $this->_db->realEscapeString(gzcompress(@cVariableHelper::jsonEncode($aInfo)));
		    $sSQLQuery = $this->_db->realEscapeString($sQuery);
			$sTime = time();
			
		    $sSQLValues .= "('$sSQLQuery', '$sService', '$sSQLHeader', '$sSQLBody', '$sSQLInfo', $sTime), ";
		    
		    if (!empty($sSQLValues))
		    {
		    	$sSQLValues = trim($sSQLValues, ", ");
		    	$sSQLInsert = "INSERT INTO result(query, service, head, body, info, timestamp) VALUES " . $sSQLValues;
		    		
		    	$this->_db->query($sSQLInsert);
		    }
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
		$sSQL = "
			SELECT
				result.id AS id,
				result.query AS query,
				result.service AS service,
				result.head AS head,
				result.body AS body,
				result.info AS info
			FROM
				result
			LIMIT $this->queueLimit				
			";
		
		$aResults = $this->_db->fetchAllFromQuery($sSQL);
		
		if (!empty($aResults)) 
		{
			foreach ($aResults as $aResult)
			{
					$aIds[] = $aResult['id'];
					$sQuery = $aResult['query'];
					$sService = $aResult['service'];
					$sHeader = gzuncompress($aResult['head']);
					$sBody = gzuncompress($aResult['body']);
					$sInfo = gzuncompress($aResult['info']);
					
					$aInfo = cVariableHelper::jsonDecode($sInfo);
					
					$oCurlResult = new cCurlResult($aInfo['opt'], $aInfo['info'], $sHeader . $sBody);
					
					//Redudantan kod, izmeniti curl result da moze da primi kompresovan rezultat.
					$oCurlResult->compress();
					
					$this->_results[] = array('result' => $oCurlResult, 'service' => $sService, 'query' => $sQuery);
			}
			
			$sIds = implode(',', $aIds);
			
			//!!! Sta sa ovim ? 
			
// 			$sSQL = "DELETE FROM result WHERE id IN ($sIds)";					
// 			$this->_db->query($sSQL);
			
			return TRUE;
		}
		else 
			return FALSE;
	}
	

	public function deleteAll()
	{
	    $this->_db->query("TRUNCATE TABLE result");
	}

	
	// parametre treba da vuce iz property-ja
	public function run()
	{
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


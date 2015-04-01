<?php
/**
 * cCSVLogResource class
 * Copyright (c) 2005-2011 PKM-Inc
 */
class cCSVLogResource
{
	public $_handle;
	
	
	/**
	 * Instance of cCSVParser class  
	 *
	 * @var cCSVParser
	 */
	public $_parser;
	
	public $resultsLimit = 10000;
	
	public function getData()
	{
		$iBytes = 128*1024;

		$sCSV = "";
		$aData = array();
				
		for ($i = 0; $i <= $this->resultsLimit; $i++) 
		{
			if(!feof($this->_handle))
				$sCSV .= stream_get_line($this->_handle, $iBytes, "\n") . "\n";
			else 
				break;				
		}

		if($sCSV !== "")
		{
			$this->_parser->delimiter = ";";
			$this->_parser->fields = array('url', 'ua', 'time');
	//		$this->_parser->offset = 1;
	//		$this->_parser->heading = true;
			$this->_parser->parse($sCSV);
			$aData = $this->_parser->data;
		}

		return $aData;
	}
	
	public function __construct(cCSVParser $oCSVParser)
	{
		$this->_parser = $oCSVParser;
		$this->_handle = gzopen("./log/access_log_8.csv.gz", "r");
	}
}
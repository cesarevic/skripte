<?php

class cTidy extends tidy
{
	public $aTidyConfig;
	
	public function clean($sInput)
	{
		$sResult = $sInput;
		
		$this->parseString($sResult, $this->aTidyConfig, 'utf8');
		
		$this->cleanRepair();
		
		$sResult = $this->body()->value;
	
		return $sResult;
	}	
}
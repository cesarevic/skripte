<?php
/**
 * Framework core component
 * Copyright (c) 2005-2009 ViewSource
 */
class cLogWriterToFile extends cLogWriterMemory
{
	
	
	protected function _write($aMessageData)
	{	
		$sMessage = $aMessageData['Id'] . '   ' . $aMessageData['Message'] . "\n" . str_repeat('-', 100) . "\n";
		file_put_contents("log.txt", $sMessage, FILE_APPEND);
	}
		
}
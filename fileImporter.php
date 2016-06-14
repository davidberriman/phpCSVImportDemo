<?php
# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      14/06/2016
#  Purpose:   Import product data from CSV into database
#  Plblic functions: 
#  			   importCSV : import the CSV file specified 

#  Usage Example:
#                     require_once("fileImporter.php");
#                     $importer = new FileImporter();
#                     $importer->importCSV($file);
#
# ========================================================================#

class FileImporter
{
	// ------------------------------------------------------
	// Class variables
	// ------------------------------------------------------
	private $array;
	private $errorRecords;
	
	public  $error;
	public  $numberProcessed = 0;
	public  $numberImported = 0;
	public  $numberFailed = 0;
	 
	
	// ------------------------------------
	// Import CSV file into database
	// ------------------------------------
	public function importCSV($file)
	{
		if(!$this->parseCSV($file))
		{
			return false;
		}
		
		if(!$this->processArray())
		{
			return false;
		}
		
		return true;
	}
	
	
	// ------------------------------------
	// Convert the CSV file to an array
	// ------------------------------------
	private function parseCSV($file)
	{

		// check we have a value to work with
		if(!isset($file) )
		{
			$this-> error = "ERROR - file data was not found". PHP_EOL;
			return false;	
		}	
		
		$this->array = explode(PHP_EOL, $file);
		
		if(!is_array( $this->array ))
		{
			$this-> error = "ERROR - file data could not be converted". PHP_EOL;
			return false;	
		}
		
		return true;
	}
	
	
	private function processArray()
	{
		
		foreach ($this->array as &$value) 
		{
			$this->numberProcessed++;
			
			// convert file contents into a php array
			$array = explode(",", $value);
			
			// check we have an array from the CSV data
			if(!is_array($array) || count($array) != 6)
			{
				$this->numberFailed++;
			}else
			{
				if($this->insertIntoDatabase())
				{
					$this->numberImported++;
				}else
				{
					$this->numberFailed++;
				}
			}
		}

		return true;
	}
	
	
	
	// ------------------------------------
	// Email function 
	// ------------------------------------
	private function insertIntoDatabase($array)
	{
	
	}	
	
	

}
?>
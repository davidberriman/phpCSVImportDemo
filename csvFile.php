<?php

# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      14/06/2016
#  Purpose:   Import product data from CSV into database
#  Plblic functions: 
#  			   importCSV : import the CSV file specified 
#
#  Usage Example:
#                     require_once("csvFile.php");
#                     $importer = new CSVFile("fileToBeProcessed.csv");
#					  $importer->parseCSV();  // check this equals true
#
# ========================================================================#

class CSVFile
{
	// ------------------------------------------------------
	// Class variables
	// ------------------------------------------------------
	protected $array;
	
	protected $file;

	public  $error;
	public  $numberRecords = 0;


	
	// -------------------------------------------------------------------
	// copy file to class property
	// -------------------------------------------------------------------
	public function __construct($file)
	{
		$this->file = $file;
	}
	

	
	// -------------------------------------------------------------------
	// Convert the CSV file to an array
	// -------------------------------------------------------------------
	public function parseCSV()
	{
			
		// check we have a value to work with
		if(!isset($this->file) )
		{
			$this-> error = "ERROR - file data was not found". PHP_EOL;
			return false;	
		}	
		
		// make the CSV file an array -> each line is an array item
		$this->array = explode(PHP_EOL, $this->file);
		
		// check we have the expected datq
		if(!is_array( $this->array ))
		{
			$this-> error = "ERROR - file data could not be processed". PHP_EOL;
			return false;	
		}
		
		
		// count the array to get the number of lines
		$this->numberRecords = count($this->array);
		
		$i = 0;
		
		// go through each line and check each one has commas to ensure we
		// have a proper CSV file
		foreach ($this->array as &$value) 
		{		
			// convert file contents into a php array
			$array = explode(",", $value);
			
			// check each line has commas so we have more than count() = 1. Also check it is not
			// the last line becasue that may not have any values
			if((!is_array($array) || count($array) < 2) && $i != (count($this->array) - 1))
			{
				$this-> error = "ERROR - file data was not in CSV format. Line number ".$i." did not have commas ". PHP_EOL;
				return false;
			}
			
			$i++;
		}
		
		return true;
	}
	
		

}
?>
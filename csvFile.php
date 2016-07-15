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
<<<<<<< HEAD
	protected $CSVLineArray;   // each line of the CSV file is an item in the array
	protected $importFile;
=======
	protected $array;
	
	protected $file;
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25

	public  $error;
	public  $numberRecords = 0;


	
	// -------------------------------------------------------------------
	// copy file to class property
	// -------------------------------------------------------------------
	public function __construct($file)
	{
<<<<<<< HEAD
		$this->importFile = $file;
=======
		$this->file = $file;
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25
	}
	

	
	// -------------------------------------------------------------------
	// Convert the CSV file to an array
	// -------------------------------------------------------------------
	public function parseCSV()
	{
			
		// check we have a value to work with
<<<<<<< HEAD
		if(!isset($this->importFile) )
		{
			$this-> error = "ERROR - file data was not found". PHP_EOL;
			return false;	
		}
				
		// make the CSV file an array -> each line is an array item
		$this->CSVLineArray = explode(PHP_EOL, $this->importFile);
		
		// check we have the expected datq
		if(!is_array( $this->CSVLineArray ))
=======
		if(!isset($this->file) )
		{
			$this-> error = "ERROR - file data was not found". PHP_EOL;
			return false;	
		}	
		
		// make the CSV file an array -> each line is an array item
		$this->array = explode(PHP_EOL, $this->file);
		
		// check we have the expected data
		if(!is_array( $this->array ))
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25
		{
			$this-> error = "ERROR - file data could not be processed". PHP_EOL;
			return false;	
		}
		
		
		// count the array to get the number of lines
<<<<<<< HEAD
		$this->numberRecords = count($this->CSVLineArray);
=======
		$this->numberRecords = count($this->array);
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25
		
		$i = 0;
		
		// go through each line and check each one has commas to ensure we
		// have a proper CSV file
<<<<<<< HEAD
		foreach ($this->CSVLineArray as &$value) 
=======
		foreach ($this->array as &$value) 
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25
		{		
			// convert file contents into a php array
			$array = explode(",", $value);
			
			// check each line has commas so we have more than count() = 1. Also check it is not
<<<<<<< HEAD
			// the last line becasue that may not have any values
			if((!is_array($array) || count($array) < 2) && $i != (count($this->CSVLineArray) - 1))
=======
			// the last line becasue that may not have any values ie. is just a carrage return.
			if((!is_array($array) || count($array) < 2) && $i != (count($this->array) - 1))
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25
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
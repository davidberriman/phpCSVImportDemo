<?php

require_once('sanitize.php');
require_once('connect.php');

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
	
	public  $processedRecords;
	public  $error;
	public  $numberProcessed = 0;
	public  $numberImported = 0;
	public  $numberFailed = 0;
	

	
	// -------------------------------------------------------------------
	// Import CSV file into database
	// -------------------------------------------------------------------
	public function importCSV($file, $test)
	{
		// convert file to an array for easy manipulation
		if(!$this->parseCSV($file))
		{
			return false;
		}
		
		// process array to import data
		if(!$this->processCSVLines($test))
		{
			return false;
		}
		
		return true;
	}
	
	
	// -------------------------------------------------------------------
	// Convert the CSV file to an array
	// -------------------------------------------------------------------
	private function parseCSV($file)
	{

		// check we have a value to work with
		if(!isset($file) )
		{
			$this-> error = "ERROR - file data was not found". PHP_EOL;
			return false;	
		}	
		
		// make the CSV file an array -> each line is an array item
		$this->array = explode(PHP_EOL, $file);
		
		// check we have the expected datq
		if(!is_array( $this->array ))
		{
			$this-> error = "ERROR - file data could not be converted". PHP_EOL;
			return false;	
		}
		
		// remove the first line which is a row of headings
		unset($this->array[0]);
		
		return true;
	}
	
	
	
	// -------------------------------------------------------------------
	// Main driving function that loops through the array 
	// and calls the processLine method for each line of the CSV file
	// -------------------------------------------------------------------
	private function processCSVLines($test)
	{
		// make connection to database
		$Database = new Connect(); 
		$mysqli = $Database->getConnection();
		
		if (mysqli_connect_errno()) {
			$this-> error = "ERROR - could not connect to the database". PHP_EOL.  $Database->error. PHP_EOL;
			return false;
		}
		
		// set timezone
		date_default_timezone_set('Europe/London');
				
		// processedRecords is an array that saves all the processing information
		// for the log message output		
		// make a null value at position zero so the position in the array
		// corresponds to the line number in the file
		$this->processedRecords[0] = "";
		
		// create a Sanitize object to pass to the insertIntoDatabase metheod
		// in the loop - saves creating a new object everytime
		$clean = new Sanitize(); 
		
		foreach ($this->array as &$value) 
		{
			$this->processLine($value, $mysqli, $clean, $test);
		}
		
		// close databse connections
		$mysqli->close();

		return true;
	}
	
	
	
	
	// -------------------------------------------------------------------
	// Process the values in each line of the CSV file and call the
	// insertIntoDatabase method to insert into database if no errors are found 
	// -------------------------------------------------------------------
	private function processLine($value, $mysqli, $clean, $test)
	{
		
		// convert file contents into a php array
		$array = explode(",", $value);
		
		// incrememnt the numberProcessed variable
		$this->numberProcessed++;
				
		// variable to see if this row in the CSV can be proccessed (had no errors)
		$process = true;
		
		
		// save the data that was used in the process 
		// so we can use that in the report
		$this->makeOutputArray('data', $value);
		
		// save line number in the output array for reporting
		$this->makeOutputArray('line', $this->numberProcessed);
		
		if(!is_array($array))
		{
			$this->makeOutputArray('reason', "Data could not be converted into an array");
			$process = false;
		}
			
		if($process && count($array) != 6)
		{
			$this->makeOutputArray('reason', "Data had incorrect number of columns");
			$process = false;
		}
		
		// check data is valid
		if($process && !$this->isValid($array))
		{
			$process = false;
		}else
		{
			// if it is then check it meets the import criteria
			if($process && !$this->meetsImportRules($array))
			{
				$process = false;
			}
		}
		
		// do not process any further in test mode
		if($test == "TEST=Y")
		{
			if($process)
			{
				$this->numberImported++;
				$this->makeOutputArray('output', "SUCCESS");
			}else
			{
				$this->numberFailed++;
				$this->makeOutputArray('output', "ERROR");
			}
			return true;	
		}
		
		// process if row had no errors
		if($process)
		{
			if($this->insertIntoDatabase($mysqli, $array, $clean))
			{
				$this->numberImported++;
				$this->makeOutputArray('output', "SUCCESS");
			}else
			{
				$this->numberFailed++;
				$this->makeOutputArray('output', "ERROR");
			}
		}else
		{
			$this->numberFailed++;
			$this->makeOutputArray('output', "ERROR");
		}
	}
	
	
	
	// -------------------------------------------------------------------
	// Check if the data meets the import criteria
	// -------------------------------------------------------------------
	private function meetsImportRules($array)
	{
		// check item meets the criteria for import 
		// Any stock item which costs less that £5 and has less than 10 stock will not be imported. 
		// Any stock items which cost over £1000 will not be imported.
		
		if(isset($array[4]))
		{
			$productCost  = $array[4];
			if($productCost > 1000)
			{
				$this->makeOutputArray('reason', "Does not meet criteria for import - item cost: {$productCost} is greater than 1000");
				return false;
			}
		}
		
		if(isset($array[3]) && isset($array[4]))
		{
			$productStock = $array[3];
			$productCost  = $array[4];
			if($productStock < 10 && $productCost < 5)
			{
				$this->makeOutputArray('reason', "Does not meet criteria for import - Item stock: {$productStock} & item cost: {$productCost} (costs < 5 & stock < 10)");
				return false;
			}
		}
		
		return true;
	}
	
	
	// -------------------------------------------------------------------
	// Check each of the values in the array are the correct data type
	// 
	// -------------------------------------------------------------------
	private function isValid($array)
	{
		// check item 3 is int 
		if(isset($array[3]))
		{
			if(!is_numeric($array[3]) && !is_int($array[3]))
			{
				$this->makeOutputArray('reason', "Data in column 4 was not an integer");
				return false;
			}
			
		}
		
		// get float value then compare ($array[4] is the same to ensure we have a float
		if( isset($array[4]))
		{
			// check item 4 is float				
			if( !is_numeric($array[4]) )
			{
				// could make this error message a less technical term if necessary
				$this->makeOutputArray('reason', "Data in column 5 was not a float");
				return false;
			}
		}
		
		$numbers = array(0, 1, 2, 5);
		
		// check remaining items are strings
		foreach ($numbers as &$value) 
		{
			if(isset($array[$value]))
			{
				if( !is_string($array[$value]))
				{
					$this->makeOutputArray('reason', "Data in column {$value} was not a string");
					return false;
				}
				
				if($this->isEncodingOK($array[$value]) === false)
				{
					$this->makeOutputArray('reason', "The character encoding was not UTF8 complient");
					return false;
				}
			}
			
		}
		
		return true;		
	}
	
	
	
	// -------------------------------------------------------------------
	// Check character encoding is acceptable
	// -------------------------------------------------------------------
	private function isEncodingOK($value)
	{
		$encoding = mb_detect_encoding($value);
		
		if($encoding == "UTF-8")
		{
			return $value;
		}
			
		// iconv returns false if encoding failed
		return iconv($encoding, "UTF-8", $value);
	}
	
	
	
	// -------------------------------------------------------------------
	// Make output array which whill be used for an HTML file with
	// processing information
	// -------------------------------------------------------------------
	private function makeOutputArray($id, $value)
	{
		$this->processedRecords[$this->numberProcessed - 1][$id] = $value;
	}
	
					
	
	// -------------------------------------------------------------------
	// Insert data into the database 
	// -------------------------------------------------------------------
	private function insertIntoDatabase($mysqli, $array, $clean)
	{	
		// create SQL now and use it in the loop
		$sql =" INSERT INTO tblProductData 
		(strProductCode, strProductName, strProductDesc, intProductStock, fltProductCost, strDiscontinued, dtmDiscontinued) 
		VALUES (?, ?, ?, ?, ?, ?, ?) ";
			
		$stmt = $mysqli->prepare($sql);
							
		if ($stmt == false || $stmt->error) 
		{
			$this-> error = "ERROR - could not insert into the database (" . $mysqli->errno . ") ". $mysqli->error . PHP_EOL;
			return false;
		}
		
		// (strProductCode, strProductName, strProductDesc, intProductStock, fltProductCost, strDiscontinued) 
		$productCode  = $clean->clean($array[0]);
		$productName  = $clean->clean($array[1]);
		$productDesc  = $clean->clean($array[2]);
		$productStock = $clean->clean($array[3]);
		$productCost  = $clean->clean($array[4]);
		$productDiss  = $clean->clean($array[5]);
		
		if($productDiss == "yes")
		{
			$discontinuedDate = date("Y-m-d H:i:s");	
		}else
		{
			$discontinuedDate = NULL;
		}
		
		// sanatize data and insert it into the database
		// so nothing nasty is entered
		$stmt->bind_param('sssidss', 
						$productCode, 
						$productName, 
						$productDesc, 
						$productStock, 
						$productCost, 
						$productDiss,
						$discontinuedDate);
		
		if ($stmt == false || $stmt->error) 
		{
			$this->makeOutputArray('reason', "Error inserting into database : ".$stmt->error);
			return false;
		}
		
		$stmt->execute();
		
		if ($stmt == false || $stmt->error) 
		{
			$this->makeOutputArray('reason', "Error inserting into database : ".$stmt->error);
			return false;
		}		
		
		$stmt->close();
		return true;
		
	}	
	
	

}
?>
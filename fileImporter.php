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
	private $errorRecords;
	private $processedRecords;
	
	public  $error;
	public  $numberProcessed = 0;
	public  $numberImported = 0;
	public  $numberFailed = 0;
	public  $reportFile;
	
	// -------------------------------------------------------------------
	// Import CSV file into database
	// -------------------------------------------------------------------
	public function importCSV($file)
	{
		if(!$this->parseCSV($file))
		{
			return false;
		}
		
		if(!$this->processCSVLines())
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
		
		return true;
	}
	
	
	
	// -------------------------------------------------------------------
	// Main driving function the loops through the array 
	// and calls the processLine method for each line of the CSV file
	// -------------------------------------------------------------------
	private function processCSVLines()
	{
		// make connection to database
		$Database = new Connect(); 
		$mysqli = $Database->getConnection();
		
		if (mysqli_connect_errno()) {
			$this-> error = "ERROR - could not connect to the database". PHP_EOL.  $Database->error. PHP_EOL;
			return false;
		}
		
		// create SQL now and use it in the loop
		$sql =" INSERT INTO tblProductData 
		(strProductCode, strProductName, strProductDesc, intProductStock, fltProductCost, strDiscontinued) 
		VALUES (?, ?, ?, ?, ?, ?) ";
		
		if (!$stmt = $mysqli->prepare($sql)) 
		{	
			$mysqli->close();
			$this-> error = "ERROR - could not insert into the database". PHP_EOL.  $Database->error. PHP_EOL;
			return false;
		}
		
		// make a null value at position zero so the position in the array
		// corresponds to the line number in the file
		$this->processedRecords[0] = "";
		
		// create a Sanitize object to pass to the insertIntoDatabase metheod
		// in the loop - saves creating a new object everytime
		$clean = new Sanitize(); 
		
		foreach ($this->array as &$value) 
		{
			$this->processLine($value, $stmt, $stmt, $sql, $clean);
		}
		
		// close databse connections
		$stmt->close();
		$mysqli->close();

		return true;
	}
	
	
	
	
	// -------------------------------------------------------------------
	// Process the values in each line of the CSV file and call the
	// insertIntoDatabase method to insert into database if no errors are found 
	// -------------------------------------------------------------------
	private function processLine($value, $stmt, $sql, $clean)
	{
		// convert file contents into a php array
		$array = explode(",", $value);
		
		// incrememnt the numberProcessed variable
		$this->numberProcessed++;
		
		// variable to see if this row in the CSV can be proccessed (had no errors)
		$process = true;
		
		if(!is_array($array))
		{
			$this->makeOutputArray('reason', "Data could not be converted into an array");
			$process = false;
		}
					
		if(count($array) != 6)
		{
			$this->makeOutputArray('reason', "Data had incorrect number of columns");
			$process = false;
		}
		
		if(!$this->isValid($array))
		{
			$process = false;
		}
		
		// process if row had no errors
		if($process)
		{
			if($this->insertIntoDatabase($stmt, $sql, $array, $clean))
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
		}
	}
	
	
	// -------------------------------------------------------------------
	// Check each of the values in the array
	// are the correct data type
	// -------------------------------------------------------------------
	private function isValid($array)
	{
		// check item 3 is int 
		if(isset($array[3]) && (!is_numeric($array[3]) && !is_int($array[3])))
		{
			$this->makeOutputArray('reason', "Data in column 4 was not an integer");
			return false;
		}
		
		// check item 4 is float
		$float  = floatval($array[4]);
		// get float value then compare ($array[4] is the same to ensure we have a float
		if( isset($array[4]) && ((!is_float($float) && !is_int($float))  || ($float != $array[4]) ))
		{
			// could make this error message a less technical term if necessary
			$this->makeOutputArray('reason', "Data in column 5 was not a float");
			return false;
		}
		
		$numbers = array(0, 1, 2, 5);
		
		// check remaining items are strings
		foreach ($this->array as &$value) 
		{
			if(isset($array[$value]) && !is_string($array[$value]))
			{
				$this->makeOutputArray('reason', "Data in column {$value} was not a string");
				return false;
			}
		}
		
		return true;		
	}
	
	
	
	// -------------------------------------------------------------------
	// Make output array which whill be used for an HTML file with
	// processing information
	// -------------------------------------------------------------------
	private function makeOutputArray($id, $value)
	{
		$this->processedRecords[$this->numberProcessed][$id] = $value;
	}
	
	
	public function getErrors()
	{
		echo print_r($this->processedRecords);
	}
		
	
	// -------------------------------------------------------------------
	// Insert data into the database 
	// -------------------------------------------------------------------
	private function insertIntoDatabase($stmt, $sql, $array, $clean)
	{
							
		if ($stmt == false || $stmt->error) 
		{
			error_log("EEROR - Class: FileImporter  ;  Method : insertIntoDatabase  ; error : stmt was not valid ".$stmt->error);
			return false;
		}

		// sanatize data and insert it into the database
		// so nothing nasty is entered
		$stmt->bind_param('sssids', 
						$clean->clean($array[0]), 
						$clean->clean($array[1]), 
						$clean->clean($array[2]), 
						$clean->clean($array[3]), 
						$clean->clean($array[4]), 
						$clean->clean($array[5]));
		
		if ( $stmt == false || $stmt->error) 
		{
			error_log("EEROR - Class: FileImporter  ;  Method : insertIntoDatabase (bind_param)  ; error : stmt was not valid ".$stmt->error);
			return false;
		}
		
		$stmt->execute();
		
		if ( $stmt == false || $stmt->error) 
		{
			error_log("EEROR - Class: FileImporter  ;  Method : insertIntoDatabase (execute) ; error : stmt was not valid ".$stmt->error);
			return false;
		}		
		
		return true;
		
	}	
	
	

}
?>
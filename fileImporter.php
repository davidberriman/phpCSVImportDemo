<?php

require_once('csvFile.php');
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
#                     $importer = new FileImporter($file, $test);
#                     $importer->importFile();  // check this equals true
#
# ========================================================================#

class FileImporter extends CSVFile
{
	// ------------------------------------------------------
	// Class variables
	// ------------------------------------------------------
	private $processedRecords;
	private $testMode;  // either "TEST=Y" or "TEST=N"  // stops saving to database
	
	public  $numberProcessed = 0;
	public  $numberImported = 0;
	public  $numberFailed = 0;

	 // for import rules
	const MAX_PRODUCT_COST = 1000;  // don't import above this threshold
	const MIN_PRODUCT_STOCK = 10;   // don't import if stock is < 10 and price < 5
	const MIN_STOCK_PRICE = 5;      // don't import if stock is < 10 and price < 5
	
	// column numbers in CSV file
	private  $columnProductCode;
	private  $columnProductName;
	private  $columnProductDescription;
	private  $columnStock;
	private  $columnCost;
	private  $columnDiscontinued;
	
	// column titles ecpected in the csv file
	private $expectedColumnHeadings = array('Product Code', 'Product Name', 'Product Description', 'Stock', 'Cost in GBP', 'Discontinued');

	
	// -------------------------------------------------------------------
	// Init class variables
	// -------------------------------------------------------------------
	public function __construct($file, $test)
	{
		// init parent class vars with the csv file
		parent::__construct($file);
	
		$this->testMode = $test;
		
		// set timezone
		date_default_timezone_set('Europe/London');		
	}
	
	
	// -------------------------------------------------------------------
	// Import CSV file into database
	// -------------------------------------------------------------------
	public function importFile()
	{

		// call parent function to validate the CSV
		if(!$this->parseCSV())
		{
			return false;
		}
		
		// get the column numbers for the required items
		$this->getColumnNumbers();
			
		// check column headings have been found in the CSV file
		if(!$this->checkColumnNumbers())
		{
			return false;
		}
				
		// remove the first line which is a row of headings
		unset($this->CSVLineArray[0]);
		
		// process array to import data
		if(!$this->processCSVLines())
		{
			return false;
		}
		
		return true;
	}
	
	
	
	// -------------------------------------------------------------------
	// Return array with results from processing
	// -------------------------------------------------------------------
	public function getResults()
	{
		return $this->processedRecords;	
	}
	

	// -------------------------------------------------------------------
	// Set the class column heading numbers - this is incase they change
	// with future files
	// -------------------------------------------------------------------
	private function getColumnNumbers()
	{
		// get the first item from CSVLineArray (which is the list of column titles)
		$array = explode(",", $this->CSVLineArray[0]);
		
		// trim white space from begining and end of each item in array
		$array = array_map('trim',$array);
				
		$this->columnProductCode = array_search($this->expectedColumnHeadings[0], $array); // expecte to be 0
		$this->columnProductName = array_search($this->expectedColumnHeadings[1], $array); // expecte to be 1
		$this->columnProductDescription = array_search($this->expectedColumnHeadings[2], $array); // expecte to be 2
		$this->columnStock = array_search($this->expectedColumnHeadings[3], $array); // expecte to be 3
		$this->columnCost = array_search($this->expectedColumnHeadings[4], $array); // expecte to be 4
		$this->columnDiscontinued = array_search($this->expectedColumnHeadings[5], $array); // expecte to be 5
		
		return true;
				
	}
	
	
	
	
	// -------------------------------------------------------------------
	// Verify that the class column heading numbers have been set
	// -------------------------------------------------------------------
	private function checkColumnNumbers()
	{
		
		$columnCode = $this->columnProductCode;
		$columnName = $this->columnProductName;
		$columnDescription = $this->columnProductDescription;
		$columnStock = $this->columnStock;
		$columnCost = $this->columnCost;
		$columnDiscontinued = $this->columnDiscontinued;
		
		$checkColumnHeadings = array($columnCode, 
									$columnName, 
									$columnDescription, 
									$columnStock, 
									$columnCost, 
									$columnDiscontinued);
		
		$i = 0; // used to provide error message
		
		// loop through each item and check that the expected column heading was found
		foreach ($checkColumnHeadings as &$title) 
		{
			if($title === false)
			{
				$this-> error = "ERROR - could not find column heading: (".$this->expectedColumnHeadings[$i].")". PHP_EOL;
				return false;
			}
		}
		
		return true;
	}
	

	
	// -------------------------------------------------------------------
	// Main driving function that loops through the array 
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

		// processedRecords is an array that saves all the processing information
		// for the log message output		
		// make a null value at position zero so the position in the array
		// corresponds to the line number in the file
		$this->processedRecords[0] = "";
		
		// create a Sanitize object to pass to the insertIntoDatabase metheod
		// in the loop - saves creating a new object everytime
		$clean = new Sanitize(); 
		
		foreach ($this->CSVLineArray as &$value) 
		{
			$this->processLine($value, $mysqli, $clean);
		}
		
		// close databse connections
		$mysqli->close();

		return true;
	}
	
	
	
	
	// -------------------------------------------------------------------
	// Process the values in each line of the CSV file and call the
	// insertIntoDatabase method to insert into database if no errors are found 
	// -------------------------------------------------------------------
	private function processLine($value, $mysqli, $clean)
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
		if($this->testMode == "TEST=Y")
		{
			if($process)
			{
				$this->numberImported++;
				$this->makeOutputArray('outcome', "SUCCESS");
			}else
			{
				$this->numberFailed++;
				$this->makeOutputArray('outcome', "ERROR");
			}
			return true;	
		}
		
		// process if row had no errors
		if($process)
		{
			if($this->insertIntoDatabase($mysqli, $array, $clean))
			{
				$this->numberImported++;
				$this->makeOutputArray('outcome', "SUCCESS");
			}else
			{
				$this->numberFailed++;
				$this->makeOutputArray('outcome', "ERROR");
			}
		}else
		{
			$this->numberFailed++;
			$this->makeOutputArray('outcome', "ERROR");
		}
		
		return true;
	}
	
	
	
	// -------------------------------------------------------------------
	// Check if the data meets the import criteria
	// -------------------------------------------------------------------
	private function meetsImportRules($array)
	{
		// check item meets the criteria for import 
		// Any stock item which costs less that £5 and has less than 10 stock will not be imported. 
		// Any stock items which cost over £1000 will not be imported.
		
		if(isset($array[$this->columnCost]))
		{
			$productCost  = $array[$this->columnCost];
			if($productCost > self::MAX_PRODUCT_COST)

			{
				$this->makeOutputArray('reason', "Does not meet criteria for import - item cost: {$productCost} is greater than 1000");
				return false;
			}
		}
		
		if(isset($array[$this->columnStock]) && isset($array[$this->columnCost]))
		{
			$productStock = $array[$this->columnStock];
			$productCost  = $array[$this->columnCost];
			if($productStock < self::MIN_PRODUCT_STOCK && $productCost < self::MIN_STOCK_PRICE)
			{
				$this->makeOutputArray('reason', "Does not meet criteria for import - Item stock: {$productStock} & item cost: {$productCost} (costs < 5 & stock < 10)");
				return false;
			}
		}
		
		return true;
	}
	
	
	// -------------------------------------------------------------------
	// Check each of the values in the array are the correct data type
	// -------------------------------------------------------------------
	private function isValid($array)
	{
		// check item 3 is int 
		if(isset($array[$this->columnStock]))
		{
			// item is really a string so ctype_digit will check that it is just numbers 
			// in that string eg. is_int
			if(!ctype_digit($array[$this->columnStock]))

			{
				$this->makeOutputArray('reason', "Data in column 4 was not an integer");
				return false;
			}
		}
		
		// check item 4 is numeric
		if( isset($array[$this->columnCost]))
		{
			// check item 4 is float				
			if( !is_numeric($array[$this->columnCost]) )
			{
				// could make this error message a less technical term if necessary
				$this->makeOutputArray('reason', "Data in column 5 was not a float");
				return false;
			}
		}
		
		$numbers = array($this->columnProductCode, $this->columnProductName, $this->columnProductDescription, $this->columnDiscontinued);
		
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
		// create prepared SQL
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
		// sanatize data before inserting it into the database so nothing nasty is entered
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
<?php

require_once("fileImporter.php");
require_once("connect.php");

# ========================================================================#
#
#  Php script to import a CSV file with product data
#
#  CSV file format: 
#  Product Code,   Product Name,   Product Description,   Stock,   Cost in GBP,   Discontinued
#  varchar(10)     varchar(50)     varchar(255)           int(6)   float          varchar(1)
#
#  Example:
#  To run this script enter php then the script name in terminal followed by the import file name (with full directory)
#  eg:  $ php importFile.php Desktop/WrenDemo/stock.csv
#
#  After the script has finished it will create an HTML file with the output results in the same directory as the script
#
# ========================================================================#

	$filename = $argv[1];

	// check that we have a file name with directory passed into the script
	if(!isset($filename))
	{
		die("ERROR - Please provide a file name (with directory) after entering the script name". PHP_EOL);  
	}
	
	
	// ensure the file exists
	if(!file_exists ($filename))
	{
		die("ERROR - The file specified could not be found. Please ensure you enter the full directory with file name.". PHP_EOL);
	}
	
	
	// copy contents of file to variable for manipulating
	$file = file_get_contents($filename);
	
	
	// check that the file has sucessfully populated the variable
	if(!isset($file))
	{
		die("ERROR - There was an error reading the file". PHP_EOL);
	}
	
	
	$importer = new FileImporter();
	
	
	if(!$importer->importCSV($file))
	{
		echo "ERROR - ". $importer->error. PHP_EOL;
	}else
	{
		echo "The file data was sucessfully imported.";
	}


?>
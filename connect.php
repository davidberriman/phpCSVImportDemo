<?php
# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      14/06/2016
#  Purpose:   Make a database connection
#  Plblic functions: 
#  					  getConnection : get connection
#  Usage Example:
#                     require_once("connect.php");
#                     $Database = new Connect();
#                     $mysqli = $Database->getConnection();
#
# ========================================================================#

class Connect
{
	// ------------------------------------------------------
	// Class variables
	// ------------------------------------------------------
	private $connection;
	public $error;
	
	private $host = 'localhost';
	private $username = 'phpDemoCSVImport_user';
	private $password = 'password';
	private $database = 'phpDemoCSVImport';


	// ------------------------------------
	// Connect to database 
	// ------------------------------------
	public function __construct() 
	{
		
		$this->connection = mysqli_connect($this->host, $this->username, $this->password, $this->database);
		
		// Error handling.
		if (mysqli_connect_error()) 
		{
		    $this->error = 'ERROR - Failed to connect to MySQL: ' . mysqli_connect_error();
			return false;
		}
		
		if(!mysqli_query($this->connection, "SET NAMES 'UTF8'"))
        {
            return false;
        }
	
 	}
	
	
	// ------------------------------------
	// Close connection 
	// ------------------------------------
	public function close() 
	{
    	return $this->connection->close();
  	}
	
	
	// ------------------------------------
	// Get connection 
	// ------------------------------------
	public function getConnection() 
	{
    	return $this->connection;
  	}
	
}
?>
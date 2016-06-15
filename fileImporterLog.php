<?php

require_once('sanitize.php');
require_once('connect.php');
require_once ('versionTest.php');

# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      14/06/2016
#  Purpose:   Create a log file with the outcome of the CSV import process
#  Plblic functions: 
#  			  logResults : Create a log file with the outcome of the CSV import process
#
#  Usage Example:
#                     require_once("fileImporterLog.php");
#                     $logFile = new FileImporterLog();
#                     $logFile->logResults($file);
#
# ========================================================================#

class FileImporterLog
{
	
	// ------------------------------------------------------
	// Class variables
	// ------------------------------------------------------	
	public  $error;
    public $filename;
    private $started;



    // -------------------------------------------------------------------
    // Main function called to create the log output
    // -------------------------------------------------------------------
    public function logResults($data, $filename, $processed, $imported, $failed)
    {
		
        // check we have some data to work with
        if(!isset($data))
        {    
            $this->error = "ERROR - Please provide some data to be logged";
            return false;
        }

        // check data is in expected format
        if(!is_array($data))
        {    
            $this->error = "ERROR - the data supplied was not in the expected format. Expected array but recived: ". gettype($data);
            return false;
        }
		
		date_default_timezone_set('Europe/London');

        // get the start time which will be used for the filename / title
        $this->started = date("Y-m-d-H-i-s");  

        // if we have a filename then save it to class property
        // otherwise create a name with the date
        if(isset($filename) && $filename != "")
        {
            $this->checkFilename($filename);
        }else
        {
            $this->createFilename();
        }

        // create the output
        if(!$html = $this->createOutput($data, $processed, $imported, $failed))
        {
			return false;
        }
		
		if(!file_put_contents($this->filename, $html))
		{
			$this->error = "ERROR - could not write data to directory: ". $this->filename;
			return false;
		}
	
		return true;
    }



    // -------------------------------------------------------------------
    // function to drive the creation of the html output
    // -------------------------------------------------------------------
    private function createOutput($data, $processed, $imported, $failed)
    {
        $output = "";
        $output .= $this->returnHead();
        $output .= "<body>";
		
        $output .= $this->returnTitle();
		
		$output .= $this->returnSummary($processed, $imported, $failed);
		
		$output .= $this->returnTableHTML($data);

        $output .= "<body>";
        $output .= "<html>";
        return $output;
    }
	
	// -------------------------------------------------------------------
    // Return summary table
    // -------------------------------------------------------------------
    private function returnSummary($processed, $imported, $failed)
    {
        $output = "";
        $output .= '<table>';
		$output .= '<caption>Process Summary</caption> ';
        $output .= '<tbody> ';
        $output .= '<tr>';
        $output .= '<td>Number Processed</td>';
		$output .= '<td>'.$processed.'</td>';
        $output .= '</tr>';
		$output .= '<tr>';
        $output .= '<td>Number Imported</td>';
		$output .= '<td>'.$imported.'</td>';
        $output .= '</tr>';
		$output .= '<tr>';
        $output .= '<td>Number Failed</td>';
		$output .= '<td>'.$failed.'</td>';
        $output .= '</tr>';
        $output .= '</tbody>';
        $output .= '</table>';
        return $output;
    }



	// -------------------------------------------------------------------
    // Return main table HTML
    // -------------------------------------------------------------------
    private function returnTableHTML($data)
    {
        $output = "";
        $output .= '<table>';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>Line Number</th>';
		$output .= '<th>Data</th>';
		$output .= '<th>Outcome</th>';
		$output .= '<th>Error Reason</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody> ';
		$output .= $this->returnRows($data);
        $output .= '</tbody>';
        $output .= '</table>';
        return $output;
    }


    // -------------------------------------------------------------------
    // Return table rows for report
    // -------------------------------------------------------------------
    private function returnRows($data)
    {
        $output = "";
		
		$arrayIDs = array('line', 'data', 'output', 'reason');
		
		// loop through 'outer' array which is each line in the CSV
        foreach ($data as &$value)
        {
            $output .= "<tr>";
			
			// loop through each item (td cell) for each line
			foreach ($arrayIDs as &$id)
        	{
				// add a class to colour the output cell red/green 
				// if this is the output item
				if(isset($value[$id]) && $value[$id] != "")
				{
					if($id == "output") // add class to output
					{
						$output .= "<td class=\"".$value[$id]."\">".$value[$id]."</td>";
					}else
					{
						$output .= "<td>".$value[$id]."</td>";
					}
					
				}else
				{
					$output .= "<td></td>";
				}
			}
            $output .= "</tr>";
        }
		return $output;
    }



    // -------------------------------------------------------------------
    // Return HTML head
    // -------------------------------------------------------------------
    private function returnHead()
    {
        $output  = "<!DOCTYPE html>";
        $output .= "<html lang=\"en-UK\">";
        $output .= "<head>";
        $output .= "<title>CSV Import</title>";
        $output .= "<meta charset=\"utf-8\">";
        $output .= "<style>".$this->returnCSS()."</style>";
        $output .= "</head>";
        return $output;
    }


    // -------------------------------------------------------------------
    // Return basic CSS
    // -------------------------------------------------------------------
    private function returnCSS()
    {
        $output  = "";

		$output  = "body{
						text-align : center;
					}";
					
		$output .= "h1{
						margin-bottom : 30px;
					}";
		
		$output .= "table{
						text-align : left;
						border-collapse: collapse;
						margin:auto;
						border: 1px solid #eee;
						margin-bottom : 30px;
					}";

		$output .= "th, td{
						padding: 15px;
						border: 1px solid #eee;
					}";
		$output .= "td {
    					text-align: left;
					}";
		$output .= "th {
    					text-align: center;
					}";
		$output .= "tr:nth-child(even) {
						background-color: #f2f2f2;
					}";
		$output .= "td:nth-child(4) {
    					max-width: 200px;
					}";
		$output .= ".ERROR {
						background-color: #f2dede;
					}";
		$output .= ".SUCCESS {
						background-color: #dff0d8;
					}";
        return $output;
    }


    // -------------------------------------------------------------------
    // Return title of report (which is the file name)
    // -------------------------------------------------------------------
    private function returnTitle()
    {
        $output  = "<h1>";
        $output  .= "CSV Output Report: " . str_replace("log/", "", $this->filename);
        $output  .= "</h1>";
        return $output;
    }


    // -------------------------------------------------------------------
    // create a default filename for the log output
    // -------------------------------------------------------------------
    private function createFilename()
    {
        $this->filename = "log/CSV-Import-". $this->started. ".html";
    }



    // -------------------------------------------------------------------
    // check the filename if valid if they have provided one. Else, make
    // a default name
    // -------------------------------------------------------------------
    private function checkFilename($path)
    {
		// split provided path into component parts eg directory/filename/extension
        $path_parts = pathinfo($path);

		// default location for the output is a folder called log
		$dirname = "log/";
		
        $basename = $path_parts['basename'];
        $extension = $path_parts['extension'];
        $filename = $path_parts['filename']; // needs PHP >= 5.2.0

        // filename needs PHP >= 5.2.0 so check we can get 
		// the filename from the $path variable	otherwise
		// do it manually	
		$version = new VersionTest();
		
        if(!$version->isPHPGreaterOrEqualTo("5.2.0"))
        {
            // find the filename manually if this php version is < 5.2
            $filename = str_replace(".".$extension,"",$basename);
        }

        // ensure we have an HTML extension
        $path = $dirname.$filename. ".html";
			
        // check directory exists and make one if it doesn't
        if (!file_exists($dirname))
        {
			//  CREATE FOLDER
			if(!mkdir($dirname, 0777, true))
			{
				$this->error =  "ERROR - Could not find / create 'log/' folder for the output report";
				$this->createFilename();
				return false;	
			}
        }
		
        // create a unique name if file exists
        if (file_exists($path))
        {
            $i = 0;
            while(file_exists($path))
            {
                $i++;
                // add a number to the name until it is unique
                 $path = $dirname.$filename. "-". $i . ".html";
            }
        }

        $this->filename = $path;

        return;

    }
	
	

}
?>
<?php

# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      14/06/2016
#  Purpose:   Sanitize all inputs so nothing nasty comes in and harms the server
#  Public functions: 
#  					 clean    :  Sanitize all inputs
#  Usage Example:
#                     require_once ('include/sanitize.php');
#                     $clean = new Sanitize();
#                     $someData = $clean -> clean("Some publicly inputted value");
#
# ========================================================================#

class Sanitize
{
	
	// ------------------------------------------------------
	//  Sanitize all input
	// ------------------------------------------------------
	public function clean($data) 
	{
	  $data = trim($data);
	  $data = stripslashes($data);
	  $data = htmlspecialchars($data);
	  
	  return $data;
	}
	
}

?>
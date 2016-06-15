<?php

# ========================================================================#
#
#  Author:    David Berriman
#  Version:	  1.0
#  Date:      15/06/2016
#  Purpose:   Check to see if the php version of this system is greater than or equal to 
#             the version passed in so we can ensure that features are available.
#
#  Public functions: 
#  					 isPHPGreaterOrEqualTo    :  is current version usable?
#  Usage Example:
#                     require_once ('versionTest.php');
#                     $version = new VersionTest();
#                     if($version -> isPHPGreaterOrEqualTo("5.2.0"))  // we can use features >= 5.2.0 
#
# ========================================================================#

class VersionTest
{
	
	// ------------------------------------------------------
	// Check to see if php is greater than or equel to the 
	// version passed in which may have several decemal points
	// so we just want the first 2 numbers
	// ------------------------------------------------------
	public function isPHPGreaterOrEqualTo($versionToCheck) 
	{
		
	  // convert php version into an array because
	  // it may have several decemal places eg: 5.2.0
	  $versionArray = explode(".", phpversion());
	  
	  // create a version with just one decemal
	  $version = $versionArray[0].".".$versionArray[1];
	  
	  // check to see if this php version is less than 
	  // value passed in
	  if (floatval($version) < $versionToCheck)
      {
		  return false;
	  }else
	  {
		  return true;
	  }
	  
	}
	
}

?>
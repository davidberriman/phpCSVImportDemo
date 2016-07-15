

-- Create database

<<<<<<< HEAD
CREATE DATABASE wrenTest;

-- and use...

USE wrenTest;
=======
CREATE DATABASE phpDemoCSVImport;

-- and use...

USE phpDemoCSVImport;
>>>>>>> 17e489a2281d2fca36321e95f6fa10673f7dae25

-- Create table for data

CREATE TABLE tblProductData (
  intProductDataId int(10) unsigned NOT NULL AUTO_INCREMENT,
  strProductName varchar(50) NOT NULL,
  strProductDesc varchar(255) NOT NULL,
  strProductCode varchar(10) NOT NULL,
  dtmAdded datetime DEFAULT NULL,
  dtmDiscontinued datetime DEFAULT NULL,
  stmTimestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (intProductDataId),
  UNIQUE KEY (strProductCode)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Stores product data';

ALTER TABLE tblProductData 
ADD intProductStock INT NULL ,
ADD fltProductCost FLOAT NULL, 
ADD `strDiscontinued` VARCHAR( 3 ) NULL ;  


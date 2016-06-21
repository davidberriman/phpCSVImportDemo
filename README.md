# phpCSVImportDemo
This is a demonstration of php programming ability

The brief below was given to assess my php programming skills. This documentation explains how to use it.

PHP Test

In order to add some new and exci0ng products to the site, we need to process a CSV file from a supplier.
This file contains product information which we need to extract and insert into a database table.
In addition, we need to apply some simple business rules to the data we import. A table already exists to receive this information, but the table needs some tweaks in order to work correctly with this file.

The Solution

You need to write a script which will read the CSV file, parse the contents and then insert the data into a MySQL database table.
The import process will be run from the command line and when it completes it needs to report how many items were processed, how many were successful, and how many were skipped. See the import rules below.

Objectives

Your solution must use PHP in an OO style and MySQL. Code should be clearly laid out and well commented.
Any SQL used to alter the table should be included in a .sql file with the submission.
Using a command line argument the script can be run in 'test' mode. This will perform everything the normal import does, but not insert the data into the database.
The supplier provides a stock level and price which we currently do not store. Using suitable data types, add two columns to the table to capture this informa0on.

Import Rules

Any stock item which costs less that £5 and has less than 10 stock will not be imported. 
Any stock items which cost over £1000 will not be imported.
Any stock item marked as discontinued will be imported, but will have the discontinued date set as the current date.
Any items which fail to be inserted correctly need to be listed in a report at the end of the import process.

Additional Considerations

Because the data is from an external source, it may present certain issues. These include:
1. Whether the data is correctly formatted for CSV
2. Whether the data is correctly formatted for use with a database
3. Potential data encoding issues or line termination problems
4. Manual interference with the file which may invalidate some entries
Either address these concerns in the code or indicate in your response how you would tackle these issues if you had more 0me to develop your script.

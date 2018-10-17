<?php
/*
 * @Author	:   Channaveer Hakari
 * @Email    	:   channaveer888@gmail.com
 * @Version	:   1.0
 * @Description	:   This Script is used to backup your complete database or partial tables backup
 */

/* Error Reporting */
error_reporting(E_ALL);

/* Creating New Instance for the class DatabaseBackup */
$dbBackup	=	new DatabaseBackup();
/* @Function Called - 	backupDatabase() 
 * @parameters		-	1st Param - Default : fetches all the tables
				Specific Tables - array(table1,table2,table3...);
 *				2nd Param - If not specified then creates one where this script resides
 */
$tables	=	'*';
$dbBackup->backupDatabase($tables,'BackupLogs');

class DatabaseBackup{
	private $hostname		=	''; /* DB Hostname */
	private $username		=	''; /* DB Username */
	private $password		=	''; /* DB Password */
	private $database		=	''; /* Database Name */
	private $characterSet		=	'utf8'; /* DB Character Set */
	private $backupDirectory	=	'BackupLogs'; /* Backup Directory */
	
	/* Mysqli Connection Handle */
	private $link			=	'';
	
	/* Class Constructor */
	function __construct(){
		/* Initialization of DB variables */
		$this->hostname		=	'localhost';
		$this->username		=	'root';
		$this->password		=	'';
		$this->database		=	'test_db';
		/* Call DB Initialization Function */
		$this->initalizeDB();
		
	}
	
	/* Function used to Initialize the MySQL DB */
	private function initalizeDB(){
		$this->link	=	mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
		/* If any error then display appropriate message */
		if(mysqli_connect_error()){
			die('Connection Error - '.mysqli_connect_errno().' : '.mysqli_connect_error());
		}
		/* If the Character Set is not defined then set default defined one */
		if(!mysqli_character_set_name($this->link)){
			mysqli_set_charset($this->link,$this->characterSet);
		}
	}
	
	/* Function is used to Backup you Database */
	public function backupDatabase($tables = '*',$backupDirectory = ''){
		/* If all the tables needed */
		if($tables == '*'){
			$tables =	array();
			/* Fetch all the tables of the current database */
			$result	=	mysqli_query($this->link,"SHOW TABLES");
			/* Loop through all the rows and assign to $tables array */
			while($row = mysqli_fetch_row($result)){
				$tables[]	=	$row[0];
			}
		}else{
		/* If $tables is an array then assign directly else explode the string */
			$tables	=	is_array($tables) ? $tables : explode(',',$tables);
		}
		/* Create the database */
		$sql	=	'SET FOREIGN_KEY_CHECKS = 0;'."\n".'CREATE DATABASE IF NOT EXISTS `'.$this->database."`;\n";
		/* Use the database */
		$sql	.=	'USE `'.$this->database.'`;';
		
		/* Loop throug all the $tables */
		foreach($tables as $table){
			/* Output message */
			echo 'Logging Table : `'.$table.'` : ';
			
			/* Fetch the details of the table */
			$tableDetails	=	mysqli_query($this->link, "SELECT * FROM ".$table);
			
			/* Check the Number of Coloumns in the table */
			$totalCols	=	mysqli_num_fields($tableDetails);
			
			/* If the table exists then drop */
			$sql		.=	"\n\nDROP TABLE IF EXISTS `".$table."`;\n";
			/* Create the table structure */
			$result1	=	mysqli_fetch_row(mysqli_query($this->link,'SHOW CREATE TABLE '.$table));
			$sql		.=	$result1[1].";\n\n";
			
			
			while($row = mysqli_fetch_row($tableDetails)){
				$sql	.=	'INSERT INTO `'.$table.'` VALUES(';
				for($j=0; $j<$totalCols; $j++){
					$row[$j]	=	preg_replace("/\n/","\\n",addslashes($row[$j]));
					if (isset($row[$j]))
					{
						$sql .= '"'.$row[$j].'"' ;
					}
					else
					{
						$sql.= '""';
					}

					if ($j < ($totalCols-1))
					{
						$sql .= ', ';
					}
				}
				$sql	.=	"); \n";
			}
			echo 'Completed <br/>';
		}
		$sql .= 'SET FOREIGN_KEY_CHECKS = 1;';
		/* If the 2nd parameter was not specified then default one will be passed */
		$backupDirectory = ($backupDirectory == '') ? $this->backupDirectory : $backupDirectory;
		if($this->logDatabase($sql,$backupDirectory)){
			echo '<h4>Exported Database <span style="color:#7D0097">`'.$this->database.'`</span>Successfully to folder - <span style="color:#1CAD7A"> `'.$backupDirectory.'`</span><h4>';exit;
		}else{
			echo '<h2>Error in Exporting Database '.$this->database.'<h2>';exit;
		}
		
	}
	
	/* Function used to Log the Database */
	private function logDatabase($sql,$backupDirectory = ''){
		if(!$sql){
			return false;
		}
		
		if(!file_exists($backupDirectory)){
			if(mkdir($backupDirectory)){
				$filename	=	'log_'.$this->database.date('Y-m-d_H-i-s');
				$fileHandler	=	fopen($backupDirectory.'/'.$filename.'.sql','w+');
				fwrite($fileHandler,$sql);
				fclose($fileHandler);
				return true;
			}
		}else{
			$filename	=	'log_'.$this->database.date('Y-m-d_H-i-s');
			$fileHandler	=	fopen($backupDirectory.'/'.$filename.'.sql','w+');
			fwrite($fileHandler,$sql);
			fclose($fileHandler);
			return true;
		}	
		return false;
	}
}

<?php

	require_once('config.php');

	ini_set('max_execution_time', 3600);
	ini_set('memory_limit', '512M');

	ob_start();
	ob_end_flush();


	$date = new DateTime('now');
	
	
	
	/* CODE SECTION - LIVE BACKUP */
	
	$cmd = "/Applications/MAMP/Library/bin/mysqldump -u {$s1_db_user} -p{$s1_db_pass} {$s1_db_name} > backup/dump_{$s1_db_name}_{$date->format('mdYhis')}.sql 2>&1";

	$output = shell_exec($cmd);
	
	echo "Successfully exported {$s1_db_name} database.".nl2br("\n\n");
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Successfully exported {$s1_db_name} database."."\n\n",3,'sync.log');	
	ob_flush();
	flush();	
	
	$cmd = "tar -zcf {$s2_root_dir}/sync/backup/product_images_{$date->format('mdYhis')}.tar.gz {$s1_root_dir}/media/catalog/product/*";
	shell_exec($cmd);
	
	/* CODE SECTION - DUMP STAGING DB */	
	
	$cmd = "/Applications/MAMP/Library/bin/mysqldump -u {$s2_db_user} -p{$s2_db_pass} {$s2_db_name} > dump_{$s2_db_name}_{$date->format('mdY')}.sql 2>&1";

	$output = shell_exec($cmd);
	
	echo "Successfully exported {$s2_db_name} database.".nl2br("\n\n");
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Successfully exported {$s2_db_name} database."."\n\n",3,'sync.log');	
	ob_flush();
	flush();
	
	
	/* CODE SECTION - CLEAR ALL TABLES FROM LIVE DB */	
	
	$connection = new mysqli('localhost', $s1_db_user, $s1_db_pass, $s1_db_name);
	if(mysqli_connect_error()) {
		echo "Failed to connect to MySQL: " . mysql_connect_error().nl2br("\n\n");
		error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Failed to connect to to MySQL: " . mysql_connect_error()."\n\n",3,'sync.log');
		$connection->close();
		exit(0);
	}	
	echo "Successfully connected to {$s1_db_name} database.".nl2br("\n\n");
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Successfully connected to {$s1_db_name}"."\n\n",3,'sync.log');	
	ob_flush();
	flush();
	
	$query = "SET foreign_key_checks = 0";
	$connection->query($query);
	$query = "SHOW TABLES";
	$result = $connection->query($query);
	
	if ($result->num_rows > 0) {
		while($row = $result->fetch_array(MYSQLI_NUM)) {
			$connection->query('DROP TABLE IF EXISTS '.$row[0]);
			error_log("`{$s1_db_name}.{$row[0]}` table deleted."."\n",3,'sync.log');
		}
	}		
	$connection->close();	
	
	error_log("{$result->num_rows} tables successfully deleted."."\n\n",3,'sync.log');
	ob_flush();
	flush();
	
	
	/* CODE SECTION - IMPORT DUMPED FILE FROM STAGING DB INTO LIVE DB */	
	
	$cmd = "/Applications/MAMP/Library/bin/mysql -u {$s1_db_user} -p{$s1_db_pass} {$s1_db_name} < dump_{$s2_db_name}_{$date->format('mdY')}.sql";

	$output = shell_exec($cmd);
	
	echo "Successfully imported {$s1_db_name} database.".nl2br("\n\n");
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Successfully imported {$s1_db_name} database."."\n\n",3,'sync.log');	
	ob_flush();
	flush();
	
	
	/* CODE SECTION - SET THE PROPER CONFIG DATA IN THE LIVE DATABASE */	
	
	$connection = new mysqli('localhost', $s1_db_user, $s1_db_pass, $s1_db_name);
	if(mysqli_connect_error()) {
		echo "Failed to connect to MySQL: " . mysql_connect_error().nl2br("\n\n");
		error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Failed to connect to to MySQL: " . mysql_connect_error()."\n\n",3,'sync.log');
		$connection->close();
		exit(0);
	}	
	echo "Successfully connected to {$s1_db_name} database.".nl2br("\n\n");
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Successfully connected to {$s1_db_name}"."\n\n",3,'sync.log');
	ob_flush();
	flush();
	
	$query = 'UPDATE core_config_data SET `value`="'.$s1_base_url.'" WHERE `path`="web/unsecure/base_url"';
	if ($connection->query($query) === TRUE) {
	    echo "Successfully updated 'web/unsecure/base_url'".nl2br("\n\n");;
	    error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Successfully updated 'web/unsecure/base_url'"."\n\n",3,'sync.log');
		ob_flush();
		flush();
	} else {
		echo "Error updating record: ".$connection->error.nl2br("\n\n");;
		error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Error updating record: ".$connection->error."\n\n",3,'sync.log');
		ob_flush();
		flush();
	}
	
	$query = 'UPDATE core_config_data SET `value`="'.$s1_secure_url.'" WHERE `path`="web/secure/base_url"';
	if ($connection->query($query) === TRUE) {
	    echo "Successfully updated 'web/secure/base_url'".nl2br("\n\n");;
	    error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Successfully updated 'web/secure/base_url'"."\n\n",3,'sync.log');
		ob_flush();
		flush();
	} else {
	    echo "Successfully updated 'web/secure/base_url'".nl2br("\n\n");;
	    error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
		error_log("Successfully updated 'web/secure/base_url'"."\n\n",3,'sync.log');
		ob_flush();
		flush();
	}	
	
	
	
	/* CODE SECTION - LOAD LIVE MAGENTO APP */	
	
	$magentoPath = $s1_root_dir.'/app/Mage.php';
	
	require_once($magentoPath); //Path to Magento
	umask(0);
	Mage::app();
	
	Mage::app()->cleanCache();
	Mage::app()->getCacheInstance()->flush();
	
	echo "Cache cleared on {$s1_base_url}".nl2br("\n\n");;
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Cache cleared on {$s1_base_url}"."\n\n",3,'sync.log');	
	ob_flush();
	flush();
	
	
	/* CODE SECTION - COPY PRODUCT IMAGES FROM STAGING TO LIVE */
	
	$cmd = "rm -rf {$s1_root_dir}/media/catalog/product/*";
	shell_exec($cmd);
	
	$cmd = "cp -r {$s2_root_dir}/media/catalog/product/* {$s1_root_dir}/media/catalog/product/";
	shell_exec($cmd);
	
	echo "Product images synced.".nl2br("\n\n");;
	error_log($date->format('Y-m-d\TH:i:sP')."\n",3,'sync.log');
	error_log("Product images synced."."\n\n",3,'sync.log');	

	error_log("\n\n********************************************************************************\n\n",3,'sync.log');
	
	$cmd = "mv dump_{$s2_db_name}_{$date->format('mdY')}.sql backup/dump_{$s2_db_name}_{$date->format('mdYhis')}.sql";
	shell_exec($cmd);
	
?>
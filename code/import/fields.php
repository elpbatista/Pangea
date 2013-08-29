<?php
	//global $dbconn;
	$z = new XMLReader();	
	$z->open("data/_MegaPangea.xml");
		
	while ($z->read() && $z->name !== 'ROW');
	
	while ($z->name === 'ROW') { 		
		$node = new SimpleXMLElement($z->readOuterXML());
		
			foreach ($node as $key => $value) {
				echo $key."<br/>";
			}
		
		unset($node);
		$z->next('ROW');
	  
	  //if ($i==21350) exit();
	}
	
	$z->close();
?>

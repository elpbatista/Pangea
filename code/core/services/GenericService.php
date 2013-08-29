<?php
	include_once dirname ( __FILE__ ) . '/../PangeaObject.php';
	
	abstract class GenericService extends PangeaObject {
		public function GenericService(){
			parent::__construct();
		}
	}
?>
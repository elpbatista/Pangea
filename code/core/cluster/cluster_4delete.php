<?php

/*$a = new Cluster ();
$parent ['label'] = 'person';
$parent ['id'] = 35;
$child ['label'] = 'isAuthor';
$child ['id'] = 36;

$a->addNodo ( $parent, $child );
*/
class cluster_4delete {
	private $cluster;
	private $mapa;
	public function __construct() {
		$this->cluster = array ();
		$this->cluster [0] = array ('text' => 'Persona', 'id' => 1, 'leaf' => true,'contador'=>0 );//$this->cluster [0] = array ('text' => 'person', 'id' => 1, 'leaf' => false, 'children' => array (array ('text' => 'name', 'id' => 5, 'leaf' => true ) ) );
		$this->cluster [1] = array ('text' => 'Entidad Corporativa', 'id' => 2, 'leaf' => true,'contador'=>0 );
		$this->cluster [2] = array ('text' => 'Documentos', 'id' => 3, 'leaf' => true,'contador'=>0 );
		$this->cluster [3] = array ('text' => 'Lugar', 'id' => 6, 'leaf' => true,'contador'=>0 );
		//$this->cluster [3] = array ('text' => 'location', 'id' => 4, 'leaf' => true );
		//$this->cluster [3] = array ('text' => 'lenguage', 'id' => 5, 'leaf' => true );
		//$this->cluster [5] = array ('text' => 'event', 'id' => 7, 'leaf' => true );
		
		$person = array ('Persona', 0 );
		$corporateBody = array ('Entidad Corporativa', 1 );
		$document = array ('Documentos', 2, array ('Libro', 'Revista', 'Disco Compacto','Folleto','Periódico','Audiovisual','Otro','Boletín','Colección','Colección privada','Grabado','Litografía','Tesis','Volante
		') );
		$place = array('Lugar',3);
		
		$this->mapa = array ();
		$this->mapa [0] = $person;
		$this->mapa [1] = $corporateBody;
		$this->mapa [2] = $document;
		$this->mapa [3] = $place;
	}
	
	public function getCluster() {
		return $this->cluster;
	}
	public function addNodo($parent/*id,label*/,$child/*id,label*/)
	{
		$clusterParent = $this->findRootParent ( $parent ['label'] );
		$this->put ( $clusterParent, $parent, $child );
		$this->cluster;
	}
	private function findRootParent($labelParent) {
		for($i = 0; $i < sizeof ( $this->mapa ); $i ++) {
			if ($this->mapa [$i] [0] == $labelParent)
				return $this->cluster [$this->mapa [$i] [1]];
			else if (sizeof ( $this->mapa [$i] ) > 2)
				for($j = 0; $j < $this->mapa [$i] [2]; $j ++)
					if ($this->mapa [$i] [2] [$j] == $labelParent)
						return $this->cluster [$this->mapa [$i] [1]];
		}
		return null;
	}
	private function put($clusterParent, $parent, $child) {
		$clusterParent ['id'] .= ',' . $parent ['id'];
		//aumentar contador
		$clusterParent ['leaf'] = false;
		$clusterParent ['contador'] ++ ;
		$arrChild = array ();
		
		if (! isset ( $clusterParent ['children'] )) {
			
			$arrChild ['text'] = $child ['label'];
			$arrChild ['id'] = $child ['id'];
			$arrChild ['leaf'] = true;
			$arrChild ['contador'] = 1;
			//poner contador
			$clusterParent ['children'] = $arrChild;
		} else {
			$change = 0;
			for($i = 0; $i < sizeof ( $clusterParent ['children'] ); $i ++) {
				if ($clusterParent ['children'] [$i] ['text'] == $child ['label']) {
					$clusterParent ['children'] [$i] ['id'] .= ',' . $child ['id'];
					$clusterParent ['children'] [$i] ['contador']++;
					$change = 1;
		
				}
			}
			if ($change == 0) {
				$arrChild ['text'] = $child ['label'];
				$arrChild ['id'] = $child ['id'];
				$arrChild ['leaf'] = true;
				$arrChild ['contador'] = 1;
				$clusterParent ['children'] [sizeof ( $clusterParent ['children'] )] = $arrChild;
			}
		}
	}
}
?>
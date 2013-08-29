<?php
/**
	 * Función utilitaria que presenta la información de un arreglo multidimensional en forma de árbol.
	 * @access public
	 * @method showArrayAsATree($array)
	 * @param array $array Arreglo multidimensional
	 */
	function showArrayAsATree($array)
	{
		if(gettype($array)=="array")
		{
			echo "<ul>";
			for ($i = 0; $i < sizeof($array); $i++) 
			{
				echo "<li>";
				for($j=0;$j<sizeof($array[$i][0]);$j++)
				{
					//print_r(utf8_decode($array[$i][0][$j])." ");
					echo($array[$i][0][$j]." ");
				}
				//print_r("<br>");
				if(isset($array[$i][1]))
					if(sizeof($array[$i][1])!=0)
					{
						showArrayAsATree($array[$i][1]);		
					}
				echo "</li>";
			}
			echo "</ul>";
		}
	}
	
?>
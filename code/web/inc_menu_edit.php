<div class="tbox-edit">
 	<ul class="pangea">
 		<li title="<?php echo 'Etiqueta';?>" class="rdfs:label xsd:string" typeof="xsd:string"><span class="icon-pencil"></span></li>
 		<li title="<?php echo 'Etiqueta controlada';?>" class="pangea:Form pangea:AdquisitionWay pangea:Availability pangea:Subject pangea:Color pangea:Language pangea:Shape pangea:Collection pangea:DocumentType pangea:Typology" typeof="pangea:DescriptorEntity"><span class="icon-tag"></span></li>
 		<li title="<?php echo $lbl['pangea:date'];?>" class="pangea:date" typeof=""><span class="icon-calendar"></span></li>
 		<li title="<?php echo 'Identificador';?>" class="nomelose" typeof="xsd:string"><span class="icon-barcode"></span></li>
 		<li title="<?php echo 'Enlace externo';?>" class="nomelose" typeof="xsd:string"><span class="icon-globe"></span></li>
 		<li title="<?php echo 'Imagen';?>" class="nomelose" typeof="nomelose"><span class="icon-picture"></span></li>
 		<?php if (isset($items)){ ?>
			<li title="<?php echo 'Precio';?>" class="pangea:price" typeof="xsd:float"><span class="icon-money">$</span></li>
 		<?php } ?>
 	</ul>
 	<ul class="frbr">
 		<li title="<?php echo $lbl['frbr:Person'];?>" class="frbr:Core frbr:ResponsibleEntity frbr:Person" typeof="frbr:Person"><span class="icon-adult"></span></li>
 		<li title="<?php echo $lbl['frbr:CorporateBody'];?>" class="frbr:Core frbr:ResponsibleEntity frbr:CorporateBody" typeof="frbr:CorporateBody"><span class="icon-home"></span></li>
 		<li title="<?php echo $lbl['frbr:Place'];?>" class="frbr:Core frbr:Subject frbr:Place" typeof="frbr:Place"><span class="icon-map-marker"></span></li>
 		<li title="<?php echo $lbl['frbr:Event'];?>" class="frbr:Core frbr:Subject frbr:Event" typeof="frbr:Event"><span class="icon-certificate"></span></li>
 		<li title="<?php echo $lbl['frbr:Object'];?>" class="frbr:Core frbr:Subject frbr:Object" typeof="frbr:Object"><span class="icon-gift"></span></li>
 		<li title="<?php echo $lbl['frbr:Concept'];?>" class="frbr:Core frbr:Subject frbr:Concept" typeof="frbr:Concept"><span class="icon-tags"></span></li>
 	</ul>
 	<ul class="comments">
 		<li title="<?php echo 'Comentario';?>" class="rdfs:comment" typeof="xsd:string"><span class="icon-comment"></span></li>
 		<li title="<?php echo $lbl['pangea:warning'];?>" class="pangea:warning" typeof="xsd:string"><span class="icon-warning-sign"></span></li>
 		<li title="<?php echo $lbl['pangea:error'];?>" class="pangea:error" typeof="xsd:string"><span class="icon-remove-circle"></span></li>
 	</ul>
</div>

<?php
/*
 * Definición de la tabla entity_type 
 */
$rules = array (
	array('table_name' => 'cpa_sp', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'sp')
				)
	),
	array('table_name' => 'cpa_en', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'en')
				)
	),
	array('table_name' => 'cpa_fr', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'fr')
				)
	),
	array('table_name' => 'cpa_de', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'de')
				)
	),
	array('table_name' => 'cpa_it', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'it')
				)
	),
	array('table_name' => 'cpa_ct', 
				'property_list' => array(
						array('property_name' => 'lang', 'property_value' => 'ct')
				)
	)
);

$entity_type = array(  
					   
					   /*  Definición de Persona */
					   array(
					   'label' => array( 'sp' => 'Persona' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'person', 'source' => 'person'
					   ),
					   /*  Definición de Entidad Corporativa */
					   array(
					   'label' => array( 'sp' => 'Entidad Corporativa' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'corporate_body', 'source' => 'corporate_body'
					   ),
					   /*  Definición de Lugar */
					   array(
					   'label' => array( 'sp' => 'Lugar' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'place', 'source' => 'place'
					   ),
					   /*  Definición de Concepto */
					   array(
					   'label' => array( 'sp' => 'Concepto' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'concept', 'source' => 'concept'
					   ),
					   /*  Definición de Evento */	
					   array(
					   'label' => array( 'sp' => 'Evento' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'event', 'source' => 'event'
					   ),
					   array(
					   'label' => array( 'sp' => 'Objeto' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null' ), 
					   'table_name' => 'object', 'source' => 'object'
					   ),
					   array(
					   'label' => array( 'sp' => 'Manifestación' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null' ), 
					   'table_name' => 'manifestation', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Expresión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null' ), 
					   'table_name' => 'expression', 'source' => 'expression'
					   ),
					   array(
					   'label' => array( 'sp' => 'Obra' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null' ), 
					   'table_name' => 'work', 'source' => 'work'
					   ),
					   array(
					   'label' => array( 'sp' => 'Título' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null' ), 
					   'table_name' => 'title', 'source' => 'title'
					   )
			   );

			   
$document_type = array(
					   /*  Definición de los Tipos de Documentos */	
					   array(
					   'label' => array( 'sp' => 'Revista' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Folleto' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Libro' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Periódico' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Disco Compacto' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Otro' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),					   					   
					   array(
					   'label' => array( 'sp' => 'Audiovisual' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Boletín' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Colección' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Colección privada' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Grabado' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Litografía' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Tesis' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   ),
					   array(
					   'label' => array( 'sp' => 'Volante' , 
					   					         'en' => 'null' , 
					   					         'fr' => 'null' , 
												 'de' => 'null'
					              ), 
					   'table_name' => 'phisical_entity', 'source' => 'manifestation'
					   )
				);
					   
/*
 * 
 * Definición de la tabla property_type 
 * 
 */
$property_type = array (
					/******************************************************
					 * 
					 *			Relaciones de Puntos de Acceso
					 *  
					 ******************************************************/
					/*  Definición del nodo Título */
					array (
						'label' => array( 'sp' => 'Título' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
						'is_visible' => 'false',
						'table_name' => 'has_title_property', 		
						'domain_table' => 'descriptor_entity',
						'range_table' => 'controlled_access_point',						
						'inverse' => TRUE,
					  'source' => 'access_point',
					  'inverse_source' => 'title',
						'sons' => array(
										array (
										'label' => array( 'sp' => 'Título propio' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_proper',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array( 'sp' => 'Título alternativo' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_alternative',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array( 'sp' => 'Título paralelo' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_parallel',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array( 'sp' => 'Título colectivo' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_colective',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array( 'sp' => 'Título suministrado' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_suplied',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array(
									   'label' => array( 'sp' => 'Información Aportada' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
									   'is_visible' => 'true',               
									   'table_name' => 'has_title_contributed',
									   'domain_table' => 'descriptor_entity',
									   'range_table' => 'controlled_access_point',
									   'inverse' => TRUE,
									   'source' => 'access_point',
									   'inverse_source' => 'title',
									   ),
									   array(
									   'label' => array( 'sp' => 'Otra Información Sobre el Título' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
									   'is_visible' => 'true',               
									   'table_name' => 'has_title_oit',
									   'domain_table' => 'descriptor_entity',
									   'range_table' => 'controlled_access_point',
									   'inverse' => TRUE,
									   'source' => 'access_point',
									   'inverse_source' => 'title',
									   ),
									   array(
									   'label' => array( 'sp' => 'Otra Información Sobre el Título Paralelo' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
									   'is_visible' => 'true',               
									   'table_name' => 'has_title_oit_parallel',
									   'domain_table' => 'descriptor_entity',
									   'range_table' => 'controlled_access_point',
									   'inverse' => TRUE,
									   'source' => 'access_point',
									   'inverse_source' => 'title',
									   )
									)
					),
					/*  Definición del nodo Título Suministrado */
					array(
					   'label' => array( 'sp' => 'Título Suministrado' , 'en' => 'null' , 'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_title_suplied',
					   'domain_table' => 'descriptor_entity',
					   'range_table' => 'controlled_access_point',
					   'inverse' => TRUE,
					   'source' => 'access_point',
					   'inverse_source' => 'title',           
					   'sons' => array( 
										array( 
										'label' => array ( 'sp' => 'Título Uniforme' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_uniform',
										'domain_table' => 'bibliographic_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'work'),
										array (
										'label' => array ( 'sp' => 'Título Traducido' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_translated',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array ( 'sp' => 'Sin Título Colectivo' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_without_colective',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array ( 'sp' => 'Sin Título' ,  'en' => 'null' ,	'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_without',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title'),
										array (
										'label' => array ( 'sp' => 'Variante del Título' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_title_variant_of',
										'domain_table' => 'descriptor_entity',
										'range_table' => 'controlled_access_point',
										'inverse' => TRUE,
					  				'source' => 'access_point',
					  				'inverse_source' => 'title')
									)                                                                          
					   ),					   
					   /*  Definición del nodo Nomenclador */
					   array(
					   'label' => array( 'sp' => 'Nomenclador' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_nomen',
					   'domain_table' => 'describable_entity',
					   'range_table' => 'controlled_access_point',
					   'inverse' => TRUE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Nombre' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_name',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'controlled_access_point',
					   				'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Alias' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_alias',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'controlled_access_point',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Nombre científico' ,'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_scientific_name',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'controlled_access_point',
										'inverse' => TRUE),
										array (   
										'label' => array ( 'sp' => 'Término preferido' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_pref_term',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'controlled_access_pointy',
										'inverse' => TRUE)
									)                                           
						),
						/* Definición del nodo ISBN, ISSN, Clasificaciones, Lenguaje */
						array(
					   'label' => array( 'sp' => 'ISBN' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_isbn',
					   'domain_table' => 'manifestation',
					   'range_table' => 'issn',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'ISSN' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_issn',
					   'domain_table' => 'manifestation',
					   'range_table' => 'isbn',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Clasificación Dewey' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_classif_dewey',
					   'domain_table' => 'manifestation',
					   'range_table' => 'classif_dewey',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Clasificación CDU' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_classif_cdu',
					   'domain_table' => 'manifestation',
					   'range_table' => 'classif_cdu',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Lenguaje' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_language',
					   'domain_table' => 'manifestation',
					   'range_table' => 'controlled_access_point',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),
					   /*  Definición del nodo Notas */
						array (
							'label' => array( 'sp' => 'Notas' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
							'is_visible' => 'true',
							'table_name' => 'note', 		
							'domain_table' => 'describable_entity',
							'range_table' => 'textual',						
							'inverse' => FALSE,
							'sons' => array(
											array (
											'label' => array( 'sp' => 'Nota de adquisición' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
											'is_visible' => 'true',
											'table_name' => 'has_note_adquisition',
											'domain_table' => 'item',
											'range_table' => 'textual',
											'inverse' => FALSE),
											array (
											'label' => array( 'sp' => 'Nota de contenido' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
											'is_visible' => 'true',
											'table_name' => 'has_note_content',
											'domain_table' => 'manifestation',
											'range_table' => 'textual',
											'inverse' => FALSE),
											array (
											'label' => array( 'sp' => 'Nota de encuadernación' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
											'is_visible' => 'true',
											'table_name' => 'has_note_boundwith',
											'domain_table' => 'item',
											'range_table' => 'textual',
											'inverse' => FALSE),
											array (
											'label' => array( 'sp' => 'Nota general' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
											'is_visible' => 'true',
											'table_name' => 'has_note_general',
											'domain_table' => 'manifestation',
											'range_table' => 'textual',
											'inverse' => FALSE),
											array (
											'label' => array( 'sp' => 'Nota del elemento' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
											'is_visible' => 'true',
											'table_name' => 'has_note_item',
											'domain_table' => 'item',
											'range_table' => 'textual',
											'inverse' => FALSE)
										)
						),
					   /******************************************************
					    * 
					    *			Relaciones de Entidad
					    *  
					    ******************************************************/
					   /*  Definición del nodo Responsabilidad */
					   array(
					   'label' => array( 'sp' => 'Responsable' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'responsible',
					   'domain_table' => 'bibliographic_entity',
					   'range_table' => 'person_corporate_body',
					   'inverse' => FALSE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Dueño' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_owner',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
					   				'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Director' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_director',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Impresor' ,'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_printer',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (   
										'label' => array ( 'sp' => 'Autor' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_author',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (   
										'label' => array ( 'sp' => 'Editorial' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_editorial',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE)
									)                                           
						),
						/*  Definición del nodo Autor */
						array(
					   'label' => array( 'sp' => 'Autor' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_author',
					   'domain_table' => 'bibliographic_entity',
					   'range_table' => 'person_corporate_body',
					   'inverse' => TRUE,           
					   'sons' => array( 
										array( 
										'label' => array ( 'sp' => 'Anotador' , 'en' => 'null' ,'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_annotator',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Editor' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_editor',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Redactor' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_redator',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Ilustrador' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_illustrator',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Curador' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'is_visible' => 'true',
										'table_name' => 'has_curator',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body',
										'inverse' => TRUE),
										array (
										'label' => array ( 'sp' => 'Compilador' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_compiler',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body'
										),
										array (
										'label' => array ( 'sp' => 'Diseñador' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_designer',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body'
										),
										array (
										'label' => array ( 'sp' => 'Prologuista' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_prologist',
					   				'domain_table' => 'bibliographic_entity',
					   				'range_table' => 'person_corporate_body'
										)
									)                                                                            
					   ),
					   /*  Definición del nodo Ilustrador */
					   array(
					   'label' => array( 'sp' => 'Ilustrador' , 'en' => 'null' ,'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_illustrator',
					   'domain_table' => 'bibliographic_entity',
					   'range_table' => 'person_corporate_body',
					   'inverse' => TRUE,           
					   'sons' => array( 
								   				array( 
													'label' => array ( 'sp' => 'Caricaturista' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
													'inverse' => TRUE,
													'is_visible' => 'true',
													'table_name' => 'has_cartoonist',
								   				'domain_table' => 'bibliographic_entity',
								   				'range_table' => 'person_corporate_body'
													)
											)										                                                                          
					   ),
					   /*  Definición del nodo Lugar */
					   array(
					   'label' => array( 'sp' => 'Lugar' , 'en' => 'null' , 'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'false',               
					   'table_name' => 'has_place',
					   'domain_table' => 'describable_entity',
					   'range_table' => 'place',
					   'inverse' => FALSE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Lugar de Impresión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_printer_place',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'place'
										),
										array (
										'label' => array ( 'sp' => 'Lugar de Edición' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_edition_place',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'place'
										),
										array (
										'label' => array ( 'sp' => 'País de Impresión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_edition_country',
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'place'
									)
								)                                                                            
					   ),
					   /*  Definición del nodo Forma */
					   array(
					   'label' => array( 'sp' => 'Forma' , 'en' => 'null' , 'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'form',
					   'domain_table' => 'bibliographic_entity',
					   'range_table' => 'type',
					   'inverse' => FALSE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Forma de la Manifestación' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_manifestation_form',
					   				'domain_table' => 'manifestation',
					   				'range_table' => 'document_type'
										),
										array (
										'label' => array ( 'sp' => 'Forma de la Expresión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_expression_form',
					   				'domain_table' => 'expression',
					   				'range_table' => 'typology_doc'
										),
										array (
										'label' => array ( 'sp' => 'Forma de la Obra' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => TRUE,
										'is_visible' => 'true',
										'table_name' => 'has_work_form',
					   				'domain_table' => 'work',
					   				'range_table' => 'literary_form'
									)
								)                                                                            
					   ),
					   /*  Definición del nodo FRBR */
					   array(
					   'label' => array( 'sp' => 'Ejemplificación' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_exemplifies',
					   'domain_table' => 'item',
					   'range_table' => 'manifestation',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Materialización' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_materializes',
					   'domain_table' => 'manifestation',
					   'range_table' => 'expression',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Realización' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_realizes',
					   'domain_table' => 'expression',
					   'range_table' => 'work',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),					   
					   /*  Definición del nodo Materia */
					   array(
					   'label' => array( 'sp' => 'Materia' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_subject',
					   'domain_table' => 'work',
					   'range_table' => 'describable_entity',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),
					   /*  Definición de título */
					   array(
					   'label' => array( 'sp' => 'Título' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_title',
					   'domain_table' => 'describable_entity',
					   'range_table' => 'descriptor_entity',
					   'inverse' => TRUE,
					   'sons' => array()
					   ),
					   /******************************************************
					    * 
					    *			Relaciones de Literales
					    *   
					    ******************************************************/
					   /*  Definición del nodo Precio */
					   array(
					   'label' => array( 'sp' => 'Precio' , 'en' => 'null' , 'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'price',
					   'domain_table' => 'item',
					   'range_table' => 'price',
					   'object_type' => 'float',
					   'inverse' => FALSE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Precio CUC' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_price_cuc',
					   				'domain_table' => 'item',
					   				'range_table' => 'price'
										),
										array (
										'label' => array ( 'sp' => 'Precio MN' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_price_mn',
					   				'domain_table' => 'item',
					   				'range_table' => 'price'
										),
										array (
										'label' => array ( 'sp' => 'Precio USD' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_price_usd',
					   				'domain_table' => 'item',
					   				'range_table' => 'price'
										)
									)
					   ),
					   /*  Definición del nodo Fecha */
					   array(
					   'label' => array( 'sp' => 'Fecha' , 'en' => 'null' , 'fr' => 'null'  , 'de' => 'null'),
					   'is_visible' => 'false',               
					   'table_name' => 'date',			
					   'domain_table' => 'describable_entity',
					   'range_table' => 'date',
					   'object_type' => 'date',
					   'inverse' => FALSE,           
					   'sons' => array( 
					   				array( 
										'label' => array ( 'sp' => 'Fecha de entrada' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_entry_date',			
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'date'
										),
										array (
										'label' => array ( 'sp' => 'Fecha de Edición' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_edition_date',			
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'date'
										),
										array (
										'label' => array ( 'sp' => 'Fecha de Impresión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
										'inverse' => FALSE,
										'is_visible' => 'true',
										'table_name' => 'has_printer_date',			
					   				'domain_table' => 'describable_entity',
					   				'range_table' => 'date'
										)
									)                                                                            
					   ),
					   /*  Definición del nodo Altura, Longitud, Volumen, Peso */
					   array(
					   'label' => array( 'sp' => 'Altura' , 'en' => 'null' , 'fr' => 'null', 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_height',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_height',
					   'object_type' => 'float',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Longitud' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_lenght',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_lenght',
					   'object_type' => 'float',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Volumen' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_volume',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_volume',
					   'object_type' => 'float',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Peso' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_weight',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_weight',
					   'object_type' => 'float',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   /*  Definición del nodo Paginas */
					   array(
					   'label' => array( 'sp' => 'Páginas' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_pages',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_pages',
					   'object_type' => 'integer',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   /*  Definición del nodo Localización, Disponibilidad, Acta de compra, Via de Adquisición, Acta de Comisión */
					   array(
					   'label' => array( 'sp' => 'Localización' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_location',
					   'domain_table' => 'item',
					   'range_table' => 'has_location',
					   'object_type' => 'string',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Disponibilidad' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_availability',
					   'domain_table' => 'item',
					   'range_table' => 'has_availability',
					   'object_type' => 'string',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Acta de compra' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_buy_act',
					   'domain_table' => 'item',
					   'range_table' => 'has_buy_act',
					   'object_type' => 'string',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Via de adquisición' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_adquisition_way',
					   'domain_table' => 'item',
					   'range_table' => 'has_adquisition_way',
					   'object_type' => 'string',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Acta de la comisión' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_commission_act',
					   'domain_table' => 'item',
					   'range_table' => 'has_commission_act',
					   'object_type' => 'string',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   /******************************************************
					    * 
					    *			Relaciones de Propiedad
					    *   
					    ******************************************************/
					   /*  Definición del nodo Valor, Forma y Color */
					   array(
					   'label' => array( 'sp' => 'Valor' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_value',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_value',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Forma' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_shape',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_shape',
					   'inverse' => FALSE,
					   'sons' => array()
					   ),
					   array(
					   'label' => array( 'sp' => 'Color' , 'en' => 'null' , 'fr' => 'null' , 'de' => 'null'),
					   'is_visible' => 'true',               
					   'table_name' => 'has_color',
					   'domain_table' => 'manifestation',
					   'range_table' => 'has_color',
					   'inverse' => FALSE,
					   'sons' => array()
					   )				   
				);
				
/*
 * Definición de la tabla entity_property
 */
$entity_property = array (
											array('entity_type' => 'phisical_entity', 
														'property_type' => array(
																									'date','form','has_classif_cdu','has_classif_dewey','has_color','has_exemplifies','has_height','has_isbn','has_issn','has_language',
																									'has_lenght','has_location','has_materializes','has_nomen','has_pages','has_place','has_realizes','has_shape','has_subject','has_title',
																									'has_volume','has_weight','note','price','responsible'
																									//'is_exemplifies','is_materializes','is_realizes','is_subject', 
																							 ) 
											),
											array('entity_type' => 'person', 
														'property_type' => array(
																									'date','has_nomen','has_place','has_weight','has_heigth'
																									//'is_subject','responsible'
																							 )
											),
											array('entity_type' => 'corporate_body', 
														'property_type' => array(
																									'date','has_nomen','has_place','note'
																									//'responsible',
																							 )
											),
											array('entity_type' => 'place', 
														'property_type' => array(
																									'date','has_nomen','note'
																									//'is_subject','is_place',
																							 )
											),
											array('entity_type' => 'event', 
														'property_type' => array(
																									'date','has_nomen','has_place'
																									//'is_subject'
																							 )
											),
											array('entity_type' => 'concept', 
														'property_type' => array(
																									'has_pref_term'
																							 )
											),
											array('entity_type' => 'title', 
														'property_type' => array(
																									'has_title_property'
																									//'is_subject','responsible'
																							 )
											)
											
									 );

/*
												 
											   array(
											   'label' => array( 'sp' => 'null' , 'en' => 'null' , 'fr' => 'null'),
											   'is_visible' => 'true',               
											   'table_name' => 'null',
											   'domain_table' => 'null',
											   'range_table' => 'null',
											   'inverse' => FALSE,           
											   'sons' => array( 
							                  'label' => array ( 'sp' => 'null' , 'en' => 'null' , 'fr' => 'null'),
											   				'inverse' => TRUE,
											   				'is_visible' => 'true',
											   				'table_name' => 'null',
											   				'domain_table' => 'null',
											   				'range_table' => 'null'
											   				),
											   				array (
							                  'label' => array ( 'sp' => 'null' , 'en' => 'null' , 'fr' => 'null'),
											   				'inverse' => TRUE,
											   				'is_visible' => 'true',
											   				'table_name' => 'null',
											   				'domain_table' => 'null',
											   				'range_table' => 'null'
											   				)                                                                            
											   )
*/
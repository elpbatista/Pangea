<?php
define ( 'PG_LOG_PATH', dirname ( __FILE__ ) . '/../logs/' );
define ( 'PG_RESULTSETS_REPO_PATH', dirname ( __FILE__ ) . '/../search/resultsets/' );
define ( 'PG_MODEL_REPO_PATH', dirname ( __FILE__ ) . '/../search/resultsets/' );

//define ( 'PG_DEFAULT_TIMEZONE', 'America/New_York' );
/*
 * Estas 2 variables de configuracion son altamente dependientes entre si. De modo que:
 * if(PG_SAVE_QUERIES == false) then PG_USE_PERSISTENS_QUERIES = false. En el sentido 
 * contrario la dependencia es parcial o nula 
 * (if(PG_SAVE_QUERIES == true) then PG_USE_PERSISTENS_QUERIES = {true|false}). 
 * */

define ( 'PG_APP_DEBUG_MODE', false );

define ( 'PG_SAVE_INFO_RETRIEVED', true );
define ( 'PG_USE_PERSISTENS_QUERIES', false );
define ( 'PG_GENERATE_LABELED_TRIPLES', true );
define ( 'PG_SHOW_PROCEDURE_TIMES', false );

define ( 'PG_LOG_ACTIVITY', false );

//define ( 'PG_QUERY_RESERVED_KEYWORDS', '' );
/**
 * TODO..
 * Esto lleva procesamiento....
 * */
define ( 'PG_QUERY_RESERVED_KEYWORDS', 'tit=dc:title|aut=frbr:creator|own=frbr:owner' );
define ( 'PG_QUERY_INDEXING_TYPE', 'DEFAULT' );
define ( 'PG_DBMS_WILDCARDS_PATTERN_REG', '[_%]' );
define ( 'PG_QUERY_FOR_LIKE_ESCAPE_CHAR', '\\' );

define ( 'PG_SHORT_BUILD_LEVEL', '/s' ); // stand for short
define ( 'PG_MEDIUM_BUILD_LEVEL', '/m' ); // stand for medium
define ( 'PG_LARGE_BUILD_LEVEL', '/l' ); // stand for large

define ( 'PG_DB_SHORT_BUILD_LEVEL', 0 ); // stand for short
define ( 'PG_DB_MEDIUM_BUILD_LEVEL', 1 ); // stand for medium
define ( 'PG_DB_LARGE_BUILD_LEVEL', 2 ); // stand for large

define ( 'PG_DEFAULT_INFERENCE_LEVEL', 0 );
define ( 'PG_DEFAULT_PAGE_SIZE', 10 );
define ( 'PG_DEFAULT_INITIAL_PAGE_INDEX', 1 );
define ( 'PG_DEFAULT_LANGUAGE', 'sp' );
define ( 'PG_DEFAULT_SEARCH_LOGICAL_OPERATION', '||' ); // || (logical OR), && (logical AND)


define ( 'PG_PERSISTEN_QUERY_STATUS_INACTIVE', 0 );
define ( 'PG_PERSISTEN_QUERY_STATUS_INPROCESS', 1 );
define ( 'PG_PERSISTEN_QUERY_STATUS_ACTIVE', 2 );

define ( 'ZENDAPI_INCLUDE_DIR', dirname ( __FILE__ ) . '/../lib/Zend/' );
define ( 'RDFAPI_INCLUDE_DIR', dirname ( __FILE__ ) . '/../lib/RAP/api/' );

# RDF Schema 


$GLOBALS ["RDF_GENERATED_SCHEMA_DOMAIN_RANGE"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_SUB_CLASS"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_CLASS"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_CLASS_INSTANCE"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_PROPERTY"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_SUB_PROPERTY"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_INVERSE"] = true;
$GLOBALS ["RDF_GENERATED_SCHEMA_LABEL"] = true;

/*
 * Seudo consulta utilizada para almacenar las tripletas asociadas al schema que se almacenaran
 * en BD.
 * */
define ( 'RDF_GENERATED_SCHEMA_QUERY', 'GET SCHEMA' );
define ( 'RDF_GENERATED_ENTITY_QUERY', 'GET ENTITY $' );
define ( 'RDF_GENERATED_ENTITY_BASICFIELDS_QUERY', 'GET PARTIAL ENTITY $' );
define ( 'RDF_GENERATED_ENTITYNAME_QUERY', 'GET ENTITYNAME $' );

define ( 'PG_PERSISTENS_TTL_INTERVAL', 1 ); // in days


define ( 'PG_DEFAULT_TIMEZONE', 'UTC' );
define ( 'PG_DEFAULT_DATETIME_FORMAT', DATE_RFC822 );

define ( 'PG_NULL_CHAR_STR_REPLACE', '~n~' );
define ( 'PG_DELIMETER_CHAR', '|' );
define ( 'PG_SEARCH_QUERY_DELIMETER_CHAR', '=' );
define ( 'PG_VALUES_DELIMETER_CHAR', ',' );

define ( 'PG_SEARCH_QUERY_EQUAL_CHAR', '=' );
define ( 'PG_SEARCH_QUERY_OR_CHAR', '|' );
define ( 'PG_SEARCH_QUERY_AND_CHAR', '+' );
define ( 'PG_SEARCH_QUERY_ALTERNATIVE_PATTERN_REG', '\(\w[\|\w]*\)' );
define ( 'PG_SEARCH_INFILTER_CHUNK_SIZE_ID', 500 );

define ( 'PG_PROPERTY_NAME_DELIMETER_CHAR', ':' );
define ( 'PG_PROPERTY_NAME_PATTERN_REG', '\w:\w' );
define ( 'PG_BNODE_NAME_PATTERN_REG', '^_:' );

define ( 'PG_BUILDED_PROPERTY', 'pangea:builded' );
define ( 'PG_FILTERED_PROPERTY', 'pangea:filtered' );

define ( 'PG_CLIENT_CONNECTION_ENCODING', 'UTF8' );

define ( 'PG_ENTITY_SAVE_OPERATION_REPEATS', 2 );

define ( 'META_CLASS_TABLENAME', 'owl:Class' );
define ( 'META_PROPERTY_TABLENAME', 'owl:Property' );
define ( 'META_DATAPROPERTY_TABLENAME', 'owl:DatatypeProperty' );
define ( 'META_OBJECTPROPERTY_TABLENAME', 'owl:ObjectProperty' );

define ( 'PG_ROOT_TABLENAME', 'pangea' );
define ( 'PG_ROOT_INT_TABLENAME', 'pangeaInteger' );
define ( 'PG_CLASS_TABLENAME', 'pangea:Class' );
define ( 'PG_BASIC_PROPERTY_TABLENAME', 'pangea:bareNecessities' );
define ( 'PG_CONCEPT_NAMING_PROPERTY_TABLENAME', 'pangea:nomen' );
define ( 'PG_ENTITY_NAMING_PROPERTY_TABLENAME', 'rdfs:label' );
define ( 'PG_PROPERTY_TABLENAME', 'pangea:Property' );
define ( 'PG_DATAPROPERTY_TABLENAME', 'pangea:DatatypeProperty' );
define ( 'PG_OBJECTPROPERTY_TABLENAME', 'pangea:ObjectProperty' );
define ( 'PG_MAGIC_SUBJECT_LABEL_TYPEOF_VIEWNAME', 'subject_label_typeof_mv' );

define ( 'PG_ENTITY_FORM_PROPERTY_NAME', 'pangea:hasForm' );

define ( 'PG_BIB_MANIFESTATION_EXPRESSION_BIND_PROPERTY_NAME', 'frbr:embodimentOf' );
define ( 'PG_BIB_EXPRESSION_WORK_BIND_PROPERTY_NAME', 'frbr:realizationOf' );
define ( 'PG_BIB_MANIFESTATION_ITEM_BIND_PROPERTY_NAME', 'frbr:exemplar' );

define ( 'PG_DEFAULT_CLUSTER_ID', 'frbr:Manifestation' );

define ( 'PG_DEFAULT_LABEL_PROPERTY', 'skos:prefLabel,skosxl:prefLabel' );
?>
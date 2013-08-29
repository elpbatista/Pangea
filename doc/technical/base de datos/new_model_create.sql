/*Se crea una usuario que ser· el dueÒo de la Base de datos y de todos los objetos*/
CREATE ROLE pangea LOGIN
  ENCRYPTED PASSWORD 'md52bca19c9c104cce43026f78998ab449c'
  NOSUPERUSER INHERIT NOCREATEDB NOCREATEROLE;

CREATE DATABASE "PANGEA_DESA"
  WITH OWNER = pangea
       ENCODING = 'UTF8';
       
CREATE SEQUENCE system_meta_object_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;
ALTER TABLE system_meta_object_id_seq OWNER TO pangea;

CREATE SEQUENCE system_object_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;
ALTER TABLE system_object_id_seq OWNER TO pangea;

CREATE SEQUENCE entity_property_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 4139
  CACHE 1;
ALTER TABLE entity_property_id_seq OWNER TO pangea;

CREATE SEQUENCE rules_object_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;
ALTER TABLE rules_object_id_seq OWNER TO pangea;

/********************************************************************************************/       
/*pangea*/
/********************************************************************************************/
CREATE TABLE pangea
(
  id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT pangea_pkey PRIMARY KEY (id)
)
WITH (OIDS=TRUE);
ALTER TABLE pangea OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point*/
/********************************************************************************************/ 
CREATE TABLE access_point
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  "text" character varying(4000) NOT NULL,
  CONSTRAINT access_point_pkey PRIMARY KEY (id)
)
INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE access_point OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/textual*/ 
/********************************************************************************************/ 
CREATE TABLE textual
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT textual_pkey PRIMARY KEY (id),
  CONSTRAINT textual_text_key UNIQUE (text)
)
INHERITS (access_point)
WITH (OIDS=TRUE);
ALTER TABLE textual OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/identifier*/ 
/********************************************************************************************/ 

CREATE TABLE identifier
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT identifier_pkey PRIMARY KEY (id),
  CONSTRAINT identifier_text_key UNIQUE (text)
)
INHERITS (access_point)
WITH (OIDS=TRUE);
ALTER TABLE identifier OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/identifier/isbn*/ 
/********************************************************************************************/ 
CREATE TABLE isbn
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT isbn_pkey PRIMARY KEY (id),
  CONSTRAINT isbn_text_key UNIQUE (text)
)
INHERITS (identifier)
WITH (OIDS=TRUE);
ALTER TABLE isbn OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/identifier/issn*/ 
/********************************************************************************************/ 
CREATE TABLE issn
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT issn_pkey PRIMARY KEY (id),
  CONSTRAINT issn_text_key UNIQUE (text)
)
INHERITS (identifier)
WITH (OIDS=TRUE);
ALTER TABLE issn OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/classification*/ 
/********************************************************************************************/ 
CREATE TABLE classification
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT classification_pkey PRIMARY KEY (id),
  CONSTRAINT classification_text_key UNIQUE (text)
)
INHERITS (access_point)
WITH (OIDS=TRUE);
ALTER TABLE classification OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/classification/classif_dewey*/ 
/********************************************************************************************/ 
CREATE TABLE classif_dewey
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT classif_dewey_pkey PRIMARY KEY (id),
  CONSTRAINT classif_dewey_text_key UNIQUE (text)
)
INHERITS (classification)
WITH (OIDS=TRUE);
ALTER TABLE classif_dewey OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/classification/classif_cdu*/ 
/********************************************************************************************/ 
CREATE TABLE classif_cdu
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  CONSTRAINT classif_cdu_pkey PRIMARY KEY (id),
  CONSTRAINT classif_cdu_text_key UNIQUE (text)
)
INHERITS (classification)
WITH (OIDS=TRUE);
ALTER TABLE classif_cdu OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point*/ 
/********************************************************************************************/ 
CREATE TABLE controlled_access_point
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'NO',
  CONSTRAINT controlled_access_point_pkey PRIMARY KEY (id),
  CONSTRAINT controlled_access_point_text_key UNIQUE (text)
)
INHERITS (access_point)
WITH (OIDS=TRUE);
ALTER TABLE controlled_access_point OWNER TO pangea; 

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_en*/ 
/********************************************************************************************/ 

CREATE TABLE cpa_en
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'en',
  CONSTRAINT cpa_en_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_en_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_en OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_sp*/ 
/********************************************************************************************/

CREATE TABLE cpa_sp
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'sp',
  CONSTRAINT cpa_sp_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_sp_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_sp OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_it*/ 
/********************************************************************************************/ 

CREATE TABLE cpa_it
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'it',
  CONSTRAINT cpa_it_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_it_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_it OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_fr*/ 
/********************************************************************************************/ 

CREATE TABLE cpa_fr
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'fr',
  CONSTRAINT cpa_fr_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_fr_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_fr OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_ot*/ 
/********************************************************************************************/ 
CREATE TABLE cpa_ot
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'ot',
  CONSTRAINT cpa_ot_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_ot_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_ot OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_de*/ 
/********************************************************************************************/ 
CREATE TABLE cpa_de
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'de',
  CONSTRAINT cpa_de_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_de_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_de OWNER TO pangea;

/********************************************************************************************/       
/*pangea/access_point/controlled_access_point/cpa_ct*/ 
/********************************************************************************************/ 
CREATE TABLE cpa_ct
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   "text" character varying(4000) NOT NULL,
  lang character varying(5) DEFAULT 'ct',
  CONSTRAINT cpa_ct_pkey PRIMARY KEY (id),
  CONSTRAINT cpa_ct_text_key UNIQUE (text)
)
INHERITS (controlled_access_point)
WITH (OIDS=TRUE);
ALTER TABLE cpa_ct OWNER TO pangea;

/********************************************************************************************/       
/*pangea/descriptor_entity*/ 
/********************************************************************************************/ 

CREATE TABLE descriptor_entity
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT descriptor_entity_pkey PRIMARY KEY (id)
)
INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE descriptor_entity OWNER TO pangea;

/********************************************************************************************/       
/*pangea/descriptor_entity/title*/ 
/********************************************************************************************/ 

CREATE TABLE title
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT title_pkey PRIMARY KEY (id)
)
INHERITS (descriptor_entity)
WITH (OIDS=TRUE);
ALTER TABLE title OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity*/ 
/********************************************************************************************/ 

CREATE TABLE describable_entity
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT describable_entity_pkey PRIMARY KEY (id)
)
INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE describable_entity OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/event*/ 
/********************************************************************************************/ 

CREATE TABLE event
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT event_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE event OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/place*/ 
/********************************************************************************************/ 

CREATE TABLE place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT place_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE place OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept*/ 
/********************************************************************************************/ 

CREATE TABLE concept
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT concept_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE concept OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept/type*/ 
/********************************************************************************************/ 

CREATE TABLE type
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT type_pkey PRIMARY KEY (id)
)
INHERITS (concept)
WITH (OIDS=TRUE);
ALTER TABLE type OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept/color*/ 
/********************************************************************************************/ 

CREATE TABLE color
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT color_pkey PRIMARY KEY (id)
)
INHERITS (concept)
WITH (OIDS=TRUE);
ALTER TABLE color OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept/type/document_type*/ 
/********************************************************************************************/ 

CREATE TABLE document_type
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT document_type_pkey PRIMARY KEY (id)
)
INHERITS (type)
WITH (OIDS=TRUE);
ALTER TABLE document_type OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept/type/typology_doc*/ 
/********************************************************************************************/ 

CREATE TABLE typology_doc
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT typology_doc_pkey PRIMARY KEY (id)
)
INHERITS (type)
WITH (OIDS=TRUE);
ALTER TABLE typology_doc OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/concept/type/literary_form*/ 
/********************************************************************************************/ 

CREATE TABLE literary_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT literary_form_pkey PRIMARY KEY (id)
)
INHERITS (type)
WITH (OIDS=TRUE);
ALTER TABLE literary_form OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/phisical_entity*/ 
/********************************************************************************************/ 

CREATE TABLE phisical_entity
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT phisical_entity_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE phisical_entity OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/phisical_entity/item*/ 
/********************************************************************************************/ 

CREATE TABLE item
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT item_pkey PRIMARY KEY (id)
)
INHERITS (phisical_entity)
WITH (OIDS=TRUE);
ALTER TABLE item OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/phisical_entity/object*/ 
/********************************************************************************************/ 
CREATE TABLE object
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT object_pkey PRIMARY KEY (id)
)
INHERITS (phisical_entity)
WITH (OIDS=TRUE);
ALTER TABLE object OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/bibliographic_entity*/ 
/********************************************************************************************/ 

CREATE TABLE bibliographic_entity
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT bibliographic_entity_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE bibliographic_entity OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/bibliographic_entity/work*/ 
/********************************************************************************************/ 

CREATE TABLE work
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT work_pkey PRIMARY KEY (id)
)
INHERITS (bibliographic_entity)
WITH (OIDS=TRUE);
ALTER TABLE work OWNER TO pangea;
/********************************************************************************************/       
/*pangea/describable_entity/bibliographic_entity/expression*/ 
/********************************************************************************************/ 
CREATE TABLE expression
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT expression_pkey PRIMARY KEY (id)
)
INHERITS (bibliographic_entity)
WITH (OIDS=TRUE);
ALTER TABLE expression OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/bibliographic_entity/manifestation*/ 
/********************************************************************************************/ 

CREATE TABLE manifestation
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT manifestation_pkey PRIMARY KEY (id)
)
INHERITS (bibliographic_entity)
WITH (OIDS=TRUE);
ALTER TABLE manifestation OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/person_corporate_body*/ 
/********************************************************************************************/ 

CREATE TABLE person_corporate_body
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT person_corporate_body_pkey PRIMARY KEY (id)
)
INHERITS (describable_entity)
WITH (OIDS=TRUE);
ALTER TABLE person_corporate_body OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/person_corporate_body/person*/ 
/********************************************************************************************/ 
CREATE TABLE person
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT person_pkey PRIMARY KEY (id)
)
INHERITS (person_corporate_body)
WITH (OIDS=TRUE);
ALTER TABLE person OWNER TO pangea;

/********************************************************************************************/       
/*pangea/describable_entity/person_corporate_body/corporate_body*/ 
/********************************************************************************************/ 
CREATE TABLE corporate_body
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT corporate_body_pkey PRIMARY KEY (id)
)
INHERITS (person_corporate_body)
WITH (OIDS=TRUE);
ALTER TABLE corporate_body OWNER TO pangea;

/********************************************************************************************/       
/*pangea/property*/ 
/********************************************************************************************/ 
CREATE TABLE property
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  subject_source character varying(50) NOT NULL,
  subject integer NOT NULL,
  object_source character varying(50),
  "object" text NOT NULL,
  CONSTRAINT property_idkey UNIQUE (id, object)
)
INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE property OWNER TO pangea;

/********************************************************************************************/   
/*pangea/property/property_as_relation*/ 
/********************************************************************************************/ 
CREATE TABLE property_as_relation
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
  "value" integer NOT NULL,  
  CONSTRAINT property_as_relation_pkey PRIMARY KEY (subject, value),
  CONSTRAINT property_as_relation_idkey UNIQUE (id)
)
INHERITS (property)
WITH (OIDS=TRUE);
ALTER TABLE property_as_relation OWNER TO pangea;

/********************************************************************************************/   
/*pangea/property/property_as_relation/property_as_relation_entity*/ 
/********************************************************************************************/ 
CREATE TABLE property_as_relation_entity
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT property_as_relation_entity_pkey PRIMARY KEY (subject, value),
  CONSTRAINT property_as_relation_entity_idkey UNIQUE (id)
)
INHERITS (property_as_relation)
WITH (OIDS=TRUE);
ALTER TABLE property_as_relation_entity OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_title*/
/********************************************************************************************/
CREATE TABLE has_title
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   subject_source character varying(50) DEFAULT 'manifestation',
   object_source character varying(50) DEFAULT 'title',
  CONSTRAINT has_title_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_title OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_title*/
/********************************************************************************************/
CREATE TABLE is_title
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   subject_source character varying(50) DEFAULT 'title',
   object_source character varying(50) DEFAULT 'manifestation',
  CONSTRAINT is_title_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE is_title OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_color*/
/********************************************************************************************/
CREATE TABLE has_color
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_color_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_color_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_color OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place*/
/********************************************************************************************/
CREATE TABLE has_place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_place_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_place_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_place OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/has_edition_place*/
/********************************************************************************************/
CREATE TABLE has_edition_place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_edition_place_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_edition_place_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE has_edition_place OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/is_edition_place*/
/********************************************************************************************/
CREATE TABLE is_edition_place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_edition_place_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_edition_place_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE is_edition_place OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/has_printer_place*/
/********************************************************************************************/
CREATE TABLE has_printer_place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_printer_place_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_printer_place_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE has_printer_place OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/is_printer_place*/
/********************************************************************************************/
CREATE TABLE is_printer_place
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_printer_place_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_printer_place_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE is_printer_place OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/has_edition_country*/
/********************************************************************************************/
CREATE TABLE has_edition_country
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_edition_country_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_edition_country_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE has_edition_country OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_place/is_edition_country*/
/********************************************************************************************/
CREATE TABLE is_edition_country
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_edition_country_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_edition_country_idkey UNIQUE (id)
)
INHERITS (has_place)
WITH (OIDS=TRUE);
ALTER TABLE is_edition_country OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_shape*/
/********************************************************************************************/
CREATE TABLE has_shape
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_shape_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_shape_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_shape OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_value*/
/********************************************************************************************/
CREATE TABLE has_value
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_value_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_value_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_value OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/has_subject*/
/********************************************************************************************/
CREATE TABLE has_subject
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_subject_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_subject_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE has_subject OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_subject*/
/********************************************************************************************/
CREATE TABLE is_subject
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_subject_pkey PRIMARY KEY (subject, value),
  CONSTRAINT is_subject_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE is_subject OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form*/
/********************************************************************************************/
CREATE TABLE form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT form_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/has_manifestation_form*/
/********************************************************************************************/
CREATE TABLE has_manifestation_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_manifestation_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_manifestation_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE has_manifestation_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/is_manifestation_form*/
/********************************************************************************************/
CREATE TABLE is_manifestation_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_manifestation_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT is_manifestation_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE is_manifestation_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/has_expression_form*/
/********************************************************************************************/
CREATE TABLE has_expression_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_expression_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_expression_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE has_expression_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/is_expression_form*/
/********************************************************************************************/
CREATE TABLE is_expression_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_expression_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT is_expression_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE is_expression_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/has_work_form*/
/********************************************************************************************/
CREATE TABLE has_work_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_work_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_work_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE has_work_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/form/is_work_form*/
/********************************************************************************************/
CREATE TABLE is_work_form
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_work_form_pkey PRIMARY KEY (subject, value),
  CONSTRAINT is_work_form_idkey UNIQUE (id)
)
INHERITS (form)
WITH (OIDS=TRUE);
ALTER TABLE is_work_form OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr*/
/********************************************************************************************/
CREATE TABLE relation_frbr
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT relation_frbr_pkey PRIMARY KEY (subject,value),
  CONSTRAINT relation_frbr_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE relation_frbr OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/has_realizes*/
/********************************************************************************************/
CREATE TABLE has_realizes
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_realizes_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_realizes_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE has_realizes OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/is_realizes*/
/********************************************************************************************/
CREATE TABLE is_realizes
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_realizes_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_realizes_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE is_realizes OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/has_materializes*/
/********************************************************************************************/
CREATE TABLE has_materializes
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_materializes_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_materializes_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE has_materializes OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/is_materializes*/
/********************************************************************************************/
CREATE TABLE is_materializes
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_materializes_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_materializes_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE is_materializes OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/has_exemplifies*/
/********************************************************************************************/
CREATE TABLE has_exemplifies
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_exemplifies_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_exemplifies_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE has_exemplifies OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/relation_frbr/is_exemplifies*/
/********************************************************************************************/
CREATE TABLE is_exemplifies
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_exemplifies_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_exemplifies_idkey UNIQUE (id)
)
INHERITS (relation_frbr)
WITH (OIDS=TRUE);
ALTER TABLE is_exemplifies OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible*/
/********************************************************************************************/
CREATE TABLE responsible
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT responsible_pkey PRIMARY KEY (subject,value),
  CONSTRAINT responsible_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE responsible OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_owner*/
/********************************************************************************************/
CREATE TABLE has_owner
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_owner_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_owner_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE has_owner OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_owner*/
/********************************************************************************************/
CREATE TABLE is_owner
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_owner_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_owner_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE is_owner OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_printer*/
/********************************************************************************************/
CREATE TABLE has_printer
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_printer_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_printer_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE has_printer OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_printer*/
/********************************************************************************************/
CREATE TABLE is_printer
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_printer_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_printer_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE is_printer OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_editorial*/
/********************************************************************************************/
CREATE TABLE has_editorial
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_editorial_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_editorial_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE has_editorial OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_editorial*/
/********************************************************************************************/
CREATE TABLE is_editorial
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_editorial_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_editorial_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE is_editorial OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_director*/
/********************************************************************************************/
CREATE TABLE has_director
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_director_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_director_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE has_director OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_director*/
/********************************************************************************************/
CREATE TABLE is_director
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_director_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_director_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE is_director OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author*/
/********************************************************************************************/
CREATE TABLE has_author
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_author_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_author_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE has_author OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author*/
/********************************************************************************************/
CREATE TABLE is_author
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_author_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_author_idkey UNIQUE (id)
)
INHERITS (responsible)
WITH (OIDS=TRUE);
ALTER TABLE is_author OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_designer*/
/********************************************************************************************/
CREATE TABLE has_designer
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_designer_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_designer_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_designer OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_designer*/
/********************************************************************************************/
CREATE TABLE is_designer
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_designer_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_designer_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_designer OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_prologist*/
/********************************************************************************************/
CREATE TABLE has_prologist
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_prologist_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_prologist_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_prologist OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_prologist*/
/********************************************************************************************/
CREATE TABLE is_prologist
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_prologist_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_prologist_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_prologist OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_compiler*/
/********************************************************************************************/
CREATE TABLE has_compiler
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_compiler_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_compiler_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_compiler OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_compiler*/
/********************************************************************************************/
CREATE TABLE is_compiler
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_compiler_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_compiler_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_compiler OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_curator*/
/********************************************************************************************/
CREATE TABLE has_curator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_curator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_curator_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_curator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_curator*/
/********************************************************************************************/
CREATE TABLE is_curator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_curator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_curator_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_curator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_illustrator*/
/********************************************************************************************/
CREATE TABLE has_illustrator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_illustrator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_illustrator_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_illustrator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_illustrator/has_cartoonist*/
/********************************************************************************************/
CREATE TABLE has_cartoonist
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_cartoonist_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_cartoonist_idkey UNIQUE (id)
)
INHERITS (has_illustrator)
WITH (OIDS=TRUE);
ALTER TABLE has_cartoonist OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_illustrator*/
/********************************************************************************************/
CREATE TABLE is_illustrator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_illustrator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_illustrator_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_illustrator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_illustrator/is_cartoonist*/
/********************************************************************************************/
CREATE TABLE is_cartoonist
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_cartoonist_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_cartoonist_idkey UNIQUE (id)
)
INHERITS (is_illustrator)
WITH (OIDS=TRUE);
ALTER TABLE is_cartoonist OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_redator*/
/********************************************************************************************/
CREATE TABLE has_redator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_redator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_redator_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_redator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_redator*/
/********************************************************************************************/
CREATE TABLE is_redator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_redator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_redator_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_redator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_editor*/
/********************************************************************************************/
CREATE TABLE has_editor
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_editor_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_editor_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_editor OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_editor*/
/********************************************************************************************/
CREATE TABLE is_editor
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_editor_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_editor_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_editor OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/has_author/has_annotator*/
/********************************************************************************************/
CREATE TABLE has_annotator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_annotator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_annotator_idkey UNIQUE (id)
)
INHERITS (has_author)
WITH (OIDS=TRUE);
ALTER TABLE has_annotator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/responsible/is_author/is_annotator*/
/********************************************************************************************/
CREATE TABLE is_annotator
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_annotator_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_annotator_idkey UNIQUE (id)
)
INHERITS (is_author)
WITH (OIDS=TRUE);
ALTER TABLE is_annotator OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_language*/
/********************************************************************************************/
CREATE TABLE is_language
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  subject_source character varying(50) DEFAULT 'access_point',
  CONSTRAINT is_language_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_language_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE is_language OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen*/
/********************************************************************************************/
CREATE TABLE is_nomen
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   subject_source character varying(50) DEFAULT 'access_point',
  CONSTRAINT is_nomen_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_nomen_idkey UNIQUE (id)
)
INHERITS (property_as_relation_entity)
WITH (OIDS=TRUE);
ALTER TABLE is_nomen OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_pref_term*/
/********************************************************************************************/
CREATE TABLE is_pref_term
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_pref_term_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_pref_term_idkey UNIQUE (id)
)
INHERITS (is_nomen)
WITH (OIDS=TRUE);
ALTER TABLE is_pref_term OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_name*/
/********************************************************************************************/
CREATE TABLE is_name
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_name_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_name_idkey UNIQUE (id)
)
INHERITS (is_nomen)
WITH (OIDS=TRUE);
ALTER TABLE is_name OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_alias*/
/********************************************************************************************/
CREATE TABLE is_alias
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_alias_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_alias_idkey UNIQUE (id)
)
INHERITS (is_nomen)
WITH (OIDS=TRUE);
ALTER TABLE is_alias OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_scientific_name*/
/********************************************************************************************/
CREATE TABLE is_scientific_name
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_scientific_name_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_scientific_name_idkey UNIQUE (id)
)
INHERITS (is_nomen)
WITH (OIDS=TRUE);
ALTER TABLE is_scientific_name OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property*/
/********************************************************************************************/
CREATE TABLE is_title_property
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   object_source character varying(50) DEFAULT 'title',
  CONSTRAINT is_title_property_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_property_idkey UNIQUE (id)
)
INHERITS (is_nomen)
WITH (OIDS=TRUE);
ALTER TABLE is_title_property OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_proper*/
/********************************************************************************************/
CREATE TABLE is_title_proper
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_proper_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_proper_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_proper OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_parallel*/
/********************************************************************************************/
CREATE TABLE is_title_parallel
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_parallel_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_parallel_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_parallel OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_colective*/
/********************************************************************************************/
CREATE TABLE is_title_colective
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_colective_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_colective_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_colective OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_alternative*/
/********************************************************************************************/
CREATE TABLE is_title_alternative
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_alternative_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_alternative_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_alternative OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied*/
/********************************************************************************************/
CREATE TABLE is_title_suplied
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_suplied_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_suplied_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_suplied OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied/is_title_variant_of*/
/********************************************************************************************/
CREATE TABLE is_title_variant_of
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_variant_of_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_variant_of_idkey UNIQUE (id)
)
INHERITS (is_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE is_title_variant_of OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied/is_title_uniform*/
/********************************************************************************************/
CREATE TABLE is_title_uniform
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   object_source character varying(50) DEFAULT 'work',
  CONSTRAINT is_title_uniform_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_uniform_idkey UNIQUE (id)
)
INHERITS (is_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE is_title_uniform OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied/is_title_translated*/
/********************************************************************************************/
CREATE TABLE is_title_translated
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_translated_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_translated_idkey UNIQUE (id)
)
INHERITS (is_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE is_title_translated OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied/is_title_without*/
/********************************************************************************************/
CREATE TABLE is_title_without
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_without_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_without_idkey UNIQUE (id)
)
INHERITS (is_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE is_title_without OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_suplied/is_title_without_colective*/
/********************************************************************************************/
CREATE TABLE is_title_without_colective
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_title_without_colective_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_without_colective_idkey UNIQUE (id)
)
INHERITS (is_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE is_title_without_colective OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_contributed*/
/********************************************************************************************/
CREATE TABLE is_title_contributed
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:  subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
  CONSTRAINT is_title_contributed_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_contributed_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_contributed OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_oit*/
/********************************************************************************************/
CREATE TABLE is_title_oit
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:  subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
  CONSTRAINT is_title_oit_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_oit_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_oit OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_entity/is_nomen/is_title_property/is_title_oit_parallel*/
/********************************************************************************************/
CREATE TABLE is_title_oit_parallel
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:  subject_source character varying(50) DEFAULT 'access_point',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'title',
  CONSTRAINT is_title_oit_parallel_pkey PRIMARY KEY (subject,value),
  CONSTRAINT is_title_oit_parallel_idkey UNIQUE (id)
)
INHERITS (is_title_property)
WITH (OIDS=TRUE);
ALTER TABLE is_title_oit_parallel OWNER TO pangea;

/********************************************************************************************/   
/*pangea/property/property_as_relation/property_as_relation_cpa*/ 
/********************************************************************************************/ 
CREATE TABLE property_as_relation_cpa
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  object_source character varying(50) DEFAULT 'access_point',
  CONSTRAINT property_as_relation_cpa_pkey PRIMARY KEY (subject, value),
  CONSTRAINT property_as_relation_cpa_idkey UNIQUE (id)
)
INHERITS (property_as_relation)
WITH (OIDS=TRUE);
ALTER TABLE property_as_relation_cpa OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen*/
/********************************************************************************************/
CREATE TABLE has_nomen
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_nomen_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_nomen_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_nomen OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_pref_term*/
/********************************************************************************************/
CREATE TABLE has_pref_term
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_pref_term_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_pref_term_idkey UNIQUE (id)
)
INHERITS (has_nomen)
WITH (OIDS=TRUE);
ALTER TABLE has_pref_term OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_name*/
/********************************************************************************************/
CREATE TABLE has_name
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_name_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_name_idkey UNIQUE (id)
)
INHERITS (has_nomen)
WITH (OIDS=TRUE);
ALTER TABLE has_name OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_alias*/
/********************************************************************************************/
CREATE TABLE has_alias
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_alias_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_alias_idkey UNIQUE (id)
)
INHERITS (has_nomen)
WITH (OIDS=TRUE);
ALTER TABLE has_alias OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_scientific_name*/
/********************************************************************************************/
CREATE TABLE has_scientific_name
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_scientific_name_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_scientific_name_idkey UNIQUE (id)
)
INHERITS (has_nomen)
WITH (OIDS=TRUE);
ALTER TABLE has_scientific_name OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property*/
/********************************************************************************************/
CREATE TABLE has_title_property
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
   subject_source character varying(50) DEFAULT 'title',
  CONSTRAINT has_title_property_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_property_idkey UNIQUE (id)
)
INHERITS (has_nomen)
WITH (OIDS=TRUE);
ALTER TABLE has_title_property OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_proper*/
/********************************************************************************************/
CREATE TABLE has_title_proper
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_proper_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_proper_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_proper OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_parallel*/
/********************************************************************************************/
CREATE TABLE has_title_parallel
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_parallel_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_parallel_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_parallel OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_colective*/
/********************************************************************************************/
CREATE TABLE has_title_colective
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_colective_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_colective_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_colective OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_alternative*/
/********************************************************************************************/
CREATE TABLE has_title_alternative
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_alternative_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_alternative_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_alternative OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied*/
/********************************************************************************************/
CREATE TABLE has_title_suplied
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_suplied_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_suplied_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_suplied OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_contributed*/
/********************************************************************************************/
CREATE TABLE has_title_contributed
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_contributed_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_contributed_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_contributed OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_oit*/
/********************************************************************************************/
CREATE TABLE has_title_oit
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_oit_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_oit_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_oit OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_oit_parallel*/
/********************************************************************************************/
CREATE TABLE has_title_oit_parallel
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_oit_parallel_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_oit_parallel_idkey UNIQUE (id)
)
INHERITS (has_title_property)
WITH (OIDS=TRUE);
ALTER TABLE has_title_oit_parallel OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied/has_title_variant_of*/
/********************************************************************************************/
CREATE TABLE has_title_variant_of
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_variant_of_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_variant_of_idkey UNIQUE (id)
)
INHERITS (has_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE has_title_variant_of OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied/has_title_uniform*/
/********************************************************************************************/
CREATE TABLE has_title_uniform
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  subject_source character varying(50) DEFAULT 'work',
  CONSTRAINT has_title_uniform_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_uniform_idkey UNIQUE (id)
)
INHERITS (has_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE has_title_uniform OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied/has_title_translated*/
/********************************************************************************************/
CREATE TABLE has_title_translated
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_translated_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_translated_idkey UNIQUE (id)
)
INHERITS (has_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE has_title_translated OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied/has_title_without*/
/********************************************************************************************/
CREATE TABLE has_title_without
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_without_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_without_idkey UNIQUE (id)
)
INHERITS (has_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE has_title_without OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_nomen/has_title_property/has_title_suplied/has_title_without_colective*/
/********************************************************************************************/
CREATE TABLE has_title_without_colective
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) DEFAULT 'title',
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_title_without_colective_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_title_without_colective_idkey UNIQUE (id)
)
INHERITS (has_title_suplied)
WITH (OIDS=TRUE);
ALTER TABLE has_title_without_colective OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note*/
/********************************************************************************************/
CREATE TABLE note
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT note_pkey PRIMARY KEY (subject, value),
  CONSTRAINT note_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE note OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note/has_note_general*/
/********************************************************************************************/
CREATE TABLE has_note_general
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_note_general_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_note_general_idkey UNIQUE (id)
)
INHERITS (note)
WITH (OIDS=TRUE);
ALTER TABLE has_note_general OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note/has_note_item*/
/********************************************************************************************/
CREATE TABLE has_note_item
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_note_item_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_note_item_idkey UNIQUE (id)
)
INHERITS (note)
WITH (OIDS=TRUE);
ALTER TABLE has_note_item OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note/has_note_content*/
/********************************************************************************************/
CREATE TABLE has_note_content
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_note_content_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_note_content_idkey UNIQUE (id)
)
INHERITS (note)
WITH (OIDS=TRUE);
ALTER TABLE has_note_content OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note/has_note_boundwith*/
/********************************************************************************************/
CREATE TABLE has_note_boundwith
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_note_boundwith_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_note_boundwith_idkey UNIQUE (id)
)
INHERITS (note)
WITH (OIDS=TRUE);
ALTER TABLE has_note_boundwith OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/note/has_note_adquisition*/
/********************************************************************************************/
CREATE TABLE has_note_adquisition
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_note_adquisition_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_note_adquisition_idkey UNIQUE (id)
)
INHERITS (note)
WITH (OIDS=TRUE);
ALTER TABLE has_note_adquisition OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_isbn*/
/********************************************************************************************/
CREATE TABLE has_isbn
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_isbn_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_isbn_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_isbn OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_issn*/
/********************************************************************************************/
CREATE TABLE has_issn
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_issn_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_issn_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_issn OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_classif_dewey*/
/********************************************************************************************/
CREATE TABLE has_classif_dewey
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_classif_dewey_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_classif_dewey_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_classif_dewey OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_classif_cdu*/
/********************************************************************************************/
CREATE TABLE has_classif_cdu
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_classif_cdu_pkey PRIMARY KEY (subject, value),
  CONSTRAINT has_classif_cdu_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_classif_cdu OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_relation/property_as_relation_cpa/has_language*/
/********************************************************************************************/
CREATE TABLE has_language
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'access_point',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_language_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_language_idkey UNIQUE (id)
)
INHERITS (property_as_relation_cpa)
WITH (OIDS=TRUE);
ALTER TABLE has_language OWNER TO pangea;

/********************************************************************************************/   
/*pangea/property/property_as_relation/property_as_relation_property*/ 
/********************************************************************************************/ 
CREATE TABLE property_as_relation_property
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  object_source character varying(50) DEFAULT 'property',
  subject_source character varying(50) DEFAULT 'access_point',
  CONSTRAINT property_as_relation_property_pkey PRIMARY KEY (subject, value),
  CONSTRAINT property_as_relation_property_idkey UNIQUE (id)
)
INHERITS (property_as_relation)
WITH (OIDS=TRUE);
ALTER TABLE property_as_relation_property OWNER TO pangea;

/********************************************************************************************/       
/*pangea/property/property_as_literal*/ 
/********************************************************************************************/ 
CREATE TABLE property_as_literal
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
   object_source character varying(50) DEFAULT 'literal',
   data_type character varying(50) DEFAULT 'string',
  CONSTRAINT property_as_literal_pkey PRIMARY KEY (subject),
  CONSTRAINT property_as_literal_idkey UNIQUE (id)
)
INHERITS (property)
WITH (OIDS=TRUE);
ALTER TABLE property_as_literal OWNER TO pangea;

/********************************************************************************************/       
/*pangea/property/property_as_literal/literal_float*/ 
/********************************************************************************************/ 
CREATE TABLE literal_float
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
  "value" float NOT NULL,
  data_type character varying(50) DEFAULT 'float',
  CONSTRAINT literal_float_pkey PRIMARY KEY (subject,value),
  CONSTRAINT literal_float_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE literal_float OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/has_height*/
/********************************************************************************************/
CREATE TABLE has_height
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_height_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_height_idkey UNIQUE (id)
)
INHERITS (literal_float)
WITH (OIDS=TRUE);
ALTER TABLE has_height OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/has_volume*/
/********************************************************************************************/
CREATE TABLE has_volume
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_volume_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_volume_idkey UNIQUE (id)
)
INHERITS (literal_float)
WITH (OIDS=TRUE);
ALTER TABLE has_volume OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/has_weight*/
/********************************************************************************************/
CREATE TABLE has_weight
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_weight_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_weight_idkey UNIQUE (id)
)
INHERITS (literal_float)
WITH (OIDS=TRUE);
ALTER TABLE has_weight OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/has_length*/
/********************************************************************************************/
CREATE TABLE has_length
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_length_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_length_idkey UNIQUE (id)
)
INHERITS (literal_float)
WITH (OIDS=TRUE);
ALTER TABLE has_length OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/price*/
/********************************************************************************************/
CREATE TABLE price
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT price_pkey PRIMARY KEY (subject,value),
  CONSTRAINT price_idkey UNIQUE (id)
)
INHERITS (literal_float)
WITH (OIDS=TRUE);
ALTER TABLE price OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/price/has_price_cuc*/
/********************************************************************************************/
CREATE TABLE has_price_cuc
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_price_cuc_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_price_cuc_idkey UNIQUE (id)
)
INHERITS (price)
WITH (OIDS=TRUE);
ALTER TABLE has_price_cuc OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/price/has_price_mn*/
/********************************************************************************************/
CREATE TABLE has_price_mn
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_price_mn_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_price_mn_idkey UNIQUE (id)
)
INHERITS (price)
WITH (OIDS=TRUE);
ALTER TABLE has_price_mn OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_float/price/has_price_usd*/
/********************************************************************************************/
CREATE TABLE has_price_usd
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" float NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'float',
  CONSTRAINT has_price_usd_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_price_usd_idkey UNIQUE (id)
)
INHERITS (price)
WITH (OIDS=TRUE);
ALTER TABLE has_price_usd OWNER TO pangea;

/********************************************************************************************/       
/*pangea/property/property_as_literal/literal_integer*/ 
/********************************************************************************************/ 
CREATE TABLE literal_integer
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
  "value" integer NOT NULL,
  data_type character varying(50) DEFAULT 'integer',
  CONSTRAINT literal_integer_pkey PRIMARY KEY (subject,value),
  CONSTRAINT literal_integer_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE literal_integer OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/literal_integer/has_pages*/
/********************************************************************************************/
CREATE TABLE has_pages
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'integer',
  CONSTRAINT has_pages_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_pages_idkey UNIQUE (id)
)
INHERITS (literal_integer)
WITH (OIDS=TRUE);
ALTER TABLE has_pages OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/date*/
/********************************************************************************************/
CREATE TABLE date
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
  "value" character varying(100) NOT NULL,
   data_type character varying(50) DEFAULT 'date',
  CONSTRAINT date_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE date OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/date/has_edition_date*/
/********************************************************************************************/
CREATE TABLE has_edition_date
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" character varying(100) NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'date',
  CONSTRAINT has_edition_date_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_edition_date_idkey UNIQUE (id)
)
INHERITS (date)
WITH (OIDS=TRUE);
ALTER TABLE has_edition_date OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/date/has_printer_date*/
/********************************************************************************************/
CREATE TABLE has_printer_date
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" character varying(100) NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'date',
  CONSTRAINT has_printer_date_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_printer_date_idkey UNIQUE (id)
)
INHERITS (date)
WITH (OIDS=TRUE);
ALTER TABLE has_printer_date OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/date/has_entry_date*/
/********************************************************************************************/
CREATE TABLE has_entry_date
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" character varying(100) NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'date',
  CONSTRAINT has_entry_date_pkey PRIMARY KEY (subject,value),
  CONSTRAINT has_entry_date_idkey UNIQUE (id)
)
INHERITS (date)
WITH (OIDS=TRUE);
ALTER TABLE has_entry_date OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/has_location*/
/********************************************************************************************/
CREATE TABLE has_location
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'string',
  "value" character varying(100) NOT NULL,
  CONSTRAINT has_location_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE has_location OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/has_availability*/
/********************************************************************************************/
CREATE TABLE has_availability
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'string',
  "value" character varying(100) NOT NULL,
  CONSTRAINT has_availability_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE has_availability OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/has_adquisition_way*/
/********************************************************************************************/
CREATE TABLE has_adquisition_way
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'string',
  "value" character varying(100) NOT NULL,
  CONSTRAINT has_adquisition_way_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE has_adquisition_way OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/has_commission_act*/
/********************************************************************************************/
CREATE TABLE has_commission_act
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'string',
  "value" character varying(100) NOT NULL,
  CONSTRAINT has_commission_act_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE has_commission_act OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/property_as_literal/has_buy_act*/
/********************************************************************************************/
CREATE TABLE has_buy_act
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50) DEFAULT 'literal',
-- Inherited:   "object" text NOT NULL,
-- Inherited:   data_type character varying(50) DEFAULT 'string',
  "value" character varying(100) NOT NULL,
  CONSTRAINT has_buy_act_idkey UNIQUE (id)
)
INHERITS (property_as_literal)
WITH (OIDS=TRUE);
ALTER TABLE has_buy_act OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/source*/
/********************************************************************************************/
CREATE TABLE source
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
  "value" integer NOT NULL,
  CONSTRAINT source_pkey PRIMARY KEY (subject, value),
  CONSTRAINT source_idkey UNIQUE (id)
)
INHERITS (property)
WITH (OIDS=TRUE);
ALTER TABLE source OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/source/has_local_source*/
/********************************************************************************************/
CREATE TABLE has_local_source
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_local_source_pkey PRIMARY KEY (subject),
  CONSTRAINT has_local_source_idkey UNIQUE (id)
)
INHERITS (source)
WITH (OIDS=TRUE);
ALTER TABLE has_local_source OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/source/is_local_source*/
/********************************************************************************************/
CREATE TABLE is_local_source
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_local_source_pkey PRIMARY KEY (subject),
  CONSTRAINT is_local_source_idkey UNIQUE (id)
)
INHERITS (source)
WITH (OIDS=TRUE);
ALTER TABLE is_local_source OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/source/has_external_source*/
/********************************************************************************************/

CREATE TABLE has_external_source
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT has_external_source_pkey PRIMARY KEY (subject),
  CONSTRAINT has_external_source_idkey UNIQUE (id)
)
INHERITS (source)
WITH (OIDS=TRUE);
ALTER TABLE has_external_source OWNER TO pangea;

/********************************************************************************************/
/*pangea/property/source/is_external_source*/
/********************************************************************************************/

CREATE TABLE is_external_source
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject_source character varying(50) NOT NULL,
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object_source character varying(50),
-- Inherited:   "object" text NOT NULL,
-- Inherited:   "value" integer NOT NULL,
  CONSTRAINT is_external_source_pkey PRIMARY KEY (subject),
  CONSTRAINT is_external_source_idkey UNIQUE (id)
)
INHERITS (source)
WITH (OIDS=TRUE);
ALTER TABLE is_external_source OWNER TO pangea;

/********************************************************************************************/
/*meta_pangea*/
/********************************************************************************************/
CREATE TABLE meta_pangea
(
  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  sp_label character varying(50) NOT NULL,
  en_label character varying(50) NULL,
  fr_label character varying(50) NULL,
  de_label character varying(50) NULL,
  concept_oid oid,
  parent integer,
  table_name character varying(50),
  source character varying(50),
  CONSTRAINT meta_pangea_pkey PRIMARY KEY (id),
  CONSTRAINT meta_pangea_parent_fkey FOREIGN KEY (parent)
      REFERENCES meta_pangea (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT meta_pangea_label_key UNIQUE (sp_label)
)
WITH (OIDS=TRUE);
ALTER TABLE meta_pangea OWNER TO pangea;

/********************************************************************************************/
/*meta_pangea/entity_type*/
/********************************************************************************************/
CREATE TABLE entity_type
(
-- Inherited:   id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:   sp_label character varying(50) NOT NULL,
-- Inherited:   en_label character varying(50) NULL,
-- Inherited:   fr_label character varying(50) NULL,
-- Inherited:   de_label character varying(50) NULL,
-- Inherited:   concept_oid oid,
-- Inherited:   parent integer,
-- Inherited:   table_name character varying(50),
-- Inherited:   source character varying(50),
  CONSTRAINT entity_type_pkey PRIMARY KEY (id)
)
INHERITS (meta_pangea)
WITH (OIDS=TRUE);
ALTER TABLE entity_type OWNER TO pangea;

/********************************************************************************************/
/*meta_pangea/property_type*/
/********************************************************************************************/

CREATE TABLE property_type
(
-- Inherited:   id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:   sp_label character varying(50) NOT NULL,
-- Inherited:   en_label character varying(50) NULL,
-- Inherited:   fr_label character varying(50) NULL,
-- Inherited:   de_label character varying(50) NULL,
-- Inherited:   concept_oid oid,
-- Inherited:   parent integer,
-- Inherited:   table_name character varying(50),
-- Inherited:   source character varying(50),
  is_visible boolean DEFAULT true,
  domain_table character varying(50),
  range_table character varying(50),
  inverse integer,
  object_type character varying(50),
  CONSTRAINT property_type_pkey PRIMARY KEY (id)
)
INHERITS (meta_pangea)
WITH (OIDS=TRUE);
ALTER TABLE property_type OWNER TO pangea;

/********************************************************************************************/      
/*entity_property*/
/********************************************************************************************/
CREATE TABLE entity_property
(
  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  entity_type integer NOT NULL,
  property_type integer NOT NULL,
  CONSTRAINT entity_property_pkey PRIMARY KEY (id, entity_type, property_type),
  CONSTRAINT entity_property_entity_type_fkey FOREIGN KEY (entity_type)
      REFERENCES entity_type (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT entity_property_property_type_fkey FOREIGN KEY (property_type)
      REFERENCES property_type (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (OIDS=FALSE);
ALTER TABLE entity_property OWNER TO pangea;

/********************************************************************************************/      
/*rules*/
/********************************************************************************************/
CREATE TABLE rules
( 
  id integer DEFAULT nextval('rules_object_id_seq'::regclass),
  table_name character varying(50) NOT NULL,
  property character varying(50) NOT NULL,
  value character varying(50) NOT NULL,
  CONSTRAINT rules_pkey PRIMARY KEY (id)
)
WITH (OIDS=FALSE);
ALTER TABLE rules OWNER TO pangea;

/********************************************************************************************/      
/*physical_characteristic*/
/********************************************************************************************/
CREATE TABLE physical_characteristic
(
  id integer DEFAULT nextval('system_object_id_seq'::regclass),
  subject integer NOT NULL,
  "object" integer NOT NULL,
  CONSTRAINT physical_characteristic_pkey PRIMARY KEY (subject, object),
  CONSTRAINT physical_characteristic_idkey UNIQUE (id)
)
WITH (OIDS=TRUE);
ALTER TABLE physical_characteristic OWNER TO pangea;


/********************************************************************************************/      
/*functions/sp_ascii*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION sp_ascii(character varying)
  RETURNS text AS
$BODY$
SELECT TRANSLATE
($1,
'·‡‚„‰ÈËÍÎÌÏÔÛÚÙıˆ˙˘˚¸¡¿¬√ƒ…» ÀÕÃœ”“‘’÷⁄Ÿ€‹Á«',
'aaaaaeeeeiiiooooouuuuAAAAAEEEEIIIOOOOOUUUUcC');
$BODY$
  LANGUAGE 'sql' VOLATILE
  COST 100;
ALTER FUNCTION sp_ascii(character varying) OWNER TO pangea;
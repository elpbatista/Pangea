
CREATE DATABASE "PANGEA_AUTH"
  WITH OWNER = pangea
       ENCODING = 'UTF8';

/*********************************************************************/   
    
CREATE SEQUENCE system_object_id_seq
  INCREMENT 1
  MINVALUE 1
  MAXVALUE 9223372036854775807
  START 1
  CACHE 1;
ALTER TABLE system_object_id_seq OWNER TO pangea;


/********************************************************************************************/
/*user*/
/********************************************************************************************/
CREATE TABLE "user"
(
  user_id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
  user_email text,
  username text NOT NULL,
  "password" text NOT NULL,
  real_name text,
  CONSTRAINT user_id PRIMARY KEY (user_id)
)
WITH (OIDS=FALSE);
ALTER TABLE "user" OWNER TO pangea;

/********************************************************************************************/
/*roles*/
/********************************************************************************************/
CREATE TABLE "roles"
(
  rol_id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
  rol_name text,
  description text,
  CONSTRAINT rol_id PRIMARY KEY (rol_id)
)
WITH (OIDS=FALSE);
ALTER TABLE "roles" OWNER TO pangea;

/********************************************************************************************/
/*user_roles*/
/********************************************************************************************/
CREATE TABLE "user_roles"
(
  user_rol_id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
  user_id integer NOT NULL,
  rol_id integer NOT NULL,
  CONSTRAINT user_rol_id PRIMARY KEY (user_rol_id),
  CONSTRAINT rol_id FOREIGN KEY (rol_id)
      REFERENCES "roles" (rol_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT user_id FOREIGN KEY (user_id)
      REFERENCES "user" (user_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (OIDS=FALSE);
ALTER TABLE "user_roles" OWNER TO pangea;

/************************************************************************************************/
/*permission*/
/************************************************************************************************/
CREATE TABLE permission
(
  permission_id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
  permission_name text NOT NULL,
  description text,
  CONSTRAINT permission_id PRIMARY KEY (permission_id)
)
WITH (
  OIDS=FALSE
);

ALTER TABLE permission OWNER TO pangea;

/************************************************************************************************/
/*permission_roles*/
/************************************************************************************************/

CREATE TABLE permission_roles
(
  rol_id integer NOT NULL,
  permission_rol_id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
  permission_id integer NOT NULL,
  CONSTRAINT permission_rol_id PRIMARY KEY (permission_rol_id),
  CONSTRAINT permission_id FOREIGN KEY (permission_id)
      REFERENCES permission (permission_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT rol_id FOREIGN KEY (rol_id)
      REFERENCES roles (rol_id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
ALTER TABLE permission_roles OWNER TO pangea;
/**************************************************************************************************************/

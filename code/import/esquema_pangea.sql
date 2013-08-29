/*Se crea una usuario que será el dueño de la Base de datos y de todos los objetos*/
CREATE ROLE pangea LOGIN
  ENCRYPTED PASSWORD 'md52bca19c9c104cce43026f78998ab449c'
  NOSUPERUSER INHERIT NOCREATEDB NOCREATEROLE;

CREATE DATABASE "PANGEA_DESA"
  WITH OWNER = pangea
       ENCODING = 'UTF8';
       
/***************************************************************/
 /*Lo de hendrix*/

/*CREATE TYPE RDF_OBJECT AS (rdf_type text, rdf_value text, rdf_lang text, dtype text);
CREATE TYPE triples as (_subject integer, _predicate text, _object RDF_OBJECT);
CREATE TYPE CRUD AS (property name, subject name, n3w RDF_OBJECT, o1d RDF_OBJECT  );
CREATE TYPE DTIONARY AS (property TEXT, object RDF_OBJECT);
CREATE TYPE tinsert AS (subject INTEGER, class TEXT, dict DTIONARY[]);*/

/*estos nuevos me los dio roman*/

CREATE TYPE RDF_OBJECT AS (rdf_type TEXT, rdf_class TEXT, rdf_value TEXT, rdf_lang TEXT, rdf_dtype TEXT);
CREATE TYPE triples as (triple_subject integer, triple_predicate text, triple_object RDF_OBJECT);
CREATE TYPE CRUD AS (crud_property name, crud_subject name, crud_new RDF_OBJECT, crud_old RDF_OBJECT  );
CREATE TYPE DTIONARY AS (dtionary_property TEXT, dtionary_object RDF_OBJECT);
CREATE TYPE tinsert AS (tinsert_subject INTEGER, tinsert_class TEXT, tinsert_dict DTIONARY[]);


CREATE OR REPLACE FUNCTION rdf_crud(records _CRUD) RETURNS SETOF RECORD AS $hen$

DECLARE
                mn INTEGER := array_lower(records, 1);
                mx INTEGER := array_upper(records,1);     
                unresolved _CRUD;
                cont INTEGER := 1;
                _id TEXT;
                _object TEXT;
                ret_new TEXT;
                ret_old TEXT;    
                m RECORD;

BEGIN

  /*crear una table temporal para guarder los nodos blancos que vengan de la presentacion para resolverlos posteriormente*/
DROP TABLE IF EXISTS TEMP_RESOLVED;

CREATE TEMP TABLE TEMP_RESOLVED(bnode TEXT, id INTEGER); 

FOR t IN mn..mx LOOP
/*tratamiento de exepciones precondicionales.*/

 BEGIN   

 /* la table no puede ser null. */

 IF records[t].property IS NULL THEN                                                      
 RAISE EXCEPTION 'Predicate cannot be null';

  END IF;

  --El subject no puede ser null. 
 IF records[t].subject IS NULL THEN 

  RAISE EXCEPTION 'Subject cannot be null';
  END IF;
--los objects no pueden ser null simultaneamente.

   IF records[t].n3w IS NULL AND records[t].o1d IS NULL THEN
  ret_new = NULL;

ret_old = NULL;
RAISE EXCEPTION 'Objects cannot be null';
  END IF;

   -- si no hay Nuevo record se procede a eliminar el  Viejo.
 IF records[t].n3w IS NULL THEN

 ret_new := NULL;
 ret_old := records[t].o1d.rdf_value;

   EXECUTE 'DELETE FROM '

      ||quote_ident(records[t].property)

      ||' WHERE subject = '

      ||records[t].subject

      ||' AND object = '

      ||quote_literal(ret_old)

      ||';';

  CONTINUE;
   END IF;                                

 ret_new := records[t].n3w.rdf_value;

   --si es un  nodo blanco se resuelve mas tarde.                                 

   IF records[t].n3w.rdf_type = 'bnode' THEN                                                       

        unresolved[cont] = records[t];

        cont := cont + 1;

   --si es un literal se procede.

   ELSIF records[t].n3w.rdf_type = 'literal' THEN

    --si el record Viejo es null se inserta

   IF records[t].o1d IS NULL THEN

    ret_old = NULL;

   EXECUTE  'INSERT INTO '

    ||quote_ident(records[t].property)

    ||' (subject, object, datatype, lang) VALUES('

    ||records[t].subject

    ||', '

    ||quote_literal(records[t].n3w.rdf_value)

    ||', '

    ||quote_literal(records[t].n3w.dtype)

    ||', '

    ||quote_literal(records[t].n3w.rdf_lang)

    ||') RETURNING id'        

    INTO _id;                                                                                                                          

    INSERT INTO TEMP_RESOLVED VALUES(records[t].subject, _id);                                                                           

      ELSE

       --si el Viejo no es null se actualiza

        ret_old := records[t].o1d.rdf_value;

        EXECUTE 'UPDATE '

           ||quote_ident(records[t].property)

           ||' SET object = '                                                                                            

           ||quote_literal(records[t].n3w.rdf_value)

           ||', datatype = '

           ||quote_literal(records[t].n3w.dtype)

           ||', lang = '

           ||quote_literal(records[t].n3w.rdf_lang)                                                                                         

           ||' WHERE subject = '

           ||records[t].subject

           ||' AND object = '

           ||quote_literal(ret_old)

           ||';';

           END IF;

            ELSE

               --se procede con la uri de forma similar a con el literal

               IF records[t].o1d IS NULL THEN                                                               
                 ret_old = NULL;

                 EXECUTE 'INSERT INTO '

                   ||quote_ident(records[t].property)

                   ||' (subject, object) VALUES('

                   ||records[t].subject

                   ||', '

                   ||quote_literal(records[t].n3w.rdf_value)

                   ||');';

                     ELSE

                        ret_old := records[t].o1d.rdf_value;

                        EXECUTE 'UPDATE '

                          ||quote_ident(records[t].property)

                          ||' SET object = '                                                                                            

                          ||quote_literal(records[t].n3w.rdf_value)

                          ||' WHERE subject = '

                          ||records[t].subject

                          ||' AND object = '

                          ||quote_literal(ret_old)

                          ||';';

                          END IF;

                      END IF;

          EXCEPTION WHEN OTHERS THEN

              m := (records[t].subject, records[t].property, ret_new, ret_old, SQLSTATE, SQLERRM);

             RETURN NEXT m;

             END;

                END LOOP;         

                --si no hay mas nada que resolver se termina

                IF unresolved IS NULL THEN

                  RETURN;

                END IF;

                --resolviendo los nodos blancos

                mn := array_lower(unresolved, 1);

                mx := array_upper(unresolved,1);         

                FOR t IN mn..mx LOOP

                               EXECUTE 'SELECT id FROM TEMP_RESOLVED WHERE bnode = $1;'

                               INTO _id

                               USING unresolved[t].n3w.rdf_value;

                                                                              

                               unresolved[t] := (unresolved[t].property,unresolved[t].subject, ('uri', _id, NULL, NULL)::RDF_OBJECT, unresolved[t].o1d)::CRUD;

                END LOOP;

                RETURN QUERY SELECT * FROM rdf_crud(unresolved) AS (subject NAME, property NAME, new_object TEXT, old_object TEXT, err_code TEXT, err_msg TEXT);

END;

$hen$ LANGUAGE plpgsql;


/**********************************************************************************************/
/*functions/is_bnode*/   /*esta es la de hendrix*/
/*********************************************************************************************/
/*CREATE OR REPLACE FUNCTION is_bnode(text)
  RETURNS boolean AS
$BODY$
 BEGIN
     RETURN false;
 END
 $BODY$
  LANGUAGE 'plpgsql' VOLATILE SECURITY DEFINER
  COST 100;
ALTER FUNCTION is_bnode(text) OWNER TO pangea;*/

/*esta es la de roman*/
/*CREATE OR REPLACE FUNCTION is_bnode(entity integer)
   RETURNS boolean AS
$BODY$DECLARE

isbnode BOOLEAN;
  BEGIN
SELECT INTO isbnode COUNT(*) = 0 FROM pangea WHERE(ID = entity);

RETURN isbnode;
  END
  $BODY$
   LANGUAGE 'plpgsql' VOLATILE SECURITY DEFINER
   COST 100;
ALTER FUNCTION is_bnode(integer) OWNER TO pangea;*/

/*esta es la última que me dió Román*/
CREATE OR REPLACE FUNCTION is_bnode(entity text)
   RETURNS boolean AS
$BODY$DECLARE

isbnode BOOLEAN;
  BEGIN
BEGIN
SELECT COUNT(*) = 0 FROM pangea WHERE(ID = entity::integer)
INTO isbnode;

EXCEPTION WHEN OTHERS THEN
isbnode := 't'::BOOLEAN;

END;

RETURN isbnode;
  END
  $BODY$
   LANGUAGE 'plpgsql' VOLATILE SECURITY DEFINER
   COST 100;
ALTER FUNCTION is_bnode(text) OWNER TO pangea;

/********************************************************************************************/      
/*functions/rdf_insert*/
/********************************************************************************************/
/*esta es la de hendrix*/
/*CREATE OR REPLACE FUNCTION rdf_insert(records tinsert[])
  RETURNS SETOF record AS
$BODY$DECLARE

	mn INTEGER := array_lower(records, 1);
	mx INTEGER := array_upper(records,1);

	mintn INTEGER;
	mintx INTEGER;
	
	unresolved _TINSERT;
	
	cont INTEGER := 1;		
	
	_subject TEXT;
	_object TEXT;	
	ret_new TEXT;
	ret_old TEXT;
	qry TEXT;

	_rdfobject RDF_OBJECT;
	

	d DTIONARY;
		
	m RECORD;

BEGIN
	--crear una table temporal para guarder los nodos blancos que vengan de la presentacion para resolverlos posteriormente
	DROP TABLE IF EXISTS TEMP_RESOLVED;
	CREATE TEMP TABLE TEMP_RESOLVED(bnode TEXT, id TEXT);
	FOR t IN mn..mx LOOP
		--tratamiento de exepciones precondicionales.
		BEGIN
			IF records[t].subject IS NULL THEN

				RAISE EXCEPTION 'Subject cannot be null';
			END IF;
			
			_subject := records[t].subject;
			
			IF is_bnode(_subject) THEN 
			
				IF records[t].cla55 IS NULL THEN

					RAISE EXCEPTION 'Not Datatype Supplied';
				END IF;

				EXECUTE	'INSERT INTO '
					||quote_ident(records[t].cla55)
					||' (id) VALUES ('
					||nextval('system_object_id_seq')
					||') RETURNING id;'	
					INTO _subject;

				INSERT INTO TEMP_RESOLVED VALUES(records[t].subject, _subject);
			END IF;
			

			EXCEPTION WHEN OTHERS THEN
				m := (_subject, ''::TEXT, ret_new, SQLSTATE, SQLERRM);				
				RETURN NEXT m;
				CONTINUE;
		END;

		mintn := array_lower(records[t].dict, 1);
		mintx := array_upper(records[t].dict,1);		

		FOR i IN mintn..mintx LOOP

			d = records[t].dict[i];
			_rdfobject := d.obj3ct;
			--tratamiento de exepciones precondicionales.
			BEGIN

				-- la table no puede ser null. 
				IF d.property IS NULL THEN 
				 	
					 RAISE EXCEPTION 'Predicate cannot be null';
				END IF;

				--el object no pueden ser null.
				IF _rdfobject IS NULL THEN
					ret_new = NULL;					
					RAISE EXCEPTION 'Object cannot be null';
				END IF;

				ret_new := _rdfobject.rdf_value;
				--si es un  nodo blanco se resuelve mas tarde.			
				IF _rdfobject.rdf_type = 'bnode' THEN				
					unresolved[cont] := (_subject, d.property, _rdfobject.rdf_value);
					cont := cont + 1;

				--si es un literal se procede.
				ELSIF _rdfobject.rdf_type = 'literal' THEN

					IF (_rdfobject.dtype IS NULL) THEN
					
						qry := 'INSERT INTO '
						||quote_ident(d.property)
						||' (subject, object, lang) VALUES('
						||quote_literal(_subject)
						||', '
						||quote_literal(_rdfobject.rdf_value)
						||', '						
						||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
						||');';
					ELSE
						qry := 'INSERT INTO '
						||quote_ident(d.property)
						||' (subject, object, datatype, lang) VALUES('
						||quote_literal(_subject)
						||', '
						||quote_literal(_rdfobject.rdf_value)
						||', '
						||quote_literal(_rdfobject.dtype)
						||', '
						||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
						||');';
					
					END IF;					
					
				ELSE 

					qry := 'INSERT INTO '
						||quote_ident(d.property)
						||' (subject, object) VALUES('
						||quote_literal(_subject)
						||', '
						||quote_literal(_rdfobject.rdf_value)
						||');';
				END IF;

				EXECUTE	qry ;

				EXCEPTION WHEN OTHERS THEN
					m := (_subject, d.property, ret_new, SQLSTATE, SQLERRM);

					RETURN NEXT m;
			END;
		END LOOP;
		
	END LOOP;
	m := (_subject::TEXT, ''::TEXT, ''::TEXT, ''::TEXT, ''::TEXT);	
	RETURN NEXT m;
	IF unresolved IS NULL THEN
		RETURN;
	END IF;

	--resolviendo los nodos blancos
	mn := array_lower(unresolved, 1);
	mx := array_upper(unresolved,1);

	FOR t IN mn..mx LOOP
		
		EXECUTE 'SELECT id FROM TEMP_RESOLVED WHERE bnode = $1;'
		INTO _subject 
		USING unresolved[t].f3;

		EXECUTE 'INSERT INTO '
			||quote_ident(unresolved[t].f2)
			||' (subject, object) VALUES('
			||quote_literal(unresolved[t].f1)
			||', '
			||quote_literal(_subject)
			||');';	
	END LOOP;
	
	
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION rdf_insert(tinsert[]) OWNER TO pangea;*/


/*esta es la de roman*/

/*CREATE OR REPLACE FUNCTION rdf_insert(records tinsert[])
  RETURNS SETOF record AS
$BODY$DECLARE

 mn INTEGER := array_lower(records, 1);
 mx INTEGER := array_upper(records,1);

 mintn INTEGER;
 mintx INTEGER;
 
 unresolved _TINSERT;
 
 cont INTEGER := 1;  
 
 _subject INTEGER;
 _object TEXT; 
 ret_new TEXT;
 ret_old TEXT;
 qry TEXT;

 _rdfobject RDF_OBJECT;
 

 d DTIONARY;
  
 m RECORD;

BEGIN
 --crear una table temporal para guarder los nodos blancos que vengan de la presentacion para resolverlos posteriormente
 DROP TABLE IF EXISTS TEMP_RESOLVED;
 CREATE TEMP TABLE TEMP_RESOLVED(bnode TEXT, id TEXT);
 FOR t IN mn..mx LOOP
  --tratamiento de exepciones precondicionales.
  BEGIN
   IF records[t].subject IS NULL THEN
    RAISE EXCEPTION 'Subject cannot be null';
   END IF;
   
   _subject := records[t].subject;
   
   IF is_bnode(_subject) THEN 
   
    IF records[t].class IS NULL THEN

     RAISE EXCEPTION 'Not Datatype Supplied';
    END IF;

    EXECUTE 'INSERT INTO '
     ||quote_ident(records[t].class)
     ||' (id) VALUES ('
     ||nextval('system_object_id_seq')
     ||') RETURNING id;' 
     INTO _subject;

    INSERT INTO TEMP_RESOLVED VALUES(records[t].subject, _subject);
   END IF;
   

   EXCEPTION WHEN OTHERS THEN
    m := (_subject, ''::INTEGER, ret_new, SQLSTATE, SQLERRM);    
    RETURN NEXT m;
    CONTINUE;
  END;

  mintn := array_lower(records[t].dict, 1);
  mintx := array_upper(records[t].dict,1);  

  FOR i IN mintn..mintx LOOP

   d = records[t].dict[i];
   _rdfobject := d.object;
   --tratamiento de exepciones precondicionales.
   BEGIN

    -- la table no puede ser null. 
    IF d.property IS NULL THEN 
      
      RAISE EXCEPTION 'Predicate cannot be null';
    END IF;

    --el object no pueden ser null.
    IF _rdfobject IS NULL THEN
     ret_new = NULL;     
     RAISE EXCEPTION 'Object cannot be null';
    END IF;

    ret_new := _rdfobject.rdf_value;
    --si es un  nodo blanco se resuelve mas tarde.   
    IF _rdfobject.rdf_type = 'bnode' THEN    
     unresolved[cont] := (_subject, d.property, _rdfobject.rdf_value);
     cont := cont + 1;

    --si es un literal se procede.
    ELSIF _rdfobject.rdf_type = 'literal' THEN

     IF (_rdfobject.dtype IS NULL) THEN
     
      qry := 'INSERT INTO '
      ||quote_ident(d.property)
      ||' (subject, object, lang) VALUES('
      ||_subject
      ||', '
      ||quote_literal(_rdfobject.rdf_value)
      ||', '      
      ||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
      ||');';
     ELSE
      qry := 'INSERT INTO '
      ||quote_ident(d.property)
      ||' (subject, object, datatype, lang) VALUES('
      ||_subject
      ||', '
      ||quote_literal(_rdfobject.rdf_value)
      ||', '
      ||quote_literal(_rdfobject.dtype)
      ||', '
      ||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
      ||');';
     
     END IF;     
     
    ELSE 

     qry := 'INSERT INTO '
      ||quote_ident(d.property)
      ||' (subject, object) VALUES('
      ||_subject
      ||', '
      ||_rdfobject.rdf_value
      ||');';
    END IF;

    EXECUTE qry ;

    EXCEPTION WHEN OTHERS THEN
     m := (_subject, d.property, ret_new, SQLSTATE, SQLERRM);

     RETURN NEXT m;
   END;
  END LOOP;
  
 END LOOP;
 
 --m := (_subject::TEXT, ''::TEXT, ''::TEXT, ''::TEXT, ''::TEXT); 
 --RETURN NEXT m;
 IF unresolved IS NULL THEN
  RETURN;
 END IF;

 --resolviendo los nodos blancos
 mn := array_lower(unresolved, 1);
 mx := array_upper(unresolved,1);

 FOR t IN mn..mx LOOP
  
  EXECUTE 'SELECT id FROM TEMP_RESOLVED WHERE bnode = $1;'
  INTO _subject 
  USING unresolved[t].f3;

  EXECUTE 'INSERT INTO '
   ||quote_ident(unresolved[t].f2)
   ||' (subject, object) VALUES('
   ||unresolved[t].f1
   ||', '
   ||quote_literal(_subject)
   ||');'; 
 END LOOP;
 
 
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION rdf_insert(tinsert[]) OWNER TO pangea;*/


/*esta es la ultima de roman*/

CREATE OR REPLACE FUNCTION rdf_insert(records tinsert[])
   RETURNS SETOF record AS
$BODY$DECLARE

mn INTEGER := array_lower(records, 1);
mx INTEGER := array_upper(records,1);

mintn INTEGER;
mintx INTEGER;

unresolved _TINSERT;

cont INTEGER := 1;

_subject INTEGER;
_object TEXT;
ret_new TEXT;
ret_old TEXT;
qry TEXT;

_rdfobject RDF_OBJECT;

d DTIONARY;

m RECORD;

_target_subject TEXT;

BEGIN
--crear una table temporal para guarder los nodos blancos que vengan de la presentacion para resolverlos posteriormente
DROP TABLE IF EXISTS TEMP_RESOLVED;

CREATE TEMP TABLE TEMP_RESOLVED(bnode TEXT, id INTEGER);

FOR t IN mn..mx LOOP
--tratamiento de exepciones precondicionales.
BEGIN
IF records[t].subject IS NULL THEN
RAISE EXCEPTION 'Subject cannot be null';
END IF;

_subject := records[t].subject;

IF is_bnode(_subject) THEN

IF records[t].cla55 IS NULL THEN

RAISE EXCEPTION 'Not Datatype Supplied';
END IF;

EXECUTE 'INSERT INTO '
||quote_ident(records[t].cla55)
||' (id) VALUES ('
||nextval('system_object_id_seq')
||') RETURNING id;'
INTO _subject;

INSERT INTO TEMP_RESOLVED VALUES(records[t].subject, _subject);
END IF;


EXCEPTION WHEN OTHERS THEN
m := (_subject::TEXT, ''::TEXT, ret_new, SQLSTATE, SQLERRM);
RETURN NEXT m;
CONTINUE;
END;

mintn := array_lower(records[t].dict, 1);
mintx := array_upper(records[t].dict,1);

FOR i IN mintn..mintx LOOP

d = records[t].dict[i];
_rdfobject := d.obj3ct;
--tratamiento de exepciones precondicionales.
BEGIN

-- la table no puede ser null.
IF d.property IS NULL THEN

RAISE EXCEPTION 'Predicate cannot be null';
END IF;

--el object no pueden ser null.
IF _rdfobject IS NULL THEN
ret_new = NULL;
RAISE EXCEPTION 'Object cannot be null';
END IF;

qry := NULL;

ret_new := _rdfobject.rdf_value;
--si es un  nodo blanco se resuelve mas tarde.
IF _rdfobject.rdf_type = 'bnode' THEN
unresolved[cont] := (_subject, d.property, _rdfobject.rdf_value);
cont := cont + 1;

--si es un literal se procede.
ELSIF _rdfobject.rdf_type = 'literal' THEN
BEGIN
EXECUTE 'SELECT subject FROM '
||quote_ident(d.property)
||' WHERE(subject = '
||_subject
||' AND object = '
||quote_literal(_rdfobject.rdf_value)
||');' INTO _target_subject;

IF (_target_subject IS NULL) THEN
IF (_rdfobject.dtype IS NULL) THEN
qry := 'INSERT INTO '
||quote_ident(d.property)
||' (subject, object, lang) VALUES('
||_subject
||', '
||quote_literal(_rdfobject.rdf_value)
||', '
||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
||');';
ELSE
qry := 'INSERT INTO '
||quote_ident(d.property)
||' (subject, object, datatype, lang) VALUES('
||_subject
||', '
||quote_literal(_rdfobject.rdf_value)
||', '
||quote_literal(_rdfobject.dtype)
||', '
||coalesce(quote_literal(_rdfobject.rdf_lang),'NULL')
||');';
END IF;
END IF;
END;
ELSE
BEGIN
EXECUTE 'SELECT subject FROM '
||quote_ident(d.property)
||' WHERE(subject = '
||_subject
||' AND object = '
||_rdfobject.rdf_value
||');' INTO _target_subject;

IF (_target_subject IS NULL) THEN
qry := 'INSERT INTO '
||quote_ident(d.property)
||' (subject, object) VALUES('
||_subject
||', '
||_rdfobject.rdf_value
||');';
END IF;
END;
END IF;

IF (qry IS NOT NULL) THEN
EXECUTE qry ;
END IF;

EXCEPTION WHEN OTHERS THEN
m := (_subject::TEXT, d.property, ret_new, SQLSTATE, SQLERRM);

RETURN NEXT m;
END;
END LOOP;

END LOOP;

--m := (_subject::TEXT, ''::TEXT, ''::TEXT, ''::TEXT, ''::TEXT);
--RETURN NEXT m;
IF unresolved IS NULL THEN
RETURN;
END IF;

--resolviendo los nodos blancos
mn := array_lower(unresolved, 1);
mx := array_upper(unresolved,1);

FOR t IN mn..mx LOOP

EXECUTE 'SELECT id FROM TEMP_RESOLVED WHERE bnode = $1;'
INTO _subject
USING unresolved[t].f3;

EXECUTE 'INSERT INTO '
||quote_ident(unresolved[t].f2)
||' (subject, object) VALUES('
||unresolved[t].f1
||', '
||quote_literal(_subject)
||');';
END LOOP;


END;
$BODY$
   LANGUAGE 'plpgsql' VOLATILE
   COST 100
   ROWS 1000;
ALTER FUNCTION rdf_insert(tinsert[]) OWNER TO pangea;







CREATE OR REPLACE FUNCTION rdf_insert(records tinsert[])
   RETURNS SETOF record AS
$BODY$DECLARE

mn INTEGER := array_lower(records, 1);
mx INTEGER := array_upper(records,1);

mintn INTEGER;
mintx INTEGER;
_subject INTEGER;
_object TEXT;

qry TEXT;
qry_columns TEXT;
qry_values TEXT;

_rdfobject RDF_OBJECT;

d DTIONARY;

m RECORD;

_target_subject INTEGER;

BEGIN
FOR t IN mn..mx LOOP
BEGIN
IF (records[t].tinsert_subject IS NULL) THEN
RAISE EXCEPTION 'Subject cannot be null';
END IF;

IF is_bnode(records[t].tinsert_subject) THEN
EXECUTE 'SELECT ID FROM "bNodes" WHERE("idBnode" = $1);'
INTO _subject
USING records[t].tinsert_subject;

IF (_subject IS NULL) THEN
BEGIN
IF records[t].tinsert_class IS NULL THEN
RAISE EXCEPTION 'Not Datatype Supplied for Subject';
END IF;

EXECUTE 'INSERT INTO ' || quote_ident(records[t].tinsert_class) 
|| ' (id) VALUES (' || nextval('system_object_id_seq') || ') RETURNING 
id;'
INTO _subject;

INSERT INTO "bNodes"("idBnode", ID) 
VALUES(records[t].tinsert_subject, _subject);
END;
END IF;
ELSE
_subject := records[t].tinsert_subject::INTEGER;
END IF;

EXCEPTION WHEN OTHERS THEN
m := (_subject::TEXT, ''::TEXT, records[t].tinsert_subject, 
SQLSTATE, SQLERRM);

RETURN NEXT m;

CONTINUE;
END;

mintn := array_lower(records[t].tinsert_dict, 1);
mintx := array_upper(records[t].tinsert_dict,1);

FOR i IN mintn..mintx LOOP

d = records[t].tinsert_dict[i];
_rdfobject := d.dtionary_object;
--tratamiento de exepciones precondicionales.
BEGIN

-- la table no puede ser null.
IF d.dtionary_property IS NULL THEN
RAISE EXCEPTION 'Predicate cannot be null';
END IF;

--el object no pueden ser null.
IF _rdfobject IS NULL THEN
_object = NULL;

RAISE EXCEPTION 'Object cannot be null';
END IF;

qry := NULL;

_object = _rdfobject.rdf_value;

--literales
IF _rdfobject.rdf_type = 'literal' THEN
BEGIN
--EXECUTE 'SELECT subject FROM ' || quote_ident(d._property) || ' WHERE(subject = ' || _subject || ' AND object = ' || quote_literal(_object) || ');'
EXECUTE 'SELECT subject FROM ' || 
quote_ident(d.dtionary_property) || ' WHERE(subject = $1 AND object = 
$2);'
INTO _target_subject
USING _subject, _object;

IF (_target_subject IS NULL) THEN
BEGIN
qry_columns := 'subject, object';
qry_values := _subject || ', ' || quote_literal(_object);

IF (_rdfobject.rdf_dtype IS NOT NULL) THEN
BEGIN
qry_columns := qry_columns ||', datatype';
qry_values := qry_values || ', ' || 
quote_literal(_rdfobject.rdf_dtype);
END;
END IF;

IF (_rdfobject.rdf_lang IS NOT NULL) THEN
BEGIN
qry_columns := qry_columns ||', lang';
qry_values := qry_values || ', ' || 
quote_literal(_rdfobject.rdf_lang);
END;
END IF;

qry := 'INSERT INTO ' || quote_ident(d.dtionary_property) ||'(' 
|| qry_columns || ')' || ' VALUES(' || qry_values || ')';
END;
END IF;
END;
ELSE
BEGIN
--nodo blanco
IF is_bnode(_rdfobject.rdf_value) THEN
IF _rdfobject.rdf_class IS NULL THEN
RAISE EXCEPTION 'Not Datatype Supplied for Object';
END IF;

EXECUTE 'SELECT ID FROM "bNodes" WHERE("idBnode" = $1);'
INTO _object
USING _rdfobject.rdf_value;

IF (_object IS NULL) THEN
BEGIN
EXECUTE 'INSERT INTO ' || quote_ident(_rdfobject.rdf_class) || 
' (id) VALUES (' || nextval('system_object_id_seq') || ') RETURNING id;'
INTO _object;

INSERT INTO "bNodes"("idBnode", ID) 
VALUES(_rdfobject.rdf_value, _object);
END;
END IF;
END IF;

--qry := 'SELECT subject FROM ' || quote_ident(d.dtionary_property) || ' WHERE(subject = ' ||_subject ||' AND object = ' || _object || ');';

EXECUTE 'SELECT subject FROM ' || 
quote_ident(d.dtionary_property) || ' WHERE(subject = $1 AND object = 
$2);'
INTO _target_subject
USING _subject::INTEGER, _object::INTEGER;

IF (_target_subject IS NULL) THEN
qry := 'INSERT INTO ' || quote_ident(d.dtionary_property) || ' 
(subject, object) VALUES(' || _subject || ', ' || quote_literal(_object) 
|| ');';
END IF;
END;
END IF;

IF (qry IS NOT NULL) THEN
EXECUTE qry ;
END IF;

EXCEPTION WHEN OTHERS THEN
m := (_subject::TEXT, d.dtionary_property, _object, SQLSTATE, 
SQLERRM);

RETURN NEXT m;
END;
END LOOP;

END LOOP;
END;
$BODY$
   LANGUAGE 'plpgsql' VOLATILE
   COST 100
   ROWS 1000;
ALTER FUNCTION rdf_insert(tinsert[]) OWNER TO pangea;




/********************************************************************************************/      
/*functions/rdf_update*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION rdf_update(records triples[])
  RETURNS SETOF record AS
$BODY$
DECLARE

	mn INTEGER := array_lower(records, 1);
	mx INTEGER := array_upper(records, 1);

	qry TEXT;
		
	m RECORD;

BEGIN
	FOR t IN mn..mx LOOP

		BEGIN
			IF records[t]._object.rdf_type = 'literal' THEN		
				

				qry := 'UPDATE '
					||quote_ident(records[t]._predicate)
					||' SET object = '						
					||quote_literal(records[t]._object.rdf_value)					
					||', lang = '
					||coalesce(quote_literal(records[t]._object.rdf_lang),'NULL')
					||' WHERE subject = '
					||records[t]._subject
					||';';
			ELSE

				qry := 'UPDATE '
					||quote_ident(records[t]._predicate)
					||' SET object = '						
					||records[t]._object.rdf_value
					||' WHERE subject = '
					||records[t]._subject				
					||';';
			END IF;

			EXECUTE qry;

			EXCEPTION WHEN OTHERS THEN
				m := (records[t]._subject, records[t]._predicate, records[t]._object.rdf_value, SQLSTATE, SQLERRM);
				RETURN NEXT m;
		
		END;

	END LOOP;

END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION rdf_update(triples[]) OWNER TO pangea;


/********************************************************************************************/      
/*functions/rdf_delete*/  /*esta es la función original de hendrix*/
/********************************************************************************************/
/*
CREATE OR REPLACE FUNCTION rdf_delete(records triples[])
  RETURNS SETOF record AS
$BODY$DECLARE
	mn INTEGER := array_lower(records, 1);
	mx INTEGER := array_upper(records, 1);

	m RECORD;
	
	inverse_table name;
BEGIN
	FOR t IN mn..mx LOOP

		BEGIN
			inverse_table := getinverse(records[t]._predicate);
			
			IF records[t]._object IS NOT NULL THEN
				BEGIN
					EXECUTE 'DELETE FROM '
							||quote_ident(records[t]._predicate)
							||' WHERE subject = '
							||quote_literal(records[t]._subject)
							||' AND object = '
							||quote_literal(records[t]._object.rdf_value)
							||';';
				
					IF inverse_table IS NOT NULL THEN		
						EXECUTE 'DELETE FROM '
							||inverse_table
							||' WHERE subject = '
							||quote_literal(records[t]._object.rdf_value)
							||' AND object = '
							||quote_literal(records[t]._subject)
							||';';	
					END IF;				
				END;
			
			ELSE
				BEGIN
					EXECUTE 'DELETE FROM '
							||quote_ident(records[t]._predicate)
							||' WHERE subject = '
							||quote_literal(records[t]._subject)
							||';';				
							
					IF inverse_table IS NOT NULL THEN		
						EXECUTE 'DELETE FROM '
							||inverse_table
							||' WHERE object = '
							||quote_literal(records[t]._subject)
							||';';	
					END IF;									
				END;				
			END IF;
			
								
			EXCEPTION WHEN OTHERS THEN
				m := (records[t]._subject, records[t]._predicate, records[t]._object.rdf_value, SQLSTATE, SQLERRM);
				RETURN NEXT m;
		
		END;
	END LOOP;
END;$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION rdf_delete(triples[]) OWNER TO pangea;*/

/*******************************************************************************************************/
/*esta fue la que yo cambié*/
/*******************************************************************************************************/
/*CREATE OR REPLACE FUNCTION rdf_delete2(records triples[])
  RETURNS SETOF record AS
$BODY$DECLARE
	mn INTEGER := array_lower(records, 1);
	mx INTEGER := array_upper(records, 1);

	m RECORD;
	
	inverse_table name;
BEGIN
	FOR t IN mn..mx LOOP

		BEGIN
			inverse_table := getinverse(records[t]._predicate);
			
			IF records[t]._object IS NOT NULL THEN
             BEGIN
			  IF records[t]._object.rdf_type = 'literal' THEN	
			 
					EXECUTE 'DELETE FROM '
							||quote_ident(records[t]._predicate)
							||' WHERE subject = '
							||records[t]._subject
							||' AND object = '
							||quote_literal(records[t]._object.rdf_value)
							||';';
			  ELSE
				BEGIN	
				   EXECUTE 'DELETE FROM '
							||quote_ident(records[t]._predicate)
							||' WHERE subject = '
							||records[t]._subject
							||' AND object = '
							||records[t]._object.rdf_value
							||';';
							
					IF inverse_table IS NOT NULL THEN		
						EXECUTE 'DELETE FROM '
							||inverse_table
							||' WHERE subject = '
							||records[t]._object.rdf_value
							||' AND object = '
							||records[t]._subject
							||';';	
					END IF;	
			     END;	
		     END IF;	
			END;
			ELSE
				BEGIN
					EXECUTE 'DELETE FROM '
							||quote_ident(records[t]._predicate)
							||' WHERE subject = '
							||records[t]._subject
							||';';				
							
					IF inverse_table IS NOT NULL THEN		
						EXECUTE 'DELETE FROM '
							||inverse_table
							||' WHERE object = '
							||quote_literal(records[t]._subject)
							||';';	
					END IF;									
				END;				
			END IF;
			
								
			EXCEPTION WHEN OTHERS THEN
				m := (records[t]._subject, records[t]._predicate, records[t]._object.rdf_value, SQLSTATE, SQLERRM);
				RETURN NEXT m;
		
		END;
	END LOOP;
END;$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION rdf_delete(triples[]) OWNER TO pangea;*/
/*********************************************************************/ 
/*esta es la de roman*/
/*********************************************************************/ 

CREATE OR REPLACE FUNCTION rdf_delete(records triples[])
   RETURNS SETOF record AS
$BODY$DECLARE
mn INTEGER := array_lower(records, 1);
mx INTEGER := array_upper(records, 1);

m RECORD;

inverse_table name;

qry TEXT;
inverse_qry TEXT;

null_triple triples[] := ARRAY[(NULL, NULL, (NULL, NULL, NULL, NULL, 
NULL)::RDF_OBJECT)::TRIPLES];
BEGIN
FOR t IN mn..mx LOOP

BEGIN
inverse_table := getinverse(records[t].triple_predicate);

qry := NULL;

inverse_qry := NULL;
/*
Aqui no se porque la condicion anterior: (records[t]._object IS NOT 
NULL) no devolvia el resultado esperado.
Tuve que buscar una solucion alternativa aunque un poco mas 
compleja.
*/
IF (COALESCE(records[t].triple_object, null_triple[0].triple_object) 
= records[t].triple_object) THEN
BEGIN
qry := 'DELETE FROM ' || quote_ident(records[t].triple_predicate) 
|| ' WHERE subject = ' || records[t].triple_subject || ' AND object = ' 
|| quote_literal(records[t].triple_object.rdf_value) || ';';

IF inverse_table IS NOT NULL THEN
inverse_qry := 'DELETE FROM ' || quote_ident(inverse_table) || ' 
WHERE subject = ' || records[t].triple_object.rdf_value || ' AND object 
= ' || quote_literal(records[t].triple_subject) || ';';
END IF;
END;

ELSE
BEGIN
qry := 'DELETE FROM ' || quote_ident(records[t].triple_predicate) 
|| ' WHERE subject = ' || records[t].triple_subject || ';';

IF inverse_table IS NOT NULL THEN
inverse_qry := 'DELETE FROM ' || quote_ident(inverse_table) || ' 
WHERE object = ' || quote_literal(records[t].triple_subject) || ';';
END IF;
END;
END IF;

IF (qry IS NOT NULL) THEN
BEGIN
EXECUTE qry;

IF (inverse_qry IS NOT NULL) THEN
EXECUTE inverse_qry;
END IF;
END;
END IF;

EXCEPTION WHEN OTHERS THEN
m := (records[t].triple_subject, records[t].triple_predicate, 
records[t].triple_object.rdf_value, SQLSTATE, SQLERRM);

RETURN NEXT m;

END;
END LOOP;
END;$BODY$
   LANGUAGE 'plpgsql' VOLATILE
   COST 100
   ROWS 1000;
ALTER FUNCTION rdf_delete(triples[]) OWNER TO pangea;

/*********************************************************************/   
/*Triggers/ set_text_object*/  /* este trigger se utiliza para rellenar la columna text_object en la tabla que heredan de bareNecessities*/
/*******************************************************************/
CREATE OR REPLACE FUNCTION set_text_object()
  RETURNS trigger AS
$BODY$

DECLARE
 casted_text text;
BEGIN
	casted_text  := CAST(NEW.object AS text);

	NEW.text_object := casted_text;

	RETURN NEW;
	
END;$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION set_text_object() OWNER TO pangea;

/*********************************************************************/
/*Triggers/set_numeric_fields */  /*esta es de román*/
/*********************************************************************/
CREATE OR REPLACE FUNCTION set_numeric_fields()
  RETURNS trigger AS
$BODY$

DECLARE
 casted_numeric INTEGER;
BEGIN
	IF (TG_OP = 'INSERT') THEN
		BEGIN
			casted_numeric := CAST(NEW.subject AS integer);

			NEW.numeric_subject := casted_numeric;

			casted_numeric := CAST(NEW.object AS integer);

			NEW.numeric_object := casted_numeric;

			EXCEPTION
			    WHEN invalid_text_representation then
				RETURN NEW;
		END;		
	END IF;

	RETURN NEW;
	
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION set_numeric_fields() OWNER TO pangea;


/*********************************************************************/
/*Triggers/set_numeric_id */  /*esta es de román*/
/*********************************************************************/
CREATE OR REPLACE FUNCTION set_numeric_id()
  RETURNS trigger AS
$BODY$

BEGIN
	IF (TG_OP = 'INSERT') THEN
		NEW.numeric_id := CAST(NEW.id AS INTEGER);		
	END IF;

	RETURN NEW;	
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;
ALTER FUNCTION set_numeric_id() OWNER TO pangea;


/******************************************************************/
       
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


/*******************************************************/
/*INICIO DE LAS TABLAS DEL META*/
/*******************************************************/

/********************************************************************************************/
/*meta:schema*/
/********************************************************************************************/
CREATE TABLE "meta:schema"
(
  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  prefix character varying(100) NOT NULL,
  namespace character varying(100) NOT NULL,
  CONSTRAINT "meta:schema_pkey" PRIMARY KEY (id)
)
WITH (OIDS=TRUE);
ALTER TABLE "meta:schema" OWNER TO pangea;

insert into "meta:schema" (prefix, namespace) values ('rdf','http://www.w3.org/1999/02/22-rdf-syntax-ns#');
insert into "meta:schema" (prefix, namespace) values ('rdfs','http://www.w3.org/2000/01/rdf-schema#');
insert into "meta:schema" (prefix, namespace) values ('owl','http://www.w3.org/2002/07/owl#');
insert into "meta:schema" (prefix, namespace) values ('xml','http://www.w3.org/XML/1998/namespace');
insert into "meta:schema" (prefix, namespace) values ('pangea','http://www.ohc.cu/2011/06/pangea#');
insert into "meta:schema" (prefix, namespace) values ('frbr','http://purl.org/vocab/frbr/core#');
insert into "meta:schema" (prefix, namespace) values ('skosxl','http://www.w3.org/2008/05/skos-xl#');
insert into "meta:schema" (prefix, namespace) values ('xsd','http://www.w3.org/2001/XMLSchema');



/********************************************************************************************/
/*rdf:resource*/
/********************************************************************************************/
CREATE TABLE "rdf:resource"
(
  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  uri character varying(100) NOT NULL,
  label character varying(100) NOT NULL,
  CONSTRAINT "rdf:resource_pkey" PRIMARY KEY (uri),
  CONSTRAINT "rdf:resource_idkey" UNIQUE (id)
)
WITH (OIDS=TRUE);
ALTER TABLE "rdf:resource" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:class*/
/********************************************************************************************/
CREATE TABLE "owl:class"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  uri character varying(100) NOT NULL,
-- Inherited:  label character varying(100) NOT NULL,
-- description character varying(100) NOT NULL,
  CONSTRAINT "owl:class_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:class_idkey" UNIQUE (id)
)
INHERITS ("rdf:resource")
WITH (OIDS=TRUE);
ALTER TABLE "owl:class" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property*/
/********************************************************************************************/
CREATE TABLE "owl:Property"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  uri character varying(100) NOT NULL,
-- Inherited:  label character varying(100) NOT NULL,
  domain character varying(100) NOT NULL,
  range character varying(100) NOT NULL,
--  type character varying(100) NOT NULL,
  CONSTRAINT "owl:Property_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:Property_idkey" UNIQUE (id)
)
INHERITS ("rdf:resource")
WITH (OIDS=TRUE);
ALTER TABLE "owl:Property" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:ObjectProperty*/
/********************************************************************************************/
CREATE TABLE "owl:ObjectProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL,
  CONSTRAINT "owl:ObjectProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:ObjectProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:Property")
WITH (OIDS=TRUE);
ALTER TABLE "owl:ObjectProperty" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:ObjectProperty/owl:TransitiveProperty*/
/********************************************************************************************/
CREATE TABLE "owl:TransitiveProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL,
  CONSTRAINT "owl:TransitiveProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:TransitiveProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:TransitiveProperty" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:ObjectProperty/owl:SymmetricProperty*/
/********************************************************************************************/
CREATE TABLE "owl:SymmetricProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL,
  CONSTRAINT "owl:SymmetricProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:SymmetricProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:SymmetricProperty" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:ObjectProperty/owl:FunctionalProperty*/        
/********************************************************************************************/
CREATE TABLE "owl:FunctionalProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL, 
  --CONSTRAINT "owl:FunctionalProperty_pkey" PRIMARY KEY (id)
  CONSTRAINT "owl:FunctionalProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:FunctionalProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:FunctionalProperty" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:ObjectProperty/owl:InverseFunctionalProperty*/
/********************************************************************************************/
CREATE TABLE "owl:InverseFunctionalProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL,
 -- CONSTRAINT "owl:InverseFunctionalProperty_pkey" PRIMARY KEY (id)
  CONSTRAINT "owl:InverseFunctionalProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:InverseFunctionalProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:InverseFunctionalProperty" OWNER TO pangea;

/********************************************************************************************/
/*rdf:resource/owl:Property/owl:DatatypeProperty*/
/********************************************************************************************/
CREATE TABLE "owl:DatatypeProperty"
(
  -- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  -- Inherited:  uri character varying(100) NOT NULL,
  -- Inherited:  label character varying(100) NOT NULL,
  -- domain character varying(100) NOT NULL,
  -- range character varying(100) NOT NULL,
  -- type character varying(100) NOT NULL,
 -- CONSTRAINT "owl:DatatypeProperty_pkey" PRIMARY KEY (id)
  CONSTRAINT "owl:DatatypeProperty_pkey" PRIMARY KEY (uri),
  CONSTRAINT "owl:DatatypeProperty_idkey" UNIQUE (id)
)
INHERITS ("owl:Property")
WITH (OIDS=TRUE);
ALTER TABLE "owl:DatatypeProperty" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty*/
/********************************************************************************************/
CREATE TABLE "meta:objectProperty"
(
  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
  subject text NOT NULL, 
  CONSTRAINT "meta:objectProperty_pkey" PRIMARY KEY (id)
)
WITH (OIDS=TRUE);
ALTER TABLE "meta:objectProperty" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/owl:logical*/
/********************************************************************************************/
CREATE TABLE "owl:logical"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL, 
  CONSTRAINT "owl:logical_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:objectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:logical" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/owl:cardinal*/
/********************************************************************************************/
CREATE TABLE "owl:cardinal"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL, 
  CONSTRAINT "owl:cardinal_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:objectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "owl:cardinal" OWNER TO pangea;


/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation*/
/********************************************************************************************/
CREATE TABLE "meta:descriptionRelation"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
  object text NOT NULL,
  CONSTRAINT "meta:descriptionRelation_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:objectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "meta:descriptionRelation" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/rdf:subPropertyOf*/
/********************************************************************************************/
CREATE TABLE "rdf:subPropertyOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "rdf:subPropertyOf_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionRelation")
WITH (OIDS=TRUE);
ALTER TABLE "rdf:subPropertyOf" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/rdf:subClassOf*/
/********************************************************************************************/
CREATE TABLE "rdf:subClassOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "rdf:subClassOf_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionRelation")
WITH (OIDS=TRUE);
ALTER TABLE "rdf:subClassOf" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/owl:relational*/
/********************************************************************************************/
CREATE TABLE "owl:relational"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:relational_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionRelation")
WITH (OIDS=TRUE);
ALTER TABLE "owl:relational" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/owl:relational/owl:equivalent*/
/********************************************************************************************/
CREATE TABLE "owl:equivalent"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:equivalent_pkey" PRIMARY KEY (id)
)
INHERITS ("owl:relational")
WITH (OIDS=TRUE);
ALTER TABLE "owl:equivalent" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/owl:relational/owl:inverseOf*/
/********************************************************************************************/
CREATE TABLE "owl:inverseOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:inverseOf_pkey" PRIMARY KEY (id)
)
INHERITS ("owl:relational")
WITH (OIDS=TRUE);
ALTER TABLE "owl:inverseOf" OWNER TO pangea;

/********************************************************************************************/      
/*triggers/inverse*/
/********************************************************************************************/
/* esta es la que había antes*/
/*CREATE OR REPLACE FUNCTION inverse()
  RETURNS trigger AS
$BODY$

DECLARE
inverse_table name;
query text;
BEGIN
	inverse_table := getinverse(TG_TABLE_NAME);

	IF inverse_table IS NULL THEN 
		RETURN NULL;
	END IF;

	IF (TG_OP = 'INSERT') THEN

		query := 'INSERT INTO ' 
		||quote_ident(inverse_table)
		||'(subject, object) VALUES('
		||NEW.object
		||', '
		||NEW.subject
		||')';		

	ELSIF (TG_OP = 'UPDATE') THEN

		query := 'UPDATE '
		||quote_ident(inverse_table)
		||' SET object = '
		||NEW.subject
		||', '
		||'subject = '
		||NEW.object
		||' WHERE subject = '
		||OLD.object
		||' AND object = '
		||OLD.subject
		||';';
		
	ELSIF (TG_OP = 'DELETE') THEN

		query := 'DELETE FROM '
		||quote_ident(inverse_table)
		||' WHERE subject = '
		||OLD.object
		||' AND object = '
		||OLD.subject
		||';';

	END IF;

	EXECUTE query;

	RETURN NULL;
	
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION inverse() OWNER TO pangea;*/

/*esta es la última que me dió román*/

CREATE OR REPLACE FUNCTION inverse()
   RETURNS trigger AS
$BODY$

DECLARE
inverse_table name;
_target_colunm_exists boolean;
query text;
BEGIN
  inverse_table := getinverse(TG_TABLE_NAME);

  IF inverse_table IS NULL THEN RETURN NULL;
    END IF;

  IF (TG_OP = 'INSERT') THEN
  BEGIN

   EXECUTE 'SELECT 1 WHERE EXISTS (SELECT column_name FROM information_schema.columns WHERE(table_name = $1 and column_name = $2));'
   INTO _target_colunm_exists
   USING inverse_table, 'text_object';

   IF (_target_colunm_exists) THEN
     query := 'INSERT INTO '
    ||quote_ident(inverse_table)
    ||'(subject, object, text_object) VALUES('
    ||quote_literal(NEW.object)
    ||', '
    ||NEW.subject
    ||', '
    ||quote_literal(NEW.subject)
    ||');';
  ELSE
    query := 'INSERT INTO '
   ||quote_ident(inverse_table)
   ||'(subject, object) VALUES('
   ||NEW.object
   ||', '
   ||quote_literal(NEW.subject)
   ||')';
  END IF;
 END;

 ELSIF (TG_OP = 'UPDATE') THEN
  BEGIN
   EXECUTE 'SELECT 1 WHERE EXISTS (SELECT column_name FROM information_schema.columns WHERE(table_name = $1 and column_name = $2));'
   INTO _target_colunm_exists
   USING inverse_table, 'text_object';

  IF (_target_colunm_exists) THEN
    query := 'INSERT INTO '
    ||quote_ident(inverse_table)
    ||'(subject, object, text_object) VALUES('
    || quote_literal(NEW.object)
    || ', '
    || NEW.subject
    || ', '
    || quote_literal(NEW.subject)
    || ');';
   query := 'UPDATE '
   ||quote_ident(inverse_table)
   ||' SET object = '
   ||quote_literal(NEW.subject)
   ||', '
   ||'subject = '
   ||NEW.object
   ||', '
   ||'text_object = '
   ||quote_literal(NEW.subject)
   ||' WHERE subject = '
   ||OLD.object
   ||' AND object = '
   ||quote_literal(OLD.subject)
   ||';';

 ELSE
  query := 'UPDATE '
  ||quote_ident(inverse_table)
  ||' SET object = '
  ||quote_literal(NEW.subject)
  ||', '
  ||'subject = '
  ||NEW.object
  ||' WHERE subject = '
  ||OLD.object
  ||' AND object = '
  ||quote_literal(OLD.subject)
  ||';';
 END IF;
END;

ELSIF (TG_OP = 'DELETE') THEN

 query := 'DELETE FROM '
 ||quote_ident(inverse_table)
 ||' WHERE subject = '
 ||OLD.object
 ||' AND object = '
 ||quote_literal(OLD.subject)
 ||';';

END IF;

EXECUTE query;

RETURN NULL;

END;
$BODY$
   LANGUAGE plpgsql VOLATILE
   COST 100;
ALTER FUNCTION inverse() OWNER TO pangea;

/********************************************************************************************/      
/*triggers/declaretrigger*/
/********************************************************************************************/
CREATE OR REPLACE FUNCTION declaretrigger()
  RETURNS trigger AS
$BODY$

DECLARE

BEGIN
	EXECUTE 'CREATE TRIGGER inverse AFTER INSERT OR UPDATE OR DELETE ON '
	||quote_ident(NEW.Object)
	||'FOR EACH ROW EXECUTE PROCEDURE inverse()';
	RETURN NULL;	
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION declaretrigger() OWNER TO pangea;

CREATE TRIGGER inverse_inf
  AFTER INSERT OR UPDATE
  ON "owl:inverseOf"
  FOR EACH ROW
  EXECUTE PROCEDURE declaretrigger();

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass*/
/********************************************************************************************/
CREATE TABLE "meta:descriptionClass"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "meta:descriptionClass_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionRelation")
WITH (OIDS=TRUE);
ALTER TABLE "meta:descriptionClass" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:unionOf*/
/********************************************************************************************/
CREATE TABLE "owl:unionOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:unionOf_class_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionClass")
WITH (OIDS=TRUE);
ALTER TABLE "owl:unionOf" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:intersectionOf*/
/********************************************************************************************/
CREATE TABLE "owl:intersectionOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:intersectionOf_class_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionClass")
WITH (OIDS=TRUE);
ALTER TABLE "owl:intersectionOf" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:enumeration*/
/********************************************************************************************/
CREATE TABLE "owl:enumeration"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:enumeration_class_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionClass")
WITH (OIDS=TRUE);
ALTER TABLE "owl:enumeration" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:complementOf*/
/********************************************************************************************/
CREATE TABLE "owl:complementOf"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  CONSTRAINT "owl:complementOf_class_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionClass")
WITH (OIDS=TRUE);
ALTER TABLE "owl:complementOf" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:restriction*/
/********************************************************************************************/
CREATE TABLE "owl:restriction"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
  value character varying(100) NOT NULL,
  type character varying(100) NOT NULL,
  CONSTRAINT "owl:restriction_class_pkey" PRIMARY KEY (id)
)
INHERITS ("meta:descriptionClass")
WITH (OIDS=TRUE);
ALTER TABLE "owl:restriction" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:restriction/owl:restrictionProperty*/
/********************************************************************************************/
CREATE TABLE "owl:restrictionProperty"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
-- Inherited:  value character varying(100) NOT NULL,
-- Inherited:  type character varying(100) NOT NULL,
  CONSTRAINT "owl:restrictionProperty_pkey" PRIMARY KEY (id)
)
INHERITS ("owl:restriction")
WITH (OIDS=TRUE);
ALTER TABLE "owl:restrictionProperty" OWNER TO pangea;

/********************************************************************************************/
/*meta:objectProperty/meta:descriptionRelation/meta:descriptionClass/owl:restriction/owl:restrictionCardinality*/
/********************************************************************************************/
CREATE TABLE "owl:restrictionCardinality"
(
-- Inherited:  id integer DEFAULT nextval('system_meta_object_id_seq'::regclass),
-- Inherited:  subject text NOT NULL,
-- Inherited:  object text NOT NULL,
-- Inherited:  value character varying(100) NOT NULL,
-- Inherited:  type character varying(100) NOT NULL,
  CONSTRAINT "owl:restrictionCardinality_pkey" PRIMARY KEY (id)
)
INHERITS ("owl:restriction")
WITH (OIDS=TRUE);
ALTER TABLE "owl:restrictionCardinality" OWNER TO pangea;

/*****************************************************/
/*FIN DE LAS TABLAS DEL META*/
/*****************************************************/

/********************************************************************************************/       
/*pangea*/
/********************************************************************************************/
CREATE TABLE "pangea"
(
  id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:pkey" PRIMARY KEY (id)
)
WITH (OIDS=TRUE);
ALTER TABLE "pangea" OWNER TO pangea;

/***************************************************/
/*INICIO DE LAS TABLAS DE LAS PROPIEDADES*/
/***************************************************/
/*********************************************************************************************/
/*pangea:bareNecessities*/
/*********************************************************************************************/
CREATE TABLE "pangea:bareNecessities"
(
  subject integer NOT NULL,  
  text_object text NOT NULL, 
  CONSTRAINT "pangea:bareNecessities_pkey" PRIMARY KEY (subject, text_object)
)
WITH (OIDS=TRUE);
ALTER TABLE "pangea:bareNecessities" OWNER TO pangea;

/*NOTA ACLARATORIA
 * a la columna donde se van a almacenar los object hubo que ponerle text_object en ves de object
 *  porque los object de las tablas hijas unos son numéricos y otros textuales, entonces daba conflicto la recuperación
 * desde la padre, además en cada tabla hija hubo que crear una columna adicional llamada text_object que se rellena con el mismo valor
 *  de la columna object mediante el trigger set_text_object, para que correspondiera con la de la tabla padre y así poder 
 * recuperar estos valores desde bareNecessities*/

/********************************************************************************************/       
/*pangea/pangea:Property*/
/********************************************************************************************/ 
CREATE TABLE "pangea:Property"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),  
  subject integer NOT NULL,  
 -- CONSTRAINT "pangea:Property_pkey" PRIMARY KEY (subject, object), --esta linea la agregue yo al igual que la de abajo, solo estaba la del medio que ahora esta comentada
 -- CONSTRAINT "pangea:Property_pkey" UNIQUE (id, object)
  CONSTRAINT "pangea:Property_idkey" UNIQUE (id)
)
INHERITS ("pangea")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Property" OWNER TO pangea;

/********************************************************************************************/   
/*pangea/pangea:Property/pangea:ObjectProperty*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:ObjectProperty"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
  object integer NOT NULL,
  CONSTRAINT "pangea:ObjectProperty_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:ObjectProperty_idkey" UNIQUE (id)
)
INHERITS ("pangea:Property")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:ObjectProperty" OWNER TO pangea;

/*insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:ObjectProperty','pangea:Class','pangea:Class','Object Property');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:ObjectProperty','pangea:Property');*/


/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasState*/        
/********************************************************************************************/
/*CREATE TABLE "pangea:hasState"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasState_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasState_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasState" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasState','frbr:Core','pangea:State','has State');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasState','pangea:ObjectProperty');
*/
/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasAvailability*/
/********************************************************************************************/
CREATE TABLE "pangea:hasAvailability"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasAvailability_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:hasAvailability_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasAvailability" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasAvailability','frbr:Item','pangea:Availability','availability');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasAvailability','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasAdquisitionWay*/
/********************************************************************************************/
CREATE TABLE "pangea:hasAdquisitionWay"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasAdquisitionWay_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:hasAdquisitionWay_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasAdquisitionWay" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasAdquisitionWay','frbr:Item','pangea:AdquisitionWay','adquisition way');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasAdquisitionWay','pangea:ObjectProperty');


/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/foaf:depiction*/
/********************************************************************************************/
CREATE TABLE "foaf:depiction"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "foaf:depiction_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "foaf:depiction_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "foaf:depiction" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('foaf:depiction','frbr:Endeavour','foaf:Image','depiction');
insert into "rdf:subPropertyOf" (subject,object) values ('foaf:depiction','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/foaf:depiction/foaf:img*/
/********************************************************************************************/
CREATE TABLE "foaf:img"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "foaf:img_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "foaf:img_idkey" UNIQUE (id)
)
INHERITS ("foaf:depiction", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "foaf:img" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('foaf:img','frbr:Endeavour','foaf:Image','img');
insert into "rdf:subPropertyOf" (subject,object) values ('foaf:img','foaf:depiction');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "foaf:img" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/foaf:depicts*/
/********************************************************************************************/
CREATE TABLE "foaf:depicts"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "foaf:depicts_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "foaf:depicts_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "foaf:depicts" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('foaf:depicts','foaf:Image','frbr:Endeavour','depicts');
insert into "rdf:subPropertyOf" (subject,object) values ('foaf:depicts','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('foaf:depicts','foaf:depiction');
insert into "owl:inverseOf" (subject,object) values ('foaf:depicts','foaf:img');
/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/foaf:thumbnail*/
/********************************************************************************************/
CREATE TABLE "foaf:thumbnail"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "foaf:thumbnail_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "foaf:thumbnail_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "foaf:thumbnail" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('foaf:thumbnail','frbr:Endeavour','foaf:Image','thumbnail');
insert into "rdf:subPropertyOf" (subject,object) values ('foaf:thumbnail','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:inScheme*/
/********************************************************************************************/
CREATE TABLE "skos:inScheme"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:inScheme_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:inScheme_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "skos:inScheme" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:inScheme','frbr:Concept','skos:ConceptScheme','in scheme');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:inScheme','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:hasTopConcept*/
/********************************************************************************************/
CREATE TABLE "skos:hasTopConcept"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:hasTopConcept_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:hasTopConcept_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "skos:hasTopConcept" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:hasTopConcept','skos:ConceptScheme','frbr:Concept','top concept');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:hasTopConcept','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:inScheme/skos:topConceptOf*/
/********************************************************************************************/
CREATE TABLE "skos:topConceptOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:topConceptOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:topConceptOf_idkey" UNIQUE (id)
)
INHERITS ("skos:inScheme")
WITH (OIDS=TRUE);
ALTER TABLE "skos:topConceptOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:topConceptOf','frbr:Concept','skos:ConceptScheme','top concept of');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:topConceptOf','skos:inScheme');
insert into "owl:inverseOf" (subject,object) values ('skos:topConceptOf','skos:hasTopConcept');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation*/
/********************************************************************************************/
CREATE TABLE "skos:semanticRelation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:semanticRelation_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:semanticRelation_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "skos:semanticRelation" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:semanticRelation','frbr:Concept','frbr:Concept','semantic relation');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:semanticRelation','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:broaderTransitive*/
/********************************************************************************************/
CREATE TABLE "skos:broaderTransitive"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:broaderTransitive_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:broaderTransitive_idkey" UNIQUE (id)
)
INHERITS ("skos:semanticRelation")
WITH (OIDS=TRUE);
ALTER TABLE "skos:broaderTransitive" OWNER TO pangea;

insert into "owl:TransitiveProperty" (uri, domain, range, label) values ('skos:broaderTransitive','frbr:Concept','frbr:Concept','broader Transitive');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:broaderTransitive','skos:semanticRelation');
/*insert into "owl:transitive" (subject) values ('skos:broaderTransitive');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:broaderTransitive/skos:broader*/
/********************************************************************************************/
CREATE TABLE "skos:broader"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:broader_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:broader_idkey" UNIQUE (id)
)
INHERITS ("skos:broaderTransitive")
WITH (OIDS=TRUE);
ALTER TABLE "skos:broader" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:broader','frbr:Concept','frbr:Concept','broader');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:broader','skos:broaderTransitive');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:narrowerTransitive*/
/********************************************************************************************/
CREATE TABLE "skos:narrowerTransitive"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:narrowerTransitive_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:narrowerTransitive_idkey" UNIQUE (id)
)
INHERITS ("skos:semanticRelation")
WITH (OIDS=TRUE);
ALTER TABLE "skos:narrowerTransitive" OWNER TO pangea;

insert into "owl:TransitiveProperty" (uri, domain, range, label) values ('skos:narrowerTransitive','frbr:Concept','frbr:Concept','narrower Transitive');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:narrowerTransitive','skos:semanticRelation');
insert into "owl:inverseOf" (subject,object) values ('skos:narrowerTransitive','skos:broaderTransitive');
/*insert into "owl:transitive" (subject) values ('skos:narrowerTransitive');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:narrowerTransitive/skos:narrower*/
/********************************************************************************************/
CREATE TABLE "skos:narrower"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:narrower_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:narrower_idkey" UNIQUE (id)
)
INHERITS ("skos:narrowerTransitive")
WITH (OIDS=TRUE);
ALTER TABLE "skos:narrower" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skos:narrower','frbr:Concept','frbr:Concept','narrower');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:narrower','skos:narrowerTransitive');
insert into "owl:inverseOf" (subject,object) values ('skos:narrower','skos:broader');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:closeMatch*/
/********************************************************************************************/
CREATE TABLE "skos:closeMatch"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:closeMatch_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:closeMatch_idkey" UNIQUE (id)
)
INHERITS ("skos:semanticRelation")
WITH (OIDS=TRUE);
ALTER TABLE "skos:closeMatch" OWNER TO pangea;

insert into "owl:SymmetricProperty" (uri, domain, range, label) values ('skos:closeMatch','frbr:Concept','frbr:Concept','close match');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:closeMatch','skos:semanticRelation');
/*insert into "owl:symmetric" (subject) values ('skos:closeMatch');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:closeMatch/skos:exactMatch*/
/********************************************************************************************/
CREATE TABLE "skos:exactMatch"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:exactMatch_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:exactMatch_idkey" UNIQUE (id)
)
INHERITS ("skos:closeMatch")
WITH (OIDS=TRUE);
ALTER TABLE "skos:exactMatch" OWNER TO pangea;

insert into "owl:SymmetricProperty" (uri, domain, range, label) values ('skos:exactMatch','frbr:Concept','frbr:Concept','exact match');
insert into "owl:TransitiveProperty" (uri, domain, range, label) values ('skos:exactMatch','frbr:Concept','frbr:Concept','exact match');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:exactMatch','skos:closeMatch');
/*insert into "owl:symmetric" (subject) values ('skos:exactMatch');
insert into "owl:transitive" (subject) values ('skos:exactMatch');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:related*/
/********************************************************************************************/
CREATE TABLE "skos:related"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:related_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:related_idkey" UNIQUE (id)
)
INHERITS ("skos:semanticRelation")
WITH (OIDS=TRUE);
ALTER TABLE "skos:related" OWNER TO pangea;

insert into "owl:SymmetricProperty" (uri, domain, range, label) values ('skos:related','frbr:Concept','frbr:Concept','related');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:related','skos:semanticRelation');
/*insert into "owl:symmetric" (subject) values ('skos:related');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/skos:semanticRelation/skos:related/skos:relatedMatch*/
/********************************************************************************************/
CREATE TABLE "skos:relatedMatch"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skos:relatedMatch_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:relatedMatch_idkey" UNIQUE (id)
)
INHERITS ("skos:related")
WITH (OIDS=TRUE);
ALTER TABLE "skos:relatedMatch" OWNER TO pangea;

insert into "owl:SymmetricProperty" (uri, domain, range, label) values ('skos:relatedMatch','frbr:Concept','frbr:Concept','related match');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:relatedMatch','skos:related');
/*insert into "owl:symmetric" (subject) values ('skos:relatedMatch');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasSubject*/
/********************************************************************************************/
CREATE TABLE "pangea:hasSubject"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "pangea:hasSubject_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasSubject_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasSubject" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasSubject','pangea:Class','pangea:Subject','has subject');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasSubject','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:hasSubject" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:subjectOf*/
/********************************************************************************************/
CREATE TABLE "pangea:subjectOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:subjectOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:subjectOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:subjectOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:subjectOf','pangea:Subject','pangea:Class','subject of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:subjectOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('pangea:subjectOf','pangea:hasSubject');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasColor*/
/********************************************************************************************/
CREATE TABLE "pangea:hasColor"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasColor_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasColor_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasColor" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasColor','frbr:Manifestation','pangea:Color','color');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasColor','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasLanguage*/
/********************************************************************************************/
CREATE TABLE "pangea:hasLanguage"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasLanguage_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasLanguage_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasLanguage" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasLanguage','frbr:Expression','pangea:Language','language');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasLanguage','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasShape*/
/********************************************************************************************/
CREATE TABLE "pangea:hasShape"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasShape_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasShape_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasShape" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasShape','frbr:Manifestation','pangea:Shape','shape');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasShape','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasCollection*/
/********************************************************************************************/
CREATE TABLE "pangea:hasCollection"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:hasCollection_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:hasCollection_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasCollection" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:hasCollection','frbr:Item','pangea:Collection','collection');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasCollection','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:collectionOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:collectionOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:collectionOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:collectionOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:collectionOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:collectionOf','pangea:Collection','frbr:Item','collection of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:collectionOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('pangea:collectionOf','pangea:hasCollection');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place*/
/********************************************************************************************/
CREATE TABLE "pangea:place"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "pangea:place_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:place_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:place" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:place','frbr:Endeavour','frbr:Place','place');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:place','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:place" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place/pangea:birthPlace*/
/********************************************************************************************/
CREATE TABLE "pangea:birthPlace"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:birthPlace_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:birthPlace_idkey" UNIQUE (id)
)
INHERITS ("pangea:place")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:birthPlace" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:birthPlace','frbr:Person','frbr:Place','birth place');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:birthPlace','pangea:place');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place/pangea:deathPlace*/
/********************************************************************************************/
CREATE TABLE "pangea:deathPlace"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:deathPlace_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:deathPlace_idkey" UNIQUE (id)
)
INHERITS ("pangea:place")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:deathPlace" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:deathPlace','frbr:Person','frbr:Place','death place');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:deathPlace','pangea:place');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place/pangea:editionPlace*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editionPlace"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:editionPlace_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editionPlace_idkey" UNIQUE (id)
)
INHERITS ("pangea:place")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editionPlace" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editionPlace','frbr:Core','frbr:Place','edition place');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editionPlace','pangea:place');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place/pangea:printerPlace*/
/********************************************************************************************/
/*CREATE TABLE "pangea:printerPlace"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:printerPlace_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:printerPlace_idkey" UNIQUE (id)
)
INHERITS ("pangea:place")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:printerPlace" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:printerPlace','frbr:Core','frbr:Place','printer place');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:printerPlace','pangea:place');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:place/pangea:editionCountry*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editionCountry"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:editionCountry_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editionCountry_idkey" UNIQUE (id)
)
INHERITS ("pangea:place")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editionCountry" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editionCountry','frbr:Core','frbr:Place','edition country');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editionCountry','pangea:place');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf*/
/********************************************************************************************/
CREATE TABLE "pangea:placeOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:placeOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:placeOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:placeOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:placeOf','frbr:Place','frbr:Endeavour','place of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:placeOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('pangea:placeOf','pangea:place');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf/pangea:birthPlaceOf*/
/********************************************************************************************/
CREATE TABLE "pangea:birthPlaceOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:birthPlaceOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:birthPlaceOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:placeOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:birthPlaceOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:birthPlaceOf','frbr:Place','frbr:Person','birth place of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:birthPlaceOf','pangea:placeOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:birthPlaceOf','pangea:birthPlace');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf/pangea:deathPlaceOf*/
/********************************************************************************************/
CREATE TABLE "pangea:deathPlaceOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:deathPlaceOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:deathPlaceOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:placeOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:birthPlaceOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:deathPlaceOf','frbr:Place','frbr:Person','death place of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:deathPlaceOf','pangea:placeOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:deathPlaceOf','pangea:deathPlace');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf/pangea:editionPlaceOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editionPlaceOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:editionPlaceOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editionPlaceOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:placeOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editionPlaceOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editionPlaceOf','frbr:Place','frbr:Core','edition place of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editionPlaceOf','pangea:placeOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:editionPlaceOf','pangea:editionPlace');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf/pangea:printerPlaceOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:printerPlaceOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:printerPlaceOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:printerPlaceOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:placeOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:printerPlaceOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:printerPlaceOf','frbr:Place','frbr:Core','printer place of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:printerPlaceOf','pangea:placeOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:printerPlaceOf','pangea:printerPlace');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:placeOf/pangea:editionCountryOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editionCountryOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:editionCountryOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editionCountryOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:placeOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editionCountryOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editionCountryOf','frbr:Place','frbr:Core','edition country of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editionCountryOf','pangea:placeOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:editionCountryOf','pangea:editionCountry');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomen*/
/********************************************************************************************/
CREATE TABLE "pangea:nomen"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:nomen_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:nomen_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:nomen" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:nomen','frbr:Core','skosxl:Label','nomen');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:nomen','pangea:ObjectProperty');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomen/skosxl:prefLabel*/
/********************************************************************************************/
CREATE TABLE "skosxl:prefLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "skosxl:prefLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:prefLabel_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomen", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:prefLabel" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:prefLabel','frbr:Core','skosxl:Label','preferred label');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:prefLabel','pangea:nomen');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "skosxl:prefLabel" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomen/skosxl:altLabel*/
/********************************************************************************************/
CREATE TABLE "skosxl:altLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skosxl:altLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:altLabel_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomen")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:altLabel" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:altLabel','frbr:Core','skosxl:Label','alternative label');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:altLabel','pangea:nomen');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomen/skosxl:hiddenLabel*/
/********************************************************************************************/
CREATE TABLE "skosxl:hiddenLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skosxl:hiddenLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:hiddenLabel_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomen")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:hiddenLabel" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:hiddenLabel','frbr:Core','skosxl:Label','hidden label');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:hiddenLabel','pangea:nomen');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomenOf*/
/********************************************************************************************/
CREATE TABLE "pangea:nomenOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:nomenOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:nomenOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:nomenOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:nomenOf','skosxl:Label','frbr:Core','nomen of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:nomenOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('pangea:nomenOf','pangea:nomen');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomenOf/skosxl:prefLabelOf*/
/********************************************************************************************/
CREATE TABLE "skosxl:prefLabelOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skosxl:prefLabelOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:prefLabelOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomenOf")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:prefLabelOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:prefLabelOf','skosxl:Label','frbr:Core','preferred label of');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:prefLabelOf','pangea:nomenOf');
insert into "owl:inverseOf" (subject,object) values ('skosxl:prefLabelOf','skosxl:prefLabel');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomenOf/skosxl:altLabelOf*/
/********************************************************************************************/
CREATE TABLE "skosxl:altLabelOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skosxl:altLabelOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:altLabelOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomenOf")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:altLabelOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:altLabelOf','skosxl:Label','frbr:Core','alternative label of');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:altLabelOf','pangea:nomenOf');
insert into "owl:inverseOf" (subject,object) values ('skosxl:altLabelOf','skosxl:altLabel');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:nomenOf/skosxl:hiddenLabelOf*/
/********************************************************************************************/
CREATE TABLE "skosxl:hiddenLabelOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "skosxl:hiddenLabelOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skosxl:hiddenLabelOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:nomenOf")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:hiddenLabelOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('skosxl:hiddenLabelOf','skosxl:Label','frbr:Core','hidden label of');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:hiddenLabelOf','pangea:nomenOf');
insert into "owl:inverseOf" (subject,object) values ('skosxl:hiddenLabelOf','skosxl:hiddenLabel');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/pangea:hasForm*/
/********************************************************************************************/
CREATE TABLE "pangea:hasForm"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
 
  CONSTRAINT "pangea:hasForm_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:hasForm_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:hasForm" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('pangea:hasForm','frbr:Endeavour','pangea:Form','form');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:hasForm','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:hasForm" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedSubject*/
/********************************************************************************************/
CREATE TABLE "frbr:relatedSubject"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "frbr:relatedSubject_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:relatedSubject_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:relatedSubject" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:relatedSubject','frbr:Work','frbr:Core','subject');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:relatedSubject','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "frbr:relatedSubject" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedSubjectOf*/
/********************************************************************************************/
CREATE TABLE "frbr:relatedSubjectOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:relatedSubjectOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:relatedSubjectOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:relatedSubjectOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:relatedSubjectOf','frbr:Core','frbr:Work','subject of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:relatedSubjectOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('frbr:relatedSubjectOf','frbr:relatedSubject');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity*/
/********************************************************************************************/
CREATE TABLE "frbr:relatedResponsibleEntity"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "frbr:relatedResponsibleEntity_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:relatedResponsibleEntity_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:relatedResponsibleEntity" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:relatedResponsibleEntity','frbr:Endeavour','frbr:ResponsibleEntity','responsible entity');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:relatedResponsibleEntity','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "frbr:relatedResponsibleEntity" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:owner*/
/********************************************************************************************/
CREATE TABLE "frbr:owner"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:owner_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:owner_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:owner" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:owner','frbr:Item','frbr:ResponsibleEntity','owner');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:owner','frbr:relatedResponsibleEntity');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:producer*/
/********************************************************************************************/
CREATE TABLE "frbr:producer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:producer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:producer_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:producer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:producer','frbr:Manifestation','frbr:ResponsibleEntity','producer');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:producer','frbr:relatedResponsibleEntity');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:producer/pangea:editorial*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editorial"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "pangea:editorial_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editorial_idkey" UNIQUE (id)
)
INHERITS ("frbr:producer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editorial" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editorial','frbr:Manifestation','frbr:ResponsibleEntity','editorial');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editorial','frbr:producer');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/pangea:printer*/
/********************************************************************************************/
/*CREATE TABLE "pangea:printer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:printer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:printer_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:printer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:printer','frbr:Manifestation','frbr:ResponsibleEntity','printer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:printer','frbr:relatedResponsibleEntity');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:creator*/
/********************************************************************************************/
CREATE TABLE "frbr:creator"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:creator_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:creator_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:creator" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:creator','frbr:Work','frbr:ResponsibleEntity','creator');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:creator','frbr:relatedResponsibleEntity');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer*/
/********************************************************************************************/
CREATE TABLE "frbr:realizer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:realizer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:realizer_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:realizer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:realizer','frbr:Expression','frbr:ResponsibleEntity','realizer');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:realizer','frbr:relatedResponsibleEntity');

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:editor*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:editor"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:editor_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editor_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editor" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editor','frbr:Expression','frbr:ResponsibleEntity','editor');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editor','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:adapter*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:adapter"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:adapter_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:adapter_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:adapter" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:adapter','frbr:Expression','frbr:ResponsibleEntity','adapter');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:adapter','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:annotator*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:annotator"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:annotator_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:annotator_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:annotator" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:annotator','frbr:Expression','frbr:ResponsibleEntity','annotator');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:annotator','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:arranger*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:arranger"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:arranger_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:arranger_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:arranger" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:arranger','frbr:Expression','frbr:ResponsibleEntity','arranger');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:arranger','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:cartoonist*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:cartoonist"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:cartoonist_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:cartoonist_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:cartoonist" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:cartoonist','frbr:Expression','frbr:ResponsibleEntity','cartoonist');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:cartoonist','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:draw*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:draw"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:draw_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:draw_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:draw" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:draw','frbr:Expression','frbr:ResponsibleEntity','draw');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:draw','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:compiler*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:compiler"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:compiler_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:compiler_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:compiler" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:compiler','frbr:Expression','frbr:ResponsibleEntity','compiler');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:compiler','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:proofreader*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:proofreader"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:proofreader_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:proofreader_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:proofreader" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:proofreader','frbr:Expression','frbr:ResponsibleEntity','proofreader');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:proofreader','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:photographer*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:photographer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:photographer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:photographer_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:photographer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:photographer','frbr:Expression','frbr:ResponsibleEntity','photographer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:photographer','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:engraver*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:engraver"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:engraver_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:engraver_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:engraver" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:engraver','frbr:Expression','frbr:ResponsibleEntity','engraver');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:engraver','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:screenwriter*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:screenwriter"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:screenwriter_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:screenwriter_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:screenwriter" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:screenwriter','frbr:Expression','frbr:ResponsibleEntity','screenwriter');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:screenwriter','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:illustrator*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:illustrator"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:illustrator_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:illustrator_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:illustrator" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:illustrator','frbr:Expression','frbr:ResponsibleEntity','illustrator');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:illustrator','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:introducer*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:introducer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:introducer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:introducer_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:introducer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:introducer','frbr:Expression','frbr:ResponsibleEntity','introducer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:introducer','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:prologue*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:prologue"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:prologue_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:prologue_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:prologue" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:prologue','frbr:Expression','frbr:ResponsibleEntity','prologue');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:prologue','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:drafter*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:drafter"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:redactor_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:redactor_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:drafter" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:drafter','frbr:Expression','frbr:ResponsibleEntity','drafter');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:drafter','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:interpreter*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:interpreter"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:interpreter_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:interpreter_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:interpreter" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:interpreter','frbr:Expression','frbr:ResponsibleEntity','interpreter');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:interpreter','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:translator*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:translator"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:translator_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:translator_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:translator" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:translator','frbr:Expression','frbr:ResponsibleEntity','translator');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:translator','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:tutor*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:tutor"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:tutor_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:tutor_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:tutor" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:tutor','frbr:Expression','frbr:ResponsibleEntity','tutor');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:tutor','frbr:realizer');*/


/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:cartographer*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:cartographer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:cartographer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:cartographer_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:cartographer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:cartographer','frbr:Expression','frbr:ResponsibleEntity','cartographer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:cartographer','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:manager*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:manager"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:manager_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:manager_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:manager" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:manager','frbr:Expression','frbr:ResponsibleEntity','manager');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:manager','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:composer*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:composer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:composer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:composer_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:composer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:composer','frbr:Expression','frbr:ResponsibleEntity','composer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:composer','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:musician*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:musician"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:musician_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:musician_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:musician" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:musician','frbr:Expression','frbr:ResponsibleEntity','musician');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:musician','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:presenter*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:presenter"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:presenter_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:presenter_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:presenter" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:presenter','frbr:Expression','frbr:ResponsibleEntity','presenter');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:presenter','frbr:realizer');*/

/***********************************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntity/frbr:realizer/pangea:designer*/
/**********************************************************************************************************/
/*CREATE TABLE "pangea:designer"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:designer_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:designer_idkey" UNIQUE (id)
)
INHERITS ("frbr:realizer")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:designer" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:designer','frbr:Expression','frbr:ResponsibleEntity','designer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:designer','frbr:realizer');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf*/
/********************************************************************************************/
CREATE TABLE "frbr:relatedResponsibleEntityOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:relatedResponsibleEntityOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:relatedResponsibleEntityOf_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:relatedResponsibleEntityOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:relatedResponsibleEntityOf','frbr:ResponsibleEntity','frbr:Endeavour','responsible entity of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:relatedResponsibleEntityOf','pangea:ObjectProperty');
insert into "owl:inverseOf" (subject,object) values ('frbr:relatedResponsibleEntityOf','frbr:relatedResponsibleEntity');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/frbr:ownerOf*/
/********************************************************************************************/
CREATE TABLE "frbr:ownerOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:ownerOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:ownerOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntityOf")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:ownerOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:ownerOf','frbr:ResponsibleEntity','frbr:Item','owner of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:ownerOf','frbr:relatedResponsibleEntityOf');
insert into "owl:inverseOf" (subject,object) values ('frbr:ownerOf','frbr:owner');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/frbr:producerOf*/
/********************************************************************************************/
CREATE TABLE "frbr:producerOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:producerOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:producerOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntityOf")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:producerOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:producerOf','frbr:ResponsibleEntity','frbr:Manifestation','producer of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:producerOf','frbr:relatedResponsibleEntityOf');
insert into "owl:inverseOf" (subject,object) values ('frbr:producerOf','frbr:producer');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/frbr:producerOf/pangea:editorialOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editorialOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:editorialOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editorialOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:producerOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editorialOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:editorialOf','frbr:ResponsibleEntity','frbr:Manifestation','editorial of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editorialOf','frbr:producerOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:editorialOf','pangea:editorial');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/pangea:printerOf*/
/********************************************************************************************/
/*CREATE TABLE "pangea:printerOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  CONSTRAINT "pangea:printerOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:printerOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntityOf")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:printerOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('pangea:printerOf','frbr:ResponsibleEntity','frbr:Manifestation','printer of');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:printerOf','frbr:relatedResponsibleEntityOf');
insert into "owl:inverseOf" (subject,object) values ('pangea:printerOf','pangea:printer');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/frbr:creatorOf*/
/********************************************************************************************/
CREATE TABLE "frbr:creatorOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:creatorOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:creatorOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntityOf")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:creatorOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:creatorOf','frbr:ResponsibleEntity','frbr:Work','creator of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:creatorOf','frbr:relatedResponsibleEntityOf');
insert into "owl:inverseOf" (subject,object) values ('frbr:creatorOf','frbr:creator');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedResponsibleEntityOf/frbr:realizerOf*/
/********************************************************************************************/
CREATE TABLE "frbr:realizerOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:realizerOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:realizerOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedResponsibleEntityOf")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:realizerOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:realizerOf','frbr:ResponsibleEntity','frbr:Expression','realizer of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:realizerOf','frbr:relatedResponsibleEntityOf');
insert into "owl:inverseOf" (subject,object) values ('frbr:realizerOf','frbr:realizer');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour*/
/********************************************************************************************/
CREATE TABLE "frbr:relatedEndeavour"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
 text_object text NOT NULL, 
  CONSTRAINT "frbr:relatedEndeavour_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:relatedEndeavour_idkey" UNIQUE (id)
)
INHERITS ("pangea:ObjectProperty", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:relatedEndeavour" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:relatedEndeavour','frbr:Endeavour','frbr:Endeavour','relation FRBR');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:relatedEndeavour','pangea:ObjectProperty');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "frbr:relatedEndeavour" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:suplement*/
/********************************************************************************************/
CREATE TABLE "frbr:suplement"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:suplement_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:suplement_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:suplement" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:suplement','frbr:Endeavour','frbr:Endeavour','suplement');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:suplement','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:suplementOf*/
/********************************************************************************************/
CREATE TABLE "frbr:suplementOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:suplementOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:suplementOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:suplementOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:suplementOf','frbr:Endeavour','frbr:Endeavour','suplement of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:suplementOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:suplementOf','frbr:suplement');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:transformation*/
/********************************************************************************************/
CREATE TABLE "frbr:transformation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:transformation_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:transformation_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:transformation" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:transformation','frbr:Endeavour','frbr:Endeavour','transformation');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:transformation','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:transformationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:transformationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:transformationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:transformationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:transformationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:transformationOf','frbr:Endeavour','frbr:Endeavour','transformation of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:transformationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:transformationOf','frbr:transformation');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:arrangement*/
/********************************************************************************************/
CREATE TABLE "frbr:arrangement"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:arrangement_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:arrangement_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:arrangement" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:arrangement','frbr:Expression','frbr:Expression','arrangement');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:arrangement','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:arrangementOf*/
/********************************************************************************************/
CREATE TABLE "frbr:arrangementOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:arrangementOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:arrangementOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:arrangementOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:arrangementOf','frbr:Expression','frbr:Expression','arrangement of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:arrangementOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:arrangementOf','frbr:arrangement');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:realization*/
/********************************************************************************************/
CREATE TABLE "frbr:realization"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:realization_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:realization_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:realization" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:realization','frbr:Work','frbr:Expression','realization');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:realization','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:realizationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:realizationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:realizationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:realizationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:realizationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:realizationOf','frbr:Expression','frbr:Work','realization of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:realizationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:realizationOf','frbr:realization');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:abridgement*/
/********************************************************************************************/
CREATE TABLE "frbr:abridgement"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:abridgement_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:abridgement_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:abridgement" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:abridgement','frbr:Expression','frbr:Expression','abridgement');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:abridgement','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:abridgementOf*/
/********************************************************************************************/
CREATE TABLE "frbr:abridgementOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:abridgementOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:abridgementOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:abridgementOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:abridgementOf','frbr:Expression','frbr:Expression','abridgement of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:abridgementOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:abridgementOf','frbr:abridgement');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:summarization*/
/********************************************************************************************/
CREATE TABLE "frbr:summarization"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:summarization_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:summarization_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:summarization" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:summarization','frbr:Endeavour','frbr:Endeavour','summarization');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:summarization','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:summarizationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:summarizationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:summarizationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:summarizationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:summarizationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:summarizationOf','frbr:Endeavour','frbr:Endeavour','summarization of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:summarizationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:summarizationOf','frbr:summarization');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:adaption*/
/********************************************************************************************/
CREATE TABLE "frbr:adaption"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:adaption_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:adaption_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:adaption" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:adaption','frbr:Endeavour','frbr:Endeavour','adaption');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:adaption','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:adaptionOf*/
/********************************************************************************************/
CREATE TABLE "frbr:adaptionOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:adaptionOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:adaptionOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:adaptionOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:adaptionOf','frbr:Endeavour','frbr:Endeavour','adaption of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:adaptionOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:adaptionOf','frbr:adaption');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:alternate*/
/********************************************************************************************/
CREATE TABLE "frbr:alternate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:alternate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:alternate_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:alternate" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:alternate','frbr:Manifestation','frbr:Manifestation','alternate');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:alternate','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:alternateOf*/
/********************************************************************************************/
CREATE TABLE "frbr:alternateOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:alternateOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:alternateOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:alternateOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:alternateOf','frbr:Manifestation','frbr:Manifestation','alternate of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:alternateOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:alternateOf','frbr:alternate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:embodiment*/
/********************************************************************************************/
CREATE TABLE "frbr:embodiment"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:embodiment_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:embodiment_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:embodiment" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:embodiment','frbr:Expression','frbr:Manifestation','embodiment');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:embodiment','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:embodimentOf*/
/********************************************************************************************/
CREATE TABLE "frbr:embodimentOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:embodimentOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:embodimentOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:embodimentOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:embodimentOf','frbr:Manifestation','frbr:Expression','embodiment of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:embodimentOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:embodimentOf','frbr:embodiment');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:part*/
/********************************************************************************************/
CREATE TABLE "frbr:part"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:part_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:part_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:part" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:part','frbr:Endeavour','frbr:Endeavour','part');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:part','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:partOf*/
/********************************************************************************************/
CREATE TABLE "frbr:partOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:partOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:partOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:partOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:partOf','frbr:Endeavour','frbr:Endeavour','part of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:partOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:partOf','frbr:part');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:successor*/
/********************************************************************************************/
CREATE TABLE "frbr:successor"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:successor_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:successor_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:successor" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:successor','frbr:Endeavour','frbr:Endeavour','successor');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:successor','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:successorOf*/
/********************************************************************************************/
CREATE TABLE "frbr:successorOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:successorOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:successorOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:successorOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:successorOf','frbr:Endeavour','frbr:Endeavour','successor of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:successorOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:successorOf','frbr:successor');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:reconfiguration*/
/********************************************************************************************/
CREATE TABLE "frbr:reconfiguration"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:reconfiguration_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:reconfiguration_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:reconfiguration" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:reconfiguration','frbr:Item','frbr:Item','reconfiguration');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:reconfiguration','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:reconfigurationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:reconfigurationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:reconfigurationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:reconfigurationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:reconfigurationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:reconfigurationOf','frbr:Item','frbr:Item','reconfiguration of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:reconfigurationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:reconfigurationOf','frbr:reconfiguration');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:translation*/
/********************************************************************************************/
CREATE TABLE "frbr:translation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:translation_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:translation_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:translation" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:translation','frbr:Expression','frbr:Expression','translation');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:translation','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:translationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:translationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:translationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:translationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:translationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:translationOf','frbr:Expression','frbr:Expression','translation of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:translationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:translationOf','frbr:translation');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:exemplar*/
/********************************************************************************************/
CREATE TABLE "frbr:exemplar"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:exemplar_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:exemplar_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:exemplar" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:exemplar','frbr:Manifestation','frbr:Item','exemplar');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:exemplar','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:exemplarOf*/
/********************************************************************************************/
CREATE TABLE "frbr:exemplarOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:exemplarOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:exemplarOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:exemplarOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:exemplarOf','frbr:Item','frbr:Manifestation','exemplar of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:exemplarOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:exemplarOf','frbr:exemplar');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:complement*/
/********************************************************************************************/
CREATE TABLE "frbr:complement"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:complement_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:complement_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:complement" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range,label) values ('frbr:complement','frbr:Endeavour','frbr:Endeavour','complement');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:complement','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:complementOf*/
/********************************************************************************************/
CREATE TABLE "frbr:complementOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:complementOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:complementOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:complementOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:complementOf','frbr:Endeavour','frbr:Endeavour','complement of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:complementOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:complementOf','frbr:complement');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:revision*/
/********************************************************************************************/
CREATE TABLE "frbr:revision"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:revision_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:revision_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:revision" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:revision','frbr:Expression','frbr:Expression','revision');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:revision','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:revisionOf*/
/********************************************************************************************/
CREATE TABLE "frbr:revisionOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:revisionOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:revisionOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:revisionOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:revisionOf','frbr:Expression','frbr:Expression','revision of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:revisionOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:revisionOf','frbr:revision');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:imitation*/
/********************************************************************************************/
CREATE TABLE "frbr:imitation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:imitation_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:imitation_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:imitation" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:imitation','frbr:Endeavour','frbr:Endeavour','imitation');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:imitation','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:imitationOf*/
/********************************************************************************************/
CREATE TABLE "frbr:imitationOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:imitationOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:imitationOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:imitationOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:imitationOf','frbr:Endeavour','frbr:Endeavour','imitation of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:imitationOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:imitationOf','frbr:imitation');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:reproduction*/
/********************************************************************************************/
CREATE TABLE "frbr:reproduction"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:reproduction_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:reproduction_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:reproduction" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:reproduction','frbr:Endeavour','frbr:Endeavour','reproduction');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:reproduction','frbr:relatedEndeavour');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:ObjectProperty/frbr:relatedEndeavour/frbr:reproductionOf*/
/********************************************************************************************/
CREATE TABLE "frbr:reproductionOf"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object integer NOT NULL,
  CONSTRAINT "frbr:reproductionOf_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "frbr:reproductionOf_idkey" UNIQUE (id)
)
INHERITS ("frbr:relatedEndeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:reproductionOf" OWNER TO pangea;

insert into "owl:ObjectProperty" (uri, domain, range, label) values ('frbr:reproductionOf','frbr:Endeavour','frbr:Endeavour','reproduction of');
insert into "rdf:subPropertyOf" (subject,object) values ('frbr:reproductionOf','frbr:relatedEndeavour');
insert into "owl:inverseOf" (subject,object) values ('frbr:reproductionOf','frbr:reproduction');

/********************************************************************************************/   
/*pangea/pangea:Property/pangea:DatatypeProperty*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:DatatypeProperty"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
  object text NOT NULL,
  datatype character varying(50) DEFAULT 'xsd:string',
  lang character varying(30) DEFAULT 'sp',
  CONSTRAINT "pangea:DatatypeProperty_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:DatatypeProperty_idkey" UNIQUE (id)

)
INHERITS ("pangea:Property")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:DatatypeProperty" OWNER TO pangea;

/********************************************************************************************/   
/*pangea/pangea:Property/pangea:DatatypeProperty/skosxl:literalForm*/ 
/********************************************************************************************/ 
CREATE TABLE "skosxl:literalForm"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skosxl:literalForm_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "skosxl:literalForm_idkey" UNIQUE (id)
)
INHERITS ("pangea:DatatypeProperty")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:literalForm" OWNER TO pangea;


insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skosxl:literalForm','skosxl:Label','xsd:string','string');
insert into "rdf:subPropertyOf" (subject,object) values ('skosxl:literalForm','pangea:DatatypeProperty');

/********************************************************************************************/   
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:literalString"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:literalString_pkey" PRIMARY KEY (subject, object),
  CONSTRAINT "pangea:literalString_idkey" UNIQUE (id)
)
INHERITS ("pangea:DatatypeProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:literalString" OWNER TO pangea;

/*insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:literalString','frbr:Core','xsd:string','string');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:literalString','pangea:DatatypeProperty');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:serialOrd*/        /*Es literal tipo string hasta que se arreglen los datos porque en realidad es tipo entero*/
/********************************************************************************************/
CREATE TABLE "pangea:serialOrd"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:serialOrd_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:serialOrd_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:serialOrd" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:serialOrd','frbr:Expression','xsd:string', 'tomo');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:serialOrd','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:extent*/        /*Es literal tipo string hasta que se arreglen los datos porque en realidad es tipo entero*/
/********************************************************************************************/
CREATE TABLE "pangea:extent"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:extent_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:extent_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:extent" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:extent','frbr:Expression','xsd:string', 'extent');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:extent','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psYear*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psYear"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psYear_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psYear_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psYear" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psYear','frbr:Manifestation','xsd:string', 'psYear');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psYear','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psEpoch*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psEpoch"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psEpoch_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psEpoch_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psEpoch" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psEpoch','frbr:Manifestation','xsd:string', 'psEpoch');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psEpoch','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psVolume*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psVolume"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psVolume_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psVolume_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psVolume" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psVolume','frbr:Manifestation','xsd:string', 'psVolume');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psVolume','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psNumber*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psNumber"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psNumber_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psNumber_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psNumber" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psNumber','frbr:Manifestation','xsd:string', 'psNumber');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psNumber','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psMonths*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psMonths"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psMonths_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psMonths_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psMonths" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psMonths','frbr:Manifestation','xsd:string', 'psMonths');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psMonths','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:psDay*/
/********************************************************************************************/
/*CREATE TABLE "pangea:psDay"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:psDay_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:psDay_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:psDay" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:psDay','frbr:Manifestation','xsd:string', 'psDay');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:psDay','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:illustration*/
/********************************************************************************************/
CREATE TABLE "pangea:illustration"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:illustration_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:illustration_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:illustration" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:illustration','frbr:Expression','xsd:string', 'illustration');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:illustration','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:serialNumber*/
/********************************************************************************************/
CREATE TABLE "pangea:serialNumber"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:serialNumber_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:serialNumber_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:serialNumber" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:serialNumber','frbr:Expression','xsd:string', 'serial Number');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:serialNumber','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:subSerialNumber*/
/********************************************************************************************/
CREATE TABLE "pangea:subSerialNumber"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:subSerialNumber_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:subSerialNumber_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:subSerialNumber" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:subSerialNumber','frbr:Expression','xsd:string', 'sub Serial Number');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:subSerialNumber','pangea:literalString');


/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:strDate*/
/********************************************************************************************/
CREATE TABLE "pangea:strDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
 text_object text NOT NULL, 
  CONSTRAINT "pangea:strDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:strDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:strDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:strDate','frbr:Endeavour','xsd:string', 'strDate');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:strDate','pangea:literalString');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:strDate" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note*/
/********************************************************************************************/
CREATE TABLE "pangea:note"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
 text_object text NOT NULL, 
  CONSTRAINT "pangea:note_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:note_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:note" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:note','frbr:Endeavour','xsd:string', 'note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:note','pangea:literalString');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:note" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note/pangea:generalNote*/
/********************************************************************************************/
CREATE TABLE "pangea:generalNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:generalNote_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:generalNote_idkey" UNIQUE (id)
)
INHERITS ("pangea:note")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:generalNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:generalNote','frbr:Manifestation','xsd:string', 'general note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:generalNote','pangea:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note/pangea:contentNote*/
/********************************************************************************************/
CREATE TABLE "pangea:contentNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:contentNote_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:contentNote_idkey" UNIQUE (id)
)
INHERITS ("pangea:note")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:contentNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:contentNote','frbr:Expression','xsd:string','content note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:contentNote','pangea:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note/pangea:accompanyNote*/
/********************************************************************************************/
CREATE TABLE "pangea:accompanyNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:accompanyNote_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:accompanyNote_idkey" UNIQUE (id)
)
INHERITS ("pangea:note")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:accompanyNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:accompanyNote','frbr:Item','xsd:string','accompany note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:accompanyNote','pangea:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note/pangea:itemNote*/
/********************************************************************************************/
CREATE TABLE "pangea:itemNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:itemNote_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:itemNote_idkey" UNIQUE (id)
)
INHERITS ("pangea:note")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:itemNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:itemNote','frbr:Item','xsd:string','item note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:itemNote','pangea:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:note/pangea:adquisitionNote*/
/********************************************************************************************/
CREATE TABLE "pangea:adquisitionNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:adquisitionNote_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:adquisitionNote_idkey" UNIQUE (id)
)
INHERITS ("pangea:note")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:adquisitionNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:adquisitionNote','frbr:Item','xsd:string','adquisition note');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:adquisitionNote','pangea:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:namePrefix*/
/********************************************************************************************/
CREATE TABLE "pangea:namePrefix"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:namePrefix_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:namePrefix_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:namePrefix" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:namePrefix','frbr:Person','xsd:string','prefix');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:namePrefix','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:nameSuffix*/
/********************************************************************************************/
CREATE TABLE "pangea:nameSuffix"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:nameSuffix_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:nameSuffix_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:nameSuffix" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:nameSuffix','frbr:Person','xsd:string','suffix');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:nameSuffix','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:scientificName*/
/********************************************************************************************/
CREATE TABLE "pangea:scientificName"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:scientificName_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:scientificName_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:scientificName" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:scientificName','frbr:Core','xsd:string','scientific name');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:scientificName','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:name*/
/********************************************************************************************/
CREATE TABLE "pangea:name"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:name_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:name_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:name" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:name','frbr:Core','xsd:string','name');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:name','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:firstName*/
/********************************************************************************************/
CREATE TABLE "pangea:firstName"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:firstName_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:firstName_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:firstName" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:firstName','frbr:Core','xsd:string','first name');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:firstName','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:lastName*/
/********************************************************************************************/
CREATE TABLE "pangea:lastName"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:lastName_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:lastName_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:lastName" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:lastName','frbr:Person','xsd:string', 'last name');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:lastName','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:label*/
/********************************************************************************************/
CREATE TABLE "rdfs:label"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "rdfs:label_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "rdfs:label_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "rdfs:label" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('rdfs:label','pangea','xsd:string','rdfs label');
insert into "rdf:subPropertyOf" (subject,object) values ('rdfs:label','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:label/skos:prefLabel*/
/********************************************************************************************/
CREATE TABLE "skos:prefLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
 text_object text NOT NULL, 
  CONSTRAINT "skos:prefLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:prefLabel_idkey" UNIQUE (id)
)
INHERITS ("rdfs:label", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "skos:prefLabel" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:prefLabel','pangea','xsd:string','skos prefLabel');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:prefLabel','rdfs:label');


/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "skos:prefLabel" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();
   
/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:label/skos:altLabel*/
/********************************************************************************************/
CREATE TABLE "skos:altLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:altLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:altLabel_idkey" UNIQUE (id)
)
INHERITS ("rdfs:label")
WITH (OIDS=TRUE);
ALTER TABLE "skos:altLabel" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:altLabel','pangea','xsd:string','skos altLabel');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:altLabel','rdfs:label');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:label/skos:hiddenLabel*/
/********************************************************************************************/
CREATE TABLE "skos:hiddenLabel"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:hiddenLabel_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "skos:hiddenLabel_idkey" UNIQUE (id)
)
INHERITS ("rdfs:label")
WITH (OIDS=TRUE);
ALTER TABLE "skos:hiddenLabel" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:hiddenLabel','pangea','xsd:string','skos hiddenLabel');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:hiddenLabel','rdfs:label');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:classifDewey*/
/********************************************************************************************/
CREATE TABLE "pangea:classifDewey"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:classifDewey_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:classifDewey_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:classifDewey" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:classifDewey','frbr:Work','xsd:string','classification dewey');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:classifDewey','pangea:literalString');


/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:classifCdu*/
/********************************************************************************************/
CREATE TABLE "pangea:classifCdu"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:classifCdu_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:classifCdu_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:classifCdu" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:classifCdu','frbr:Work','xsd:string','classification cdu');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:classifCdu','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:isbn*/
/********************************************************************************************/
CREATE TABLE "pangea:isbn"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:isbn_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:isbn_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:isbn" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:isbn','frbr:Manifestation','xsd:string','isbn');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:isbn','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:issn*/
/********************************************************************************************/
CREATE TABLE "pangea:issn"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:issn_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:issn_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:issn" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:issn','frbr:Expression','xsd:string','issn');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:issn','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:notation*/
/********************************************************************************************/
CREATE TABLE "skos:notation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:notation_idkey" UNIQUE (id),
  CONSTRAINT "skos:notation_pkey" PRIMARY KEY (subject, object)
 
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "skos:notation" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:notation','frbr:Concept','xsd:string','notation');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:notation','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note*/
/********************************************************************************************/
CREATE TABLE "skos:note"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:note_idkey" UNIQUE (id),
  CONSTRAINT "skos:note_pkey" PRIMARY KEY (subject, object)
  
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "skos:note" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:note','frbr:Concept','xsd:string','note');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:note','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:changeNote*/
/********************************************************************************************/
CREATE TABLE "skos:changeNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:changeNote_idkey" UNIQUE (id),
  CONSTRAINT "skos:changeNote_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:changeNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range,label) values ('skos:changeNote','frbr:Concept','xsd:string','change note');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:changeNote','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:definition*/
/********************************************************************************************/
CREATE TABLE "skos:definition"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:definition_idkey" UNIQUE (id),
  CONSTRAINT "skos:definition_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:definition" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:definition','frbr:Concept','xsd:string','definition');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:definition','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:scopeNote*/
/********************************************************************************************/
CREATE TABLE "skos:scopeNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:scopeNote_idkey" UNIQUE (id),
  CONSTRAINT "skos:scopeNote_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:scopeNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:scopeNote','frbr:Concept','xsd:string','scope note');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:scopeNote','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:historyNote*/
/********************************************************************************************/
CREATE TABLE "skos:historyNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:historyNote_idkey" UNIQUE (id),
  CONSTRAINT "skos:historyNote_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:historyNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:historyNote','frbr:Concept','xsd:string','history note');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:historyNote','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:example*/
/********************************************************************************************/
CREATE TABLE "skos:example"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:example_idkey" UNIQUE (id),
  CONSTRAINT "skos:example_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:example" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:example','frbr:Concept','xsd:string','example');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:example','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/skos:note/skos:editorialNote*/
/********************************************************************************************/
CREATE TABLE "skos:editorialNote"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "skos:editorialNote_idkey" UNIQUE (id),
  CONSTRAINT "skos:editorialNote_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("skos:note")
WITH (OIDS=TRUE);
ALTER TABLE "skos:editorialNote" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('skos:editorialNote','frbr:Concept','xsd:string','editorial note');
insert into "rdf:subPropertyOf" (subject,object) values ('skos:editorialNote','skos:note');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:location*/
/********************************************************************************************/
/*CREATE TABLE "pangea:location"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:location_idkey" UNIQUE (id),
  CONSTRAINT "pangea:location_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:location" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:location','frbr:Item','xsd:string','location');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:location','pangea:literalString');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:comissionAct*/
/********************************************************************************************/
CREATE TABLE "pangea:comissionAct"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:comissionAct_idkey" UNIQUE (id),
  CONSTRAINT "pangea:comissionAct_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:comissionAct" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:comissionAct','frbr:Item','xsd:string','comission act');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:comissionAct','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:buyAct*/
/********************************************************************************************/
CREATE TABLE "pangea:buyAct"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:buyAct_idkey" UNIQUE (id),
  CONSTRAINT "pangea:buyAct_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:buyAct" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:buyAct','frbr:Item','xsd:string','buy act');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:buyAct','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/pangea:numberStock*/
/********************************************************************************************/
CREATE TABLE "pangea:numberStock"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:integer',
  CONSTRAINT "pangea:numberStock_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:numberStock_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalString")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:numberStock" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:numberStock','frbr:Item','xsd:string','number stock');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:numberStock','pangea:literalString');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:comment*/
/********************************************************************************************/
CREATE TABLE "rdfs:comment"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
 text_object text NOT NULL, 
  CONSTRAINT "rdfs:comment_idkey" UNIQUE (id),
  CONSTRAINT "rdfs:comment_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("pangea:literalString", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "rdfs:comment" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('rdfs:comment','pangea:Class','xsd:string','comment');
insert into "rdf:subPropertyOf" (subject,object) values ('rdfs:comment','pangea:literalString');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "rdfs:comment" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:comment/pangea:warning*/
/********************************************************************************************/
CREATE TABLE "pangea:warning"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:warning_idkey" UNIQUE (id),
  CONSTRAINT "pangea:warning_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("rdfs:comment")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:warning" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:warning','pangea:Class','xsd:string','warning');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:warning','rdfs:comment');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalString/rdfs:comment/pangea:error*/
/********************************************************************************************/
CREATE TABLE "pangea:error"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:string',
-- lang character varying(5),
  CONSTRAINT "pangea:error_idkey" UNIQUE (id),
  CONSTRAINT "pangea:error_pkey" PRIMARY KEY (subject, object)
)
INHERITS ("rdfs:comment")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:error" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:error','pangea:Class','xsd:string','error');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:error','rdfs:comment');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate*/
/********************************************************************************************/
CREATE TABLE "pangea:literalDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:literalDate_idkey" UNIQUE (id),
  CONSTRAINT "pangea:literalDate_pkey" PRIMARY KEY (subject, object)
  
)
INHERITS ("pangea:DatatypeProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:literalDate" OWNER TO pangea;

/*insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:literalDate','frbr:Core','xsd:date', 'date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:literalDate','pangea:DatatypeProperty');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:birthDate*/
/********************************************************************************************/
CREATE TABLE "pangea:birthDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:birthDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:birthDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:birthDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:birthDate','frbr:Person','xsd:date','birth date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:birthDate','pangea:literalDate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:deathDate*/
/********************************************************************************************/
CREATE TABLE "pangea:deathDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:deathDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:deathDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:deathDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:deathDate','frbr:Person','xsd:date','death date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:deathDate','pangea:literalDate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:editionDate*/
/********************************************************************************************/
/*CREATE TABLE "pangea:editionDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:editionDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:editionDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:editionDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:editionDate','frbr:Endeavour','xsd:date','edition date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:editionDate','pangea:literalDate');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:printerDate*/
/********************************************************************************************/
/*CREATE TABLE "pangea:printerDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:printerDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:printerDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:printerDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:printerDate','frbr:Endeavour','xsd:date','printer date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:printerDate','pangea:literalDate');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:entryDate*/
/********************************************************************************************/
/*CREATE TABLE "pangea:entryDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:entryDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:entryDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:entryDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:entryDate','frbr:Endeavour','xsd:date','entry date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:entryDate','pangea:literalDate');*/


/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:date*/
/********************************************************************************************/
CREATE TABLE "pangea:date"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
 text_object text NOT NULL, 
  CONSTRAINT "pangea:date_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:date_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:date" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:date','frbr:Endeavour','xsd:date','date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:date','pangea:literalDate');

/*trigger para rellenar la columna text_object*/
CREATE TRIGGER set_text_object BEFORE INSERT OR UPDATE
   ON "pangea:date" FOR EACH ROW
   EXECUTE PROCEDURE public.set_text_object();

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:startDate*/
/********************************************************************************************/
CREATE TABLE "pangea:startDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:startDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:startDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:startDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:startDate','frbr:Core','xsd:date','start date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:startDate','pangea:literalDate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:endDate*/
/********************************************************************************************/
CREATE TABLE "pangea:endDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:endDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:endDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:endDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:endDate','frbr:Core','xsd:date','end date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:endDate','pangea:literalDate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:currentDate*/
/********************************************************************************************/
CREATE TABLE "pangea:currentDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:currentDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:currentDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:currentDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:currentDate','frbr:Core','xsd:date','current date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:currentDate','pangea:literalDate');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalDate/pangea:instantDate*/
/********************************************************************************************/
CREATE TABLE "pangea:instantDate"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   object text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:date',
  CONSTRAINT "pangea:instantDate_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:instantDate_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalDate")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:instantDate" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:instantDate','frbr:Core','xsd:date','instant date');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:instantDate','pangea:literalDate');

/********************************************************************************************/       
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalInteger*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:literalInteger"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  datatype character varying(50) DEFAULT 'xsd:integer',
  CONSTRAINT "pangea:literalInteger_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:literalInteger_idkey" UNIQUE (id)
)
INHERITS ("pangea:DatatypeProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:literalInteger" OWNER TO pangea;

/*insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:literalInteger','frbr:Core','xsd:integer','integer');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:literalInteger','pangea:DatatypeProperty');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalInteger/pangea:pages*/
/********************************************************************************************/
CREATE TABLE "pangea:pages"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:integer',
  CONSTRAINT "pangea:pages_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:pages_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalInteger")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:pages" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:pages','frbr:Manifestation','xsd:integer','pages');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:pages','pangea:literalInteger');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalInteger/pangea:year*/  /*lo de esta tabla se unió también en strDate */
/********************************************************************************************/
/*CREATE TABLE "pangea:year"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:integer',
  CONSTRAINT "pangea:year_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:year_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalInteger", "pangea:bareNecessities")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:year" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:year','frbr:Manifestation','xsd:integer', 'year');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:year','pangea:literalInteger');*/

/********************************************************************************************/       
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:literalFloat"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
  datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:literalFloat_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:literalFloat_idkey" UNIQUE (id)
)
INHERITS ("pangea:DatatypeProperty")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:literalFloat" OWNER TO pangea;

/*insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:literalFloat','frbr:Core','xsd:float','float');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:literalFloat','pangea:DatatypeProperty');*/

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:height*/
/********************************************************************************************/
CREATE TABLE "pangea:height"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:height_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:height_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalFloat")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:height" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:height','frbr:Manifestation','xsd:float','height');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:height','pangea:literalFloat');


/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:volume*/
/********************************************************************************************/
CREATE TABLE "pangea:volume"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:volume_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:volume_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalFloat")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:volume" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:volume','frbr:Manifestation','xsd:float','volume');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:volume','pangea:literalFloat');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:weight*/
/********************************************************************************************/
CREATE TABLE "pangea:weight"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:weight_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:weight_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalFloat")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:weight" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:weight','frbr:Manifestation','xsd:float','weight');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:weight','pangea:literalFloat');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:length*/
/********************************************************************************************/
CREATE TABLE "pangea:length"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:length_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:length_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalFloat")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:length" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:length','frbr:Manifestation','xsd:float','length');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:length','pangea:literalFloat');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:price*/
/********************************************************************************************/
CREATE TABLE "pangea:price"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:price_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:price_idkey" UNIQUE (id)
)
INHERITS ("pangea:literalFloat")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:price" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:price','frbr:Item','xsd:float','price');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:price','pangea:literalFloat');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:price/pangea:priceCuc*/
/********************************************************************************************/
CREATE TABLE "pangea:priceCuc"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:priceCuc_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:priceCuc_idkey" UNIQUE (id)
)
INHERITS ("pangea:price")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:priceCuc" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:priceCuc','frbr:Item','xsd:float','price cuc');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:priceCuc','pangea:price');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:price/pangea:priceMn*/
/********************************************************************************************/
CREATE TABLE "pangea:priceMn"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:priceMn_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:priceMn_idkey" UNIQUE (id)
)
INHERITS ("pangea:price")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:priceMn" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:priceMn','frbr:Item','xsd:float','price mn');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:priceMn','pangea:price');

/********************************************************************************************/
/*pangea/pangea:Property/pangea:DatatypeProperty/pangea:literalFloat/pangea:price/pangea:priceUsd*/
/********************************************************************************************/
CREATE TABLE "pangea:priceUsd"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- Inherited:   subject integer NOT NULL,
-- Inherited:   "object" text NOT NULL,
-- Inherited:   datatype character varying(50) DEFAULT 'xsd:float',
  CONSTRAINT "pangea:priceUsd_pkey" PRIMARY KEY (subject,object),
  CONSTRAINT "pangea:priceUsd_idkey" UNIQUE (id)
)
INHERITS ("pangea:price")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:priceUsd" OWNER TO pangea;

insert into "owl:DatatypeProperty" (uri, domain, range, label) values ('pangea:priceUsd','frbr:Item','xsd:float','price usd');
insert into "rdf:subPropertyOf" (subject,object) values ('pangea:priceUsd','pangea:price');


/***************************************************/
/*FIN DE LAS TABLAS DE LAS PROPIEDADES*/
/***************************************************/

/***************************************************/
/*INICIO DE LAS TABLAS DE LAS ENTIDADES*/
/***************************************************/

/********************************************************************************************/       
/*pangea/pangea:Class*/
/********************************************************************************************/ 
CREATE TABLE "pangea:Class"
(
-- Inherited:   integer text DEFAULT nextval('system_object_id_seq'::regclass),  
  CONSTRAINT "pangea:Class_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Class" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Class','Class');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Class','pangea');

/********************************************************************************************/       
/*pangea/pangea:Class/foaf:Image*/ 
/********************************************************************************************/ 
CREATE TABLE "foaf:Image"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "foaf:Image_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "foaf:Image" OWNER TO pangea;

/********************************************************************************************/       
/*pangea/pangea:Class/skos:ConceptScheme*/ 
/********************************************************************************************/ 
CREATE TABLE "skos:ConceptScheme"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "skos:ConceptScheme_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "skos:ConceptScheme" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('skos:ConceptScheme','ConceptScheme');
insert into "rdf:subClassOf" (subject,object) values ('skos:ConceptScheme','pangea:Class');

/********************************************************************************************/       
/*pangea/pangea:Class/owl:DataType*/
/********************************************************************************************/ 
CREATE TABLE "owl:DataType"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),  
  CONSTRAINT "owl:DataType_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "owl:DataType" OWNER TO pangea;

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:DescriptorEntity"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:DescriptorEntity_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:DescriptorEntity" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:DescriptorEntity','DescriptorEntity');
insert into "rdf:subClassOf" (subject,object) values ('pangea:DescriptorEntity','pangea:Class');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:State*/ 
/********************************************************************************************/ 
/*CREATE TABLE "pangea:State"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:State_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:State" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:State','State');
insert into "rdf:subClassOf" (subject,object) values ('pangea:State','pangea:DescriptorEntity');*/

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Image*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Image"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:Image_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Image" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Image','Image');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Image','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Subject*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Subject"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Subject_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Subject" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Subject','Subject');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Subject','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Shape*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Shape"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Shape_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Shape" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Shape','Shape');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Shape','pangea:DescriptorEntity');


/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Collection*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Collection"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Collection_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Collection" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Collection','Collection');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Collection','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Availability*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Availability"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Availability_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Availability" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Availability','Availability');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Availability','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:AdquisitionWay*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:AdquisitionWay"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:AdquisitionWay_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:AdquisitionWay" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:AdquisitionWay','Adquisition Way');
insert into "rdf:subClassOf" (subject,object) values ('pangea:AdquisitionWay','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Language*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Language"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Language_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Language" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Language','Language');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Language','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Color*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Color"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Color_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Color" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Color','Color');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Color','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Form*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Form"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:Form_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:DescriptorEntity")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Form" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Form','Form');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Form','pangea:DescriptorEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Form/pangea:DocumentType*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:DocumentType"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
-- newValue boolean DEFAULT false,
  CONSTRAINT "pangea:DocumentType_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Form")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:DocumentType" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:DocumentType','Document Type');
insert into "rdf:subClassOf" (subject,object) values ('pangea:DocumentType','pangea:Form');

/********************************************************************************************/       
/*pangea/pangea:Class/pangea:DescriptorEntity/pangea:Form/pangea:Typology*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Typology"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:Typology_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Form")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Typology" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Typology','Typology');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Typology','pangea:Form');

/********************************************************************************************/       
/*pangea/pangea:Class/skosxl:Label*/ 
/********************************************************************************************/ 
CREATE TABLE "skosxl:Label"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "skosxl:Label_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "skosxl:Label" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('skosxl:Label','Label');
insert into "rdf:subClassOf" (subject,object) values ('skosxl:Label','pangea:Class');
/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Core"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Core_pkey" PRIMARY KEY (id)
)
INHERITS ("pangea:Class")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Core" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Core','Core');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Core','pangea:Class');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Endeavour"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Endeavour_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Core")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Endeavour" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Endeavour','Endeavour');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Endeavour','frbr:Core');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Work*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Work"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Work_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Endeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Work" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Work','Work');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Work','frbr:Endeavour');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Expression"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Expression_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Endeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Expression" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Expression','Expression');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Expression','frbr:Endeavour');


/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Data*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Data"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Data_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Data" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Data','Data');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Data','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Image*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Image"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Image_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Image" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Image','Image');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Image','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Text*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Text"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Text_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Text" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Text','Text');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Text','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Sound*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Sound"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Sound_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Sound" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Sound','Sound');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Sound','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Performance*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Performance"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Performance_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Performance" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Performance','Performance');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Performance','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/MovingImage*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:MovingImage"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:MovingImage_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:MovingImage" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:MovingImage','MovingImage');
insert into "rdf:subClassOf" (subject,object) values ('frbr:MovingImage','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Serial*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Serial"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:Serial_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Serial" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Serial','Serial');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Serial','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Expression/Group*/ 
/********************************************************************************************/ 
CREATE TABLE "pangea:Group"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "pangea:Group_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Expression")
WITH (OIDS=TRUE);
ALTER TABLE "pangea:Group" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('pangea:Group','Group');
insert into "rdf:subClassOf" (subject,object) values ('pangea:Group','frbr:Expression');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Manifestation*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Manifestation"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Manifestation_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Endeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Manifestation" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Manifestation','Manifestation');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Manifestation','frbr:Endeavour');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Endeavour/frbr:Item*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Item"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Item_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Endeavour")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Item" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Item','Item');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Item','frbr:Endeavour');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Subject*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Subject"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
   CONSTRAINT "frbr:Subject_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Core")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Subject" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Subject','Subject');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Subject','frbr:Core');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Subject/frbr:Object*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Object"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Object_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Subject")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Object" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Object','Object');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Object','frbr:Subject');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Subject/frbr:Concept*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Concept"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Concept_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Subject")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Concept" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Concept','Concept');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Concept','frbr:Subject');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Subject/frbr:Place*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Place"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Place_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Subject")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Place" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Place','Place');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Place','frbr:Subject');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:Subject/frbr:Event*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Event"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Event_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Subject")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Event" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Event','Event');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Event','frbr:Subject');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:ResponsibleEntity*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:ResponsibleEntity"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:ResponsibleEntity_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:Core")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:ResponsibleEntity" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:ResponsibleEntity','ResponsibleEntity');
insert into "rdf:subClassOf" (subject,object) values ('frbr:ResponsibleEntity','frbr:Core');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:ResponsibleEntity/frbr:Person*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Person"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Person_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:ResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Person" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Person', 'Person');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Person','frbr:ResponsibleEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:ResponsibleEntity/frbr:CorporateBody*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:CorporateBody"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:CorporateBody_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:ResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:CorporateBody" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:CorporateBody', 'CorporateBody');
insert into "rdf:subClassOf" (subject,object) values ('frbr:CorporateBody','frbr:ResponsibleEntity');

/********************************************************************************************/       
/*pangea/pangea:Class/frbr:Core/frbr:ResponsibleEntity/frbr:Family*/ 
/********************************************************************************************/ 
CREATE TABLE "frbr:Family"
(
-- Inherited:   id integer DEFAULT nextval('system_object_id_seq'::regclass),
  CONSTRAINT "frbr:Family_pkey" PRIMARY KEY (id)
)
INHERITS ("frbr:ResponsibleEntity")
WITH (OIDS=TRUE);
ALTER TABLE "frbr:Family" OWNER TO pangea;

insert into "owl:class"(uri, label) values ('frbr:Family','Family');
insert into "rdf:subClassOf" (subject,object) values ('frbr:Family','frbr:ResponsibleEntity');

/**********************************************************************************************/
/*FIN DE LAS TABLAS DE LAS ENTIDADES*/
/**********************************************************************************************/

/********************************************************************************************/
/*PersistencyQuery*/
/********************************************************************************************/
 /*
ALTER TABLE "Triples" DROP CONSTRAINT "Triples_PK";
ALTER TABLE "Triples" DROP CONSTRAINT "Triples_FK";

DROP TABLE "Triples";

ALTER TABLE "PersistenQuery" DROP CONSTRAINT "PersistenQuery_PK";

DROP TABLE "PersistenQuery";*/

/******************************************************************************************/
/*bNodes*/
/*******************************************************************************************/

CREATE TABLE "bNodes"
(
   id integer NOT NULL,
   "idBnode" text NOT NULL,
   CONSTRAINT "bNodes_pkey" PRIMARY KEY (id)
)
INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE "bNodes" OWNER TO pangea;

/********************************************************************************************/
/*persistenQueries*/
/********************************************************************************************/

CREATE TABLE "persistenQueries"
(
-- Inherited:   id integer NOT NULL DEFAULT nextval('system_object_id_seq'::regclass),
   "text" text NOT NULL,
   timetolive timestamp without time zone,
   "cluster" text,
   "plainText" text,
   status smallint NOT NULL DEFAULT 2,
   tokens text,
   "filterCloud" text,
   id bigserial NOT NULL,
   CONSTRAINT "persistenQueries_pkey" PRIMARY KEY (id)
)
--INHERITS (pangea)
WITH (OIDS=TRUE);
ALTER TABLE "persistenQueries" OWNER TO pangea;
GRANT ALL ON TABLE "persistenQueries" TO pangea;
GRANT ALL ON TABLE "persistenQueries" TO postgres;
GRANT ALL ON TABLE "persistenQueries" TO public;


/********************************************************************************************/
/*triples*/
/********************************************************************************************/
/*CREATE TABLE "persistenTriples"
(
  idquery text NOT NULL,
  subject text NOT NULL,
  predicate text NOT NULL,
  "type" text NOT NULL,
  "value" text NOT NULL,
  datatype text NOT NULL,
  lang text NOT NULL,
  CONSTRAINT "persistenTriples_FK" FOREIGN KEY (idquery)
      REFERENCES "persistenQueries" (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE CASCADE
)
WITH (OIDS=FALSE);
ALTER TABLE "persistenTriples" OWNER TO pangea;*/

CREATE TABLE "persistenTriples"
(
  idquery integer NOT NULL,
  triple text NOT NULL,
  CONSTRAINT "persistenTriples_FK" FOREIGN KEY (idquery)
      REFERENCES "persistenQueries" (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE CASCADE
)
WITH (OIDS=FALSE);
ALTER TABLE "persistenTriples" OWNER TO pangea;

/********************************************************************************************/
/*ambiguous*/
/********************************************************************************************/
CREATE TABLE "ambiguous"
(
   id integer DEFAULT nextval('system_object_id_seq'::regclass),
   id_concept integer NOT NULL,
   concept text NOT NULL,
   parent text NOT NULL,

  CONSTRAINT "ambiguous_pkey" PRIMARY KEY (id_concept,concept,parent),
  CONSTRAINT "ambiguous_idkey" UNIQUE (id)
)

WITH (OIDS=FALSE);
ALTER TABLE "ambiguous" OWNER TO pangea;

/********************************************************************************************/
/*queryLogs*/    /*esta es de roman*/
/********************************************************************************************/
CREATE TABLE "queryLogs"
(
  "log" text,
  "logDate" timestamp with time zone DEFAULT now()
)
WITH (
  OIDS=FALSE
);
ALTER TABLE "queryLogs" OWNER TO pangea;

/***********************************************************************************************/
/*MAP_WEB_DOCS*/ /*tabla de hendrix para meter las imagenes*/
/**********************************************************************************************/
CREATE TABLE "MAP_WEB_DOCS"
(
  resource text NOT NULL,
  url text,
  mime text,
  CONSTRAINT "webDoc_Pk" PRIMARY KEY (resource)
)
WITH (OIDS=TRUE);
ALTER TABLE "MAP_WEB_DOCS" OWNER TO pangea;


/**********************************************************************************************/
/*Insercion de Images por lotes*/ /*de hendrix*/
/**********************************************************************************************/
CREATE OR REPLACE FUNCTION InsertImgLote(image_url text, image_lote text[], item_owner text)
RETURNS VOID AS
$BODY$

DECLARE
image_id text;
mn INTEGER := array_lower(image_lote, 1);
mx INTEGER := array_upper(image_lote, 1);

BEGIN
	IF image_url IS NOT NULL THEN
		image_id := nextval('system_object_id_seq');

		EXECUTE 'INSERT INTO "foaf:Image" (id) VALUES(' 
		||image_id
		||')';

		EXECUTE 'INSERT INTO "MAP_WEB_DOCS" (resource, url) VALUES(' 
		||image_id
		||', '
		||quote_literal(image_url)
		||')';

		EXECUTE 'INSERT INTO "foaf:depicts" (subject, object) VALUES('
			||image_id
			||', '
			||item_owner
			||')';
	END IF;

	FOR t IN mn..mx LOOP
	
		BEGIN
			image_id := nextval('system_object_id_seq');

			EXECUTE 'INSERT INTO "foaf:Image" (id) VALUES(' 
			||image_id
			||')';

			EXECUTE 'INSERT INTO "MAP_WEB_DOCS" (resource, url) VALUES(' 
			||image_id
			||', '
			||quote_literal(image_lote[t])
			||')';
			
			EXECUTE 'INSERT INTO "foaf:depiction" (subject, object) VALUES('
				||image_id
				||', '
				||item_owner
				||')';
		END;
	END LOOP;
END;

$BODY$
LANGUAGE plpgsql VOLATILE
COST 100;
ALTER FUNCTION InsertImgLote(image_url text, image_lote text[], item_owner text) OWNER TO pangea;

/********************************************************************************************/
/*Insercion de una Imagen puede ser como preferida o no*/ /*de hendrix*/
/*********************************************************************************************/
CREATE OR REPLACE FUNCTION InsertImg(image_url text, item_owner text, is_first boolean)
RETURNS VOID AS
$BODY$

DECLARE
image_id text;
table_name VARCHAR;

BEGIN

image_id := nextval('system_object_id_seq');

EXECUTE 'INSERT INTO "foaf:Image" (id) VALUES(' 
||image_id
||')';

EXECUTE 'INSERT INTO "MAP_WEB_DOCS" (resource, url) VALUES(' 
||image_id
||', '
||quote_literal(image_url)
||')';

IF is_first THEN 
	BEGIN
		table_name := 'foaf:depicts';
	END;
ELSE
	BEGIN
		table_name := 'foaf:depiction';
	END;
END IF;
	
EXECUTE 'INSERT INTO '
	||quote_ident(table_name) 
	||'(subject, object) VALUES('
	||image_id
	||', '
	||item_owner
	||')';

END;

$BODY$
LANGUAGE plpgsql VOLATILE
COST 100;
ALTER FUNCTION InsertImg(image_url text, item_owner text, is_first boolean) OWNER TO pangea;

/********************************************************************************************/
/*matviews*/  /*en esta tabla se va a llevar el control de todas las vista materializadas que hay, de momento hay una sola*/
/********************************************************************************************/

CREATE TABLE matviews (
  mv_name NAME NOT NULL PRIMARY KEY,
  v_name NAME NOT NULL,
  last_refresh TIMESTAMP WITH TIME ZONE
);
ALTER TABLE "matviews" OWNER TO pangea;

/*********************************************************************************************/
/*functions/create_matview*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION create_matview(name, name)
  RETURNS void AS
$BODY$
 DECLARE
     matview ALIAS FOR $1;
     view_name ALIAS FOR $2;
     entry matviews%ROWTYPE;
 BEGIN
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
     IF FOUND THEN
         RAISE EXCEPTION 'Materialized view ''%'' already exists.',
           matview;
     END IF;
 
     EXECUTE 'REVOKE ALL ON ' || view_name || ' FROM PUBLIC'; 
 
     EXECUTE 'GRANT SELECT ON ' || view_name || ' TO PUBLIC';
 
     EXECUTE 'CREATE TABLE ' || matview || ' AS SELECT * FROM ' || view_name;
 
     EXECUTE 'REVOKE ALL ON ' || matview || ' FROM PUBLIC';
 
     EXECUTE 'GRANT SELECT ON ' || matview || ' TO PUBLIC';
 
     INSERT INTO matviews (mv_name, v_name, last_refresh)
       VALUES (matview, view_name, CURRENT_TIMESTAMP); 
     
     RETURN;
 END
 $BODY$
  LANGUAGE plpgsql VOLATILE SECURITY DEFINER
  COST 100;
ALTER FUNCTION create_matview(name, name) OWNER TO pangea;

/*********************************************************************************************/
/*functions/drop_matview*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION drop_matview(name)
  RETURNS void AS
$BODY$
 DECLARE
     matview ALIAS FOR $1;
     entry matviews%ROWTYPE;
 BEGIN
 
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
     IF NOT FOUND THEN
         RAISE EXCEPTION 'Materialized view % does not exist.', matview;
     END IF;
 
     EXECUTE 'DROP TABLE ' || matview;
     DELETE FROM matviews WHERE mv_name=matview;
 
     RETURN;
 END
 $BODY$
  LANGUAGE plpgsql VOLATILE SECURITY DEFINER
  COST 100;
ALTER FUNCTION drop_matview(name) OWNER TO pangea;

/*********************************************************************************************/
/*functions/refresh_matview*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION refresh_matview(name)
  RETURNS void AS
$BODY$
 DECLARE 
     matview ALIAS FOR $1;
     entry matviews%ROWTYPE;
 BEGIN
 
     SELECT * INTO entry FROM matviews WHERE mv_name = matview;
 
     IF NOT FOUND THEN
         RAISE EXCEPTION 'Materialized view % does not exist.', matview;
    END IF;

    EXECUTE 'DELETE FROM ' || matview;
    EXECUTE 'INSERT INTO ' || matview
        || ' SELECT * FROM ' || entry.v_name;

    UPDATE matviews
        SET last_refresh=CURRENT_TIMESTAMP
        WHERE mv_name=matview;

    RETURN;
END
$BODY$
  LANGUAGE plpgsql VOLATILE SECURITY DEFINER
  COST 100;
ALTER FUNCTION refresh_matview(name) OWNER TO pangea;

/*********************************************************************************************/
/*functions/update_statistics*/
/********************************************************************************************/

/*CREATE OR REPLACE FUNCTION update_statistics()
  RETURNS void AS
$BODY$
 DECLARE
    
 BEGIN
	DROP INDEX  IF EXISTS  itemid_idx;
	SELECT refresh_matview('items_for_collection_mv');
	CREATE INDEX itemid_idx ON items_for_collection_mv(itemid);
 END
 $BODY$
  LANGUAGE plpgsql VOLATILE SECURITY DEFINER
  COST 100;*/
  
 CREATE OR REPLACE FUNCTION update_statistics()
  RETURNS void AS
$BODY$
  DROP INDEX  IF EXISTS  itemid_idx;
  SELECT refresh_matview('items_for_collection_mv');
  CREATE INDEX itemid_idx ON items_for_collection_mv(itemid);
$BODY$
  LANGUAGE SQL VOLATILE SECURITY DEFINER
  COST 100;
  
ALTER FUNCTION update_statistics() OWNER TO pangea;


CREATE OR REPLACE FUNCTION update_statistics2()
  RETURNS void AS
$BODY$
    SELECT drop_matview('items_for_collection_mv');
	SELECT create_matview('items_for_collection_mv', 'items_for_collection_v');
	CREATE INDEX itemid_idx ON items_for_collection_mv(itemid);

 $BODY$
  LANGUAGE SQL VOLATILE SECURITY DEFINER
  COST 100;
  
 ALTER FUNCTION update_statistics() OWNER TO pangea;

/********************************************************************************************/      
/*functions/sp_ascii*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION sp_ascii(character varying)
  RETURNS text AS
$BODY$
SELECT TRANSLATE
($1,
'��������������������������������������������',
'aaaaaeeeeiiiooooouuuuAAAAAEEEEIIIOOOOOUUUUcC');
$BODY$
  LANGUAGE 'sql' VOLATILE
  COST 100;
ALTER FUNCTION sp_ascii(character varying) OWNER TO pangea;

/********************************************************************************************/      
/*functions/script*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION script()
  RETURNS void AS
$BODY$
DECLARE
	solve record;
BEGIN
	/*EXECUTE 'SELECT object FROM "owl:inverseOf";'
	INTO solve; 
	raise NOTICE '%', solve;
	
	EXECUTE 'DROP TRIGGER inverse ON '
	||quote_ident(solve)*/

	FOR solve IN EXECUTE 'SELECT object FROM "owl:inverseOf"' LOOP
		EXECUTE 'DROP TRIGGER inverse ON '
		||'"'
		||solve.object
		||'"'
		||';';
	END LOOP;
	
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION script() OWNER TO pangea;

/********************************************************************************************/      
/*functions/getinverse*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION getinverse(tb name)
  RETURNS name AS
$BODY$
DECLARE
inverse name;
BEGIN

	EXECUTE 'SELECT subject from "owl:inverseOf" where object = $1'
	INTO inverse USING tb;
		
	RETURN inverse;
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION getinverse(name) OWNER TO pangea;

/********************************************************************************************/      
/*functions/inversetest*/
/********************************************************************************************/

CREATE OR REPLACE FUNCTION inversetest(tb name, obj text, sub text)
  RETURNS text AS
$BODY$
DECLARE
inverse_table name;
BEGIN
	inverse_table := getinverse(tb);

	IF inverse_table <> NULL THEN		
		RETURN 'NO';
	END IF;	

	EXECUTE 'INSERT INTO ' 
	||quote_ident(inverse_table)
	||'(subject, object) VALUES('
	||obj
	||', '
	||sub
	||')';
	
	RETURN 'SI';
END;

$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100;
ALTER FUNCTION inversetest(name, text, text) OWNER TO pangea;

/********************************************************************************************/      
/*functions/inserttriples*/
/********************************************************************************************/
/*
CREATE OR REPLACE FUNCTION inserttriples(triples triple[])
  RETURNS SETOF triple AS
$BODY$
DECLARE	
	mn integer := array_lower(triples, 1);
	mx integer := array_upper(triples,1);
	m record;
	
BEGIN
	FOR t IN mn..mx LOOP
		BEGIN
			m:=triples[t];
			EXECUTE 'INSERT INTO '
			||quote_ident(triples[t].property)
			||' (subject, object) VALUES('
			||quote_literal(triples[t].subject)
			||', '
			||quote_literal(triples[t].object)
			||')';

			EXCEPTION WHEN unique_violation THEN

			RETURN NEXT m;
		END;
		
	END LOOP;	
END;
$BODY$
  LANGUAGE 'plpgsql' VOLATILE
  COST 100
  ROWS 1000;
ALTER FUNCTION inserttriples(triple[]) OWNER TO pangea;*/

/*****************************************************************************/
/*vista itemsForCollection*/
/*******************************************************************************/
/*
create view itemsForCollection as

 SELECT fo.subject AS itemid, spl.object AS ownername, sal.object AS owneralias, fo.object AS ownerid, fclf.object AS itemtype, ed.object AS entrydate, slf.object AS adquisitionway,
        pp.object AS price, pp.tableoid AS currency, pclf.object AS itemcollection, lf.object AS ownertype
   FROM "frbr:owner" fo
   LEFT JOIN "skos:prefLabel" spl ON fo.object = spl.subject
   LEFT JOIN "skos:altLabel" sal ON fo.object = sal.subject
   LEFT JOIN "pangea:hasSubject" phs ON fo.object = phs.subject
   LEFT JOIN "skosxl:prefLabel" pl ON phs.object = pl.subject
   LEFT JOIN "skosxl:literalForm" lf ON lf.subject = pl.object
   LEFT JOIN "pangea:hasAdquisitionWay" paw ON fo.subject = paw.subject
   LEFT JOIN "skosxl:prefLabel" pfl ON paw.object = pfl.subject
   LEFT JOIN "skosxl:literalForm" slf ON pfl.object = slf.subject
   LEFT JOIN "pangea:price" pp ON fo.subject = pp.subject
   LEFT JOIN "pangea:date" ed ON fo.subject = ed.subject
   LEFT JOIN "pangea:hasCollection" pc ON fo.subject = pc.subject
   LEFT JOIN "skosxl:prefLabel" spfl ON pc.object = spfl.subject
   LEFT JOIN "skosxl:literalForm" pclf ON spfl.object = pclf.subject
   LEFT JOIN "frbr:exemplar" feof ON fo.subject = feof.object
   LEFT JOIN "pangea:hasForm" phf ON feof.subject = phf.subject
   LEFT JOIN "skosxl:prefLabel" fcpl ON phf.object = fcpl.subject
   LEFT JOIN "skosxl:literalForm" fclf ON fcpl.object = fclf.subject
  WHERE length(translate(translate(translate(ed.object, '/'::text, ''::text), '.'::text, ''::text), '-'::text, ''::text)) = 8 AND (translate(translate(translate(ed.object, '/'::text, ''::text), '.'::text, ''::text), '-'::text, ''::text) ~ '^(-)?[0-9]+$'::text) = true;
*/

CREATE OR REPLACE VIEW "items_for_collection_v" AS 
 SELECT fo.subject AS itemid, spl.object AS ownername, sal.object AS alias, fo.object AS ownerid, fclf.object AS itemtype, ed.object AS entrydate, slf.object AS adquisitionway, pp.object AS price, pp.tableoid AS currency, pclf.object AS itemcollection, lf.object AS ownertype
   FROM "frbr:owner" fo
   LEFT JOIN "skos:prefLabel" spl ON fo.object = spl.subject
   LEFT JOIN "skos:altLabel" sal ON fo.object = sal.subject
   LEFT JOIN "pangea:hasSubject" phs ON fo.object = phs.subject
   LEFT JOIN "skosxl:prefLabel" pl ON phs.object = pl.subject
   LEFT JOIN "skosxl:literalForm" lf ON lf.subject = pl.object
   LEFT JOIN "pangea:hasAdquisitionWay" paw ON fo.subject = paw.subject
   LEFT JOIN "skosxl:prefLabel" pfl ON paw.object = pfl.subject
   LEFT JOIN "skosxl:literalForm" slf ON pfl.object = slf.subject
   LEFT JOIN "pangea:price" pp ON fo.subject = pp.subject
   LEFT JOIN "pangea:date" ed ON fo.subject = ed.subject
   LEFT JOIN "pangea:hasCollection" pc ON fo.subject = pc.subject
   LEFT JOIN "skosxl:prefLabel" spfl ON pc.object = spfl.subject
   LEFT JOIN "skosxl:literalForm" pclf ON spfl.object = pclf.subject
   LEFT JOIN "frbr:exemplar" feof ON fo.subject = feof.object
   LEFT JOIN "pangea:hasForm" phf ON feof.subject = phf.subject
   LEFT JOIN "skosxl:prefLabel" fcpl ON phf.object = fcpl.subject
   LEFT JOIN "skosxl:literalForm" fclf ON fcpl.object = fclf.subject
  WHERE length(translate(translate(translate(ed.object, '/'::text, ''::text), '.'::text, ''::text), '-'::text, ''::text)) = 8 AND (translate(translate(translate(ed.object, '/'::text, ''::text), '.'::text, ''::text), '-'::text, ''::text) ~ '^(-)?[0-9]+$'::text) = true;
ALTER TABLE "items_for_collection_v" OWNER TO pangea;
  /*****************************************************************************************/
  /*vistas de roman*/
/*****************************************************************************************/
  
CREATE OR REPLACE VIEW player_total_score_v AS 
  SELECT subtbl.id AS subject, pc.relname AS typeof, lf.object
    FROM "pangea:DescriptorEntity" subtbl
    JOIN "pangea:nomen" pn ON subtbl.id = pn.subject
    JOIN "skosxl:literalForm" lf ON pn.object = lf.subject
    JOIN pg_class pc ON subtbl.tableoid = pc.oid
 UNION 
  SELECT subtbl.id AS subject, pc.relname AS typeof, lf.object
    FROM "frbr:Core" subtbl
    JOIN "pangea:nomen" pn ON subtbl.id = pn.subject
    JOIN "skosxl:literalForm" lf ON pn.object = lf.subject
    JOIN pg_class pc ON subtbl.tableoid = pc.oid
    ORDER BY 3;
ALTER TABLE player_total_score_v OWNER TO pangea;

CREATE OR REPLACE VIEW subject_label_typeof_v AS 
   (SELECT subtbl.id AS subject, pc.relname AS typeof, lf.object, pn.tableoid AS tableoid_v
      FROM "pangea:DescriptorEntity" subtbl
      JOIN "pangea:nomen" pn ON subtbl.id = pn.subject
      JOIN "skosxl:literalForm" lf ON pn.object = lf.subject
      JOIN pg_class pc ON subtbl.tableoid = pc.oid
    UNION 
      SELECT subtbl.id AS subject, pc.relname AS typeof, lf.object, pn.tableoid AS tableoid_v
        FROM "frbr:Core" subtbl
        JOIN "pangea:nomen" pn ON subtbl.id = pn.subject
        JOIN "skosxl:literalForm" lf ON pn.object = lf.subject
        JOIN pg_class pc ON subtbl.tableoid = pc.oid)
    UNION 
     SELECT subtbl.id AS subject, pc.relname AS typeof, rl.object, rl.tableoid AS tableoid_v
       FROM "frbr:Core" subtbl
       JOIN "rdfs:label" rl ON subtbl.id = rl.subject
       JOIN pg_class pc ON subtbl.tableoid = pc.oid
       ORDER BY 3;
ALTER TABLE subject_label_typeof_v OWNER TO pangea;
GRANT ALL ON TABLE subject_label_typeof_v TO pangea;
GRANT SELECT ON TABLE subject_label_typeof_v TO public;

/*CREATE VIEW subject_label_typeof_v AS
SELECT subtbl.numeric_id AS subject, pc.relname AS typeOf, lf.object, 
pn.tableoid AS tableoid_v
FROM "pangea:DescriptorEntity" AS subtbl
  INNER JOIN "pangea:nomen" pn ON(subtbl.id = pn.subject)
  INNER JOIN "skosxl:literalForm" lf ON(pn.object = lf.subject)
  INNER JOIN pg_class pc ON(subtbl.tableoid = pc.oid)
UNION
SELECT subtbl.numeric_id AS subject, pc.relname AS typeOf, lf.object, 
pn.tableoid AS tableoid_v
FROM "frbr:Core" AS subtbl
  INNER JOIN "pangea:nomen" pn ON(subtbl.id = pn.subject)
  INNER JOIN "skosxl:literalForm" lf ON(pn.object = lf.subject)
  INNER JOIN pg_class pc ON(subtbl.tableoid = pc.oid)

UNION

SELECT subtbl.numeric_id AS subject, pc.relname AS typeOf, rl.object, 
rl.tableoid AS tableoid_v
FROM "frbr:Core" AS subtbl
  INNER JOIN "rdfs:label" rl ON(subtbl.id = rl.subject)
  INNER JOIN pg_class pc ON(subtbl.tableoid = pc.oid)
ORDER BY object;

ALTER TABLE subject_label_typeof_v OWNER TO postgres;
GRANT ALL ON TABLE subject_label_typeof_v TO postgres;
GRANT SELECT ON TABLE subject_label_typeof_v TO public;*/
 
/********************************************************************************************/
 /* código para rellenar la tabla donde se materializa la vista de las estadísticas*/
/********************************************************************************************/
SELECT create_matview('items_for_collection_mv', 'items_for_collection_v');
CREATE INDEX itemid_idx ON items_for_collection_mv(itemid);

/********************************************************************************************/
 /* código para rellenar la tabla donde se materializa la vista de roman*/
/********************************************************************************************/
SELECT create_matview('subject_label_typeof_mv', 'subject_label_typeof_v');

/******************************************************************************************/
/*Indices*/
/*****************************************************************************************/
CREATE INDEX skosPrefLabel_onObject_wthBTree_idx ON "skos:prefLabel" 
(object);
CREATE INDEX pangeaObjectProperty_onObject_wthBTree_idx ON 
"pangea:ObjectProperty" (object);
CREATE INDEX pangeaDatatypeProperty_onObject_wthBTree_idx ON 
"pangea:DatatypeProperty" (object);
--CREATE INDEX pangeaProperty_onObject_wthBTree_idx ON "pangea:Property" 
--(object);

CREATE INDEX subject_label_typeof_mv_onEntityID_wthBTree_ordered_idx ON 
subject_label_typeof_mv (subject ASC NULLS LAST);

CREATE INDEX subject_label_typeof_mv_onObject_wthBTree_idx ON 
subject_label_typeof_mv(object);

CREATE INDEX subject_label_typeof_mv_onTypeOf_wthBTree_idx ON 
subject_label_typeof_mv(typeof);

CREATE INDEX subject_label_typeof_mv_onObject_wthGin_idx ON 
subject_label_typeof_mv USING gin (object gin_trgm_ops);



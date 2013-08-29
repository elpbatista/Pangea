-- Statistics view
SELECT refresh_matview('items_for_collection_mv');

-- Entity listing view
DROP INDEX subject_label_typeof_mv_onObject_wthBTree_idx;
DROP INDEX subject_label_typeof_mv_onTypeOf_wthBTree_idx;
DROP INDEX subject_label_typeof_mv_onObject_wthGin_idx;

SELECT refresh_matview('subject_label_typeof_mv');

CREATE INDEX subject_label_typeof_mv_onObject_wthBTree_idx ON subject_label_typeof_mv(object);
CREATE INDEX subject_label_typeof_mv_onTypeOf_wthBTree_idx ON subject_label_typeof_mv(typeof);
CREATE INDEX subject_label_typeof_mv_onObject_wthGin_idx ON subject_label_typeof_mv USING gin (object gin_trgm_ops);

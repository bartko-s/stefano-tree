-- *****************************
--        WITHOUT SCOPE
-- *****************************
CREATE TABLE public.tree_traversal
(
  tree_traversal_id integer NOT NULL DEFAULT nextval('tree_traversal_tree_traversal_id_seq'::regclass),
  name character varying(255),
  lft integer NOT NULL,
  rgt integer NOT NULL,
  parent_id integer,
  level integer,
  CONSTRAINT tree_traversal_pkey PRIMARY KEY (tree_traversal_id),
  CONSTRAINT tree_traversal_parent_id_fkey FOREIGN KEY (parent_id)
      REFERENCES public.tree_traversal (tree_traversal_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)

CREATE INDEX tree_traversal_level
  ON public.tree_traversal
  USING btree
  (level);

CREATE INDEX tree_traversal_lft
  ON public.tree_traversal
  USING btree
  (lft);

CREATE INDEX tree_traversal_parent_id
  ON public.tree_traversal
  USING btree
  (parent_id);

CREATE INDEX tree_traversal_rgt
  ON public.tree_traversal
  USING btree
  (rgt);


-- *****************************
--        WITH SCOPE
-- *****************************
CREATE TABLE public.tree_traversal_with_scope
(
  tree_traversal_id integer NOT NULL DEFAULT nextval('tree_traversal_with_scope_tree_traversal_id_seq'::regclass),
  name character varying(255),
  lft integer NOT NULL,
  rgt integer NOT NULL,
  parent_id integer,
  level integer,
  scope integer NOT NULL,
  CONSTRAINT tree_traversal_with_scope_pkey PRIMARY KEY (tree_traversal_id),
  CONSTRAINT tree_traversal_with_scope_parent_id_fkey FOREIGN KEY (parent_id)
      REFERENCES public.tree_traversal_with_scope (tree_traversal_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
)

CREATE INDEX tree_traversal_with_scope_level
  ON public.tree_traversal_with_scope
  USING btree
  (level);

CREATE INDEX tree_traversal_with_scope_lft
  ON public.tree_traversal_with_scope
  USING btree
  (lft);

CREATE INDEX tree_traversal_with_scope_parent_id
  ON public.tree_traversal_with_scope
  USING btree
  (parent_id);

CREATE INDEX tree_traversal_with_scope_rgt
  ON public.tree_traversal_with_scope
  USING btree
  (rgt);

CREATE INDEX tree_traversal_with_scope_scope
  ON public.tree_traversal_with_scope
  USING btree
  (scope);

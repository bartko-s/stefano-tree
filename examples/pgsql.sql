CREATE TABLE public.categories
(
  id serial NOT NULL,
  name character varying(255),
  lft integer NOT NULL,
  rgt integer NOT NULL,
  parent_id integer,
  level integer,
  group_id integer NOT NULL,
  CONSTRAINT categories_pkey PRIMARY KEY (id),
  CONSTRAINT categories_parent_id_fkey FOREIGN KEY (parent_id)
      REFERENCES public.categories (id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX categories_level
  ON public.categories
  USING btree
  (level);

CREATE INDEX categories_lft
  ON public.categories
  USING btree
  (lft);

CREATE INDEX categories_parent_id
  ON public.categories
  USING btree
  (parent_id);

CREATE INDEX categories_rgt
  ON public.categories
  USING btree
  (rgt);

CREATE INDEX categories_group_id
  ON public.categories
  USING btree
  (group_id);

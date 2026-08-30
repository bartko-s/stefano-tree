CREATE TABLE categories
(
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) DEFAULT NULL,
  lft INT(11) NOT NULL,
  rgt INT(11) NOT NULL,
  parent_id INT(11) DEFAULT NULL,
  level INT(11) DEFAULT NULL,
  group_id INT(11) NOT NULL,
  CONSTRAINT categories_pkey PRIMARY KEY (id),
  CONSTRAINT categories_parent_id_fkey FOREIGN KEY (parent_id)
      REFERENCES categories (id)
      ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX categories_level ON categories (level);
CREATE INDEX categories_lft ON categories (lft);
CREATE INDEX categories_parent_id ON categories (parent_id);
CREATE INDEX categories_rgt ON categories (rgt);
CREATE INDEX categories_group_id ON categories (group_id);
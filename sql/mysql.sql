-- *****************************
--        WITHOUT SCOPE
-- *****************************
CREATE TABLE `tree_traversal` (
  `tree_traversal_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  PRIMARY KEY (`tree_traversal_id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  CONSTRAINT `tree_traversal_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `tree_traversal` (`tree_traversal_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


-- *****************************
--        WITH SCOPE
-- *****************************
CREATE TABLE `tree_traversal_with_scope` (
  `tree_traversal_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `scope` int(11) NOT NULL,
  PRIMARY KEY (`tree_traversal_id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`),
  KEY `lft` (`lft`),
  KEY `rgt` (`rgt`),
  KEY `scope` (`scope`),
  CONSTRAINT `tree_traversal_with_scope_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `tree_traversal_with_scope` (`tree_traversal_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
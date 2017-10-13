# Tree

| Test Status | Code Coverage | Quality | Dependencies |
| :---: | :---: | :---: | :---: |
| [![Test Status](https://secure.travis-ci.org/bartko-s/stefano-tree.png?branch=master)](https://travis-ci.org/bartko-s/stefano-tree) | [![Code Coverage](https://coveralls.io/repos/bartko-s/stefano-tree/badge.png?branch=master)](https://coveralls.io/r/bartko-s/stefano-tree?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/?branch=master) | [![Dependency Status](https://www.versioneye.com/user/projects/53d26035851c5679c9000267/badge.svg?style=flat)](https://www.versioneye.com/user/projects/53d26035851c5679c9000267) |

[Nested Set](https://en.wikipedia.org/wiki/Nested_set_model) implementation for PHP.

[ONLINE DEMO](https://www.tree.stefanbartko.sk)

## Features

 - NestedSet(MPTT - Modified Preorder Tree Traversal)
 - Support scopes (multiple independent tree in one db table)
 - Rebuild broken tree
 - Tested with MySQL and PostgreSQL but should work with any database vendor which support transaction
 - Support Frameworks [Zend Framework 1](https://framework.zend.com/manual/1.12/en/zend.db.html), [Zend Framework 2](https://framework.zend.com/manual/2.4/en/index.html#zend-db), [Doctrine 2 DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/)
 - It is easy to implement support for any framework

## Dependencies
- Stefano Tree has no external dependencies only Php and your framework is required

## Installation

Run following command `composer require stefano/stefano-tree`

## Create Tree Adapter

|        key         |  type  | required | default value | note                               |
| :----------------: | :----: | :------: | :-----------: | :--------------------------------: |
| tableName          | string | yes      |               |                                    |
| idColumnName       | string | yes      |               |                                    |
| leftColumnName     | string | no       | lft           |                                    |
| rightColumnName    | string | no       | rgt           |                                    |
| levelColumnName    | string | no       | level         |                                    |
| parentIdColumnName | string | no       | parent_id     |                                    |
| sequenceName       | string | see note |               | required for PostgreSQL            |
| scopeColumnName    | string | see note |               | if empty scope support is disables |

- Use static factory method
```
$options = array(
    'tableName'    => 'tree_traversal',
    'idColumnName' => 'tree_traversal_id',
    // other options
);

$dbAdapter = Zend2 Db Adapter or Zend1 Db Adapter or Doctrine DBAL Connection

$tree = \StefanoTree\NestedSet::factory($options, $dbAdapter);
```

- or create tree adapter directly
```
$options = new \StefanoTree\NestedSet\Options(array(...);

$dbAdapter = ... supported db adapter ...

$nestedSetAdapter = new \StefanoTree\NestedSet\Adapter\Zend2($options, $dbAdapter);

$tree = new \StefanoTree\NestedSet($nestedSetAdapter);
```

- You can join table. Example is for Zend Framework 2 but it works similar for other supported frameworks.
```
$defaultDbSelect = $nestedSetAdapter->getDefaultDbSelect();

//zend framework select object
//http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.html#join
$defaultDbSelect->join($name, $on, $columns, $type);
$nestedSetAdapter->setDefaultDbSelect($defaultDbSelect);
```

## API

### Creating nodes

- Create root node

```
// create root node. Scope support is disabled
$tree->createRootNode(array());

// create root node. Scope support is enabled
$tree->createRootNode(array(), $scope);
```

- Create new node. You can create new node at 4 different locations.

![placements](./doc/placements.png)

```
$targetNodeId = 10;

$data = array(
    //data
);

$tree->addNodePlacementTop($targetNodeId, $data, $tree::PLACEMENT_CHILD_TOP);
$tree->addNodePlacementChildBottom($targetNodeId, $data, $tree::PLACEMENT_CHILD_BOTTOM);
$tree->addNodePlacementTop($targetNodeId, $data, $tree::PLACEMENT_TOP);
$tree->addNodePlacementBottom($targetNodeId, $data, $tree::PLACEMENT_BOTTOM);
```

### Update Node

```
$targetNodeId = 10;

$data = array(
    //data
);

$tree->updateNode($targetNodeId, $data);
```

### Move node

- You can move node at 4 different locations.

![placements](./doc/placements.png)

```
$sourceNodeId = 15;
$targetNodeId = 10;

$tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_CHILD_TOP);
$tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_CHILD_BOTTOM);
$tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_TOP);
$tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_BOTTOM);
```

### Delete node or branch

```
$nodeId = 15;

$tree->deleteBranch($nodeId);
```

### Getting nodes

- Get all children

```
$nodeId = 15;
$tree->getChildren($nodeId);
```

- Get all descendants

```
$nodeId = 15;

//all descedants
$tree->getDescendants($nodeId);

//exclude node $nodeId from result
$tree->getDescendants($nodeId, 1);

//exclude first two levels from result
$tree->getDescendants($nodeId, 2);

//get four levels
$tree->getDescendants($nodeId, 0, 4);
```

- Exclude branche from  result

```
$nodeId = 15;
$excludeBranche = 22;
$tree->getDescendants($nodeId, 0, null, $excludeBranche);
```

- Get Path

```
$nodeId = 15;

//full path
$tree->getPath($nodeId);

//exclude node $nodeId from result
$tree->getPath($nodeId, 1);

//exclude first two levels from result
$tree->getPath($nodeId, 2);

//exclude last node
$tree->getPath($nodeId, 0, true);
```

### Validation and Rebuild broken tree

- Check if tree is valid

```
$tree->isValid($rootNodeId);
```

- Rebuild broken tree

```
$tree->rebuild($rootNodeId);
```

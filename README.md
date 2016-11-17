# Tree

| Test Status | Code Coverage | Quality | Dependencies |
| :---: | :---: | :---: | :---: |
| [![Test Status](https://secure.travis-ci.org/bartko-s/stefano-tree.png?branch=master)](https://travis-ci.org/bartko-s/stefano-tree) | [![Code Coverage](https://coveralls.io/repos/bartko-s/stefano-tree/badge.png?branch=master)](https://coveralls.io/r/bartko-s/stefano-tree?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/?branch=master) | [![Dependency Status](https://www.versioneye.com/user/projects/53d26035851c5679c9000267/badge.svg?style=flat)](https://www.versioneye.com/user/projects/53d26035851c5679c9000267) |

[Nested Set](https://en.wikipedia.org/wiki/Nested_set_model) implementation for PHP.

ONLINE DEMO (soon)

## Features

 - NestedSet(MPTT - Modified Preorder Tree Traversal)
 - Support scopes (multiple independent tree in one db table)
 - Rebuild broken tree
 - Tested with MySQL and PostgreSQL but should work with any database vendor which support transaction
 - Support Frameworks [Zend Framework 1](https://framework.zend.com/manual/1.12/en/zend.db.html), [Zend Framework 2](https://framework.zend.com/manual/2.4/en/index.html#zend-db), [Doctrine 2 DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/), [Stefano Db](https://github.com/bartko-s/stefano-db)
 - It is easy to implement support for any framework

## Dependencies
- Stefano Tree has no external dependencies only Php and your framework is required

## Installation

1. Add following line to your composer.json file "stefano/stefano-tree": "~2.0.0"
2. Create db scheme [example db scheme](https://github.com/bartko-s/stefano-tree/tree/master/sql). Name of table, columns are fully customizable.
3. Create tree adapter

- Use static factory method
```
$options = new \StefanoTree\NestedSet\Options(array(
    'tableName'    => 'tree_traversal', //required
    'idColumnName' => 'tree_traversal_id', //required
    'leftColumnName' => 'lft', //optional (default lft)
    'rightColumnName' => 'rgt', //optional (default rgt)
    'levelColumnName' => 'level', //optional (default level)
    'parentIdColumnName' => 'parent_id', //optional (default parent_id)
    'sequenceName' => 'sequence_name_seq', //required for PostgreSQL
    'scopeColumnName' => 'scope', //optional
));

$dbAdapter = ... supported db adapter ...

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

- Create new node

```
$targetNodeId = 10;

$data = array(
    //data
);

$tree->addNodePlacementBottom($targetNodeId, $data);
$tree->addNodePlacementTop($targetNodeId, $data);
$tree->addNodePlacementChildBottom($targetNodeId, $data);
$tree->addNodePlacementTop($targetNodeId, $data);
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

```
$sourceNodeId = 15;
$targetNodeId = 10;

$tree->moveNodePlacementBottom($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementTop($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementChildBottom($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementChildTop($sourceNodeId, $targetNodeId);
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

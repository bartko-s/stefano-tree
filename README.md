# Tree

| Test Status | Code Coverage | Quality | Dependencies |
| :---: | :---: | :---: | :---: |
| [![Test Status](https://secure.travis-ci.org/bartko-s/stefano-tree.png?branch=master)](https://travis-ci.org/bartko-s/stefano-tree) | [![Code Coverage](https://coveralls.io/repos/bartko-s/stefano-tree/badge.png?branch=master)](https://coveralls.io/r/bartko-s/stefano-tree?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/?branch=master) | [![Dependency Status](https://www.versioneye.com/user/projects/53d26035851c5679c9000267/badge.svg?style=flat)](https://www.versioneye.com/user/projects/53d26035851c5679c9000267) |

[Nested Set](https://en.wikipedia.org/wiki/Nested_set_model) implementation for PHP.

[![Live demo](./doc/live-demo.jpg)](https://www.tree.stefanbartko.sk)

## Features

 - NestedSet(MPTT - Modified Pre-order Tree Traversal)
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
| :----------------- | :----: | :------: | :------------ | :--------------------------------- |
| tableName          | string | yes      |               |                                    |
| idColumnName       | string | yes      |               |                                    |
| leftColumnName     | string | no       | lft           |                                    |
| rightColumnName    | string | no       | rgt           |                                    |
| levelColumnName    | string | no       | level         |                                    |
| parentIdColumnName | string | no       | parent_id     |                                    |
| sequenceName       | string | see note |               | required for PostgreSQL            |
| scopeColumnName    | string | see note |               | if empty scope support is disabled |

- Use static factory method
```
use \StefanoTree\NestedSet;

$options = array(
    'tableName'    => 'tree_traversal',
    'idColumnName' => 'tree_traversal_id',
    // other options
);

$dbAdapter = Zend2 Db Adapter or Zend1 Db Adapter or Doctrine DBAL Connection

$tree = NestedSet::factory($options, $dbAdapter);
```

- or create tree adapter directly
```
use \StefanoTree\NestedSet;
use \StefanoTree\NestedSet\Options;
use \StefanoTree\NestedSet\Adapter\Zend2 as Zend2TreeDbAdapter;

$options = new Options(array(...);
$dbAdapter = ... supported db adapter ...
$nestedSetAdapter = new Zend2TreeDbAdapter($options, $dbAdapter);
$tree = new NestedSet($nestedSetAdapter);
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
use StefanoTree\Exception\ValidationException;

try {
    $data = array(
        // values
    );
    
    // create root node.
    $rootNodeId = $tree->createRootNode($data);
    
    // create root node. Second param "$scope" is required only if scope support is enabled.
    $rootNodeId = $tree->createRootNode($data, $scope);    
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}    
```

- Create new node. You can create new node at 4 different locations.

![placements](./doc/placements.png)

```
use StefanoTree\Exception\ValidationException;

try {
    $targetNodeId = 10;
    
    $data = array(
        // values
    );

    $nodeId = $tree->addNodePlacementTop($targetNodeId, $data, $tree::PLACEMENT_CHILD_TOP);
    $nodeId = $tree->addNodePlacementChildBottom($targetNodeId, $data, $tree::PLACEMENT_CHILD_BOTTOM);
    $nodeId = $tree->addNodePlacementTop($targetNodeId, $data, $tree::PLACEMENT_TOP);
    $nodeId = $tree->addNodePlacementBottom($targetNodeId, $data, $tree::PLACEMENT_BOTTOM);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}    
```

### Update Node

```
use StefanoTree\Exception\ValidationException;

try {
    $targetNodeId = 10;
    
    $data = array(
        // values
    );
    
    $tree->updateNode($targetNodeId, $data);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}    
```

### Move node

- You can move node at 4 different locations.

![placements](./doc/placements.png)

```
use StefanoTree\Exception\ValidationException;

try {
    $sourceNodeId = 15;
    $targetNodeId = 10;
    
    $tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_CHILD_TOP);
    $tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_CHILD_BOTTOM);
    $tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_TOP);
    $tree->moveNode($sourceNodeId, $targetNodeId, $tree::PLACEMENT_BOTTOM);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}        
```

### Delete node or branch

```
use StefanoTree\Exception\ValidationException;

try {
    $nodeId = 15;
    
    $tree->deleteBranch($nodeId);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}    
```

### Getting nodes

- Get descendants

```
$nodeId = 15;

// all descendants
$tree->getDescendantsQueryBuilder()
     ->get($nodeId);
     
// only children     
$tree->getDescendantsQueryBuilder()
     ->excludeFirstNLevel(1)
     ->levelLimit(1)
     ->get($nodeId);

// exclude first level($nodeId) from result
$tree->getDescendants()
     ->excludeFirstNLevel(1)
     ->get($nodeId);

// exclude first two levels from result
$tree->getDescendantsQueryBuilder()
     ->excludeFirstNLevel(2)
     ->get($nodeId);

// return first 4 level
$tree->getDescendantsQueryBuilder()
     ->levelLimit(4)
     ->get($nodeId);

// exclude branch from  result
$tree->getDescendantsQueryBuilder()
     ->excludeBranch(22)
     ->get($nodeId);
```

- Get Ancestors

```
$nodeId = 15;

// get all
$tree->getAncestorsQueryBuilder()
     ->get($nodeId);

// exclude last node($nodeId) from result
$tree->getAncestorsQueryBuilder()
     ->excludeLastNLevel(1)
     ->getPath($nodeId);

// exclude first two levels from result
$tree->getAncestorsQueryBuilder()
     ->excludeFistNLevel(2)
     ->get($nodeId);
```

### Validation and Rebuild broken tree

- Check if tree is valid

```
use StefanoTree\Exception\ValidationException;

try {
    $satus = $tree->isValid($rootNodeId);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}    
```

- Rebuild broken tree

```
use StefanoTree\Exception\ValidationException;

try {
    $tree->rebuild($rootNodeId);
} catch (ValidationException $e) {
    $errorMessage = $e->getMessage();
}     
```

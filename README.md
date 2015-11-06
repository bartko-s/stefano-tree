Tree
====

| Test Status | Code Coverage | Quality | Dependencies |
| :---: | :---: | :---: | :---: |
| [![Test Status](https://secure.travis-ci.org/bartko-s/stefano-tree.png?branch=master)](https://travis-ci.org/bartko-s/stefano-tree) | [![Code Coverage](https://coveralls.io/repos/bartko-s/stefano-tree/badge.png?branch=master)](https://coveralls.io/r/bartko-s/stefano-tree?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bartko-s/stefano-tree/?branch=master) | [![Dependency Status](https://www.versioneye.com/user/projects/53d26035851c5679c9000267/badge.svg?style=flat)](https://www.versioneye.com/user/projects/53d26035851c5679c9000267) |

Features
----------
 - NestedSet(MPTT - Modified Preorder Tree Traversal)
 - Works only with mysql and postgresql


Dependencies
------------
- Optional [Stefano DB](https://github.com/bartko-s/stefano-db) This repository is 100% compatible with Zend Framework 2 DB package
- Optional [Doctrine DBAL](https://github.com/doctrine/dbal)
- Optional [Zend Framework 1 - Db package](https://github.com/zf1/zend-db.git)

Instalation using Composer
--------------------------
1. Add following line to your composer.json file  ``` "stefano/stefano-tree": "*" ```
2. Add following line to your composer.json file ``` "doctrine/dbal": "2.*" ``` if you want to use this library with Doctrine DBAL
3. Add following line to your composer.json file ``` "stefano/stefano-db": "~1.4.0" ``` if you want to use this library with Stefano DB
4. Add following line to your composer.json file ``` "zf1/zend-db": "*" ``` if you want to use this library with Zend Framework 1
5. Create db scheme [example db scheme](https://github.com/bartko-s/stefano-tree/tree/master/sql)

Usage
-----

- Create tree adapter

Use static factory method

```
$options = new \StefanoTree\NestedSet\Options(array(
    'tableName'    => 'tree_traversal', //required
    'idColumnName' => 'tree_traversal_id', //required
    'leftColumnName' => 'lft', //optional (default lft)
    'rightColumnName' => 'rgt', //optional (default rgt)
    'levelColumnName' => 'level', //optional (default level)
    'parentIdColumnName' => 'parent_id', //optional (default parent_id)
));

// One of this
//Stefano Db
$dbAdapter = new \StefanoDb\Adapter\Adapter(...);
// or Doctrine DBAL
$dbAdapter = new \Doctrine\DBAL\Connection(...);
// or Zend 1 DB package
$dbAdapter = Zend_Db::factory(...)


$tree = \StefanoTree\NestedSet::factory($options, $dbAdapter);
```

or create tree adapter directly

```
$options = new \StefanoTree\NestedSet\Options(array(...);

$dbAdapter = new \StefanoDb\Adapter\Adapter(array(...));

$nestedSetAdapter = new \StefanoTree\NestedSet\Adapter\Zend2DbAdapter($options, $dbAdapter);

$tree = new \StefanoTree\NestedSet($nestedSetAdapter);
```

You can join table.
```
$defaultDbSelect = $nestedSetAdapter->getDefaultDbSelect();

//zend framework select object
//http://framework.zend.com/manual/2.2/en/modules/zend.db.sql.html#join
$defaultDbSelect->join($name, $on, $columns, $type);
$nestedSetAdapter->setDefaultDbSelect($defaultDbSelect);
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

- Update node

```
$targetNodeId = 10;

$data = array(
    //data
);

$tree->updateNode($targetNodeId, $data);
```

- Move node

```
$sourceNodeId = 15;
$targetNodeId = 10;

$tree->moveNodePlacementBottom($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementTop($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementChildBottom($sourceNodeId, $targetNodeId);
$tree->moveNodePlacementChildTop($sourceNodeId, $targetNodeId);
```

- Delete node or branch

```
$nodeId = 15;

$tree->deleteBranch($nodeId);
```

- Clear all nodes except root node

```
$tree->clear();
```

- Get all children

```
$nodeId = 15;
$tree->getChildren($nodeId);
```

- Get all descedants

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

ToDo
-----
- rebuild tree

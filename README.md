Tree
====

| Test Status | Code Coverage | Dependencies |
| :---: | :---: | :---: |
| <a href="https://travis-ci.org/bartko-s/stefano-tree"><img src="https://secure.travis-ci.org/bartko-s/stefano-tree.png?branch=master" /></a> | <a href='https://coveralls.io/r/bartko-s/stefano-tree?branch=master'><img src='https://coveralls.io/repos/bartko-s/stefano-tree/badge.png?branch=master' alt='Coverage Status' /></a> | <a href='https://www.versioneye.com/user/projects/51bc29745e594d00020111ca'><img src='https://www.versioneye.com/user/projects/51bc29745e594d00020111ca/badge.png' alt="Dependency Status" /></a> |

Features
----------
 - NestedSet(MPTT - Modified Preorder Tree Traversal)

Dependencies
------------
- [Stefano Db](https://github.com/bartko-s/stefano-db) This repository is 100% compatible with Zend Framework 2 DB package

Instalation using Composer
--------------------------
1. Add following line to your composer.json file  ``` "stefano/stefano-tree": "*" ```
2. Create db scheme [example db scheme](https://github.com/bartko-s/stefano-tree/tree/master/sql)

Usage
-----

- Create tree adapter

``` 
$options = new \StefanoTree\NestedSet\Options(array(
    'tableName'    => 'tree_traversal', //required
    'idColumnName' => 'tree_traversal_id', //required
    'leftColumnName' => 'lft', //optional (default lft)
    'rightColumnName' => 'rgt', //optional (default rgt)
    'levelColumnName' => 'level', //optional (default level)
    'parentIdColumnName' => 'parent_id', //optional (default parent_id)
));

$dbAdapter = new \StefanoDb\Adapter\Adapter(array(...));

$nestedSetAdapter = new \StefanoTree\NestedSet\Adapter\Zend2DbAdapter($options, $dbAdapter);

$tree = new \StefanoTree\NestedSet($nestedSetAdapter);
```

You can join table
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


ToDo
-----
- rebuild tree
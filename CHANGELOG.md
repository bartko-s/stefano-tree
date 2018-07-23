# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 4.0.0 (2018-July-23)

### Added
 - support for pure \PDO adapter
 - all adapter can handle nested transaction

### Changed (BC break)
 - static \StefanoTree\NestedSet::factory method was removed use constructor instead.
 - instead $tree->getAdapter()->setDbSelectBuilder(callable) use $options config "dbSelectBuilder"

## 3.1.0 (2018-maj-21)

### Added
 - uuid support
 - get result as nested array or flat array

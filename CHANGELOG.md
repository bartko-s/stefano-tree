# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- new addNode method with this signature \StefanoTree\TreeInterface::addNode($targetNodeId, array $data = array(), string $placement=self::PLACEMENT_CHILD_TOP): int|string
- new moveNode method with this signature \StefanoTree\TreeInterface::moveNode($sourceNodeId, $targetNodeId, string $placement = self::PLACEMENT_CHILD_TOP): bool

### Removed (BC Breaks)
- removed support for PHP version 7.0 and below
- removed support for [Deprecated] library Stefano-Db
- removed method \StefanoTree\TreeInterface::addNodePlacementBottom
- removed method \StefanoTree\TreeInterface::addNodePlacementTop
- removed method \StefanoTree\TreeInterface::addNodePlacementChildBottom
- removed method \StefanoTree\TreeInterface::addNodePlacementChildTop
- removed method \StefanoTree\TreeInterface::moveNodePlacementBottom
- removed method \StefanoTree\TreeInterface::moveNodePlacementTop
- removed method \StefanoTree\TreeInterface::moveNodePlacementChildBottom
- removed method \StefanoTree\TreeInterface::moveNodePlacementChildTop

### Changed (BC Breaks)
- All library exceptions implements \StefanoTree\Exceptions\ExceptionInterface instead of \StefanoTree\Exceptions\BaseException 
- \StefanoTree\TreeInterface::updateNode($nodeId, $data) is now \StefanoTree\TreeInterface::updateNode($nodeId, array $data)
- \StefanoTree\TreeInterface::getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null) is now \StefanoTree\TreeInterface::getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null)
- \StefanoTree\TreeInterface::createRoot throw \StefanoTree\Exception\ValidationException instead \StefanoTree\Exception\RootNodeAlreadyExistsException
- \StefanoTree\TreeInterface::addNode throw \StefanoTree\Exception\ValidationException if node cannot be created
- \StefanoTree\TreeInterface::moveNode throw \StefanoTree\Exception\ValidationException if node cannot be moved 
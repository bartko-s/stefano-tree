# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Removed
- removed support for PHP version 5.6 and below

### Changed (BC Breaks)
- \StefanoTree\TreeInterface::updateNode($nodeId, $data) is now \StefanoTree\TreeInterface::updateNode($nodeId, array $data)
- \StefanoTree\TreeInterface::addNodePlacementBottom($targetNodeId, $data = array()) is now \StefanoTree\TreeInterface::addNodePlacementBottom($targetNodeId, array $data = array())
- \StefanoTree\TreeInterface::addNodePlacementBottom returns null instead false if new node was not created
- \StefanoTree\TreeInterface::addNodePlacementTop($targetNodeId, $data = array()) is now \StefanoTree\TreeInterface::addNodePlacementTop($targetNodeId, array $data = array())
- \StefanoTree\TreeInterface::addNodePlacementTop returns null instead false if new node was not created
- \StefanoTree\TreeInterface::addNodePlacementChildBottom($targetNodeId, $data = array()) is now \StefanoTree\TreeInterface::addNodePlacementChildBottom($targetNodeId, array $data = array())
- \StefanoTree\TreeInterface::addNodePlacementChildBottom returns null instead false if new node was not created
- \StefanoTree\TreeInterface::addNodePlacementChildTop($targetNodeId, $data = array()) is now \StefanoTree\TreeInterface::addNodePlacementChildTop($targetNodeId, array $data = array())
- \StefanoTree\TreeInterface::addNodePlacementChildTop returns null instead false if new node was not created
- \StefanoTree\TreeInterface::getDescendants($nodeId = 1, $startLevel = 0, $levels = null, $excludeBranch = null) is now \StefanoTree\TreeInterface::getDescendants($nodeId, $startLevel = 0, $levels = null, $excludeBranch = null) 
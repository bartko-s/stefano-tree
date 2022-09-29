<?php

declare(strict_types=1);

namespace StefanoTree;

use Doctrine\DBAL\Connection as DoctrineConnection;
use Exception;
use Laminas\Db\Adapter\Adapter as LaminasDbAdapter;
use StefanoTree\Exception\InvalidArgumentException;
use StefanoTree\Exception\ValidationException;
use StefanoTree\NestedSet\Adapter\AdapterInterface;
use StefanoTree\NestedSet\Adapter\Doctrine2DBAL;
use StefanoTree\NestedSet\Adapter\LaminasDb;
use StefanoTree\NestedSet\Adapter\NestedTransactionDecorator;
use StefanoTree\NestedSet\Adapter\Pdo;
use StefanoTree\NestedSet\Adapter\Zend1;
use StefanoTree\NestedSet\AddStrategy;
use StefanoTree\NestedSet\AddStrategy\AddStrategyInterface;
use StefanoTree\NestedSet\Manipulator\Manipulator;
use StefanoTree\NestedSet\Manipulator\ManipulatorInterface;
use StefanoTree\NestedSet\MoveStrategy;
use StefanoTree\NestedSet\MoveStrategy\MoveStrategyInterface;
use StefanoTree\NestedSet\NodeInfo;
use StefanoTree\NestedSet\Options;
use StefanoTree\NestedSet\QueryBuilder\AncestorQueryBuilder;
use StefanoTree\NestedSet\QueryBuilder\AncestorQueryBuilderInterface;
use StefanoTree\NestedSet\QueryBuilder\DescendantQueryBuilder;
use StefanoTree\NestedSet\QueryBuilder\DescendantQueryBuilderInterface;
use StefanoTree\NestedSet\Validator\Validator;
use StefanoTree\NestedSet\Validator\ValidatorInterface;

class NestedSet implements TreeInterface
{
    private $manipulator;

    private $validator;

    /**
     * @param array|Options $options
     * @param object        $dbAdapter
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options, $dbAdapter)
    {
        if (is_array($options)) {
            $options = new Options($options);
        } elseif (!$options instanceof Options) {
            throw new InvalidArgumentException(
                sprintf('Options must be an array or instance of %s', Options::class)
            );
        }

        if ($dbAdapter instanceof AdapterInterface) {
            $adapter = $dbAdapter;
        } elseif ($dbAdapter instanceof LaminasDbAdapter) {
            $adapter = new LaminasDb($options, $dbAdapter);
        } elseif ($dbAdapter instanceof DoctrineConnection) {
            $adapter = new Doctrine2DBAL($options, $dbAdapter);
        } elseif ($dbAdapter instanceof \Zend_Db_Adapter_Abstract) {
            $adapter = new Zend1($options, $dbAdapter);
        } elseif ($dbAdapter instanceof \PDO) {
            $adapter = new Pdo($options, $dbAdapter);
        } else {
            throw new InvalidArgumentException('Db adapter "'.get_class($dbAdapter)
                .'" is not supported');
        }

        $adapter = new NestedTransactionDecorator($adapter);

        $this->manipulator = new Manipulator($options, $adapter);
    }

    /**
     * @return ManipulatorInterface
     */
    public function getManipulator(): ManipulatorInterface
    {
        return $this->manipulator;
    }

    /**
     * @return ValidatorInterface
     */
    private function getValidator(): ValidatorInterface
    {
        if (null == $this->validator) {
            $this->validator = new Validator($this->getManipulator());
        }

        return $this->validator;
    }

    /**
     * {@inheritdoc}
     */
    public function createRootNode($data = array(), $scope = null)
    {
        if ($this->getRootNode($scope)) {
            if ($scope) {
                $errorMessage = 'Root node for given scope already exist';
            } else {
                $errorMessage = 'Root node already exist';
            }

            throw new ValidationException($errorMessage);
        }

        $nodeInfo = new NodeInfo(null, null, 0, 1, 2, $scope);

        return $this->getManipulator()->insert($nodeInfo, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateNode($nodeId, array $data): void
    {
        $this->getManipulator()
            ->update($nodeId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function addNode($targetNodeId, array $data = array(), string $placement = self::PLACEMENT_CHILD_TOP)
    {
        return $this->getAddStrategy($placement)->add($targetNodeId, $data);
    }

    /**
     * @param string $placement
     *
     * @return AddStrategyInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getAddStrategy(string $placement): AddStrategyInterface
    {
        $adapter = $this->getManipulator();

        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new AddStrategy\Bottom($adapter);

            case self::PLACEMENT_TOP:
                return new AddStrategy\Top($adapter);

            case self::PLACEMENT_CHILD_BOTTOM:
                return new AddStrategy\ChildBottom($adapter);

            case self::PLACEMENT_CHILD_TOP:
                return new AddStrategy\ChildTop($adapter);

            default:
                throw new InvalidArgumentException('Unknown placement "'.$placement.'"');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function moveNode($sourceNodeId, $targetNodeId, string $placement = self::PLACEMENT_CHILD_TOP): void
    {
        $this->getMoveStrategy($placement)->move($sourceNodeId, $targetNodeId);
    }

    /**
     * @param string $placement
     *
     * @return MoveStrategyInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getMoveStrategy(string $placement): MoveStrategyInterface
    {
        $adapter = $this->getManipulator();

        switch ($placement) {
            case self::PLACEMENT_BOTTOM:
                return new MoveStrategy\Bottom($adapter);

            case self::PLACEMENT_TOP:
                return new MoveStrategy\Top($adapter);

            case self::PLACEMENT_CHILD_BOTTOM:
                return new MoveStrategy\ChildBottom($adapter);

            case self::PLACEMENT_CHILD_TOP:
                return new MoveStrategy\ChildTop($adapter);

            default:
                throw new InvalidArgumentException('Unknown placement "'.$placement.'"');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBranch($nodeId): void
    {
        $adapter = $this->getManipulator();

        $adapter->beginTransaction();

        try {
            $adapter->lockTree();

            $nodeInfo = $adapter->getNodeInfo($nodeId);

            // node does not exist
            if (!$nodeInfo) {
                $adapter->commitTransaction();

                return;
            }

            $adapter->delete($nodeInfo->getId());

            // patch hole
            $moveFromIndex = $nodeInfo->getLeft();
            $shift = $nodeInfo->getLeft() - $nodeInfo->getRight() - 1;
            $adapter->moveLeftIndexes($moveFromIndex, $shift, $nodeInfo->getScope());
            $adapter->moveRightIndexes($moveFromIndex, $shift, $nodeInfo->getScope());

            $adapter->commitTransaction();
        } catch (Exception $e) {
            $adapter->rollbackTransaction();

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNode($nodeId): ?array
    {
        return $this->getManipulator()
            ->getNode($nodeId);
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestorsQueryBuilder(): AncestorQueryBuilderInterface
    {
        return new AncestorQueryBuilder($this->getManipulator());
    }

    /**
     * {@inheritdoc}
     */
    public function getDescendantsQueryBuilder(): DescendantQueryBuilderInterface
    {
        return new DescendantQueryBuilder($this->getManipulator());
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNode($scope = null): array
    {
        return $this->getManipulator()
            ->getRoot($scope);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoots(): array
    {
        return $this->getManipulator()
            ->getRoots();
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($rootNodeId): bool
    {
        return $this->getValidator()
            ->isValid($rootNodeId);
    }

    /**
     * {@inheritdoc}
     */
    public function rebuild($rootNodeId): void
    {
        $this->getValidator()
            ->rebuild($rootNodeId);
    }
}

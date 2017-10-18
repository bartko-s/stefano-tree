<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet;

use StefanoTree\Exception\InvalidArgumentException;

class Options
{
    private $tableName = '';
    private $tableAlias = null;

    private $sequenceName = null;

    private $idColumnName = '';

    private $leftColumnName = 'lft';
    private $rightColumnName = 'rgt';
    private $levelColumnName = 'level';
    private $parentIdColumnName = 'parent_id';
    private $scopeColumnName = null;

    /**
     * @param array $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        $requiredOptions = array(
            'tableName', 'idColumnName',
        );

        $missingKeys = array_diff_key(array_flip($requiredOptions), $options);

        if (count($missingKeys)) {
            throw new InvalidArgumentException(implode(', ', array_flip($missingKeys))
                .' must be set');
        }

        $this->setOptions($options);
    }

    /**
     * @param array $options
     */
    protected function setOptions(array $options): void
    {
        foreach ($options as $name => $value) {
            $methodName = 'set'.ucfirst($name);
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    /**
     * @param string $tableName
     *
     * @throws InvalidArgumentException
     */
    public function setTableName(string $tableName): void
    {
        $tableName = trim($tableName);

        if (empty($tableName)) {
            throw new InvalidArgumentException('tableName cannot be empty');
        }

        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableAlias
     */
    public function setTableAlias(string $tableAlias): void
    {
        $tableAlias = trim($tableAlias);

        if (empty($tableAlias)) {
            throw new InvalidArgumentException('tableAlias cannot be empty');
        }

        $this->tableAlias = $tableAlias;
    }

    /**
     * If alias was not set then table name is returned.
     *
     * @return string
     */
    public function getTableAlias(): string
    {
        if (null === $this->tableAlias) {
            $this->tableAlias = $this->getTableName();
        }

        return $this->tableAlias;
    }

    /**
     * @param string $sequenceName
     */
    public function setSequenceName(string $sequenceName): void
    {
        $this->sequenceName = trim($sequenceName);
    }

    /**
     * @return string|null
     */
    public function getSequenceName(): ?string
    {
        return $this->sequenceName;
    }

    /**
     * @param string $idColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setIdColumnName(string $idColumnName): void
    {
        $idColumnName = trim($idColumnName);

        if (empty($idColumnName)) {
            throw new InvalidArgumentException('idColumnName cannot be empty');
        }

        $this->idColumnName = $idColumnName;
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string
     */
    public function getIdColumnName(bool $withTableAlias = false): string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->idColumnName) : $this->idColumnName;
    }

    /**
     * @param string $leftColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setLeftColumnName(string $leftColumnName): void
    {
        $leftColumnName = trim($leftColumnName);

        if (empty($leftColumnName)) {
            throw new InvalidArgumentException('leftColumnName cannot be empty');
        }

        $this->leftColumnName = $leftColumnName;
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string
     */
    public function getLeftColumnName(bool $withTableAlias = false): string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->leftColumnName) : $this->leftColumnName;
    }

    /**
     * @param string $rightColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setRightColumnName(string $rightColumnName): void
    {
        $rightColumnName = trim($rightColumnName);

        if (empty($rightColumnName)) {
            throw new InvalidArgumentException('rightColumnName cannot be empty');
        }

        $this->rightColumnName = $rightColumnName;
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string
     */
    public function getRightColumnName(bool $withTableAlias = false): string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->rightColumnName) : $this->rightColumnName;
    }

    /**
     * @param string $levelColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setLevelColumnName(string $levelColumnName): void
    {
        $levelColumnName = trim($levelColumnName);

        if (empty($levelColumnName)) {
            throw new InvalidArgumentException('levelColumnName cannot be empty');
        }

        $this->levelColumnName = $levelColumnName;
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string
     */
    public function getLevelColumnName(bool $withTableAlias = false): string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->levelColumnName) : $this->levelColumnName;
    }

    /**
     * @param string $parentIdColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setParentIdColumnName(string $parentIdColumnName): void
    {
        $parentIdColumnName = trim($parentIdColumnName);

        if (empty($parentIdColumnName)) {
            throw new InvalidArgumentException('parentIdColumnName cannot be empty');
        }

        $this->parentIdColumnName = $parentIdColumnName;
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string
     */
    public function getParentIdColumnName(bool $withTableAlias = false): string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->parentIdColumnName) : $this->parentIdColumnName;
    }

    /**
     * @param $scopeColumnName
     */
    public function setScopeColumnName(string $scopeColumnName): void
    {
        $this->scopeColumnName = trim($scopeColumnName);
    }

    /**
     * @param bool $withTableAlias
     *
     * @return string|null
     */
    public function getScopeColumnName(bool $withTableAlias = false): ?string
    {
        return ($withTableAlias) ? $this->addTableAlias($this->scopeColumnName) : $this->scopeColumnName;
    }

    private function addTableAlias(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return sprintf('%s.%s', $this->getTableAlias(), $value);
    }
}

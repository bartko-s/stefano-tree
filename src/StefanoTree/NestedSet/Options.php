<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet;

use StefanoTree\Exception\InvalidArgumentException;

class Options
{
    private $tableName = '';

    private $sequenceName = null;

    private $idColumnName = '';

    private $leftColumnName = 'lft';
    private $rightColumnName = 'rgt';
    private $levelColumnName = 'level';
    private $parentIdColumnName = 'parent_id';
    private $scopeColumnName = null;

    private $dbSelectBuilder = null;

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
                $this->{$methodName}($value);
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
     * @param string $sequenceName
     */
    public function setSequenceName(string $sequenceName): void
    {
        $this->sequenceName = trim($sequenceName);
    }

    /**
     * @return null|string
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
     * @param bool $withTableName
     *
     * @return string
     */
    public function getIdColumnName(bool $withTableName = false): string
    {
        return ($withTableName) ? $this->addTableName($this->idColumnName) : $this->idColumnName;
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
     * @param bool $withTableName
     *
     * @return string
     */
    public function getLeftColumnName(bool $withTableName = false): string
    {
        return ($withTableName) ? $this->addTableName($this->leftColumnName) : $this->leftColumnName;
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
     * @param bool $withTableName
     *
     * @return string
     */
    public function getRightColumnName(bool $withTableName = false): string
    {
        return ($withTableName) ? $this->addTableName($this->rightColumnName) : $this->rightColumnName;
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
     * @param bool $withTableName
     *
     * @return string
     */
    public function getLevelColumnName(bool $withTableName = false): string
    {
        return ($withTableName) ? $this->addTableName($this->levelColumnName) : $this->levelColumnName;
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
     * @param bool $withTableName
     *
     * @return string
     */
    public function getParentIdColumnName(bool $withTableName = false): string
    {
        return ($withTableName) ? $this->addTableName($this->parentIdColumnName) : $this->parentIdColumnName;
    }

    /**
     * @param string $scopeColumnName
     */
    public function setScopeColumnName(string $scopeColumnName): void
    {
        $this->scopeColumnName = trim($scopeColumnName);
    }

    /**
     * @param bool $withTableName
     *
     * @return null|string
     */
    public function getScopeColumnName(bool $withTableName = false): ?string
    {
        return ($withTableName) ? $this->addTableName($this->scopeColumnName) : $this->scopeColumnName;
    }

    private function addTableName(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return sprintf('%s.%s', $this->getTableName(), $value);
    }

    /**
     * Modify base DB select. Must be without where, order parts.
     *
     * @param null|callable $builder
     */
    public function setDbSelectBuilder(?callable $builder): void
    {
        $this->dbSelectBuilder = $builder;
    }

    /**
     * @return null|callable
     */
    public function getDbSelectBuilder(): ?callable
    {
        return $this->dbSelectBuilder;
    }
}

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
        $tableName = (string) trim($tableName);

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
        $this->sequenceName = (string) trim($sequenceName);
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
        $idColumnName = (string) trim($idColumnName);

        if (empty($idColumnName)) {
            throw new InvalidArgumentException('idColumnName cannot be empty');
        }

        $this->idColumnName = $idColumnName;
    }

    /**
     * @return string
     */
    public function getIdColumnName(): string
    {
        return $this->idColumnName;
    }

    /**
     * @param string $leftColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setLeftColumnName(string $leftColumnName): void
    {
        $leftColumnName = (string) trim($leftColumnName);

        if (empty($leftColumnName)) {
            throw new InvalidArgumentException('leftColumnName cannot be empty');
        }

        $this->leftColumnName = $leftColumnName;
    }

    /**
     * @return string
     */
    public function getLeftColumnName(): string
    {
        return $this->leftColumnName;
    }

    /**
     * @param string $rightColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setRightColumnName(string $rightColumnName): void
    {
        $rightColumnName = (string) trim($rightColumnName);

        if (empty($rightColumnName)) {
            throw new InvalidArgumentException('rightColumnName cannot be empty');
        }

        $this->rightColumnName = $rightColumnName;
    }

    /**
     * @return string
     */
    public function getRightColumnName(): string
    {
        return $this->rightColumnName;
    }

    /**
     * @param string $levelColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setLevelColumnName(string $levelColumnName): void
    {
        $levelColumnName = (string) trim($levelColumnName);

        if (empty($levelColumnName)) {
            throw new InvalidArgumentException('levelColumnName cannot be empty');
        }

        $this->levelColumnName = $levelColumnName;
    }

    /**
     * @return string
     */
    public function getLevelColumnName(): string
    {
        return $this->levelColumnName;
    }

    /**
     * @param string $parentIdColumnName
     *
     * @throws InvalidArgumentException
     */
    public function setParentIdColumnName(string $parentIdColumnName): void
    {
        $parentIdColumnName = (string) trim($parentIdColumnName);

        if (empty($parentIdColumnName)) {
            throw new InvalidArgumentException('parentIdColumnName cannot be empty');
        }

        $this->parentIdColumnName = $parentIdColumnName;
    }

    /**
     * @return string
     */
    public function getParentIdColumnName(): string
    {
        return $this->parentIdColumnName;
    }

    /**
     * @param $scopeColumnName
     */
    public function setScopeColumnName(string $scopeColumnName): void
    {
        $this->scopeColumnName = trim($scopeColumnName);
    }

    /**
     * @return string|null
     */
    public function getScopeColumnName(): ?string
    {
        return $this->scopeColumnName;
    }
}

<?php

declare(strict_types=1);

namespace StefanoTree\NestedSet;

class Utilities
{
    /**
     * Convert flat tree structure to nested.
     *
     * @param array  $flatTree
     * @param string $levelName
     *
     * @return array
     */
    public static function flatToNested(
        array $flatTree,
        string $levelName = 'level'
    ): array {
        return self::_flatToNested($flatTree, $levelName);
    }

    /**
     * @param array    $flatTree
     * @param string   $levelName
     * @param int|null $level
     * @param int      $startPos
     * @param array    $result
     *
     * @return array
     */
    private static function _flatToNested(
        array $flatTree,
        string $levelName = 'level',
        ?int $level = null,
        int $startPos = 0,
        array &$result = array()
    ): array {
        $total = count($flatTree);
        $first = true;

        for ($pos = $startPos; $pos < $total; ++$pos) {
            $item = $flatTree[$pos];

            if (null === $level) {
                $level = $item[$levelName];
            }

            if (!array_key_exists('_children', $item)) {
                $item['_children'] = array();
            }

            if ($level == $item[$levelName]) {
                $result[] = $item;
                $first = true;
            } elseif (($level + 1) == $item[$levelName] && true == $first) {
                $children = array($item);
                self::_flatToNested($flatTree, $levelName, (int) $item[$levelName], $pos + 1, $children);
                $result[count($result) - 1]['_children'] = $children;
                $first = false;
            } elseif ($level > $item[$levelName]) {
                break;
            }
        }

        return $result;
    }
}

<?php

namespace Snit;

class QuerySelector extends AbstractSelector
{
    /**
     * Allow elegant call using only parentheses like a function.
     *
     * @param string $path    List, or dictionary, or field to extract info
     * @param string $default Default value if not found
     *
     * @return string|array Extracted information or default if not found
     */
    public function __invoke($path, $default='')
    {
        $path = $this->clearPath($path);

        if (preg_match('/^\[.*\]$/', $path)) {
            $default = $default === '' ? array() : $default;

            return $this->getList($path, $default);
        } elseif (preg_match('/^\{.*\}$/', $path)) {
            return $this->getDictionaryFromPath($path);
        }

        return $this->getOne($path, $default);
    }

    /**
     * Remove all (at begin, end and inside) spaces from path.
     *
     * @param string $path Path
     *
     * @return string Path without spaces
     */
    private function clearPath($path)
    {
        // Strip off multiple spaces
        $path = preg_replace('/\s+/', '', $path);

        return $path;
    }

    /**
     * Return a list using path to find fields.
     *
     * @param string $path    Path to look for info
     * @param string $default Default value if path not match
     *
     * @return array Found data
     */
    private function getList($path, $default=array())
    {
        // Strip off []
        $path = preg_replace('/(^\[)|(\]$)/', '', $path);

        $selector = new Selector($this->data);

        return $selector->getAll($path, $default);
    }

    /**
     * Return a dictionary (array with string keys pointing to values).
     *
     * @param string $path Path to look for info
     *
     * @return array Found data with keys and values
     */
    private function getDictionaryFromPath($path)
    {
        // Strip off {}
        $path = preg_replace('/(^\{)|(\}$)/', '', $path);

        list($keys, $values) = explode(':', $path);

        $selector = new Selector($this->data);

        return $selector->getDictionary($keys, $values);
    }

    /**
     * [getOne description]
     * @param  string $path    [description]
     * @param  [type] $default [description]
     * @return [type]          [description]
     */
    private function getOne($path, $default)
    {
        $selector = new Selector($this->data);

        return $selector->getOne($path, $default);
    }
}

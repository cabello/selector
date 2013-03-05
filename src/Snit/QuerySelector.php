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

        $selector = new Selector($this->data);

        if (preg_match('/^\[.*\]$/', $path)) {
            $default = $default === '' ? array() : $default;

            return $selector->getList($path, $default);
        } elseif (preg_match('/^\{.*\}$/', $path)) {
            return $selector->getDictionaryFromPath($path);
        }

        return $selector->getOne($path, $default);
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
}

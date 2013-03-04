<?php

namespace Snit;

/**
 * Selector allows you to extract information from JSON or StdClass.
 */
class Selector
{
    /**
     * Object containing information to be extracted.
     *
     * @var \StdClass
     */
    private $data;

    /**
     * Receive the data to have information extracted.
     *
     * @param \StdClass|string $objectOrString StdClass or string (JSON to be decoded)
     */
    public function __construct($objectOrString=null)
    {
        if (is_string($objectOrString)) {
            $objectOrString = json_decode($objectOrString);
        }

        $this->data = $objectOrString;
    }

    /**
     * Advance into a structure to allow less typing when selecting.
     *
     * @param string $path Path to be focused, example: school.staff
     *
     * @return \Snit\Selector Selector focused on path
     */
    public function focus($path)
    {
        $pathParts = explode('.', $path);

        $data = $this->data;

        foreach ($pathParts as $attribute) {
            if (! isset($data->$attribute)) {
                $data = null;
                break;
            }

            $data = $data->$attribute;
        }

        return new Selector($data);
    }

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

        switch ($this->askingFor($path)) {
        case 'list':
            $default = $default === '' ? array() : $default;

            return $this->getList($path, $default);
        case 'dictionary':
            return $this->getDictionaryFromPath($path);
        default:
            return $this->getOne($path, $default);
        }
    }

    /**
     * Find the first information that match the path.
     *
     * @param string $contextPath Context to return
     * @param string $fieldPath   Field to be matched
     * @param string $value       Value to be matched
     *
     * @return \StdClass|string|array|null From context path if found
     */
    public function findOne($contextPath, $fieldPath, $value)
    {
        $items = $this->findAll($contextPath, $fieldPath, $value);

        return array_shift($items);
    }

    /**
     * [findAll description]
     *
     * @param [type] $contextPath [description]
     * @param [type] $fieldPath   [description]
     * @param [type] $value       [description]
     *
     * @return [type]              [description]
     */
    public function findAll($contextPath, $fieldPath, $value)
    {
        $contextObjects = $this->getAll($contextPath);

        $foundObjects = array_filter(
            $contextObjects,
            function ($item) use ($fieldPath, $value) {
                $contextParser = new Selector($item);
                $foundValues = $contextParser("[ {$fieldPath} ]");

                return in_array($value, $foundValues);
            }
        );

        return $foundObjects;
    }

    /**
     * [isList description]
     *
     * @param [type] $data [description]
     *
     * @return boolean       [description]
     */
    public function isList($data)
    {
        if (! is_array($data)) {
            return false;
        }

        $keys = array_keys($data);
        $stringKeys = array_filter($keys, 'is_string');

        return empty($stringKeys);
    }

    /**
     * [clearPath description]
     *
     * @param [type] $path [description]
     *
     * @return [type]       [description]
     */
    protected function clearPath($path)
    {
        // Strip off multiple spaces
        $path = preg_replace('/\s+/', '', $path);

        return $path;
    }

    /**
     * [askingFor description]
     *
     * @param [type] $path [description]
     *
     * @return [type]       [description]
     */
    protected function askingFor($path)
    {
        $posFirstChar = 0;
        $posLastChar = strlen($path) - 1;

        if (strpos($path, '[') === $posFirstChar && strpos($path, ']') === $posLastChar) {
            return 'list';
        } elseif (strpos($path, '{') === $posFirstChar && strpos($path, '}') === $posLastChar) {
            return 'dictionary';
        }

        return 'one';
    }

    /**
     * [getList description]
     *
     * @param [type] $path    [description]
     * @param [type] $default [description]
     *
     * @return [type]          [description]
     */
    protected function getList($path, $default)
    {
        // Strip off[]
        $path = preg_replace('/\[|\]/', '', $path);

        return $this->getAll($path, $default);
    }

    /**
     * [getDictionaryFromPath description]
     *
     * @param [type] $path [description]
     *
     * @return [type]       [description]
     */
    protected function getDictionaryFromPath($path)
    {
        // Strip off[]
        $path = preg_replace('/\{|\}/', '', $path);

        list($keys, $values) = explode(':', $path);

        return $this->getDictionary($keys, $values);
    }

    /**
     * [getOne description]
     *
     * @param [type] $path    [description]
     * @param string $default [description]
     *
     * @return [type]          [description]
     */
    protected function getOne($path, $default='')
    {
        $results = $this->getAll($path);
        $result = isset($results[0]) ? $results[0] : $default;

        return $result;
    }

    /**
     * [getAll description]
     *
     * @param [type] $path    [description]
     * @param array  $default [description]
     *
     * @return [type]          [description]
     */
    protected function getAll($path, $default=array())
    {
        $orToken = '|';
        $possiblePaths = explode($orToken, $path);

        foreach ($possiblePaths as $possiblePath) {
            $result = $this->getAllFromPath($possiblePath);
            if ($result !== false) {
                return $result;
            }
        }

        return $default;
    }

    /**
     * [getAllFromPath description]
     *
     * @param [type] $path [description]
     *
     * @return [type]       [description]
     */
    protected function getAllFromPath($path)
    {
        $pathParts = explode('.', $path);
        $results = array();
        $data = $this->data;

        foreach ($pathParts as $attribute) {
            if (!$data) break;
            $results = $data = $this->getAllWithAttribute($data, $attribute);
        }

        return empty($results) ? false : $results;
    }

    /**
     * [getAllWithAttribute description]
     *
     * @param [type] $data      [description]
     * @param [type] $attribute [description]
     *
     * @return [type]            [description]
     */
    protected function getAllWithAttribute($data, $attribute)
    {
        $data = $this->isList($data) ? $data : array($data);
        $results = array();

        $self = $this;

        array_map(
            function ($item) use (&$results, $attribute, $self) {
                $item = (object) $item;

                if (!isset($item->$attribute)) return;

                if ($self->isList($item->$attribute)) {
                    $results = array_merge(array_values($item->$attribute), $results);
                } else {
                    $results[] = $item->$attribute;
                }
            },
            $data
        );

        return $results;
    }

    /**
     * [getDictionary description]
     *
     * @param [type] $keysPath   [description]
     * @param [type] $valuesPath [description]
     *
     * @return [type]             [description]
     */
    protected function getDictionary($keysPath, $valuesPath)
    {
        $keys = $this->getAll($keysPath);
        $values = $this->getAll($valuesPath, array());

        if (!$keys || !is_array($keys)) {
            return array();
        }

        $keysLen = count($keys);
        $valuesLen = count($values);

        if ($keysLen > $valuesLen) {
            $values = array_pad($values, $keysLen, null);
        } elseif ($valuesLen > $keysLen) {
            $values = array_slice($values, 0, $keysLen);
        }

        return array_combine($keys, $values);
    }
}

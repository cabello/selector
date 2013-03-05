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

    private $tokens = array(
        'or'              => '|',
        'child'           => '.',
        'dictionaryBegin' => '{',
        'dictionaryEnd'   => '}',
        'listBegin'       => '[',
        'listEnd'         => ']',
    );

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
     * Find out what the user is asking for: list, dictionary or string.
     *
     * @param string $path Requested path
     *
     * @return string Possible values: list, dictionary, one (string)
     */
    private function askingFor($path)
    {
        $posFirstChar = 0;
        $posLastChar = strlen($path) - 1;

        if (strpos($path, $this->tokens['listBegin']) === $posFirstChar &&
                strpos($path, $this->tokens['listEnd']) === $posLastChar) {
            return 'list';
        } elseif (strpos($path, $this->tokens['dictionaryBegin']) === $posFirstChar &&
                strpos($path, $this->tokens['dictionaryEnd']) === $posLastChar) {
            return 'dictionary';
        }

        return 'one';
    }

    /**
     * Return a list using path to find fields.
     *
     * @param string $path    Path to look for info
     * @param string $default Default value if path not match
     *
     * @return array Found data
     */
    private function getList($path, $default)
    {
        // Strip off []
        $path = preg_replace('/\[|\]/', '', $path);

        return $this->getAll($path, $default);
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
        $path = preg_replace('/\{|\}/', '', $path);

        list($keys, $values) = explode(':', $path);

        return $this->getDictionary($keys, $values);
    }

    /**
     * Return a single result found using path.
     *
     * @param string $path    Path to look for info
     * @param string $default Default value if not found
     *
     * @return mixed Found info or default otherwise
     */
    private function getOne($path, $default='')
    {
        $results = $this->getAll($path);

        return count($results) ? array_shift($results) : $default;
    }

    /**
     * Find the first information that match the path.
     *
     * @param string $contextPath Context to return
     * @param string $fieldPath   Field to be matched
     * @param string $value       Value to be matched
     *
     * @return \StdClass|string|array|null Context if found, null otherwise
     */
    public function findOne($contextPath, $fieldPath, $value)
    {
        $items = $this->findAll($contextPath, $fieldPath, $value);

        return array_shift($items);
    }

    /**
     * Find all information that match the path.
     *
     * @param string $contextPath Context to return
     * @param string $fieldPath   Field to be matched
     * @param string $value       Value to be matched
     *
     * @return array|null Array of contexts if found, null otherwise
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
     * Advance into a structure to allow less typing when selecting.
     *
     * @param string $path Path to be focused, example: school.staff
     *
     * @return \Snit\Selector Selector focused on path
     */
    public function focus($path)
    {
        $pathParts = explode($this->tokens['child'], $path);

        $data = $this->data;

        foreach ($pathParts as $attribute) {
            if ( ! isset($data->$attribute)) {
                $data = null;
                break;
            }

            $data = $data->$attribute;
        }

        return new Selector($data);
    }

    /**
     * Find all elements that match path or default if none found.
     *
     * @param string      $path    Path to look for info
     * @param array|mixed $default Value to return if not found
     *
     * @return array|mixed Array of found items or default value otherwise
     */
    private function getAll($path, $default=array())
    {
        $possiblePaths = explode($this->tokens['or'], $path);

        foreach ($possiblePaths as $possiblePath) {
            $result = $this->getAllFromPath($possiblePath);
            if ($result !== false) {
                return $result;
            }
        }

        return $default;
    }

    /**
     * Find all elements that match path.
     *
     * @param string $path Path to look for info
     *
     * @return array|false Array of found elements, false otherwise
     */
    private function getAllFromPath($path)
    {
        $pathParts = explode($this->tokens['child'], $path);
        $results = array();
        $data = $this->data;

        foreach ($pathParts as $attribute) {
            if ( ! $data) break;
            $results = $data = $this->getAllWithAttribute($data, $attribute);
        }

        return empty($results) ? false : $results;
    }

    /**
     * Find all elements that match attribute.
     *
     * @param \StdClass $data      Data to look into
     * @param string    $attribute Attribute to match
     *
     * @return array Matched items
     */
    private function getAllWithAttribute($data, $attribute)
    {
        $data = $this->isList($data) ? $data : array($data);
        $results = array();

        $self = $this;

        array_map(
            function ($item) use (&$results, $attribute, $self) {
                $item = (object) $item;

                if ( ! isset($item->$attribute)) return;

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
     * Assemble a dictionary.
     *
     * @param string $keysPath   Path for the keys
     * @param string $valuesPath Path for the values
     *
     * @return array Array with keys matching values
     */
    private function getDictionary($keysPath, $valuesPath)
    {
        $keys = $this->getAll($keysPath);
        $values = $this->getAll($valuesPath, array());

        if ( ! $keys || !is_array($keys)) {
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

    /**
     * Determine if parameter is list (array with numeric integer keys).
     *
     * @param mixed $data List, dictionary or string
     *
     * @return boolean
     */
    public function isList($data)
    {
        if ( ! is_array($data)) {
            return false;
        }

        $keys = array_keys($data);
        $stringKeys = array_filter($keys, 'is_string');

        return empty($stringKeys);
    }
}

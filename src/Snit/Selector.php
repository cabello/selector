<?php

namespace Snit;

class Selector
{
    public $data;

    public function __construct($objectOrString=null)
    {
        if (is_string($objectOrString)) {
            $objectOrString = json_decode($objectOrString);
        }
        $this->data = $objectOrString;
    }

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

    public function findOne($contextPath, $fieldPath, $value)
    {
        $items = $this->findAll($contextPath, $fieldPath, $value);
        return array_shift($items);
    }

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

    public function isList($data)
    {
        if (! is_array($data)) {
            return false;
        }

        $keys = array_keys($data);
        $stringKeys = array_filter($keys, 'is_string');
        return empty($stringKeys);
    }

    protected function clearPath($path)
    {
        // Strip off multiple spaces
        $path = preg_replace('/\s+/', '', $path);

        return $path;
    }

    protected function askingFor($path)
    {
        $posFirstChar = 0;
        $posLastChar = strlen($path) - 1;

        if (strpos($path, '[') === $posFirstChar && strpos($path, ']') === $posLastChar) {
            return 'list';
        } else if (strpos($path, '{') === $posFirstChar && strpos($path, '}') === $posLastChar) {
            return 'dictionary';
        }

        return 'one';
    }

    protected function getList($path, $default)
    {
        // Strip off[]
        $path = preg_replace('/\[|\]/', '', $path);

        return $this->getAll($path, $default);
    }

    protected function getDictionaryFromPath($path)
    {
        // Strip off[]
        $path = preg_replace('/\{|\}/', '', $path);

        list($keys, $values) = explode(':', $path);
        return $this->getDictionary($keys, $values);
    }

    protected function getOne($path, $default='')
    {
        $results = $this->getAll($path);
        $result = isset($results[0]) ? $results[0] : $default;
        return $result;
    }

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

    protected function getAllWithAttribute($data, $attribute)
    {
        $data = $this->isList($data) ? $data : array($data);
        $results = array();

        $self = $this;

        array_map(
            function ($item) use (&$results, $attribute, $self) {
                $item = (object)$item;

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
        } else if ($valuesLen > $keysLen) {
            $values = array_slice($values, 0, $keysLen);
        }

        return array_combine($keys, $values);
    }
}

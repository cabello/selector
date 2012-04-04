<?php

namespace Snit;

class Selector {
    public $data;

    public function __construct($objectOrString=null) {
        if (is_string($objectOrString)) {
            $objectOrString = json_decode($objectOrString);
        }
        $this->data = $objectOrString;
    }

    public function focus($path) {
        $pathParts = explode( '.', $path );

        $data = $this->data;

        foreach($pathParts as $attribute) {
            if ( ! isset($data->$attribute)) {
                $data = NULL;
                break;
            }

            $data = $data->$attribute;
        }

        return new Selector($data);
    }

    public function __invoke($path, $default='') {
        // Strip off multiple spaces
        $path = preg_replace('/\s+/', '', $path);

        $posFirstChar = 0;
        $posLastChar = strlen($path) - 1;
        if (strpos($path, '[') === $posFirstChar && strpos($path, ']') === $posLastChar) {
            $default = $default === '' ? array() : $default;
            return $this->getAll(substr($path, $posFirstChar + 1, $posLastChar - 1), $default);
        } else if (strpos($path, '{') === $posFirstChar && strpos($path, '}') === $posLastChar) {
            $path = substr($path, $posFirstChar + 1, $posLastChar - 1); // remove brackets
            list($keys, $values) = explode(':', $path);
            return $this->getDictionary($keys, $values);
        }

        return $this->getOne($path, $default);
    }

    public function findOne( $contextPath, $fieldPath, $value ){
        $items = $this->findAll( $contextPath, $fieldPath, $value );
        return array_shift($items);
    }

    public function findAll( $contextPath, $fieldPath, $value ){
        $contextObjects = $this->getAll( $contextPath );

        $foundObjects = array_filter($contextObjects, function($item) use ($fieldPath, $value){
            $contextParser = new Selector($item);
            $foundValues = $contextParser("[ {$fieldPath} ]");
            return in_array($value, $foundValues);
        });

        return $foundObjects;
    }

    public function isList($data) {
        if ( ! is_array($data)) {
            return false;
        }

        $keys = array_keys($data);
        $stringKeys = array_filter($keys, 'is_string');
        return empty($stringKeys);
    }

    protected function getOne($path, $default=''){
        $results = $this->getAll($path);
        $result = isset($results[0]) ? $results[0] : $default;
        return $result;
    }

    protected function getAll($path, $default=array()) {
        // Strip off multiple spaces
        $path = preg_replace('/\s+/', '', $path);

        $orToken = '|';
        $possiblePaths = explode( $orToken, $path );

        foreach($possiblePaths as $possiblePath) {
            $result = $this->getAllFromPath($possiblePath);
            if ($result !== FALSE) {
                return $result;
            }
        }

        return $default;
    }

    protected function getAllFromPath($path) {
        $pathParts = explode( '.', $path );
        $results = array();
        $data = $this->data;

        foreach( $pathParts as $attribute ){
            if( !$data ) break;
            $results = $data = $this->getAllWithAttribute( $data, $attribute );
        }

        return empty($results) ? FALSE : $results;
    }

    protected function getAllWithAttribute( $data, $attribute ){
        $data = $this->isList($data) ? $data : array($data);
        $results = array();

        $self = $this;

        array_map(
            function($item) use (&$results, $attribute, $self){
                $item = (object)$item;

                if( !isset( $item->$attribute ) ) return;

                if( $self->isList( $item->$attribute ) ){
                    $results = array_merge(array_values($item->$attribute), $results);
                } else{
                    $results[] = $item->$attribute;
                }
            },
            $data
        );

        return $results;
    }

    protected function getDictionary( $keysPath, $valuesPath ){
        $keys = $this->getAll( $keysPath );
        $values = $this->getAll( $valuesPath, array() );

        if( !$keys || !is_array($keys) ){
            return array();
        }

        $keysLen = count($keys);
        $valuesLen = count($values);

        if( $keysLen > $valuesLen ){
            $values = array_pad( $values, $keysLen, null );
        }else if( $valuesLen > $keysLen ){
            $values = array_slice( $values, 0, $keysLen );
        }

        return array_combine( $keys, $values );
    }
}

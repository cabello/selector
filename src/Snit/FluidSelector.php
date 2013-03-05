<?php

namespace Snit;

/**
 * $s = new Selector($json);

    Basic usage:

    $s->find('profile.name')->fetch();

    By default if not found the content is a empty string.

    Custom default:

    $s->find('profile.reference')->defaultTo(null)->fetch();

    Sometimes the API has two ways to give the same information you need:

    $s->find('profile.name')->or('account.profile.name')->fetch();





  $s->find('people.name')->limit(1)->fetch()

  $s->find('people')->where('name', 'Danilo')->limit(1)->fetch();

  $s->find('people.name')->or('person.name')->defaultTo('Anonymous')->fetch();
 */

class FluidSelector extends AbstractSelector
{
    private $default = '';

    private $limit = 0;

    public function __construct($stdClass=null)
    {
        $this->data = $stdClass;
    }

    public function find($path)
    {
        $this->path = $path;

        return $this;
    }

    public function orFind($path)
    {
        $this->path .= '|' . $path;

        return $this;
    }

    public function where($key, $value)
    {
        $this->where = array($key => $value);

        return $this;
    }

    public function defaultTo($value)
    {
        $this->default = $value;

        return $this;
    }

    public function limit($amount)
    {
        $this->limit = $amount;

        return $this;
    }

    public function fetch()
    {
        $selector = new Selector($this->data);

        if ($this->limit > 1) {
            return $selector->getList($this->path, $this->default);
        }

        return $selector->getOne($this->path, $this->default);
    }
}

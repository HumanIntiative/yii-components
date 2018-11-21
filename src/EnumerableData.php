<?php

class EnumerableData extends CEnumerable implements Iterator, Countable
{
    private $position = 0;
    private $count = 0;
    private $keys = array();
    private $vars = array();

    public function __construct()
    {
        $this->rewind();
    }

    public function current()
    {
        return $this->vars[$this->keys[$this->position]];
    }

    public function key()
    {
        return $this->keys[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
        $reflection = new ReflectionClass(get_class($this));
        $this->vars = $reflection->getConstants();
        $this->keys = array_keys($this->vars);
        $this->count = count($this->vars);
    }

    public function valid()
    {
        return $this->position < count($this->vars);
    }

    public function count()
    {
        return $this->count;
    }

    public function toArray()
    {
        $data = array();
        foreach ($this as $val=>$key) {
            $key = str_replace('_', ' ', $key);
            $data[$key] = str_replace('_', ' ', $val);
        }
        return $data;
    }

    public static function getRef($val)
    {
        $reflection = new ReflectionClass(get_called_class()); //Late Static Binding
        $vars = $reflection->getConstants();
        return str_replace('_', ' ', $vars[$val]);
    }

    public static function getName($key)
    {
        $reflection = new ReflectionClass(get_called_class()); //Late Static Binding
        $vars = array_flip($reflection->getConstants());
        return str_replace('_', ' ', $vars[$key]);
    }
}

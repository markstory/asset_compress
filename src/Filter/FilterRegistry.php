<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilterInterface;
use AssetCompress\Filter\FilterCollection;
use RuntimeException;

class FilterRegistry
{
    protected $filters = [];

    public function __construct(array $filters)
    {
        foreach ($filters as $name => $filter) {
            $this->add($name, $filter);
        }
    }

    public function contains($name)
    {
        return isset($this->filters[$name]);
    }

    public function add($name, AssetFilterInterface $filter)
    {
        $this->filters[$name] = $filter;
    }

    public function get($name)
    {
        if (!isset($this->filters[$name])) {
            return null;
        }
        return $this->filters[$name];
    }

    public function remove($name)
    {
        unset($this->filters[$name]);
    }

    public function collection(array $names)
    {
        $filters = [];
        foreach ($names as $name) {
            $filter = $this->get($name);
            if ($filter === null) {
                throw new RuntimeException("Filter '$name' was not loaded/configured.");
            }
            $filters[] = $filter;
        }
        return new FilterCollection($filters);
    }

}

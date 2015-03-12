<?php
namespace AssetCompress\Filter;

use AssetCompress\AssetFilterInterface;
use AssetCompress\AssetTarget;
use AssetCompress\Filter\FilterCollection;
use RuntimeException;

class FilterRegistry
{
    protected $filters = [];

    public function __construct(array $filters = [])
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

    /**
     * Get a filter collection for a specific target.
     *
     * @param AssetTarget $target The target to get a filter collection for.
     * @return FilterCollection
     */
    public function collection(AssetTarget $target)
    {
        $filters = [];
        foreach ($target->filterNames() as $name) {
            $filter = $this->get($name);
            if ($filter === null) {
                throw new RuntimeException("Filter '$name' was not loaded/configured.");
            }
            // Clone filters so the registry is not polluted.
            $copy = clone $filter;
            $copy->settings([
                'target' => $target->name(),
                'paths' => $target->paths()
            ]);
            $filters[] = $copy;
        }
        return new FilterCollection($filters);
    }
}

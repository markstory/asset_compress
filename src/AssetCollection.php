<?php
namespace AssetCompress;

use AssetCompress\AssetConfig;
use AssetCompress\AssetTarget;
use AssetCompress\Factory;
use Countable;
use Iterator;
use InvalidArgumentException;

/**
 * A collection of AssetTargets.
 *
 * Asset targets are lazily evaluated as they are fetched from the collection
 * By using get() an AssetTarget and its dependent files will be created
 * and verified.
 */
class AssetCollection implements Countable, Iterator
{
    /**
     * The assets indexed by name.
     *
     * @var array
     */
    protected $indexed = [];

    /**
     * The assets indexed numerically.
     *
     * @var array
     */
    protected $items = [];

    /**
     * The current position.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * A factory instance that can be used to lazily build targets.
     *
     * @var AssetCompress\Factory
     */
    protected $factory;

    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param array $items Items.
     * @throws InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct(array $targets, Factory $factory)
    {
        $this->factory = $factory;
        foreach ($targets as $item) {
            $this->indexed[$item] = false;
        }
        $this->items = $targets;
    }

    /**
     * Append an asset to the collection.
     *
     * @param AssetTarget $target The target to append
     * @return void
     */
    public function append(AssetTarget $target)
    {
        $name = $target->name();
        $this->indexed[$name] = $target;
        $this->items[] = $name;
    }

    /**
     * Get an asset from the collection
     *
     * @param string $name The name of the asset you want.
     * @return null|AssetTarget Either null or the asset target.
     */
    public function get($name)
    {
        if (!isset($this->indexed[$name])) {
            return null;
        }
        if (empty($this->indexed[$name])) {
            $this->indexed[$name] = $this->factory->target($name);
        }
        return $this->indexed[$name];
    }

    /**
     * Check whether or not the collection contains the named asset.
     *
     * @param string $name The name of the asset you want.
     * @return bool
     */
    public function contains($name)
    {
        return isset($this->indexed[$name]);
    }

    /**
     * Remove an asset from the collection
     *
     * @param string $name The name of the asset you want to remove
     * @return void
     */
    public function remove($name)
    {
        if (!isset($this->indexed[$name])) {
            return;
        }
        unset($this->indexed[$name]);

        foreach ($this->items as $i => $v) {
            if ($v === $name) {
                unset($this->items[$i]);
            }
        }
    }

    /**
     * Get the length of the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->indexed);
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function next()
    {
        $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->items[$this->index]);
    }

    public function current()
    {
        $current = $this->items[$this->index];
        return $this->get($current);
    }
}

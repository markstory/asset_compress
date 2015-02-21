<?php
namespace AssetCompress;

use ArrayIterator;
use AssetCompress\AssetTarget;
use Countable;
use IteratorIterator;
use InvalidArgumentException;

/**
 * A collection of AssetTargets.
 *
 * Provides ways to query which assets exist and iterate them.
 */
class AssetCollection extends IteratorIterator implements Countable
{
    /**
     * The assets indexed by name.
     *
     * @var array
     */
    protected $indexed = [];

    /**
     * Constructor. You can provide an array or any traversable object
     *
     * @param array $items Items.
     * @throws InvalidArgumentException If passed incorrect type for items.
     */
    public function __construct(array $items)
    {
        $items = new ArrayIterator($items);
        foreach ($items as $i => $item) {
            if (!($item instanceof AssetTarget)) {
                throw new InvalidArgumentException("The item at $i is not an AssetTarget.");
            }
            $this->indexed[$item->name()] = $item;
        }
        parent::__construct($items);
    }

    /**
     * Append an asset to the collection.
     *
     * @param AssetTarget $target The target to append
     * @return void
     */
    public function append(AssetTarget $target)
    {
        $this->indexed[$target->name()] = $target;
        $this->getInnerIterator()->append($target);
    }

    /**
     * Get an asset from the collection
     *
     * @param string $name The name of the asset you want.
     * @return null|AssetTarget Either null or the asset target.
     */
    public function get($name)
    {
        if (isset($this->indexed[$name])) {
            return $this->indexed[$name];
        }
        return null;
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
        $asset = $this->indexed[$name];
        unset($this->indexed[$name]);

        $iterator = $this->getInnerIterator();
        foreach ($iterator as $i => $v) {
            if ($v === $asset) {
                unset($iterator[$i]);
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
}

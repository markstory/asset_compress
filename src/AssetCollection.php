<?php
namespace AssetCompress;

use ArrayIterator;
use AssetCompress\AssetTarget;
use Countable;
use IteratorIterator;
use InvalidArgumentException;

/**
 * A collection of AssetTargets.
 */
class AssetCollection extends IteratorIterator implements Countable
{
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

    public function append(AssetTarget $target)
    {
        $this->indexed[$target->name()] = $target;
        $this->getInnerIterator()->append($target);
    }

    public function get($name)
    {
        if (isset($this->indexed[$name])) {
            return $this->indexed[$name];
        }
        return null;
    }

    public function contains($name)
    {
        return isset($this->indexed[$name]);
    }

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

    public function count()
    {
        return count($this->indexed);
    }
}

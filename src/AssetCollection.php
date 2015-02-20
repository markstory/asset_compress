<?php
namespace AssetCompress;

use ArrayIterator;
use AssetCompress\File\FileInterface;
use Iterator;
use InvalidArgumentException;

/**
 * A collection of AssetTargets.
 */
class AssetCollection extends IteratorIterator
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
            if (!($item instanceof FileInterface)) {
                throw new InvalidArgumentException("The item at $i does not implement FileInterface.");
            }
            $this->indexed[$item->name()] = $item;
        }
        parent::__construct($items);
    }

    public function append(FileInterface $target)
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
        unset($this->indexed[$name]);
    }
}

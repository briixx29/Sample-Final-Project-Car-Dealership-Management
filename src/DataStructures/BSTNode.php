<?php

namespace App\DataStructures;

use App\Models\Vehicle;

/**
 * Class BSTNode
 * Represents a node in the Binary Search Tree.
 * Key is the unique Vehicle ID.
 */
class BSTNode
{
    public int $id;
    /** @var Vehicle|array|null */
    public $data;
    public ?BSTNode $left;
    public ?BSTNode $right;

    public function __construct(int $id, $data = null)
    {
        $this->id = $id;
        $this->data = $data;
        $this->left = null;
        $this->right = null;
    }
}

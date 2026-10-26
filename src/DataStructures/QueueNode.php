<?php

namespace App\DataStructures;

/**
 * Class QueueNode
 * Represents an individual element in the Linked-List FIFO Queue.
 */
class QueueNode
{
    /** @var mixed */
    public $data;
    public ?QueueNode $next;

    public function __construct($data)
    {
        $this->data = $data;
        $this->next = null;
    }
}

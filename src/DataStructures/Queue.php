<?php

namespace App\DataStructures;

use App\Models\TestDrive;

/**
 * Class Queue
 * Implements a First-In, First-Out (FIFO) Linked List Queue for managing pending test-drive requests.
 * 
 * Algorithm Characteristics:
 * - Enqueue Time Complexity: O(1) via rear pointer
 * - Dequeue Time Complexity: O(1) via front pointer
 * - Peek Time Complexity: O(1)
 * - Space Complexity: O(k) for k queued items
 */
class Queue
{
    private ?QueueNode $front = null;
    private ?QueueNode $rear = null;
    private int $size = 0;

    public function __construct()
    {
        $this->front = null;
        $this->rear = null;
        $this->size = 0;
    }

    /**
     * Enqueue: Adds a test-drive request to the rear of the queue.
     * Time Complexity: O(1)
     * 
     * @param TestDrive|array $item
     */
    public function enqueue($item): void
    {
        $newNode = new QueueNode($item);

        if ($this->isEmpty()) {
            $this->front = $newNode;
            $this->rear = $newNode;
        } else {
            $this->rear->next = $newNode;
            $this->rear = $newNode;
        }

        $this->size++;
    }

    /**
     * Dequeue: Removes and returns the item at the front of the queue.
     * Time Complexity: O(1)
     * 
     * @return TestDrive|array|null
     */
    public function dequeue()
    {
        if ($this->isEmpty()) {
            return null;
        }

        $removedItem = $this->front->data;
        $this->front = $this->front->next;

        if ($this->front === null) {
            $this->rear = null;
        }

        $this->size--;

        return $removedItem;
    }

    /**
     * Peek: Returns the front item without removing it.
     * Time Complexity: O(1)
     * 
     * @return TestDrive|array|null
     */
    public function peek()
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->front->data;
    }

    /**
     * Check if queue is empty.
     */
    public function isEmpty(): bool
    {
        return $this->front === null || $this->size === 0;
    }

    /**
     * Get number of elements currently waiting in queue.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Convert entire queue to an array in FIFO order without mutating the queue.
     */
    public function toArray(): array
    {
        $elements = [];
        $current = $this->front;
        while ($current !== null) {
            $elements[] = $current->data;
            $current = $current->next;
        }
        return $elements;
    }

    /**
     * Populate queue from an array of pending test drive items (ordered by submission date/id ASC).
     */
    public static function buildFromRequests(array $requests): self
    {
        $queue = new self();
        foreach ($requests as $item) {
            $queue->enqueue($item);
        }
        return $queue;
    }

    /**
     * Clear all elements
     */
    public function clear(): void
    {
        $this->front = null;
        $this->rear = null;
        $this->size = 0;
    }
}

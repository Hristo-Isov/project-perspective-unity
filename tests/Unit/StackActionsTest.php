<?php

namespace Tests\Unit\Actions;

use App\Actions\Stack\PopFromStack;
use App\Actions\Stack\PushInStack;
use App\Models\StackItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StackActionsTest extends TestCase
{
    use RefreshDatabase;

    private function push(string $value): StackItem
    {
        return (new PushInStack())->handle($value);
    }

    private function pop(): ?StackItem
    {
        return (new PopFromStack())->handle();
    }

    public function test_push_returns_the_created_model(): void
    {
        $item = $this->push('Hello');

        $this->assertInstanceOf(StackItem::class, $item);
        $this->assertTrue($item->exists);
        $this->assertSame('Hello', $item->value);
    }

    public function test_pop_returns_null_on_an_empty_stack(): void
    {
        $this->assertNull($this->pop());
    }

    public function test_pop_returns_the_most_recently_pushed_value(): void
    {
        $this->push('Hello');
        $this->push('World');

        $this->assertSame('World', $this->pop()->value);
    }

    public function test_pop_removes_the_row_it_returns(): void
    {
        $this->push('Hello');

        $this->pop();

        $this->assertSame(0, StackItem::count());
    }

    public function test_it_follows_the_example_lifo_sequence(): void
    {
        $this->push('Hello');
        $this->push('World');

        $this->assertSame('World', $this->pop()->value);

        $this->push('Again');

        $this->assertSame('Again', $this->pop()->value);
        $this->assertSame('Hello', $this->pop()->value);
        $this->assertNull($this->pop());
    }

    public function test_pop_orders_by_id_not_by_insertion_timestamp(): void
    {
        $this->freezeTime();

        foreach (['1', '2', '3'] as $value) {
            $this->push($value);
        }

        $this->assertSame('3', $this->pop()->value);
        $this->assertSame('2', $this->pop()->value);
        $this->assertSame('1', $this->pop()->value);
    }

    public function test_duplicate_values_are_popped_independently(): void
    {
        $this->push('Duplicate');
        $this->push('Duplicate');

        $this->assertSame('Duplicate', $this->pop()->value);
        $this->assertSame('Duplicate', $this->pop()->value);
        $this->assertNull($this->pop());
    }

    public function test_the_stack_is_reusable_after_being_emptied(): void
    {
        $this->push('First');
        $this->pop();

        $this->push('Second');

        $this->assertSame('Second', $this->pop()->value);
    }

    public function test_an_empty_string_value_round_trips(): void
    {
        $this->push('');

        $this->assertSame('', $this->pop()->value);
    }

    public function test_a_long_value_is_stored_intact(): void
    {
        $value = str_repeat('x', 10000);

        $this->push($value);

        $this->assertSame($value, $this->pop()->value);
    }
}

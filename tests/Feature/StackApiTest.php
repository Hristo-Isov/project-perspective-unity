<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StackApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_an_item_returns_201_with_the_value(): void
    {
        $response = $this->postJson('/api/stack', ['value' => 'Hello']);

        $response->assertCreated()->assertJson(['value' => 'Hello']);
        $this->assertDatabaseHas('stack_items', ['value' => 'Hello']);
    }

    public function test_adding_an_item_requires_a_value(): void
    {
        $this->postJson('/api/stack', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);
    }

    public function test_getting_an_item_returns_and_removes_the_top_of_the_stack(): void
    {
        $this->postJson('/api/stack', ['value' => 'Hello']);
        $this->postJson('/api/stack', ['value' => 'World']);

        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'World']);
        $this->assertDatabaseMissing('stack_items', ['value' => 'World']);
        $this->assertDatabaseHas('stack_items', ['value' => 'Hello']);
    }

    public function test_stack_follows_the_example_lifo_sequence(): void
    {
        $this->postJson('/api/stack', ['value' => 'Hello']);
        $this->postJson('/api/stack', ['value' => 'World']);

        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'World']);

        $this->postJson('/api/stack', ['value' => 'Again']);

        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'Again']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'Hello']);
    }

    public function test_lifo_order_with_many_items(): void
    {
        foreach (['1', '2', '3', '4', '5'] as $value) {
            $this->postJson('/api/stack', ['value' => $value])->assertCreated();
        }

        foreach (['5', '4', '3', '2', '1'] as $expected) {
            $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => $expected]);
        }

        $this->assertDatabaseCount('stack_items', 0);
    }

    public function test_getting_from_an_empty_stack_returns_a_null_value(): void
    {
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => null]);
    }

    public function test_duplicate_values_are_stored_and_popped_independently(): void
    {
        $this->postJson('/api/stack', ['value' => 'Duplicate']);
        $this->postJson('/api/stack', ['value' => 'Duplicate']);

        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'Duplicate']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'Duplicate']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => null]);

        $this->assertDatabaseCount('stack_items', 0);
    }

    public function test_stack_is_reusable_after_being_emptied(): void
    {
        $this->postJson('/api/stack', ['value' => 'First']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'First']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => null]);

        $this->postJson('/api/stack', ['value' => 'Second']);
        $this->deleteJson('/api/stack')->assertOk()->assertJson(['value' => 'Second']);
    }

    public function test_pushing_a_whitespace_only_value_is_rejected(): void
    {
        $this->postJson('/api/stack', ['value' => ' '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);

        $this->assertDatabaseCount('stack_items', 0);
    }

    public function test_each_push_creates_a_new_row(): void
    {
        $this->postJson('/api/stack', ['value' => 'Hello']);
        $this->postJson('/api/stack', ['value' => 'Hello']);

        $this->assertDatabaseCount('stack_items', 2);
    }
}
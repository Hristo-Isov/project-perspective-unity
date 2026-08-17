<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyValueItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_putting_a_key_returns_201_with_key_and_value(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo'])
            ->assertCreated()
            ->assertJson(['key' => 'name', 'value' => 'Hristo']);

        $this->assertDatabaseHas('key_value_items', ['key' => 'name', 'value' => 'Hristo']);
    }

    public function test_getting_a_stored_key_returns_its_value(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);

        $this->getJson('/api/store/name')
            ->assertOk()
            ->assertJson(['key' => 'name', 'value' => 'Hristo']);
    }

    public function test_getting_a_stored_key_returns_only_key_and_value(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);

        $this->getJson('/api/store/name')
            ->assertOk()
            ->assertExactJson(['key' => 'name', 'value' => 'Hristo']);
    }

    public function test_getting_a_missing_key_returns_a_null_value(): void
    {
        $this->getJson('/api/store/age')
            ->assertOk()
            ->assertJson(['key' => 'age', 'value' => null]);
    }

    public function test_it_follows_the_example_ttl_sequence(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);
        $this->getJson('/api/store/name')->assertJson(['value' => 'Hristo']);

        $this->getJson('/api/store/age')->assertJson(['value' => null]);

        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan', 'ttl' => 30]);
        $this->getJson('/api/store/name')->assertJson(['value' => 'Ivan']);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);
    }

    public function test_a_null_ttl_is_accepted_and_means_no_expiry(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => null])
            ->assertCreated();

        $this->travel(10)->years();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Hristo']);
    }

    public function test_a_key_with_a_dot_is_accepted(): void
    {
        $this->postJson('/api/store', ['key' => 'user.name', 'value' => 'Hristo'])
            ->assertCreated();

        $this->getJson('/api/store/user.name')->assertJson(['value' => 'Hristo']);
    }

    public function test_deleting_a_key_removes_it(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);

        $this->deleteJson('/api/store/name')->assertNoContent();

        $this->getJson('/api/store/name')->assertJson(['value' => null]);
        $this->assertDatabaseMissing('key_value_items', ['key' => 'name']);
    }

    public function test_deleting_a_key_returns_an_empty_body(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);

        $response = $this->deleteJson('/api/store/name');

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
    }

    public function test_deleting_a_missing_key_is_safe(): void
    {
        $this->deleteJson('/api/store/ghost')->assertNoContent();
    }

    public function test_put_requires_key_and_value(): void
    {
        $this->postJson('/api/store', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key', 'value']);
    }

    public function test_put_rejects_an_invalid_ttl(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => -5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ttl']);
    }

    public function test_put_rejects_a_zero_ttl(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ttl']);
    }

    public function test_put_rejects_a_non_integer_ttl(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 'abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ttl']);

        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30.5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ttl']);
    }

    public function test_put_rejects_a_key_longer_than_255_characters(): void
    {
        $this->postJson('/api/store', ['key' => str_repeat('a', 256), 'value' => 'Hristo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_a_key_is_still_valid_at_the_exact_ttl_boundary(): void
    {
        $this->freezeSecond();
        
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);

        $this->travel(30)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Hristo']);

        $this->travel(1)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);
    }

    public function test_overwriting_a_key_replaces_its_ttl_with_a_shorter_one(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 300]);
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan', 'ttl' => 30]);

        $this->assertDatabaseCount('key_value_items', 1);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);
    }

    public function test_overwriting_a_key_replaces_its_ttl_with_a_longer_one(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan', 'ttl' => 300]);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Ivan']);
    }

    public function test_overwriting_a_key_without_a_ttl_clears_the_previous_expiry(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan']);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Ivan']);
    }

    public function test_overwriting_a_key_with_a_null_ttl_clears_the_previous_expiry(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan', 'ttl' => null]);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Ivan']);
    }

    public function test_an_expired_key_can_be_set_again(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);

        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan'])
            ->assertCreated();
        $this->getJson('/api/store/name')->assertJson(['value' => 'Ivan']);
    }

    public function test_overwriting_a_key_does_not_create_a_second_row(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Ivan']);

        $this->assertDatabaseCount('key_value_items', 1);
        $this->assertDatabaseHas('key_value_items', ['key' => 'name', 'value' => 'Ivan']);
    }

    public function test_an_expired_key_is_removed_from_the_database_on_read(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => 30]);

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);

        $this->assertDatabaseMissing('key_value_items', ['key' => 'name']);
    }

    public function test_a_numeric_value_is_stored_and_returned(): void
    {
        $this->postJson('/api/store', ['key' => 'count', 'value' => '42'])
            ->assertCreated();

        $this->getJson('/api/store/count')->assertJson(['value' => '42']);
    }

    public function test_an_empty_value_is_rejected(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);

        $this->assertDatabaseCount('key_value_items', 0);
    }

    public function test_a_ttl_given_as_a_numeric_string_is_accepted(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo', 'ttl' => '30'])
            ->assertCreated();

        $this->travel(31)->seconds();
        $this->getJson('/api/store/name')->assertJson(['value' => null]);
    }

    public function test_put_rejects_an_empty_key(): void
    {
        $this->postJson('/api/store', ['key' => '', 'value' => 'Hristo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['key']);
    }

    public function test_a_key_of_exactly_255_characters_is_accepted(): void
    {
        $key = str_repeat('a', 255);

        $this->postJson('/api/store', ['key' => $key, 'value' => 'Hristo'])
            ->assertCreated();

        $this->getJson('/api/store/' . $key)->assertJson(['value' => 'Hristo']);
    }

    public function test_one_key_expiring_does_not_affect_another(): void
    {
        $this->postJson('/api/store', ['key' => 'short', 'value' => 'Hristo', 'ttl' => 30]);
        $this->postJson('/api/store', ['key' => 'long', 'value' => 'Ivan', 'ttl' => 300]);

        $this->travel(31)->seconds();

        $this->getJson('/api/store/short')->assertJson(['value' => null]);
        $this->getJson('/api/store/long')->assertJson(['value' => 'Ivan']);
    }

    public function test_deleting_one_key_does_not_affect_another(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => 'Hristo']);
        $this->postJson('/api/store', ['key' => 'age', 'value' => '30']);

        $this->deleteJson('/api/store/name')->assertNoContent();

        $this->getJson('/api/store/name')->assertJson(['value' => null]);
        $this->getJson('/api/store/age')->assertJson(['value' => '30']);
    }

    public function test_a_whitespace_only_value_is_rejected(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => ' '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['value']);

        $this->assertDatabaseCount('key_value_items', 0);
    }

    public function test_surrounding_whitespace_is_trimmed_from_the_value(): void
    {
        $this->postJson('/api/store', ['key' => 'name', 'value' => '  Hristo  '])
            ->assertCreated();

        $this->getJson('/api/store/name')->assertJson(['value' => 'Hristo']);
    }

    public function test_a_key_with_a_space_round_trips_when_encoded(): void
    {
        $this->postJson('/api/store', ['key' => 'user name', 'value' => 'Hristo'])
            ->assertCreated();

        $this->getJson('/api/store/' . rawurlencode('user name'))
            ->assertJson(['value' => 'Hristo']);
    }

    public function test_a_key_containing_a_slash_is_stored_but_not_readable(): void
    {
        $this->postJson('/api/store', ['key' => 'user/name', 'value' => 'Hristo'])
            ->assertCreated();

        $this->getJson('/api/store/user/name')->assertNotFound();
    }
}
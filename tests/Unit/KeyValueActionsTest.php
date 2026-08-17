<?php

namespace Tests\Unit\Actions;

use App\Actions\KeyValue\ForgetKeyValue;
use App\Actions\KeyValue\PurgeExpiredKeys;
use App\Actions\KeyValue\RetrieveKeyValue;
use App\Actions\KeyValue\StoreKeyValue;
use App\Models\KeyValueItem;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KeyValueActionsTest extends TestCase
{
    use RefreshDatabase;

    private function storeKey(string $key, string $value, ?int $ttl = null): KeyValueItem
    {
        return (new StoreKeyValue())->handle($key, $value, $ttl);
    }

    private function retrieveValue(string $key): ?string
    {
        return (new RetrieveKeyValue())->handle($key)?->value;
    }

    private function forgetKey(string $key): bool
    {
        return (new ForgetKeyValue())->handle($key);
    }

    private function purgeExpired(): int
    {
        return (new PurgeExpiredKeys())->handle();
    }

    public function test_put_and_get_round_trip(): void
    {
        $this->storeKey('name', 'Hristo');

        $this->assertSame('Hristo', $this->retrieveValue('name'));
    }

    public function test_get_returns_null_for_a_missing_key(): void
    {
        $this->assertNull($this->retrieveValue('age'));
    }

    public function test_put_returns_the_stored_model(): void
    {
        $item = $this->storeKey('name', 'Hristo');

        $this->assertInstanceOf(KeyValueItem::class, $item);
        $this->assertTrue($item->exists);
        $this->assertSame('name', $item->key);
        $this->assertSame('Hristo', $item->value);
    }

    public function test_put_without_a_ttl_stores_a_null_expires_at(): void
    {
        $this->storeKey('name', 'Hristo');

        $this->assertNull(KeyValueItem::first()->expires_at);
    }

    public function test_put_with_a_ttl_stores_the_expiry_at_now_plus_ttl(): void
    {
        $this->freezeSecond();

        $this->storeKey('name', 'Hristo', 30);

        $this->assertTrue(now()->addSeconds(30)->equalTo(KeyValueItem::first()->expires_at));
    }

    public function test_expires_at_is_cast_to_a_date(): void
    {
        $this->storeKey('name', 'Hristo', 30);

        $this->assertInstanceOf(Carbon::class, KeyValueItem::first()->expires_at);
    }

    public function test_put_reuses_the_same_row_for_an_existing_key(): void
    {
        $first = $this->storeKey('name', 'Hristo');
        $second = $this->storeKey('name', 'Ivan', 30);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, KeyValueItem::count());
    }

    public function test_put_without_a_ttl_clears_a_previous_expiry(): void
    {
        $this->storeKey('name', 'Hristo', 30);
        $this->storeKey('name', 'Ivan');

        $this->assertNull(KeyValueItem::first()->expires_at);

        $this->travel(1)->hours();
        $this->assertSame('Ivan', $this->retrieveValue('name'));
    }

    public function test_get_returns_null_once_the_ttl_has_passed(): void
    {
        $this->storeKey('name', 'Ivan', 30);

        $this->assertSame('Ivan', $this->retrieveValue('name'));

        $this->travel(31)->seconds();
        $this->assertNull($this->retrieveValue('name'));
    }

    public function test_get_purges_an_expired_row(): void
    {
        $this->storeKey('name', 'Ivan', 30);

        $this->travel(31)->seconds();
        $this->retrieveValue('name');

        $this->assertSame(0, KeyValueItem::count());
    }

    public function test_a_key_is_not_expired_at_the_exact_expiry_second(): void
    {
        $this->freezeSecond();

        $item = $this->storeKey('name', 'Hristo', 30);

        $this->travel(30)->seconds();
        $this->assertFalse($item->fresh()->isExpired());

        $this->travel(1)->seconds();
        $this->assertTrue($item->fresh()->isExpired());
    }

    public function test_get_returns_the_value_at_the_exact_expiry_second(): void
    {
        $this->freezeSecond();

        $this->storeKey('name', 'Hristo', 30);

        $this->travel(30)->seconds();
        $this->assertSame('Hristo', $this->retrieveValue('name'));

        $this->travel(1)->seconds();
        $this->assertNull($this->retrieveValue('name'));
    }

    public function test_a_zero_ttl_expires_the_key_within_the_same_second(): void
    {
        $this->freezeSecond();

        $this->storeKey('name', 'Hristo', 0);

        $this->travel(1)->seconds();
        $this->assertNull($this->retrieveValue('name'));
    }

    public function test_a_key_without_a_ttl_never_expires(): void
    {
        $item = $this->storeKey('name', 'Hristo');

        $this->travel(10)->years();

        $this->assertFalse($item->fresh()->isExpired());
        $this->assertSame('Hristo', $this->retrieveValue('name'));
    }

    public function test_remove_reports_whether_a_row_was_deleted(): void
    {
        $this->storeKey('name', 'Hristo');

        $this->assertTrue($this->forgetKey('name'));
        $this->assertFalse($this->forgetKey('name'));
        $this->assertNull($this->retrieveValue('name'));
    }

    public function test_remove_only_deletes_the_given_key(): void
    {
        $this->storeKey('name', 'Hristo');
        $this->storeKey('age', '30');

        $this->forgetKey('name');

        $this->assertNull($this->retrieveValue('name'));
        $this->assertSame('30', $this->retrieveValue('age'));
    }

    public function test_one_key_expiring_does_not_affect_another(): void
    {
        $this->storeKey('short', 'Hristo', 30);
        $this->storeKey('long', 'Ivan', 300);

        $this->travel(31)->seconds();

        $this->assertNull($this->retrieveValue('short'));
        $this->assertSame('Ivan', $this->retrieveValue('long'));
    }

    public function test_an_empty_string_value_round_trips(): void
    {
        $this->storeKey('name', '');

        $this->assertSame('', $this->retrieveValue('name'));
    }

    public function test_a_long_value_is_stored_intact(): void
    {
        $value = str_repeat('x', 10000);

        $this->storeKey('rand', $value);

        $this->assertSame($value, $this->retrieveValue('rand'));
    }

    public function test_a_unicode_value_is_stored_intact(): void
    {
        $this->storeKey('name', 'Иван');

        $this->assertSame('Иван', $this->retrieveValue('name'));
    }

    public function test_keys_are_unique(): void
    {
        KeyValueItem::create(['key' => 'name', 'value' => 'Hristo']);

        $this->expectException(QueryException::class);

        KeyValueItem::create(['key' => 'name', 'value' => 'Ivan']);
    }

    public function test_purge_deletes_only_expired_keys_with_a_ttl(): void
    {
        $this->storeKey('foo', 'gone', 30);
        $this->storeKey('bar', 'stays', 300);
        $this->storeKey('baz', 'stays');

        $this->travel(31)->seconds();

        $this->assertSame(1, $this->purgeExpired());
        $this->assertSame(2, KeyValueItem::count());
        $this->assertSame('stays', $this->retrieveValue('bar'));
        $this->assertSame('stays', $this->retrieveValue('baz'));
    }
}

<?php

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use App\Models\Setting;

beforeEach(function () {
    Setting::clearCache();

    $this->season = Season::create([
        'name' => 'Noël 2025',
        'start_date' => now()->subDays(10),
        'end_date' => now()->addDays(30),
        'next_family_number' => 1,
    ]);

    $this->family = Family::create([
        'email' => 'test@example.com',
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'address' => 'Rue de la Gare 1',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0791234567',
    ]);

    $this->giftRequest = GiftRequest::create([
        'family_id' => $this->family->id,
        'season_id' => $this->season->id,
        'status' => GiftRequest::STATUS_PENDING,
    ]);
});

test('generateCode formats code as prefix + padded family number + slash + child number', function () {
    expect(Child::generateCode('Y', 1, 1))->toBe('Y0001/1');
    expect(Child::generateCode('Y', 42, 3))->toBe('Y0042/3');
    expect(Child::generateCode('AB', 100, 5))->toBe('AB0100/5');
});

test('generateCode works with empty prefix', function () {
    expect(Child::generateCode('', 1, 1))->toBe('0001/1');
    expect(Child::generateCode('', 99, 7))->toBe('0099/7');
});

test('child is created without code when no auto-generation', function () {
    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    expect($child->code)->toBeNull();
    expect($child->child_number)->toBeNull();
});

test('assignChildNumberAndCode assigns child number 1 for first child', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $this->giftRequest->update(['family_number' => 5]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child->assignChildNumberAndCode();

    expect($child->child_number)->toBe(1);
    expect($child->code)->toBe('Y0005/1');
});

test('assignChildNumberAndCode increments child number for subsequent children', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $this->giftRequest->update(['family_number' => 3]);

    $child1 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);
    $child1->assignChildNumberAndCode();

    $child2 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2016,
        'gift' => 'Lego',
        'status' => Child::STATUS_PENDING,
    ]);
    $child2->assignChildNumberAndCode();

    $child3 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Charlie',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2020,
        'gift' => 'Peluche',
        'status' => Child::STATUS_PENDING,
    ]);
    $child3->assignChildNumberAndCode();

    expect($child1->child_number)->toBe(1);
    expect($child1->code)->toBe('Y0003/1');

    expect($child2->child_number)->toBe(2);
    expect($child2->code)->toBe('Y0003/2');

    expect($child3->child_number)->toBe(3);
    expect($child3->code)->toBe('Y0003/3');
});

test('assignChildNumberAndCode does nothing when family has no family_number', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child->assignChildNumberAndCode();

    expect($child->child_number)->toBeNull();
    expect($child->code)->toBeNull();
});

test('assignChildNumberAndCode uses setting code prefix', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Z');

    $this->giftRequest->update(['family_number' => 1]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child->assignChildNumberAndCode();

    expect($child->code)->toBe('Z0001/1');
});

test('assignChildNumberAndCode works with empty prefix', function () {
    // No CODE_PREFIX set, defaults to ''

    $this->giftRequest->update(['family_number' => 7]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child->assignChildNumberAndCode();

    expect($child->code)->toBe('0007/1');
});

test('child numbers are scoped per gift request', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');

    $family2 = Family::create([
        'email' => 'family2@example.com',
        'first_name' => 'Marie',
        'last_name' => 'Martin',
        'address' => 'Rue du Lac 5',
        'postal_code' => '1000',
        'city' => 'Lausanne',
        'phone' => '0799999999',
    ]);

    $giftRequest2 = GiftRequest::create([
        'family_id' => $family2->id,
        'season_id' => $this->season->id,
        'family_number' => 2,
        'status' => GiftRequest::STATUS_VALIDATED,
    ]);

    $this->giftRequest->update(['family_number' => 1]);

    // First family, first child
    $child1 = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);
    $child1->assignChildNumberAndCode();

    // Second family, first child — should get child_number 1, not 2
    $child2 = Child::create([
        'gift_request_id' => $giftRequest2->id,
        'first_name' => 'Bob',
        'gender' => Child::GENDER_BOY,
        'birth_year' => 2017,
        'gift' => 'Lego',
        'status' => Child::STATUS_PENDING,
    ]);
    $child2->assignChildNumberAndCode();

    expect($child1->child_number)->toBe(1);
    expect($child1->code)->toBe('Y0001/1');

    expect($child2->child_number)->toBe(1);
    expect($child2->code)->toBe('Y0002/1');
});

test('generateCode respects custom padding', function () {
    expect(Child::generateCode('Y', 1, 1, 2))->toBe('Y01/1');
    expect(Child::generateCode('Y', 1, 1, 6))->toBe('Y000001/1');
    expect(Child::generateCode('Y', 12345, 3, 3))->toBe('Y12345/3');
});

test('assignChildNumberAndCode uses setting padding', function () {
    Setting::setValue(Setting::CODE_PREFIX, 'Y');
    Setting::setValue(Setting::CODE_FAMILY_PADDING, 6);

    $this->giftRequest->update(['family_number' => 5]);

    $child = Child::create([
        'gift_request_id' => $this->giftRequest->id,
        'first_name' => 'Alice',
        'gender' => Child::GENDER_GIRL,
        'birth_year' => 2018,
        'gift' => 'Poupée',
        'status' => Child::STATUS_PENDING,
    ]);

    $child->assignChildNumberAndCode();

    expect($child->code)->toBe('Y000005/1');
});

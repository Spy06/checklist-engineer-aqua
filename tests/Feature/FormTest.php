<?php

use App\Models\FormSubmission;
use App\Models\User;
use Database\Seeders\FormSeeder;

beforeEach(function () {
    $this->seed(FormSeeder::class);
});

test('guests are redirected to the login page when visiting forms', function () {
    $response = $this->get(route('forms.show', 'work-at-height/fix-ladder/cimex/ladder-1'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit a form and submit answers', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Visit the form page
    $response = $this->get(route('forms.show', 'work-at-height/fix-ladder/cimex/ladder-1'));
    $response->assertOk();

    // Submit the form
    $submitResponse = $this->post(route('forms.store', 'work-at-height/fix-ladder/cimex/ladder-1'), [
        'date' => '2026-07-27',
        'answers' => [
            0 => '1',
        ],
        'notes' => 'Test catatan',
    ]);

    $submitResponse->assertRedirect(route('forms.show', 'work-at-height/fix-ladder/cimex/ladder-1'));
    $submitResponse->assertSessionHas('success');

    // Assert it was stored in DB
    $this->assertDatabaseHas('form_submissions', [
        'user_id' => $user->id,
        'form_type' => 'work-at-height.fix-ladder.cimex.ladder-1',
        'date' => '2026-07-27 00:00:00',
        'notes' => 'Test catatan',
    ]);
});

test('authenticated users can submit a form with zero checks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Submit the form without checking anything (no answers parameter sent)
    $submitResponse = $this->post(route('forms.store', 'work-at-height/fix-ladder/cimex/ladder-1'), [
        'date' => '2026-07-27',
        'notes' => 'Catatan kosong',
    ]);

    $submitResponse->assertRedirect(route('forms.show', 'work-at-height/fix-ladder/cimex/ladder-1'));
    $submitResponse->assertSessionHas('success');

    // Assert it was stored in DB with empty answers array
    $submission = FormSubmission::first();
    expect($submission->answers)->toBeEmpty();
});

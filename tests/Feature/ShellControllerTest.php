<?php

beforeEach(function () {
    config(['app.shell_secret' => 'test-secret']);
});

test('the shell page can be displayed', function () {
    $this->get(route('shell.index'))
        ->assertOk()
        ->assertViewIs('shell.index');
});

test('the shell command requires a secret', function () {
    $this->postJson(route('shell.store'), [
        'command' => 'echo hello',
    ])->assertUnprocessable();
});

test('the shell command is rejected with a wrong secret', function () {
    $this->postJson(route('shell.store'), [
        'secret' => 'wrong-secret',
        'command' => 'echo hello',
    ])->assertForbidden();
});

test('the shell command is rejected when no secret is configured', function () {
    config(['app.shell_secret' => null]);

    $this->postJson(route('shell.store'), [
        'secret' => 'test-secret',
        'command' => 'echo hello',
    ])->assertForbidden();
});

test('the shell command is executed with a valid secret', function () {
    $this->postJson(route('shell.store'), [
        'secret' => 'test-secret',
        'command' => 'echo hello',
    ])
        ->assertOk()
        ->assertJson([
            'resultCode' => 0,
            'output' => ['hello'],
        ]);
});

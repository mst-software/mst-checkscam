<?php

use App\Models\NewfeedPost;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function postCreatedTwoHoursAgo(): NewfeedPost
{
    $post = NewfeedPost::create([
        'user_id' => User::factory()->create(['role' => 'user', 'status' => 1])->id,
        'category' => 'Dịch vụ MMO',
        'content' => 'Bán tài khoản giá tốt',
        'price' => 150000,
    ]);
    $post->forceFill(['created_at' => now()->subHours(2)])->save();

    return $post;
}

test('relative dates are vietnamese by default', function () {
    expect(now()->subHours(2)->diffForHumans())->toBe('2 giờ trước');
});

test('newfeed pages show vietnamese relative times', function () {
    $post = postCreatedTwoHoursAgo();

    $this->get(route('newfeed.index'))->assertOk()->assertSee('2 giờ trước')->assertDontSee('hours ago');
    $this->get(route('newfeed.show', $post))->assertOk()->assertSee('2 giờ trước')->assertDontSee('hours ago');
});

test('the newfeed api returns vietnamese relative times', function () {
    postCreatedTwoHoursAgo();

    $this->getJson(route('api.newfeed.posts.index'))
        ->assertOk()
        ->assertJsonPath('data.0.created_at', '2 giờ trước');
});

test('a new comment returns a vietnamese relative time', function () {
    $report = Report::factory()->create(['status' => 'approved']);

    $createdAt = $this->postJson(route('comment.store', $report->id), [
        'full_name' => 'Nguyen Van A',
        'content' => 'Mình cũng bị đối tượng này lừa tuần trước.',
    ], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('comment.created_at');

    expect($createdAt)->toEndWith('giây trước');
});

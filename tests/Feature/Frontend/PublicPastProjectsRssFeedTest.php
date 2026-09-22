<?php

beforeEach(function () {
    seedTestData();
});

test('public past projects rss feed returns valid xml', function () {
    $publicPast = createProject(['is_public' => true]);
    $publicPast->update(['student_name' => 'Jane Public']);

    $response = $this->get('/projects/past/feed.rss');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
    $response->assertSee('<rss version="2.0"', false);
    $response->assertSee($publicPast->name, false);
    $response->assertSee(route('projects.show', $publicPast), false);
    $response->assertSee('<![CDATA['.$publicPast->short_description.']]>', false);
});

test('rss feed only includes public taken published projects', function () {
    $included = createProject(['is_public' => true]);
    $included->update(['student_name' => 'Included Student']);

    $privatePast = createProject(['is_public' => false]);
    $privatePast->update(['student_name' => 'Private Student']);

    $availablePublic = createProject(['is_public' => true]);

    $conceptPast = createProject(['is_public' => true, 'is_published' => false]);
    $conceptPast->update(['student_name' => 'Concept Student']);

    $response = $this->get('/projects/past/feed.rss');

    $response->assertStatus(200);
    $response->assertSee($included->name, false);
    $response->assertDontSee($privatePast->name, false);
    $response->assertDontSee($availablePublic->name, false);
    $response->assertDontSee($conceptPast->name, false);
});

test('rss feed is accessible without authentication', function () {
    config([
        'saml.enabled' => true,
        'saml.require_login' => true,
    ]);

    $publicPast = createProject(['is_public' => true]);
    $publicPast->update(['student_name' => 'Jane Public']);

    $response = $this->get('/projects/past/feed.rss');

    $response->assertStatus(200);
    $response->assertSee($publicPast->name, false);
});

test('past projects page advertises rss feed', function () {
    $response = $this->get('/projects/past');

    $response->assertStatus(200);
    $response->assertSee('type="application/rss+xml"', false);
    $response->assertSee(route('projects.past.feed'), false);
});

<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_index_page()
    {
        $response = $this->get(route('posts.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_record()
    {
        // Create a user for the foreign key
        $user = User::factory()->create();
        
        $postData = [
            'title' => 'Test Post Title',
            'content' => 'Test post content',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'user_id' => $user->id,
        ];
        
        // Submit the form
        $response = $this->post(route('posts.store'), $postData);
        
        // Debug response
        if ($response->status() !== 302) {
            dump($response->content());
        }
        
        // Check redirect
        $response->assertRedirect(route('posts.index'));
        
        // Check database
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post Title',
            'content' => 'Test post content',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_show_record()
    {
        $post = Post::factory()->create();
        $response = $this->get(route('posts.show', $post));
        $response->assertStatus(200);
    }

    public function test_can_edit_record()
    {
        $post = Post::factory()->create();
        $response = $this->get(route('posts.edit', $post));
        $response->assertStatus(200);
    }

    public function test_can_update_record()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();
        
        $updatedData = [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'published_at' => now()->format('Y-m-d\TH:i'),
            'user_id' => $user->id,
        ];
        
        $response = $this->put(route('posts.update', $post), $updatedData);
        $response->assertRedirect(route('posts.index'));
        
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_delete_record()
    {
        $post = Post::factory()->create();
        $response = $this->delete(route('posts.destroy', $post));
        $response->assertRedirect(route('posts.index'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
} 
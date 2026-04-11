<?php
namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('tasks.index'));
    }

    /** @test */
    public function it_loads_tasks_index_page()
    {
        $response = $this->get(route('tasks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tasks.index');
    }

    /** @test */
    public function it_returns_tasks_in_json_for_ajax_request()
    {
        Task::factory()->count(3)->create();

        $response = $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->getJson(route('tasks.getTasks'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'created_at', 'updated_at'],
                ],
            ]);
    }

    /** @test */
    public function it_redirects_if_not_ajax_request()
    {
        $response = $this->get(route('tasks.getTasks'));

        $response->assertRedirect(route('tasks.index'));
    }

    /** @test */
    public function it_creates_a_task()
    {
        $data = [
            'title'       => 'Test Task',
            'description' => 'Test Description',
        ];

        $response = $this->postJson(route('tasks.store'), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', $data);
    }

    /** @test */
    public function it_validates_task_creation()
    {
        $response = $this->postJson(route('tasks.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    /** @test */
    public function it_updates_a_task()
    {
        $task = Task::factory()->create();

        $updateData = [
            'title'       => 'Updated Title',
            'description' => 'Updated Description',
            'status'      => 'completed',
        ];

        $response = $this->postJson(route('tasks.update', $task->id), $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', $updateData);
    }

    /** @test */
    public function it_deletes_a_task()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson(route('tasks.destroy', $task->id));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}

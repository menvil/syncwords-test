<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private $token = null;

    private function authUser(): void
    {
        $response = $this->post('/api/login', ["email"=>"user@test.com", "password" => "password"]);
        $res_array = json_decode($response->content(), true);
        $this->token = $res_array['data']['token'];
        $this->user = User::where('email', 'user@test.com')->first();
    }

    public function test_auth_success(): void
    {
        $response = $this->post('/api/login', ["email"=>"user@test.com", "password" => "password"]);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('success', $res_array);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertArrayHasKey('token', $res_array['data']);
        $response->assertStatus(200);
    }

    public function test_auth_failed(): void
    {
        $response = $this->post('/api/login', ["email"=>"user@test.com", "password" => "password1"]);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertArrayHasKey('data', $res_array);
    }

    public function test_list_route(): void
    {
        $this->authUser();
        $response = $this->get('/api/list');
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertArrayHasKey('data', $res_array);

        $this->assertCount(
            Event::where('organization_id', $this->user->id)->count(),
            array_filter($res_array['data'], function ($var) {
                return ($var['organization_id'] == $this->user->id);
            })
        );
    }

    public function test_list_failed_route(): void
    {
        $response = $this->get('/api/list');
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
    }

    // ========= SHOW tests ================
    public function test_show_failed_route(): void
    {
        $event = Event::all()->last();
        $response = $this->get('/api/' . $event->id);
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
    }

    public function test_show_route(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->get('/api/' . $event->id);
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Event retrieved successfully.', $res_array['message']);
        $this->assertEquals($this->user->id, $res_array['data']['organization_id']);
    }

    public function test_show_another_user(): void
    {
        $this->authUser();
        $response = $this->get('/api/' . 1);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Error.', $res_array['message']);
        $this->assertEquals('User not authorised to see event with id #1', $res_array['data']['error']);
    }

    public function test_show_not_exist(): void
    {
        $this->authUser();
        $response = $this->get('/api/' . 1342);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Error.', $res_array['message']);
        $this->assertEquals('Cannot find event with id #1342', $res_array['data']['error']);
    }

    // ========= STORE tests ================
    public function test_store_failed_route(): void
    {
        $response = $this->post('/api');
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Unauthenticated.', $res_array['message']);
    }

    public function test_store_wrong_title(): void
    {
        $this->authUser();
        $response = $this->post('/api', ['event_title' => "", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]);
        $response->assertStatus(422);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('event_title', $res_array['data']);
    }

    public function test_store_wrong_end_date(): void
    {
        $this->authUser();
        $response = $this->post('/api', ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2021-01-01 12:10:10"]);
        $response->assertStatus(422);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
    }

    public function test_store_wrong_end_date_long(): void
    {
        $this->authUser();
        $response = $this->post('/api', ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-03-01 12:10:10"]);
        $response->assertStatus(422);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
    }

    public function test_store_wrong_date_format(): void
    {
        $this->authUser();
        $response = $this->post('/api', ['event_title' => "test", 'event_start_date' => "2022-01-01", 'event_end_date' => "2022-01-01"]);
        $response->assertStatus(422);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
    }

    public function test_store_success(): void
    {
        $this->authUser();
        $beforeStore = Event::where('organization_id', $this->user->id)->count();
        $response = $this->post('/api', ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]);
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertEquals(
            Event::where('organization_id', $this->user->id)->count(),
            $beforeStore + 1
        );
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Event created successfully.', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
    }

    // ========= DELETE tests ================
    public function test_delete_failed_route(): void
    {
        $event = Event::all()->last();
        $response = $this->delete('/api/' . $event->id);
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
    }

    public function test_delete_route(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->delete('/api/' . $event->id);
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Event deleted successfully.', $res_array['message']);
    }

    public function test_delete_another_user(): void
    {
        $this->authUser();
        $response = $this->delete('/api/' . 1);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Error.', $res_array['message']);
        $this->assertEquals('User not authorised to see event with id #1', $res_array['data']['error']);
    }

    public function test_delete_not_exist(): void
    {
        $this->authUser();
        $response = $this->delete('/api/' . 1342);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Error.', $res_array['message']);
        $this->assertEquals('Cannot find event with id #1342', $res_array['data']['error']);
    }

    // ====== UPDATE tests =======

    public function test_update_failed_route(): void
    {
        $event = Event::all()->last();
        $response = $this->put('/api/' . $event->id, ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]);
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
    }

    public function test_update_patch_failed_route(): void
    {
        $event = Event::all()->last();
        $response = $this->put('/api/' . $event->id, ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]);
        $response->assertStatus(401);
        $res_array = json_decode($response->content(), true);
        $this->assertArrayHasKey('message', $res_array);
    }

    public function test_put_route_trying_to_update_wrong_entity(): void
    {
        $this->authUser();
        $response = $this->delete('/api/' . 1, ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]);
        $response->assertStatus(404);
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Error.', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('error', $res_array['data']);
    }

    public function test_put_route_trying_to_update_wrong_field(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['id' => $event->id - 1, 'organization_id' => 1, 'event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]
        );
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Event updated successfully.', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertEquals($event->id, $res_array['data']['id']);
        $this->assertEquals($this->user->id, $res_array['data']['organization_id']);
    }

    public function test_put_route_trying_to_update_all_fields(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => "test", 'event_start_date' => "2022-01-01 10:10:10", 'event_end_date' => "2022-01-01 12:10:10"]
        );
        $response->assertStatus(200);
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Event updated successfully.', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertEquals('test', $res_array['data']['event_title']);
        $this->assertEquals('2022-01-01 10:10:10', $res_array['data']['event_start_date']);
        $this->assertEquals('2022-01-01 12:10:10', $res_array['data']['event_end_date']);
        $event->refresh();
        $this->assertEquals($event->event_title, 'test');
        $this->assertEquals($event->event_start_date, '2022-01-01 10:10:10');
        $this->assertEquals($event->event_end_date, '2022-01-01 12:10:10');
    }

    public function test_put_route_trying_to_update_title_field(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => ""]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_title', $res_array['data']);
    }

    public function test_put_route_trying_to_update_title_field_and_start_time_incorrect_date(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => "test", 'event_start_date' => "2021-01-01 10:10:10",]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
        $event->refresh();
        $this->assertNotEquals('test', $event->event_title);
    }

    public function test_put_route_trying_to_update_title_field_and_start_time_incorrect_format(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => "test", 'event_start_date' => "2022-01-01"]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
        $event->refresh();
        $this->assertNotEquals('test', $event->event_title);
    }

    public function test_put_route_trying_to_update_title_field_and_start_time_ahead(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => "test", 'event_start_date' => "2028-01-01 10:10:10"]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('message', $res_array);
        $this->assertEquals('Validation Error', $res_array['message']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
        $event->refresh();
        $this->assertNotEquals('test', $event->event_title);
    }

    public function test_put_route_trying_to_update_title_field_and_start_time_wrong_frame(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $startTime = Carbon::parse($event->event_start_date)->addMinute(1);
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_title' => "test", 'event_start_date' => $startTime->format('Y-m-d H:i:s')]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
        $this->assertArrayHasKey('event_title', $res_array['data']);
        $event->refresh();
        $this->assertEquals('test', $event->event_title);
        $this->assertEquals($startTime->format('Y-m-d H:i:s'), $event->event_start_date);
    }

    public function test_put_route_trying_to_update_end_time_wrong_frame(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $endTime = Carbon::parse($event->event_end_date)->addYear(1);
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_end_date' => $endTime->format('Y-m-d H:i:s')]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
        $event->refresh();
        $this->assertNotEquals($endTime->format('Y-m-d H:i:s'), $event->event_end_date);
    }

    public function test_put_route_trying_to_update_end_time_wrong_frame_before_start_date(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $endTime = Carbon::parse($event->event_end_date)->subYear(1);
        $response = $this->put(
            '/api/' . $event->id ,
            ['event_end_date' => $endTime->format('Y-m-d H:i:s')]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertFalse($res_array['success']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
        $event->refresh();
        $this->assertNotEquals($endTime->format('Y-m-d H:i:s'), $event->event_end_date);
    }

    public function test_put_route_trying_to_update_end_time(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $endTime = Carbon::parse($event->event_end_date)->subMinute(1);
        $response = $this->put(
            '/api/' . $event->id,
            ['event_end_date' => $endTime->format('Y-m-d H:i:s')]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
        $event->refresh();
        $this->assertEquals($endTime->format('Y-m-d H:i:s'), $event->event_end_date);
    }

    public function test_put_route_trying_to_update_all_fields_via_patch(): void
    {
        $this->authUser();
        $event = Event::all()->last();
        $endTime = Carbon::parse($event->event_end_date)->addMinute(1);
        $startTime = Carbon::parse($event->event_start_date)->addMinute(1);
        $response = $this->patch(
            '/api/' . $event->id ,
            ['event_title' => 'test', 'event_start_date' => $startTime->format('Y-m-d H:i:s'), 'event_end_date' => $endTime->format('Y-m-d H:i:s')]
        );
        $res_array = json_decode($response->content(), true);
        $this->assertTrue($res_array['success']);
        $this->assertArrayHasKey('data', $res_array);
        $this->assertArrayHasKey('event_end_date', $res_array['data']);
        $this->assertArrayHasKey('event_start_date', $res_array['data']);
        $this->assertArrayHasKey('event_title', $res_array['data']);
        $event->refresh();
        $this->assertEquals($endTime->format('Y-m-d H:i:s'), $event->event_end_date);
        $this->assertEquals($startTime->format('Y-m-d H:i:s'), $event->event_start_date);
        $this->assertEquals('test', $event->event_title);
    }
}

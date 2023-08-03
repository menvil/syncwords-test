<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\EventStoreRequest;
use App\Http\Requests\EventUpdateRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Validator;
use App\Http\Resources\EventResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\EventViewRequest;

class EventController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $events = User::findOrFail(
                auth('sanctum')->user()->id
            )->events;
            return $this->sendResponse(EventResource::collection($events), 'Events retrieved successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Error.', ['error'=>'Cannot show events for this user']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $event = Event::findOrFail($id);
            $this->authorize('view', $event);
        } catch (AuthorizationException $e) {
            return $this->sendError('Error.', ['error'=>'User not authorised to see event with id #' . $id]);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Error.', ['error'=>'Cannot find event with id #' . $id]);
        } catch (\Exception $e) {
            return $this->sendError('Error.', ['error'=>'Cannot show event with id #' . $id]);
        }
        return $this->sendResponse(new EventResource($event), 'Event retrieved successfully.');
    }

    /**
     * Store a newly created resource.
     *
     * @param EventStoreRequest $request
     * @return JsonResponse
     */
    public function store(EventStoreRequest $request): JsonResponse
    {
        $event = (new Event)->fill($request->only('event_title', 'event_start_date', 'event_end_date'))
            ->organization()
            ->associate(auth('sanctum')->user());
        $event->save();
        return $this->sendResponse(new EventResource($event->withoutRelations()), 'Event created successfully.');
    }


    /**
     * Update resource.
     *
     * @param EventUpdateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(EventUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $event = Event::findOrFail($id);
            $this->authorize('update', $event);
            $event = $event->fill($request->only('event_title', 'event_start_date', 'event_end_date'));
            $event->save();
        } catch (AuthorizationException $e) {
            return $this->sendError('Error.', ['error'=>'User not authorised to see event with id #' . $id]);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Error.', ['error'=>'Cannot find event with id #' . $id]);
        } catch (\Exception $e) {
            return $this->sendError('Error.', ['error'=>'Cannot show1 event with id #' . $id]);
        }

        return $this->sendResponse(new EventResource($event->withoutRelations()), 'Event updated successfully.');
    }

    /**
     * Delete resource.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $event = Event::findOrFail($id);
            $this->authorize('delete', $event);
            $event->delete();
        } catch (AuthorizationException $e) {
            return $this->sendError('Error.', ['error'=>'User not authorised to see event with id #' . $id]);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Error.', ['error'=>'Cannot find event with id #' . $id]);
        } catch (\Exception $e) {
            return $this->sendError('Error.', ['error'=>'Cannot show event with id #' . $id]);
        }

        return $this->sendResponse('Success', 'Event deleted successfully.');
    }
}

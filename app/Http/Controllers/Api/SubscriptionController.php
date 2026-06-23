<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Subscription::query()
                ->with('software:id,name')
                ->where('user_id', auth()->id())
                ->latest()
                ->get(),
        ]);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = Subscription::query()->firstOrCreate([
            'user_id' => $request->user()?->id,
            'software_id' => $request->integer('software_id'),
            'event' => $request->string('event')->toString(),
        ]);

        return response()->json([
            'data' => $subscription->load('software:id,name'),
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Subscription $subscription): JsonResponse
    {
        abort_unless($subscription->user_id === auth()->id(), 404);

        $subscription->delete();

        return response()->json(status: 204);
    }
}

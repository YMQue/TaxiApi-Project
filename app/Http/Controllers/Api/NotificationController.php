<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFcmTokenRequest;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return NotificationResource::collection($user->Notifications()->paginate(20))
            ->additional(['unread_count' => $this->notifications->unreadCount($user)]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $this->notifications->markAsRead($request->user(), $id);

        return $this->success('Marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        $this->notifications->markAllAsRead($request->user());

        return $this->success('All marked as read');
    }

    public function storeFcmToken(StoreFcmTokenRequest $request)
    {
        $this->notifications->saveToken($request->user(), $request->validated('fcm_token'));

        return $this->success('Token saved');
    }
}

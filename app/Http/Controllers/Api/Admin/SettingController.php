<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    use ApiResponse;

    public function __construct(private SettingRepositoryInterface $settings) {}

    public function index(): JsonResponse
    {
        return $this->success('Settings list', $this->settings->all());
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        return $this->success(
            'Settings updated successfully',
            $this->settings->update($request->validated())
        );
    }
}
<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Storage;
use Modules\Auth\Contracts\UserRepositoryInterface;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\HRM\Traits\File\FileManager;

class UserService
{
    use FileManager;

    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get the authenticated user's profile
     *
     * @return array
     */
    public function getProfile(): array
    {
        $user = auth()->user();

        return [
            'user' => new UserResource($user),
        ];
    }

    /**
     * Update the authenticated user's profile
     *
     * @param UpdateUserRequest $request
     * @return array
     */
    public function updateProfile(UpdateUserRequest $request): array
    {
        $user = auth()->user();
        $data = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->handleAvatarUpload($request, $user);
        }

        $updated = $this->userRepository->update($user->id, $data);

        if (!$updated) {
            throw new \Exception('Profile update failed');
        }

        return [
            'message' => 'Profile updated successfully',
            'user' => new UserResource($this->userRepository->find($user->id)),
        ];
    }

    /**
     * Handle avatar upload logic
     *
     * @param UpdateUserRequest $request
     * @param \App\Models\User $user
     * @return string
     */
    private function handleAvatarUpload(UpdateUserRequest $request, $user): string
    {
        // Check if user has employee record
        if (!$user->employee) {
            throw new \Exception('No employee record found for user. Only employees can upload profile images.');
        }
        // if ($user->avatar && Storage::disk('s3')->exists($user->avatar)) {
        //     $this->deleteFile($user->avatar);
        // }
        // Get file extension
        $file = $request->file('avatar');
        $extension = $file->getClientOriginalExtension();

        // Use employee code path like HRM module
        $path = "employees/{$user->employee->employee_code}/profile";
        $filename = "avatar.{$extension}";

        // Store new avatar using S3 with proper naming structure
        return $this->uploadFile($file, $path, $filename);
    }
}

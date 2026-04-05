<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\RoleController;
use Modules\Auth\Http\Controllers\UserController;
use Modules\Auth\Http\Controllers\PermissionController;
use Modules\Auth\Http\Controllers\PasswordResetController;
use App\Http\Controllers\NotificationController;

Route::post('user/login', [AuthController::class, 'login']);

// Password Reset Routes (public, no authentication required)
Route::post('user/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('user/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    // User Role Management - Specific routes must come before resource routes
    Route::get('users/logged-in/permissions', [UserController::class, 'getLoggedInUserPermissions']);
    Route::get('users/profile', [UserController::class, 'profile']);
    Route::post('users/profile', [UserController::class, 'updateProfile']);
    Route::post('users/{user}/assign-roles', [UserController::class, 'assignRoles']);
    Route::post('users/{user}/revoke-roles', [UserController::class, 'revokeRoles']);
    Route::get('users/stats', [UserController::class, 'stats']);

    Route::post('/admin/users/force-logout', [UserController::class, 'forceLogout']);

    // Resource routes - these must come after specific routes
    Route::apiResource('users', UserController::class);
    // Roles
    Route::get('roles', [RoleController::class, 'index']); // List roles
    Route::post('roles', [RoleController::class, 'store']); // Create role
    Route::get('roles/{role}', [RoleController::class, 'show']); // show role
    Route::put('roles/{role}', [RoleController::class, 'update']); // Update role
    Route::delete('roles/{role}', [RoleController::class, 'destroy']); // Delete role

    // Route::post('roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions']);
    // Route::post('roles/{role}/revoke-permissions', [RoleController::class, 'revokePermissions']);
    // Route::post('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions']);

    // Permissions
    Route::apiResource('permissions', PermissionController::class);

    // Notifications API routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::delete('/{id}', [NotificationController::class, 'remove']);
        Route::post('/mark-read', [NotificationController::class, 'markRead']);
        Route::post('/mark-unread', [NotificationController::class, 'markUnread']);
    });
});


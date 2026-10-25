<?php

use App\Http\Controllers\Audit\AuditHistoryController;
use App\Http\Controllers\Auth\PendingRegisteredUserController;
use App\Http\Controllers\Identity\UserAccountLifecycleController;
use App\Http\Controllers\Identity\UserApprovalController;
use App\Http\Controllers\Identity\UserMembershipController;
use App\Http\Controllers\Properties\EngineeringAssetController;
use App\Http\Controllers\Properties\GuestController;
use App\Http\Controllers\Properties\HousekeepingTaskController;
use App\Http\Controllers\Properties\MyPropertiesController;
use App\Http\Controllers\Properties\PropertyController;
use App\Http\Controllers\Properties\PropertyInventoryController;
use App\Http\Controllers\Properties\RatePlanController;
use App\Http\Controllers\Properties\WorkOrderController;
use App\Http\Controllers\Setup\FirstAdminController;
use App\Http\Controllers\Setup\HealthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['loopback'])->group(function () {
    Route::get('/setup/first-admin', [FirstAdminController::class, 'create'])->name('setup.first-admin');
    Route::post('/setup/first-admin', [FirstAdminController::class, 'store'])->name('setup.first-admin.store');
});

Route::middleware(['auth', 'active', 'platform-admin'])->group(function () {
    Route::get('/operator/health', HealthController::class)->name('operator.health');
    Route::get('/admin/audit', AuditHistoryController::class)->name('admin.audit.index');

    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
    Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
    Route::patch('/properties/{property}/deactivate', [PropertyController::class, 'deactivate'])->name('properties.deactivate');

    Route::get('/properties/{property}/outlets', [PropertyInventoryController::class, 'outlets'])->name('properties.outlets.index');
    Route::post('/properties/{property}/outlets', [PropertyInventoryController::class, 'storeOutlet'])->name('properties.outlets.store');
    Route::get('/properties/{property}/outlets/{outlet}/edit', [PropertyInventoryController::class, 'editOutlet'])->name('properties.outlets.edit');
    Route::put('/properties/{property}/outlets/{outlet}', [PropertyInventoryController::class, 'updateOutlet'])->name('properties.outlets.update');
    Route::patch('/properties/{property}/outlets/{outlet}/deactivate', [PropertyInventoryController::class, 'deactivateOutlet'])->name('properties.outlets.deactivate');
    Route::get('/properties/{property}/room-types', [PropertyInventoryController::class, 'roomTypes'])->name('properties.room-types.index');
    Route::post('/properties/{property}/room-types', [PropertyInventoryController::class, 'storeRoomType'])->name('properties.room-types.store');
    Route::get('/properties/{property}/room-types/{roomType}/edit', [PropertyInventoryController::class, 'editRoomType'])->name('properties.room-types.edit');
    Route::put('/properties/{property}/room-types/{roomType}', [PropertyInventoryController::class, 'updateRoomType'])->name('properties.room-types.update');
    Route::patch('/properties/{property}/room-types/{roomType}/deactivate', [PropertyInventoryController::class, 'deactivateRoomType'])->name('properties.room-types.deactivate');
    Route::get('/properties/{property}/rooms', [PropertyInventoryController::class, 'rooms'])->name('properties.rooms.index');
    Route::get('/properties/{property}/room-history', [PropertyInventoryController::class, 'roomHistory'])->name('properties.rooms.history');
    Route::post('/properties/{property}/rooms', [PropertyInventoryController::class, 'storeRoom'])->name('properties.rooms.store');
    Route::get('/properties/{property}/rooms/{room}/edit', [PropertyInventoryController::class, 'editRoom'])->name('properties.rooms.edit');
    Route::put('/properties/{property}/rooms/{room}', [PropertyInventoryController::class, 'updateRoom'])->name('properties.rooms.update');
    Route::patch('/properties/{property}/rooms/{room}/deactivate', [PropertyInventoryController::class, 'deactivateRoom'])->name('properties.rooms.deactivate');
    Route::patch('/properties/{property}/rooms/{room}/housekeeping-status', [PropertyInventoryController::class, 'updateRoomStatus'])->name('properties.rooms.status.update');
    Route::post('/properties/{property}/room-blocks', [PropertyInventoryController::class, 'storeRoomBlock'])->name('properties.room-blocks.store');
    Route::patch('/properties/{property}/rooms/{room}/blocks/{block}/cancel', [PropertyInventoryController::class, 'cancelRoomBlock'])->name('properties.room-blocks.cancel');

    Route::get('/properties/{property}/guests', [GuestController::class, 'index'])->name('properties.guests.index');
    Route::get('/properties/{property}/guests/create', [GuestController::class, 'create'])->name('properties.guests.create');
    Route::post('/properties/{property}/guests', [GuestController::class, 'store'])->name('properties.guests.store');
    Route::get('/properties/{property}/guests/{guest}/edit', [GuestController::class, 'edit'])->name('properties.guests.edit');
    Route::put('/properties/{property}/guests/{guest}', [GuestController::class, 'update'])->name('properties.guests.update');
    Route::patch('/properties/{property}/guests/{guest}/deactivate', [GuestController::class, 'deactivate'])->name('properties.guests.deactivate');

    Route::get('/properties/{property}/rate-plans', [RatePlanController::class, 'index'])->name('properties.rate-plans.index');
    Route::get('/properties/{property}/rate-plans/create', [RatePlanController::class, 'create'])->name('properties.rate-plans.create');
    Route::post('/properties/{property}/rate-plans', [RatePlanController::class, 'store'])->name('properties.rate-plans.store');
    Route::get('/properties/{property}/rate-plans/{ratePlan}/edit', [RatePlanController::class, 'edit'])->name('properties.rate-plans.edit');
    Route::put('/properties/{property}/rate-plans/{ratePlan}', [RatePlanController::class, 'update'])->name('properties.rate-plans.update');
    Route::patch('/properties/{property}/rate-plans/{ratePlan}/deactivate', [RatePlanController::class, 'deactivate'])->name('properties.rate-plans.deactivate');
});

Route::middleware(['auth', 'active', 'property-role:housekeeping'])->group(function () {
    Route::get('/properties/{property}/housekeeping', [PropertyInventoryController::class, 'housekeepingBoard'])->name('properties.housekeeping.board');
    Route::patch('/properties/{property}/housekeeping/{room}/status', [PropertyInventoryController::class, 'updateHousekeepingStatus'])->name('properties.housekeeping.status');
    Route::get('/properties/{property}/housekeeping/tasks', [HousekeepingTaskController::class, 'index'])->name('properties.housekeeping.tasks.index');
    Route::post('/properties/{property}/housekeeping/tasks', [HousekeepingTaskController::class, 'store'])->name('properties.housekeeping.tasks.store');
    Route::patch('/properties/{property}/housekeeping/tasks/{task}/assignment', [HousekeepingTaskController::class, 'assign'])->name('properties.housekeeping.tasks.assign');
    Route::patch('/properties/{property}/housekeeping/tasks/{task}/{action}', [HousekeepingTaskController::class, 'transition'])->whereIn('action', ['start', 'complete', 'cancel'])->name('properties.housekeeping.tasks.transition');
});

Route::middleware(['auth', 'active'])->get('/my/operations-properties', [WorkOrderController::class, 'properties'])->name('my.operations.properties');

Route::middleware(['auth', 'active', 'property-role:operations'])->group(function () {
    Route::get('/properties/{property}/assets', [EngineeringAssetController::class, 'index'])->name('properties.operations.assets.index');
    Route::post('/properties/{property}/assets', [EngineeringAssetController::class, 'store'])->name('properties.operations.assets.store');
    Route::put('/properties/{property}/assets/{asset}', [EngineeringAssetController::class, 'update'])->name('properties.operations.assets.update');
    Route::patch('/properties/{property}/assets/{asset}/deactivate', [EngineeringAssetController::class, 'deactivate'])->name('properties.operations.assets.deactivate');
    Route::get('/properties/{property}/work-orders', [WorkOrderController::class, 'index'])->name('properties.operations.work-orders.index');
    Route::get('/properties/{property}/work-orders/{workOrder}', [WorkOrderController::class, 'show'])->name('properties.operations.work-orders.show');
    Route::post('/properties/{property}/work-orders', [WorkOrderController::class, 'store'])->name('properties.operations.work-orders.store');
    Route::patch('/properties/{property}/work-orders/{workOrder}/assignment', [WorkOrderController::class, 'assign'])->name('properties.operations.work-orders.assign');
    Route::patch('/properties/{property}/work-orders/{workOrder}/{action}', [WorkOrderController::class, 'transition'])->whereIn('action', ['start', 'resolve', 'cancel'])->name('properties.operations.work-orders.transition');
});

Route::middleware(['auth', 'active'])->get('/my/properties', [MyPropertiesController::class, 'index'])->name('my.properties.index');

Route::middleware(['auth', 'active', 'platform-admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/', [UserApprovalController::class, 'index'])->name('index');
    Route::post('/{user}/approve', [UserApprovalController::class, 'approve'])->name('approve');
    Route::post('/{user}/reject', [UserApprovalController::class, 'reject'])->name('reject');
    Route::get('/memberships', [UserMembershipController::class, 'index'])->name('memberships.index');
    Route::post('/{user}/memberships', [UserMembershipController::class, 'store'])->name('memberships.store');
    Route::delete('/{user}/memberships/{property}', [UserMembershipController::class, 'destroy'])->name('memberships.destroy');
    Route::get('/accounts', [UserAccountLifecycleController::class, 'index'])->name('accounts.index');
    Route::post('/{user}/suspend', [UserAccountLifecycleController::class, 'suspend'])->name('suspend');
    Route::post('/{user}/reactivate', [UserAccountLifecycleController::class, 'reactivate'])->name('reactivate');
});

Route::post('/register', [PendingRegisteredUserController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('register.store');

Route::view('/', 'pages.home')->name('home');

Route::middleware(['auth', 'active'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

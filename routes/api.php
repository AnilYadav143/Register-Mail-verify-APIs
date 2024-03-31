<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend_varification_mail', [AuthController::class, 'resendVerificationMail']);

Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password-otp', [AuthController::class, 'forgotPasswordOtp']);
Route::get('/verify-forgot-verification/{otp}', [AuthController::class, 'verifyForgotPassword']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

Route::group(['prefix' => 'user', 'middlware' => 'auth:api'], function () {
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/add-page', [AuthController::class, 'addPage']);
    Route::post('/site-menu-list', [AuthController::class, 'updateMenuVisibility']);
    Route::post('/hide-from-menu-list', [AuthController::class, 'hideFromMenuList']);
    Route::post('/show-in-menu-list', [AuthController::class, 'showInMenuList']);
    Route::post('/rename-page-name', [AuthController::class, 'renamePageName']);
    Route::post('/delete-menu', [AuthController::class, 'deleteMenu']);
    Route::post('/update-page-order', [AuthController::class, 'updatePageOrder']);
    Route::get('/not-in-menu-list', [AuthController::class, 'notInMenuList']);

});

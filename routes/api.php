<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
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

Route::post('/forget-password',[UserController::class,'forgetPassword']);
Route::get('/reset-password/{token}',[UserController::class,'resetPasswordLoad']);
Route::post('/reset-password',[UserController::class,'resetPassword']);

Route::group(['middleware'=>'api'],function($routes){

    Route::post('/register',[UserController::class,'register']);
    Route::post('/login',[UserController::class,'login']);
    Route::get('/refresh-token',[UserController::class,'refresh']);
    Route::get('/logout',[UserController::class,'logout']);
    Route::get('/profile',[UserController::class,'profile']);
    Route::post('/profile-update',[UserController::class,'updateProfile']);
    Route::post('/send-verify-email/{email}',[UserController::class,'sendVerifyEmail']);
    Route::get('/verify-email/{token}',[UserController::class,'verifyEmail']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

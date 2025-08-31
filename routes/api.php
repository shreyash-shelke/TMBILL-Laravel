<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;


Route::post('/customers/import', [CustomerController::class, 'import']);
Route::get('/customers/export', [CustomerController::class, 'export']);

Route::get('/customers', [CustomerController::class, 'index']);       
Route::post('/customers', [CustomerController::class, 'store']);      
Route::get('/customers/{id}', [CustomerController::class, 'show']);   
Route::put('/customers/{id}', [CustomerController::class, 'update']); 
Route::delete('/customers/{id}', [CustomerController::class, 'destroy']); 


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

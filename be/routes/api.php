<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['api.key'])->group(function () {
    Route::get('/data', function () {
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengakses data backend dengan API Key!',
            'data' => [
                'item1' => 'Laptop',
                'item2' => 'Mouse'
            ]
        ]);
    });
});
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeepSeekService;
use Illuminate\Http\Request;
use Log;

class AiDescriptionController extends Controller
{
    public function generate(Request $request, DeepSeekService $ai)
    {
        Log::info($request);
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $description = $ai->generateDescription($request->input('name'));

        return response()->json([
            'success' => true,
            'description' => $description,
        ]);
    }
}

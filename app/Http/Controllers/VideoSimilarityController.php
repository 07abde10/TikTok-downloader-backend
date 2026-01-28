<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class VideoSimilarityController extends Controller
{
    private $aiServiceUrl = 'http://localhost:8001';
    
    public function embedVideo(Request $request)
    {
        $request->validate([
            'video_path' => 'required|string',
            'caption' => 'nullable|string',
            'hashtags' => 'nullable|string'
        ]);
        
        try {
            $response = Http::post($this->aiServiceUrl . '/embed', [
                'video_path' => $request->video_path,
                'caption' => $request->caption ?? '',
                'hashtags' => $request->hashtags ?? ''
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json(['error' => 'AI service error'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process video'], 500);
        }
    }
    
    public function findSimilar(Request $request)
    {
        $request->validate([
            'video_id' => 'required|string',
            'top_k' => 'nullable|integer|min:1|max:20'
        ]);
        
        try {
            $response = Http::post($this->aiServiceUrl . '/search', [
                'video_id' => $request->video_id,
                'top_k' => $request->top_k ?? 5
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'similar_videos' => $response->json()
                ]);
            }
            
            return response()->json(['error' => 'AI service error'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to find similar videos'], 500);
        }
    }
    
    public function healthCheck()
    {
        try {
            $response = Http::get($this->aiServiceUrl . '/health');
            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['status' => 'AI service unavailable'], 503);
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InstagramController extends Controller
{
    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        
        try {
            // Simple test response for Instagram
            if (strpos($url, 'instagram.com') !== false) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => time(),
                        'title' => 'Instagram Content',
                        'download_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
                        'thumbnail' => null,
                        'type' => 'video',
                        'images' => null
                    ]
                ]);
            }
            
            return response()->json(['error' => 'Please provide a valid Instagram URL'], 400);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process Instagram content'], 500);
        }
    }
    
    private function parseInstagramResponse($html)
    {
        // Extract video URL
        if (preg_match('/"(https:\/\/[^"]*\.(mp4|jpg|jpeg)[^"]*)"/', $html, $matches)) {
            $mediaUrl = $matches[1];
            $isVideo = strpos($mediaUrl, '.mp4') !== false;
            
            return [
                'id' => time(),
                'title' => 'Instagram Content',
                'download_url' => $isVideo ? $mediaUrl : null,
                'thumbnail' => !$isVideo ? $mediaUrl : null,
                'type' => $isVideo ? 'video' : 'image',
                'images' => !$isVideo ? [$mediaUrl] : null
            ];
        }
        
        return null;
    }
    
    public function downloadFile(Request $request)
    {
        $request->validate([
            'video_url' => 'required|string'
        ]);

        $videoUrl = $request->input('video_url');
        $customFilename = $request->input('filename');
        
        try {
            $response = Http::get($videoUrl);
            
            if ($response->successful()) {
                $contentType = $response->header('Content-Type');
                $isImage = strpos($contentType, 'image/') === 0;
                
                if ($customFilename) {
                    $filename = $customFilename;
                } else {
                    $extension = $isImage ? '.jpg' : '.mp4';
                    $filename = 'instagram_' . time() . $extension;
                }
                
                return response($response->body())
                    ->header('Content-Type', 'application/octet-stream')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Content-Length', strlen($response->body()))
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }
            
            return response()->json(['error' => 'Failed to download file'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Download failed'], 500);
        }
    }
}
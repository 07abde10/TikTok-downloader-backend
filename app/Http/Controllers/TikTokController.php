<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\TikTokService;

class TikTokController extends Controller
{
    protected $tiktokService;
    
    public function __construct(TikTokService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    public function download(Request $request)
    {
        $request->validate([
            'url' => 'required|string'
        ]);

        $url = $request->input('url');
        
        try {
            $videoData = $this->tiktokService->getVideoInfo($url);
            
            if (!$videoData) {
                return response()->json(['error' => 'Invalid TikTok URL'], 400);
            }
            
            return response()->json([
                'success' => true,
                'data' => $videoData
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process video'], 500);
        }
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
                    $filename = 'tiktok_' . time() . $extension;
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
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
            if (strpos($url, 'instagram.com') !== false) {
                // Try real Instagram extraction
                $result = $this->extractInstagramContent($url);
                if ($result) {
                    return response()->json([
                        'success' => true,
                        'data' => $result
                    ]);
                }
                
                // If extraction fails, return error
                return response()->json(['error' => 'Could not extract Instagram content. Try a different URL.'], 400);
            }
            
            return response()->json(['error' => 'Please provide a valid Instagram URL'], 400);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process Instagram content'], 500);
        }
    }
    
    private function extractInstagramContent($url)
    {
        try {
            // Try multiple Instagram APIs
            
            // Method 1: Use instasave.website
            $response = Http::asForm()->post('https://instasave.website/system/action.php', [
                'url' => $url,
                'action' => 'post'
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['url'])) {
                    return [
                        'id' => time(),
                        'title' => $data['title'] ?? 'Instagram Content',
                        'download_url' => $data['url'],
                        'thumbnail' => $data['thumb'] ?? null,
                        'type' => 'video',
                        'images' => null
                    ];
                }
            }
            
            // Method 2: Use rapidapi Instagram downloader
            $response = Http::withHeaders([
                'X-RapidAPI-Key' => 'demo-key',
                'X-RapidAPI-Host' => 'instagram-downloader-download-instagram-videos-stories.p.rapidapi.com'
            ])->get('https://instagram-downloader-download-instagram-videos-stories.p.rapidapi.com/index', [
                'url' => $url
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['media'])) {
                    return [
                        'id' => time(),
                        'title' => 'Instagram Content',
                        'download_url' => $data['media'],
                        'thumbnail' => $data['thumbnail'] ?? null,
                        'type' => 'video',
                        'images' => null
                    ];
                }
            }
            
        } catch (\Exception $e) {
            // All methods failed
        }
        
        return null;
    }
    
    private function getInstagramData($url)
    {
        try {
            // Use downloadgram.com API
            $response = Http::asForm()->post('https://downloadgram.com/reel-video-download.php', [
                'url' => $url,
                'submit' => ''
            ]);
            
            if ($response->successful()) {
                $html = $response->body();
                return $this->parseInstagramResponse($html);
            }
        } catch (\Exception $e) {
            // Ignore API errors, will use fallback
        }
        
        return null;
    }
    
    private function parseInstagramResponse($html)
    {
        // Look for video download links
        if (preg_match('/href="([^"]*\.mp4[^"]*)"[^>]*download/', $html, $matches)) {
            $videoUrl = $matches[1];
            
            // Look for thumbnail
            $thumbnail = null;
            if (preg_match('/src="([^"]*\.(jpg|jpeg|png)[^"]*)"/', $html, $thumbMatches)) {
                $thumbnail = $thumbMatches[1];
            }
            
            return [
                'id' => time(),
                'title' => 'Instagram Video',
                'download_url' => $videoUrl,
                'thumbnail' => $thumbnail,
                'type' => 'video',
                'images' => null
            ];
        }
        
        // Look for image download links
        if (preg_match('/href="([^"]*\.(jpg|jpeg|png)[^"]*)"[^>]*download/', $html, $matches)) {
            $imageUrl = $matches[1];
            
            return [
                'id' => time(),
                'title' => 'Instagram Photo',
                'download_url' => null,
                'thumbnail' => $imageUrl,
                'type' => 'images',
                'images' => [$imageUrl]
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
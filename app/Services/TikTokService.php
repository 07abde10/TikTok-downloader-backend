<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TikTokService
{
    public function getVideoInfo($url)
    {
        try {
            // Expand short URLs first
            $expandedUrl = $this->expandUrl($url);
            
            // Use tikwm.com for best quality (primary)
            $result = $this->getTikwmData($expandedUrl);
            if ($result) {
                return $result;
            }
            
            // Fallback to ssstik if tikwm fails
            return $this->getSSSTikData($expandedUrl);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function getSSSTikData($url)
    {
        try {
            $response = Http::asForm()->post('https://ssstik.io/abc', [
                'id' => $url,
                'locale' => 'en',
                'tt' => 'RFBiZ3Bi'
            ]);
            
            if ($response->successful()) {
                $html = $response->body();
                return $this->parseSSSTikResponse($html, $url);
            }
        } catch (\Exception $e) {
        }
        
        return null;
    }
    
    private function getTikwmData($url)
    {
        try {
            $response = Http::get('https://tikwm.com/api/', [
                'url' => $url,
                'hd' => 1
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['code'] === 0) {
                    $result = [
                        'id' => $data['data']['id'] ?? time(),
                        'title' => $data['data']['title'] ?? 'TikTok Content',
                        'thumbnail' => $data['data']['cover'] ?? null,
                        'type' => 'video'
                    ];
                    
                    if (isset($data['data']['images']) && !empty($data['data']['images'])) {
                        $result['type'] = 'images';
                        $result['images'] = $data['data']['images'];
                        $result['download_url'] = null;
                    } else {
                        $result['download_url'] = $data['data']['hdplay'] ?? $data['data']['play'];
                        $result['images'] = null;
                    }
                    
                    return $result;
                }
            }
        } catch (\Exception $e) {
            // Ignore fallback errors
        }
        
        return null;
    }
    
    private function expandUrl($url)
    {
        if (strpos($url, 'tiktok.com/@') !== false && strpos($url, '/video/') !== false) {
            return $url;
        }
        
        try {
            $response = Http::withOptions([
                'allow_redirects' => false,
                'timeout' => 10
            ])->get($url);
            
            $location = $response->header('Location');
            if ($location) {
                return $location;
            }
        } catch (\Exception $e) {
            // If expansion fails, try original URL
        }
        
        return $url;
    }
    
    private function parseSSSTikResponse($html, $url)
    {
        if (preg_match('/"(https:\/\/[^"]*\.mp4[^"]*)"/', $html, $matches)) {
            $videoUrl = $matches[1];
            
            $title = 'TikTok Video';
            if (preg_match('/<p class="maintext"[^>]*>([^<]+)<\/p>/', $html, $titleMatches)) {
                $title = trim(strip_tags($titleMatches[1]));
            }
            
            return [
                'id' => time(),
                'title' => $title,
                'download_url' => $videoUrl,
                'thumbnail' => null,
                'type' => 'video',
                'images' => null
            ];
        }
        
        return null;
    }
}
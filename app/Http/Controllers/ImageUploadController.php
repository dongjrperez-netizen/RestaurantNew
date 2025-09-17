<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Upload and process an image
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'type' => 'required|in:dish,category,profile',
        ]);

        try {
            $file = $request->file('image');
            $type = $request->input('type', 'dish');
            
            // Generate unique filename
            $filename = $type . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Store in appropriate directory
            $directory = match($type) {
                'dish' => 'dishes',
                'category' => 'categories', 
                'profile' => 'profiles',
                default => $type . 's'
            };
            
            $path = "images/{$directory}/{$filename}";
            $storedPath = $file->storeAs("images/{$directory}", $filename, 'public');
            
            // Return the URL - ensure it uses the correct base URL
            $url = Storage::disk('public')->url($storedPath);
            
            // Fix URL to use current app URL instead of default localhost
            if (str_starts_with($url, 'http://localhost')) {
                $url = str_replace('http://localhost', config('app.url'), $url);
            }
            
            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $storedPath,
                'filename' => $filename,
                'size' => $file->getSize(),
                'message' => 'Image uploaded successfully!'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an uploaded image
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');
            
            // Security check - only allow deletion of files in images directory
            if (!Str::startsWith($path, 'images/')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file path'
                ], 400);
            }
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully!'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get uploaded images for a restaurant
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:dish,category,profile',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $type = $request->input('type', 'dish');
            $perPage = $request->input('per_page', 20);
            
            $directoryName = match($type) {
                'dish' => 'dishes',
                'category' => 'categories', 
                'profile' => 'profiles',
                default => $type . 's'
            };
            
            $directory = "images/{$directoryName}";
            $files = Storage::disk('public')->files($directory);
            
            $images = collect($files)->map(function ($file) {
                $url = Storage::disk('public')->url($file);
                $filename = basename($file);
                $size = Storage::disk('public')->size($file);
                $lastModified = Storage::disk('public')->lastModified($file);
                
                return [
                    'path' => $file,
                    'url' => $url,
                    'filename' => $filename,
                    'size' => $size,
                    'size_human' => $this->formatBytes($size),
                    'uploaded_at' => date('Y-m-d H:i:s', $lastModified),
                ];
            })->sortByDesc('uploaded_at')->values();
            
            // Simple pagination
            $page = $request->input('page', 1);
            $total = $images->count();
            $offset = ($page - 1) * $perPage;
            $items = $images->slice($offset, $perPage);
            
            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
<?php
/**
 * Debug script to test JellyStat metadata functionality
 * Run this to troubleshoot why metadata returns "Unknown Item"
 */

require_once 'api/config/config.php';
require_once 'api/classes/organizr.php';

// Create Organizr instance (this will load config)
$organizr = new Organizr();

echo "=== JellyStat Metadata Debug Tool ===\n\n";

// Check configuration
$jellyStatURL = $organizr->config['jellyStatURL'] ?? '';
$jellyStatInternalURL = $organizr->config['jellyStatInternalURL'] ?? '';
$jellyStatApikey = $organizr->config['jellyStatApikey'] ?? '';
$enabled = $organizr->config['homepageJellyStatEnabled'] ?? false;

echo "Configuration:\n";
echo "- JellyStat URL: " . ($jellyStatURL ?: 'NOT SET') . "\n";
echo "- Internal URL: " . ($jellyStatInternalURL ?: 'NOT SET') . "\n";  
echo "- API Key: " . ($jellyStatApikey ? 'SET (****)' : 'NOT SET') . "\n";
echo "- Plugin Enabled: " . ($enabled ? 'YES' : 'NO') . "\n\n";

if (!$enabled) {
    echo "❌ JellyStat plugin is not enabled!\n";
    echo "Enable it in Organizr settings first.\n";
    exit(1);
}

if (!$jellyStatURL) {
    echo "❌ JellyStat URL is not configured!\n";
    echo "Set jellyStatURL in Organizr settings first.\n";
    exit(1);
}

// Test basic connectivity
$testUrl = !empty($jellyStatInternalURL) ? $jellyStatInternalURL : $jellyStatURL;
$testUrl = rtrim($organizr->qualifyURL($testUrl), '/');

echo "Testing connectivity to: {$testUrl}\n\n";

// Test endpoints that the metadata function would try
$testKey = 'test-item-id';
$endpoints = [];

if ($jellyStatApikey) {
    $endpoints['JellyStat API (getItem)'] = $testUrl . '/api/getItem?apiKey=' . urlencode($jellyStatApikey) . '&id=' . urlencode($testKey);
    $endpoints['JellyStat API (getItemById)'] = $testUrl . '/api/getItemById?apiKey=' . urlencode($jellyStatApikey) . '&id=' . urlencode($testKey);
}

$endpoints['Jellyfin Proxy (basic)'] = $testUrl . '/proxy/Items/' . rawurlencode($testKey);
$endpoints['Jellyfin Proxy (with fields)'] = $testUrl . '/proxy/Items/' . rawurlencode($testKey) . '?Fields=Overview,People,Genres,CommunityRating,CriticRating,Studios,Taglines,ProductionYear,PremiereDate';

// Also test a real item ID if provided via command line
if (isset($argv[1])) {
    $realKey = $argv[1];
    echo "Testing with real item ID: {$realKey}\n\n";
    
    if ($jellyStatApikey) {
        $endpoints['Real Item - JellyStat API'] = $testUrl . '/api/getItem?apiKey=' . urlencode($jellyStatApikey) . '&id=' . urlencode($realKey);
    }
    $endpoints['Real Item - Jellyfin Proxy'] = $testUrl . '/proxy/Items/' . rawurlencode($realKey) . '?Fields=Overview,People,Genres,CommunityRating,CriticRating,Studios,Taglines,ProductionYear,PremiereDate';
}

foreach ($endpoints as $name => $url) {
    echo "Testing {$name}:\n";
    echo "URL: {$url}\n";
    
    try {
        $options = $organizr->requestOptions($url, null, 
            $organizr->config['jellyStatDisableCertCheck'] ?? false,
            $organizr->config['jellyStatUseCustomCertificate'] ?? false
        );
        
        $response = Requests::get($url, [], $options);
        
        if ($response->success) {
            $data = json_decode($response->body, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "✅ SUCCESS - Status: {$response->status_code}\n";
                
                if (is_array($data)) {
                    $keys = array_keys($data);
                    echo "Response has keys: " . implode(', ', array_slice($keys, 0, 10)) . 
                         (count($keys) > 10 ? '... (' . count($keys) . ' total)' : '') . "\n";
                    
                    // Look for typical media metadata fields
                    $mediaFields = ['Name', 'Id', 'Type', 'Overview', 'Genres', 'People', 'RunTimeTicks', 'ProductionYear'];
                    $foundFields = array_intersect($keys, $mediaFields);
                    if (!empty($foundFields)) {
                        echo "Media fields found: " . implode(', ', $foundFields) . "\n";
                        
                        // Show sample values
                        foreach (['Name', 'Type', 'Overview'] as $field) {
                            if (isset($data[$field])) {
                                $value = $data[$field];
                                if (is_string($value) && strlen($value) > 50) {
                                    $value = substr($value, 0, 47) . '...';
                                }
                                echo "{$field}: {$value}\n";
                            }
                        }
                    }
                } else {
                    echo "Response is not an array: " . gettype($data) . "\n";
                }
            } else {
                echo "❌ Invalid JSON response\n";
                echo "Raw response: " . substr($response->body, 0, 200) . "...\n";
            }
        } else {
            echo "❌ HTTP Error: {$response->status_code}\n";
            if (!empty($response->body)) {
                echo "Error body: " . substr($response->body, 0, 200) . "...\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}

echo "=== Debug Complete ===\n\n";

echo "Next steps if endpoints are failing:\n";
echo "1. Verify JellyStat is running and accessible\n";  
echo "2. Check if API key is correct and has permissions\n";
echo "3. Test URLs directly in browser/curl\n";
echo "4. Check JellyStat logs for errors\n";
echo "5. Verify firewall/network connectivity\n\n";

echo "If you have a working item ID from JellyStat, run:\n";
echo "php debug_jellystat_metadata.php YOUR-ITEM-ID\n";

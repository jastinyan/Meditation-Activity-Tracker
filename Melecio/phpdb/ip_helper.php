<?php
// ip_helper.php - Add this file in your phpdb directory

/**
 * Get the real IP address of the user, even behind proxies
 */
function getRealIP() {
    $ip = '';
    
    // Check for CloudFlare IP
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    // Check for shared internet/ISP IP
    elseif (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for proxy IP (X-Forwarded-For can have multiple IPs)
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Take the first IP in the list (client IP)
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '';
        }
    }
    // Check for other proxy headers
    elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    }
    elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    }
    // Fallback to remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    
    // Validate the IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0'; // Unknown IP
    }
    
    return $ip;
}

/**
 * Get detailed browser information
 */
function getBrowserInfo() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $browser = 'Unknown';
    $browserVersion = '';
    $platform = 'Unknown';
    $platformVersion = '';
    $isMobile = false;
    $isBot = false;
    
    // Detect platform
    if (preg_match('/windows nt (\d+\.\d+)/i', $userAgent, $matches)) {
        $platform = 'Windows';
        $versionMap = ['6.1' => '7', '6.2' => '8', '6.3' => '8.1', '10.0' => '10', '11.0' => '11'];
        $platformVersion = $versionMap[$matches[1]] ?? $matches[1];
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $platform = 'macOS';
        if (preg_match('/mac os x (\d+[._]\d+)/i', $userAgent, $matches)) {
            $platformVersion = str_replace('_', '.', $matches[1]);
        }
    } elseif (preg_match('/linux/i', $userAgent)) {
        $platform = 'Linux';
        if (preg_match('/ubuntu/i', $userAgent)) {
            $platform = 'Ubuntu';
        } elseif (preg_match('/debian/i', $userAgent)) {
            $platform = 'Debian';
        } elseif (preg_match('/fedora/i', $userAgent)) {
            $platform = 'Fedora';
        } elseif (preg_match('/centos/i', $userAgent)) {
            $platform = 'CentOS';
        }
    } elseif (preg_match('/android (\d+\.\d+)/i', $userAgent, $matches)) {
        $platform = 'Android';
        $platformVersion = $matches[1];
        $isMobile = true;
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $platform = 'iOS';
        if (preg_match('/os (\d+[._]\d+)/i', $userAgent, $matches)) {
            $platformVersion = str_replace('_', '.', $matches[1]);
        }
        $isMobile = true;
    }
    
    // Detect browser
    if (preg_match('/MSIE (\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Internet Explorer';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Trident\/.*rv:(\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Internet Explorer';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Firefox\/(\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Firefox';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Chrome\/(\d+\.\d+)/i', $userAgent, $matches) && !preg_match('/Edg/i', $userAgent)) {
        $browser = 'Chrome';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Safari\/(\d+\.\d+)/i', $userAgent, $matches) && !preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Safari';
        $browserVersion = $matches[1];
    } elseif (preg_match('/Edg\/(\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Edge';
        $browserVersion = $matches[1];
    } elseif (preg_match('/OPR\/(\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Opera';
        $browserVersion = $matches[1];
    } elseif (preg_match('/YaBrowser\/(\d+\.\d+)/i', $userAgent, $matches)) {
        $browser = 'Yandex';
        $browserVersion = $matches[1];
    }
    
    // Detect bots/crawlers
    $bots = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 'perl', 'java', 'php'];
    foreach ($bots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            $isBot = true;
            break;
        }
    }
    
    // Special bot detection
    if (preg_match('/Googlebot|bingbot|Slurp|DuckDuckBot|Baiduspider|YandexBot/i', $userAgent)) {
        $isBot = true;
        if (preg_match('/(Googlebot|bingbot|Slurp|DuckDuckBot|Baiduspider|YandexBot)/i', $userAgent, $matches)) {
            $browser = $matches[1];
            $browserVersion = '';
        }
    }
    
    return [
        'full' => $userAgent,
        'browser' => $browser,
        'browser_version' => $browserVersion,
        'platform' => $platform,
        'platform_version' => $platformVersion,
        'is_mobile' => $isMobile,
        'is_bot' => $isBot,
        'formatted' => $browser . ' ' . $browserVersion . ' on ' . $platform . ($platformVersion ? ' ' . $platformVersion : ''),
        'simple' => $browser . ($browserVersion ? ' ' . $browserVersion : '')
    ];
}
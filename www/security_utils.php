<?php
/**
 * Security utilities for request origin verification
 */

class SecurityUtils {
    
    /**
     * List of allowed domains
     */
    private static $allowedOrigins = [
        'https://demo-register-server.local',
        'https://demo-register-client.local',
        'https://localhost:3000',
        'https://127.0.0.1:3000',
        'https://yourdomain.com',
    ];
    
    /**
     * List of allowed IPs (optional)
     */
    private static $allowedIPs = [
        '127.0.0.1',
        '::1',
        '192.168.1.0/24',
    ];
    
    /**
     * Secret token for custom header
     */
    private static $secretToken = '';
    
    /**
     * Verifies if the request origin is authorized
     * 
     * @param bool $strictMode If true, also checks Referer and IP
     * @return array ['allowed' => bool, 'reason' => string, 'origin' => string]
     */
    public static function verifyOrigin($strictMode = false) {
        $origin = self::getOrigin();
        $referer = self::getReferer();
        $clientIP = self::getClientIP();
        
        $result = [
            'allowed' => false,
            'reason' => '',
            'origin' => $origin,
            'referer' => $referer,
            'client_ip' => $clientIP
        ];
        
        // 1. Origin header verification
        if (self::isOriginAllowed($origin)) {
            $result['allowed'] = true;
            $result['reason'] = 'Authorized origin';
        } else {
            $result['reason'] = 'Unauthorized origin: ' . $origin;
            return $result;
        }
        
        // 2. Strict mode: additional verifications
        if ($strictMode) {
            // Referer verification
            if (!self::isRefererAllowed($referer)) {
                $result['allowed'] = false;
                $result['reason'] = 'Unauthorized referer: ' . $referer;
                return $result;
            }
            
            // IP verification
            if (!self::isIPAllowed($clientIP)) {
                $result['allowed'] = false;
                $result['reason'] = 'Unauthorized IP: ' . $clientIP;
                return $result;
            }
            
            $result['reason'] = 'Strict verifications passed';
        }
        
        return $result;
    }
    
    /**
     * Verifies origin via custom header with token
     * 
     * @param string $headerName Custom header name
     * @return bool
     */
    public static function verifyCustomHeader($headerName = 'X-Auth-Token') {
        $headerValue = self::getCustomHeader($headerName);
        return $headerValue === self::$secretToken;
    }
    
    /**
     * Gets the request origin
     */
    private static function getOrigin() {
        return $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    }
    
    /**
     * Gets the request referer
     */
    private static function getReferer() {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }
    
    /**
     * Gets the real client IP
     */
    private static function getClientIP() {
        // Proxy and load balancer verification
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Direct IP
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // If multiple IPs (comma-separated), take the first one
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Gets a custom header
     */
    private static function getCustomHeader($headerName) {
        $headerName = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        return $_SERVER[$headerName] ?? '';
    }
    
    /**
     * Checks if origin is allowed
     */
    private static function isOriginAllowed($origin) {
        if (empty($origin)) {
            return false;
        }
        
        foreach (self::$allowedOrigins as $allowedOrigin) {
            if ($origin === $allowedOrigin || 
                (strpos($allowedOrigin, '*') !== false && fnmatch($allowedOrigin, $origin))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Checks if referer is allowed
     */
    private static function isRefererAllowed($referer) {
        if (empty($referer)) {
            return false;
        }
        
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (!$refererHost) {
            return false;
        }
        
        foreach (self::$allowedOrigins as $allowedOrigin) {
            $allowedHost = parse_url($allowedOrigin, PHP_URL_HOST);
            if ($refererHost === $allowedHost) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Checks if IP is allowed
     */
    private static function isIPAllowed($ip) {
        if (empty($ip) || $ip === 'unknown') {
            return false;
        }
        
        foreach (self::$allowedIPs as $allowedIP) {
            if ($ip === $allowedIP) {
                return true;
            }
            
            // Subnet verification (CIDR format)
            if (strpos($allowedIP, '/') !== false) {
                if (self::ipInRange($ip, $allowedIP)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Checks if an IP is within a CIDR range
     */
    private static function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        
        return ($ip & $mask) === $subnet;
    }
    
    /**
     * Sets allowed domains
     */
    public static function setAllowedOrigins($origins) {
        self::$allowedOrigins = $origins;
    }
    
    /**
     * Sets allowed IPs
     */
    public static function setAllowedIPs($ips) {
        self::$allowedIPs = $ips;
    }
    
    /**
     * Sets the secret token
     */
    public static function setSecretToken($token) {
        self::$secretToken = $token;
    }
    
    /**
     * Log unauthorized access attempts
     */
    public static function logUnauthorizedAccess($result) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $result['client_ip'],
            'origin' => $result['origin'],
            'referer' => $result['referer'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'reason' => $result['reason'],
            'script' => $_SERVER['SCRIPT_NAME'] ?? ''
        ];
        
        error_log('UNAUTHORIZED ACCESS: ' . json_encode($logEntry));
    }
}

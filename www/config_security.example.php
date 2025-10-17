<?php
/**
 * EXAMPLE security configuration for origin verification
 * 
 * ⚠️  IMPORTANT: Copy this file to config_security.php and customize it
 * 
 * This file contains configuration examples. Never commit
 * the actual config_security.php file with your real production values.
 */

// Allowed domains - CUSTOMIZE ACCORDING TO YOUR NEEDS
$allowedOrigins = [
    'https://demo-register-server.local',
    'https://localhost:3000',
    'https://127.0.0.1:3000',
    'https://yourdomain.com',
    'https://app.yourdomain.com',
    // Add your production domains here
    // 'https://*.yourdomain.com', // Wildcard support
];

// Allowed IPs (optional - for stricter control)
$allowedIPs = [
    '127.0.0.1',
    '::1',
    // '192.168.1.0/24', // Example local subnet
    // Add your authorized IPs here
];

// Secret token for custom header (optional)
// ⚠️  CHANGE THIS TOKEN IN PRODUCTION!
$secretToken = 'your-secret-token-change-this-in-production';

// Verification configuration
$securityConfig = [
    'strict_mode' => true,           // Enable strict verifications (Origin + Referer + IP)
    'log_unauthorized' => true,      // Log unauthorized access attempts
    'custom_header_check' => false,  // Enable custom header verification
    'custom_header_name' => 'X-Auth-Token', // Custom header name
];

// Apply configuration
if (class_exists('SecurityUtils')) {
    SecurityUtils::setAllowedOrigins($allowedOrigins);
    SecurityUtils::setAllowedIPs($allowedIPs);
    SecurityUtils::setSecretToken($secretToken);
}

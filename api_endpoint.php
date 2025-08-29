<?php
/**
 * API Endpoint TechCorp - Configuration Management
 * ATTENTION: Cet endpoint présente des vulnérabilités de sécurité
 * 
 * Endpoint: /api/config.php
 * Méthode: GET, POST
 * Authentification: Aucune (VULNÉRABILITÉ)
 */

// Configuration de base
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // VULNÉRABILITÉ CORS
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Chargement de la configuration
$config_file = '../app_config.json';

// VULNÉRABILITÉ: Path traversal possible
if (isset($_GET['config'])) {
    $config_file = $_GET['config'];
}

// VULNÉRABILITÉ: Pas de validation du fichier
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config_data = json_decode($config_content, true);
    
    // VULNÉRABILITÉ: Exposition de toutes les données
    if ($config_data) {
        // Ajout d'informations sensibles supplémentaires
        $config_data['server_info'] = [
            'hostname' => gethostname(),
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'current_user' => get_current_user(),
            'process_id' => getmypid()
        ];
        
        // VULNÉRABILITÉ: Exposition des variables d'environnement
        $config_data['environment'] = [
            'database_host' => $_ENV['DB_HOST'] ?? '192.168.1.11',
            'database_user' => $_ENV['DB_USER'] ?? 'portal_user',
            'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'techcorp_jwt_secret_2024',
            'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@techcorp.com'
        ];
        
        // VULNÉRABILITÉ: Exposition des logs d'accès
        if (isset($config_data['recent_access_logs'])) {
            $config_data['access_analysis'] = [
                'total_connections' => count($config_data['recent_access_logs']),
                'unique_ips' => count(array_unique(array_column($config_data['recent_access_logs'], 'ip'))),
                'suspicious_activity' => [
                    'ip_93_127_133_142' => array_filter($config_data['recent_access_logs'], function($log) {
                        return $log['ip'] === '93.127.133.142';
                    }),
                    'root_access_attempts' => array_filter($config_data['recent_access_logs'], function($log) {
                        return strpos($log['action'] ?? '', 'root') !== false;
                    })
                ]
            ];
        }
        
        echo json_encode($config_data, JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['error' => 'Invalid configuration file']);
    }
} else {
    echo json_encode(['error' => 'Configuration file not found: ' . $config_file]);
}

// VULNÉRABILITÉ: Logging des accès (peut être exploité)
$access_log = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    'requested_config' => $config_file ?? 'default',
    'status' => 'success'
];

// Écriture dans un fichier de log accessible
file_put_contents('api_access.log', json_encode($access_log) . "\n", FILE_APPEND | LOCK_EX);

// VULNÉRABILITÉ: Exposition des informations de debug
if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
    echo "\n\n=== DEBUG INFORMATION ===\n";
    echo "Server Variables:\n";
    print_r($_SERVER);
    echo "\nEnvironment Variables:\n";
    print_r($_ENV);
    echo "\nPHP Info:\n";
    phpinfo();
}
?>

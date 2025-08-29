<?php
/**
 * TechCorp Configuration Manager
 * ATTENTION: Ce script présente des vulnérabilités critiques de sécurité
 * 
 * Utilisation: /config_manager.php?action=view&target=system
 * Actions: view, edit, backup, restore, validate
 */

// VULNÉRABILITÉ: Pas d'authentification
session_start();

// Configuration de base
$config_dir = './';
$backup_dir = './backups/';
$allowed_actions = ['view', 'edit', 'backup', 'restore', 'validate', 'debug'];

// VULNÉRABILITÉ: Pas de validation des paramètres
$action = $_GET['action'] ?? 'view';
$target = $_GET['target'] ?? 'app_config.json';
$format = $_GET['format'] ?? 'json';

// VULNÉRABILITÉ: Path traversal possible
$target_file = $config_dir . $target;

// Fonction de chargement de configuration
function loadConfig($file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        return json_decode($content, true);
    }
    return null;
}

// Fonction de sauvegarde de configuration
function saveConfig($file, $data) {
    // VULNÉRABILITÉ: Pas de validation des données
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Fonction de création de backup
function createBackup($source_file) {
    global $backup_dir;
    
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0777, true); // VULNÉRABILITÉ: Permissions trop larges
    }
    
    $backup_name = $backup_dir . basename($source_file) . '.backup.' . date('Y-m-d_H-i-s');
    return copy($source_file, $backup_name);
}

// Traitement des actions
switch ($action) {
    case 'view':
        $config = loadConfig($target_file);
        if ($config) {
            // VULNÉRABILITÉ: Exposition de toutes les données
            if ($format === 'json') {
                header('Content-Type: application/json');
                echo json_encode($config, JSON_PRETTY_PRINT);
            } else {
                // Format texte avec informations sensibles
                echo "=== CONFIGURATION TECHCorp ===\n";
                echo "Fichier: $target_file\n";
                echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
                
                // Exposition des informations sensibles
                if (isset($config['database'])) {
                    echo "=== BASE DE DONNÉES ===\n";
                    echo "Host: " . $config['database']['host'] . "\n";
                    echo "Port: " . $config['database']['port'] . "\n";
                    echo "Nom: " . $config['database']['name'] . "\n";
                    echo "Utilisateur: " . $config['database']['user'] . "\n\n";
                }
                
                if (isset($config['security'])) {
                    echo "=== SÉCURITÉ ===\n";
                    echo "JWT Secret: " . $config['security']['jwt_secret'] . "\n";
                    echo "Bcrypt Rounds: " . $config['security']['bcrypt_rounds'] . "\n\n";
                }
                
                if (isset($config['recent_access_logs'])) {
                    echo "=== LOGS D'ACCÈS RÉCENTS ===\n";
                    foreach ($config['recent_access_logs'] as $log) {
                        echo "[" . $log['timestamp'] . "] " . $log['ip'] . ":" . $log['port'] . " - " . $log['username'] . " - " . $log['action'] . "\n";
                    }
                    echo "\n";
                }
                
                // VULNÉRABILITÉ: Exposition des informations système
                echo "=== INFORMATIONS SYSTÈME ===\n";
                echo "Serveur: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
                echo "PHP Version: " . PHP_VERSION . "\n";
                echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
                echo "Utilisateur: " . get_current_user() . "\n";
                echo "Process ID: " . getmypid() . "\n";
            }
        } else {
            echo "Erreur: Impossible de charger la configuration";
        }
        break;
        
    case 'edit':
        // VULNÉRABILITÉ: Modification de configuration sans authentification
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = loadConfig($target_file);
            $new_data = json_decode(file_get_contents('php://input'), true);
            
            if ($config && $new_data) {
                $config = array_merge($config, $new_data);
                if (saveConfig($target_file, $config)) {
                    echo json_encode(['status' => 'success', 'message' => 'Configuration mise à jour']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la sauvegarde']);
                }
            }
        } else {
            echo "Méthode POST requise pour la modification";
        }
        break;
        
    case 'backup':
        if (createBackup($target_file)) {
            echo json_encode(['status' => 'success', 'message' => 'Backup créé avec succès']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la création du backup']);
        }
        break;
        
    case 'restore':
        // VULNÉRABILITÉ: Restauration sans validation
        $backup_file = $_GET['backup'] ?? '';
        if ($backup_file && file_exists($backup_dir . $backup_file)) {
            if (copy($backup_dir . $backup_file, $target_file)) {
                echo json_encode(['status' => 'success', 'message' => 'Configuration restaurée']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la restauration']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Fichier de backup introuvable']);
        }
        break;
        
    case 'validate':
        // VULNÉRABILITÉ: Validation basique
        $config = loadConfig($target_file);
        if ($config) {
            $errors = [];
            
            // Vérifications basiques
            if (!isset($config['database'])) $errors[] = "Section database manquante";
            if (!isset($config['security'])) $errors[] = "Section security manquante";
            if (!isset($config['web_server'])) $errors[] = "Section web_server manquante";
            
            if (empty($errors)) {
                echo json_encode(['status' => 'success', 'message' => 'Configuration valide']);
            } else {
                echo json_encode(['status' => 'error', 'errors' => $errors]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Configuration invalide']);
        }
        break;
        
    case 'debug':
        // VULNÉRABILITÉ: Exposition des informations de debug
        echo "=== DEBUG TECHCorp Configuration Manager ===\n\n";
        
        echo "=== VARIABLES SERVEUR ===\n";
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0 || in_array($key, ['REMOTE_ADDR', 'REQUEST_METHOD', 'QUERY_STRING'])) {
                echo "$key: $value\n";
            }
        }
        
        echo "\n=== VARIABLES ENVIRONNEMENT ===\n";
        foreach ($_ENV as $key => $value) {
            if (strpos($key, 'DB_') === 0 || strpos($key, 'TECH_') === 0) {
                echo "$key: $value\n";
            }
        }
        
        echo "\n=== FICHIERS DE CONFIGURATION ===\n";
        $config_files = glob($config_dir . '*.json');
        foreach ($config_files as $file) {
            echo basename($file) . " - " . (is_readable($file) ? 'Lisible' : 'Non lisible') . "\n";
        }
        
        echo "\n=== PERMISSIONS ===\n";
        echo "Dossier config: " . substr(sprintf('%o', fileperms($config_dir)), -4) . "\n";
        echo "Fichier cible: " . (file_exists($target_file) ? substr(sprintf('%o', fileperms($target_file)), -4) : 'Inexistant') . "\n";
        break;
        
    default:
        echo "Action non reconnue. Actions disponibles: " . implode(', ', $allowed_actions);
}

// VULNÉRABILITÉ: Logging des actions (peut être exploité)
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'action' => $action,
    'target' => $target,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
];

file_put_contents('config_manager.log', json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
?>

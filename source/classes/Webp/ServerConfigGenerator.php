<?php
// File: ServerConfigGenerator.php

require_once 'Nginx.php';
require_once 'Apache.php';
require_once 'IIS.php';

class ServerConfigGenerator
{

    private $home_url;
    private $home_root;
    private $extensions;
    private $configFileName;
    private $rewriteRules;
    private $wp_filesystem;

    public function __construct()
    {
        global $wp_filesystem;
        // Initialize necessary properties
        $this->home_url = home_url('/');
        $this->home_root = wp_parse_url($this->home_url, PHP_URL_PATH);
        $this->extensions = 'jpg|jpeg|png|gif'; // Adjust extensions as needed

        if (! function_exists('WP_Filesystem')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			WP_Filesystem();
		}

		$this->wp_filesystem = $wp_filesystem;

    }

    /**
     * Detect the server type (Nginx, Apache, IIS)
     * 
     * @return string The detected server type
     */
    private function detectServerType()
    {
        if (stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
            return 'nginx';
        } elseif (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false) {
            return 'apache';
        } elseif (stripos($_SERVER['SERVER_SOFTWARE'], 'microsoft-iis') !== false) {
            return 'iis';
        } else {
            return 'unknown';
        }
    }

    /**
     * Generate server-specific rewrite rules
     */
    public function generateServerConfig()
    {
        // Detect server type
        $server_type = $this->detectServerType();

        // Generate server-specific rewrite rules
        switch ($server_type) {
            case 'nginx':
                $this->rewriteRules = NginxConfig::generateRewriteRules($this->home_root, $this->extensions);
                $this->configFileName = NginxConfig::getConfigFileName();
                break;
            case 'apache':
                $this->rewriteRules = ApacheConfig::generateRewriteRules($this->home_root, $this->extensions);
                $this->configFileName = ApacheConfig::getConfigFileName();
                break;
            case 'iis':
                $this->rewriteRules = IISConfig::generateRewriteRules($this->home_root, $this->extensions);
                $this->configFileName = IISConfig::getConfigFileName();
                break;
            default:
                return null;
        }

        // Save the configuration to a file
        $this->saveConfigToFile();
    }

    /**
     * Save the generated rewrite rules to a configuration file
     */
    private function saveConfigToFile()
    {
        $path = wp_upload_dir();
        $dir = $path['basedir'] . '/conf';

        // Create the directory if it doesn't exist
        if (!$this->wp_filesystem->is_dir($dir)) {
            $this->wp_filesystem->mkdir($dir, FS_CHMOD_DIR); // Create the directory recursively with proper permissions
        }

        // Define the path to the configuration file
        $configFilePath = $dir . '/' . $this->configFileName;

        // Write the rules to the configuration file
        $this->wp_filesystem->put_contents($configFilePath, $this->rewriteRules);

        // Optionally, echo a message to confirm the config file was written
        // echo "Server configuration has been written to " . $configFilePath;
    }
}

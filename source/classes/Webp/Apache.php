<?php
// File: Apache.php

class ApacheConfig
{
    public static function generateRewriteRules($home_root, $extensions)
    {

        return trim('
        <IfModule mod_setenvif.c>
            # Vary: Accept for all the requests to jpeg, png, and gif.
            SetEnvIf Request_URI "\.(' . $extensions . ')$" REQUEST_image
        </IfModule>
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase ' . $home_root . '
        
            # Check if browser supports AVIF images.
            RewriteCond %{HTTP_ACCEPT} image/avif
            # Check if AVIF replacement image exists.
            RewriteCond %{REQUEST_FILENAME}.avif -f
            # Serve AVIF image instead.
            RewriteRule (.+)\.(' . $extensions . ')$ $1.$2.avif [T=image/avif,NC]
        
            # If no AVIF support, check for WebP support.
            RewriteCond %{HTTP_ACCEPT} !image/avif
            RewriteCond %{HTTP_ACCEPT} image/webp
            # Check if WebP replacement image exists.
            RewriteCond %{REQUEST_FILENAME}.webp -f
            # Serve WebP image instead.
            RewriteRule (.+)\.(' . $extensions . ')$ $1.$2.webp [T=image/webp,NC]
        </IfModule>
        
        <IfModule mod_headers.c>
            # Update the MIME type accordingly.
            Header append Vary Accept env=REQUEST_image
        </IfModule>
        ');

        //         return trim('
        // <IfModule mod_rewrite.c>
        //     RewriteEngine On
        //     RewriteBase ' . $home_root . '

        //     RewriteCond %{HTTP_ACCEPT} image/avif
        //     RewriteCond %{REQUEST_FILENAME}.avif -f
        //     RewriteRule ^(.+)\.(' . $extensions . ')$ $1.$2.avif [T=image/avif,L]

        //     RewriteCond %{HTTP_ACCEPT} image/webp
        //     RewriteCond %{REQUEST_FILENAME}.webp -f
        //     RewriteRule ^(.+)\.(' . $extensions . ')$ $1.$2.webp [T=image/webp,L]
        // </IfModule>');
    }

    public static function getConfigFileName()
    {
        return '.htaccess';
    }
}

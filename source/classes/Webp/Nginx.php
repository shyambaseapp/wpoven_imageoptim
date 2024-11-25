<?php
// File: Nginx.php

class NginxConfig
{
    public static function generateRewriteRules($home_root, $extensions)
    {

        // return trim(
        //     'location ~* \.(png|jpe?g|gif)$ {
        //         add_header Vary Accept;

        //         if ($http_accept ~* "webp") {
        //             set $webp_path $request_filename.webp;

        //             if (-f $webp_path) {
        //                 rewrite ^ $webp_path break;
        //             }
        //         }

        //         try_files $uri =404;
        //     }

        //     location ~* \.webp$ {
        //         types { image/webp webp; }
        //         add_header Vary Accept;
        //     }'
        // );
                return trim('
        location ~* ^(' . $home_root . '.+)\.(' . $extensions . ')$ {
            add_header Vary Accept;

            set $canavif 1;

            if ($http_accept !~* "avif"){
                set $canavif 0;
            }

            if (!-f $request_filename.avif) {
                set $canavif 0;
            }
            if ($canavif = 1){
                rewrite ^(.*) $1.avif;
                break;
            }

            set $canwebp 1;

            if ($http_accept !~* "webp"){
                set $canwebp 0;
            }

            if (!-f $request_filename.webp) {
                set $canwebp 0;
            }
            if ($canwebp = 1){
                rewrite ^(.*) $1.webp;
                break;
            }
        }');
    }

    public static function getConfigFileName()
    {
        return 'wpoven.conf';
    }
}

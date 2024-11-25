<?php
// File: IIS.php

class IISConfig
{
    public static function generateRewriteRules($home_root, $extensions)
    {
        return trim('
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Serve AVIF" stopProcessing="true">
                    <match url="^' . $home_root . '(.+)\.(' . $extensions . ')$" />
                    <conditions>
                        <add input="{HTTP_ACCEPT}" pattern="image/avif" />
                        <add input="{REQUEST_FILENAME}.avif" matchType="IsFile" />
                    </conditions>
                    <action type="Rewrite" url="{R:1}.{R:2}.avif" />
                </rule>
                <rule name="Serve WebP" stopProcessing="true">
                    <match url="^' . $home_root . '(.+)\.(' . $extensions . ')$" />
                    <conditions>
                        <add input="{HTTP_ACCEPT}" pattern="image/webp" />
                        <add input="{REQUEST_FILENAME}.webp" matchType="IsFile" />
                    </conditions>
                    <action type="Rewrite" url="{R:1}.{R:2}.webp" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>');
    }

    public static function getConfigFileName()
    {
        return 'web.config';
    }
}

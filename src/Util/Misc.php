<?php
namespace HapiClient\Util;

class Misc
{

    /**
     * Recursively removes all the keys
     * containing an empty string as value.
     * Also removes empty arrays in the process.
     * @param	$array	The array to filter
     *
     * @return	the filtered array
     */
    public static function removeEmptyStrings($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::removeEmptyStrings($value);
                
                if (empty($array[$key])) {
                    unset($array[$key]);
                }
            } elseif ($value === '') {
                unset($array[$key]);
            }
        }
        
        return $array;
    }

    /**
     * Use this to adapt Guzzle Client calls.
     */
    public static function getGuzzleVersion()
    {
        $projectDir = dirname(
            __DIR__
            . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR
        );
        if (is_file($projectDir)) {
            $projectDir = \dirname($projectDir);
        }

        $composerFile = $projectDir . DIRECTORY_SEPARATOR . 'composer.json';
        $guzzleVersion = (is_file($composerFile) ? json_decode(file_get_contents($composerFile), true) : [])['require']['guzzlehttp/guzzle'] ?? '';
        if (empty($guzzleVersion)) {
            throw new \Exception('For better version detection, please add your guzzle vendor to the composer.json, you can exceute composer require guzzlehttp/guzzle', '0');
        }

        $guzzleVersion = explode('.', $guzzleVersion);
        $majorVersion = array_shift($guzzleVersion);
        if (is_numeric($majorVersion)) {
            return (int)$majorVersion;
        } elseif (is_string($majorVersion)) {
            $version = preg_replace('/[^0-9]/', '', $majorVersion);

            return (int)$version;
        }

        return 0;
    }
    
    /**
     * Looks for a Certificate Authority file in the CA folder
     * that matches the host and return the values for the
     * verify option to its full path.
     * If no specific file regarding a host is found, uses
     * curl-ca-bundle.crt by default.
     */
    public static function verify($url, $caDir)
    {
        static $hostsByDir = [];
        
        if (!isset($hostsByDir[$caDir])) {
            $hostsByDir[$caDir] = [];
        }
        
        $extensions = ['crt', 'pem', 'cer', 'der'];
        
        // Must be https
        if (substr($url, 0, 5) != 'https') {
            return false;
        }
        
        // Default
        $defaultCA = $caDir . 'curl-ca-bundle.crt';
        
        // Look for a host specific CA file
        $host = strtolower(parse_url($url, PHP_URL_HOST));
        if (!$host) {
            return $defaultCA;
        }
        
        if (array_key_exists($host, $hostsByDir[$caDir])) {
            return $hostsByDir[$caDir][$host];
        }
        
        $filename = $host;
        do {
            // Look for the possible extensions
            foreach ($extensions as $ext) {
                if (file_exists($verify = $caDir . $filename . '.' . $ext)) {
                    $hostsByDir[$caDir][$host] = $verify;
                    return $verify;
                }
            }
            
            // Remove a subdomain each time
            $filename = substr($filename, strpos($filename, '.') + 1);
        } while (substr_count($filename, '.') > 0);
        
        // No specific match
        $hostsByDir[$caDir][$host] = $defaultCA;
        return $defaultCA;
    }
}

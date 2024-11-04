<?php
namespace EndpointService\XmlToJson;

/**
 * XmlToJson
 *
 * Generate a PDF of Office Cards (name labels) for all rooms, or an individual room if param given.
 *
 * @package EndpointService
 * @author akirk
 * @copyright Copyright (c) 2021 NPEU
 * @version 0.1

 **/

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\GenericDataException;

class XmlToJson extends \EndpointService\EndpointService
{
    #private $enable_cache = true;
    private $enable_cache = false;
    private $cache_dir = __DIR__ . '/cache/';
    private $cache_exp = 86400;

    // https://roytanck.com/2021/10/17/generating-short-hashes-in-php/
    public function generate_id( $input, $length = 8 ){
        // Create a raw binary sha256 hash and base64 encode it.
        $hash_base64 = base64_encode( hash( 'sha256', $input, true ) );
        // Replace non-urlsafe chars to make the string urlsafe.
        $hash_urlsafe = strtr( $hash_base64, '+/', '-_' );
        // Trim base64 padding characters from the end.
        $hash_urlsafe = rtrim( $hash_urlsafe, '=' );
        // Shorten the string before returning.
        return substr( $hash_urlsafe, 0, $length );
    }

    public function run()
    {
        if (empty($_GET['xml_url'])) {
            return false;
        }

        $xml_url = base64_decode(urldecode($_GET['xml_url']));

        $xml_url = filter_var($xml_url, FILTER_SANITIZE_URL);
        if (filter_var($xml_url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        // Create a cache filename from the URL:
        $cache_name = $this->generate_id($xml_url);

        // Check if a cache file exists and hadn't expired:
        $cache_file = $this->cache_dir . $cache_name . '.json';
        if ($this->enable_cache && file_exists($cache_file)) {
            $mtime = filemtime($cache_file);
            if ($mtime > (time() - $this->cache_exp)) {
                $json = file_get_contents($cache_file);
            }
        } else {

            // Do we have a proxy we need to use?
            $proxy_string = false;
            $config = Factory::getConfig();

            if ($config->get('proxy_enable')) {
                $proxy_host   = $config->get('proxy_host');
                $proxy_port   = $config->get('proxy_port');
                $proxy_user   = $config->get('proxy_user',false);
                $proxy_pass   = $config->get('proxy_pass', false);

                $proxy_string = '';
                if ($proxy_host && $proxy_port) {
                    $proxy_string = $proxy_host . ':' . $proxy_port;
                }
            }

            $context_config = [
                'http' => [
                    'request_fulluri' => true
                ]
            ];
            if ($proxy_string) {
                $context_config['http']['proxy'] = $proxy_string;
            }
            $context = stream_context_create($context_config);

            $contents = file_get_contents($xml_url, false, $context);
            $xml = simplexml_load_string($contents, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);

            // Cache the json:
            if ($this->enable_cache) {
                file_put_contents($cache_file, $json);
            }
        }

        header('Content-type: application/json');
        echo $json;
        return true;
    }
}
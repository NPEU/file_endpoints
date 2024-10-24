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

    public function run()
    {

        #https://my.corehr.com/pls/uoxrecruit/Erq_search_xml_api.build_search_xml?p_internal_external=A&p_company=10&p_department=b2
        #$data = 'aHR0cHM6Ly9teS5jb3JlaHIuY29tL3Bscy91b3hyZWNydWl0L0VycV9zZWFyY2hfeG1sX2FwaS5idWlsZF9zZWFyY2hfeG1sP3BfaW50ZXJuYWxfZXh0ZXJuYWw9QSZwX2NvbXBhbnk9MTAmcF9kZXBhcnRtZW50PWIy';
        #$proxy_credentials = 'dmzproxy.ndph.ox.ac.uk:8080';
        if (empty($_GET['xml_url'])) {
            return false;
        }


        $xml_url = base64_decode(urldecode($_GET['xml_url']));

        $xml_url = filter_var($xml_url, FILTER_SANITIZE_URL);
        if (filter_var($xml_url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        #echo '<pre>'; var_dump($xml_url); echo '</pre>'; exit;
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
        #$proxy_credentials = 'dmzproxy.ndph.ox.ac.uk:8080';
        #echo '<pre>'; var_dump($proxy_string); echo '</pre>'; exit;

        $context_config = [
            'http' => [
                'request_fulluri' => true
            ]
        ];
        if ($proxy_string) {
            $context_config['http']['proxy'] = $proxy_string;
        }
        #echo '<pre>'; var_dump($context_config); echo '</pre>'; exit;
        $context = stream_context_create($context_config);

        $contents = file_get_contents($xml_url, false, $context);
        #echo '<pre>'; var_dump($contents); echo '</pre>'; exit;
        $xml = simplexml_load_string($contents, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);

        #echo '<pre>'; var_dump($json); echo '</pre>'; exit;
        header('Content-type: application/json');
        echo $json;
        return true;
    }
}
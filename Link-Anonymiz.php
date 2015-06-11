<?php

/**
 * Plugin Name: Anonymiz Hide Referer
 * Plugin URI: http://www.anonymiz.com/plugins/
 * Description: Anonymiz Hide Referer is a plugin to Hide your referer on all external links, eg: https://www.anonymiz.com/?http://yoursite.com/.
 * Version: 1.0
 * Author: anonymiz
 * Author URI: https://www.anonymiz.com/
 * License: GPL http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses
 */


if (defined('WP_DEBUG') && (WP_DEBUG == true)) {
  error_reporting(E_ALL);
}

if (!defined('ABSPATH'))
  die(false);

add_action('init', array('AnonymizHideReferer', 'init'), 9);

class AnonymizHideReferer
{
    private static $instance;

    private function __construct() {
        add_filter('the_content', array($this, 'convert'), 999);
        add_filter('comment_text', array($this, 'convert'), 999);
    }

    public static function init() {
        if (!self::$instance) {
            self::$instance = new AnonymizHideReferer();
        }

        return self::$instance;
    }

    public function convert($content) {
    	$content = make_clickable(str_replace("..",".", $content));

        if (stripos($content, 'href=') === false) {
            return $content;
        }

        $urls = $this->domDocumentExtract($content);

        $urls = $this->regexExtract($content);
        $urls = array_unique($urls);
        $urls = array_filter($urls, array($this, 'validate'));

        $converted_urls = array_map(array($this, 'prefix'), $urls);
        $content = str_ireplace($urls, $converted_urls, $content);
        $content = str_ireplace(">https://www.anonymiz.com/?", " target=\"_blank\">", $content);

        return $content;
    }

    private function validate($url) {
        return (stripos($url, 'http') === 0);
    }

    private function prefix($url) {
        return 'https://www.anonymiz.com/?' . $url;
    }

    private function domDocumentExtract($content) {
        $urls = array();

        $lui_errors = libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->strictErrorChecking = false;
        $dom->loadHTML($content);


        $xPath = new DOMXPath($dom);
        $nodes = $xPath->query('//a/@href');

        foreach ($nodes as $href) {
            $urls[] = $href->nodeValue;
        }

        unset($nodes);
        unset($xPath);
        unset($dom);

        libxml_clear_errors();
        libxml_use_internal_errors($lui_errors);

        return $urls;
    }

    private function regexExtract($content) {
        $urls = array();

        $regex = '/(<a\s*';
        $regex .= '(.*?)\s*';
        $regex .= 'href=[\'"]+?\s*(?P<link>\S+)\s*[\'"]+?';
        $regex .= '\s*(.*?)\s*>\s*';
        $regex .= '(?P<name>[^<]*)';
        $regex .= '\s*<\/a>)/i';
   
        if (preg_match_all($regex, $content, $matches) !== false) {
            $urls = $matches['link'];
        }

        return $urls;
    }
}


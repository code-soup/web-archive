<?php

namespace CodeSoup\WebArchive\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

// Exit if accessed directly
defined('WPINC') || die;

class DOMNodeProcessor
{
    use \CodeSoup\WebArchive\Traits\HelpersTrait;

    private $fs;
    private $node;
    private $paths;

    public function __construct($node, array $paths, $fs)
    {
        $this->fs    = $fs;
        $this->node  = $node;
        $this->paths = $paths;
    }

    public function processNode()
    {   
        $node = $this->node;
        $atts = ['src', 'href'];

        foreach ($atts as $attribute)
        {
            // Skip empty
            if ( ! $node->hasAttribute($attribute))
                continue;

            $url     = $node->getAttribute($attribute);
            $url     = $this->normalizeURL($url);
            $new_url = '';

            // Check if it's an image or a CSS/JS file and process accordingly
            if ($node->tagName === 'img' && $attribute === 'src')
            {
                $new_url = $this->saveAsset($url, 'uploads');
            }
            elseif ($this->isCssJsFile($url))
            {

                $new_url = $this->saveAsset($url, 'assets');
            }

            if ( ! empty($new_url) )
            {
                $node->setAttribute($attribute, $new_url);
            }
        }

        if ($node->hasAttribute('srcset') && $node->tagName === 'img')
        {
            $srcset    = $node->getAttribute('srcset');
            $newSrcset = $this->processSrcset($srcset);

            if ( ! empty($newSrcset) ) {
                $node->setAttribute('srcset', $newSrcset);
            }
        }

        return $node->outerhtml();
    }



    private function processSrcset($srcset)
    {
        $parts    = explode(',', $srcset);
        $newParts = [];

        foreach ($parts as $part)
        {
            [$url, $descriptor] = explode(' ', trim($part) , 2) + [1 => ''];

            $url     = $this->normalizeURL($url);
            $new_url = $this->saveAsset($url, 'uploads');

            // Maybe coudn't download the image
            if ( empty($new_url) )
            {
                return $newParts;
            }

            $newParts[] = trim("$new_url $descriptor");
        }

        return implode(', ', $newParts);
    }


    /**
     * Save file
     * @param  [type] $url  [description]
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    private function saveAsset($url, $type)
    {
        $paths = $this->getPath( $url, $type );

        // Return new loaction
        if ( file_exists( $paths['path'] ) )
        {
            return $paths['uri'];
        }

        $client = new Client([
            'http_errors' => false,
        ]);

        try
        {
            $response = $client->get( $url );

            // File does not exist
            if ( 200 !== $response->getStatusCode() )
            {
                $this->log( $response->getStatusCode() );
                return;
            }

            // Create directory if it doesn't exist
            if ( ! is_dir( dirname($paths['path'])) )
            {
                wp_mkdir_p( dirname($paths['path']) );
            }

            $this->fs->put_contents( $paths['path'], $response->getBody()->getContents() );

            $this->log( $url );
            $this->log( $paths );

            return $paths['uri'];
        }
        catch (RequestException $e) {
            error_log( $e->getMessage() );
        }
    }


    /**
     * Normalize URL
     * - convert to absolute
     * - strip query args
     */
    private function normalizeURL($url)
    {
        // Ensure the URL is absolute
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $url = home_url($url);
        }

        // Strip query arguments
        $parsedUrl = wp_parse_url($url);

        return sprintf(
            '%s://%s%s',
            $parsedUrl['scheme'],
            $parsedUrl['host'],
            isset($parsedUrl['path']) ? $parsedUrl['path'] : ''
        );
    }


    private function getPath($url, $type = 'uploads')
    {
        $rel = '/' . basename($url);
        $key = $type === 'assets'
            ? 'assets'
            : 'uploads';

        if (strpos($url, $this->paths['baseurl']) !== false)
        {
            $rel = str_replace($this->paths['baseurl'], '', $url);
        }
        elseif (strpos($url, $this->paths['theme_url']) !== false)
        {
            $rel = str_replace($this->paths['theme_url'], '', $url);
        }

        return [
            'uri'  => $this->paths["{$key}_uri"] . $rel,
            'path' => $this->paths["{$key}_path"] . $rel,
        ];
    }


    /**
     * SAVE
     */
    private function isImageFile($url)
    {
        return preg_match('/\.(jpeg|jpg|png|gif|svg|webp)$/', wp_parse_url($url, PHP_URL_PATH));
    }

    private function isCssJsFile($url)
    {
        return preg_match('/\.(css|js)$/', wp_parse_url($url, PHP_URL_PATH));
    }
}


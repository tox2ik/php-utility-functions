<?php

namespace Genja\Caching;


/**
 * Utility for generating revved resources.
 *
 * Create URL for <script> and <link> elements that are based on modification time
 * of the referenced file.  This is done to enable long-lived elements in the
 * browser-cache as well as forcing them to update when we want to.
 *
 * https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching (Revved resources)
 */
Class RevisionResource
{

	/**
     * Append the mtime of a file / url before .(min).(js|css),
     * but avoid non-local resources.
     *
     * This can be useful if you are calling this method on "all client-side
     * degendencies", such as the case may be with WordPress, where you can
     * pass all of them to a "filter".
     * 
	 */
    public static function modifyUrlWithMtime($url)
    {
        $uParts = parse_url($url);
        if (! isset($uParts['host'])) {
            return static::mTime($url);
        }

        if (false
        or (false !== strpos($uParts['host'], 'cloudflare'))
        or (false !== strpos($uParts['host'], 'google'))
        or (false !== strpos($uParts['host'], 'vimeo'))
        or (false !== strpos($uParts['host'], 'jquery'))
        ) {
            return $url;
        }
        return static::mTime($url);
    }


    /**
     * Stat the file and insert `.ten digits` before (.min).js
     *
     * Given a file, i.e. /css/base.css, replace it with a string containing the
     * file's mtime, i.e. /css/base.1221534296.css.
     *
     * e.g:
     *
     *     wp_enqueue_style(dashboard, UWF::mTime(http://mw24.no//dashboard/foo.css))
     *     =>
     *     <link rel=style href=http://mw24.no//dashboard/foo.1438609214.css
     *
     * This method solves the issue of bugs that arise due to cached-javascript
     * (and CSS). The browser caches resources based on URL (and headers). We
     * are going to exploit that fact by serving a file under a URL that contains the
     * last modification time of the file.
     *
     * On the receiving end of the request, we strip the "garbage" mtime;
     *
     *     RewriteRule ^(.*)\.[\d]{10}(.min)?\.(css|js)$ $1$2.$3 [L]
     *
     * More info:
     *
     * https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching (Revved resources)
     * http://stackoverflow.com/questions/8224736/javascript-versioning-to-avoid-caching-difference-in-these-practices
     * http://stackoverflow.com/questions/8315088/prevent-requirejs-from-caching-required-scripts
     * http://stackoverflow.com/questions/118884/what-is-an-elegant-way-to-force-browsers-to-reload-cached-css-js-files
     *
     * @param string $fileUrl a path to a file or a url to that file.
     * @param string $documentRoot the internal equivalent of http://host.tld.
     *               e.g: /var/www/host
     * @param int number of seconds that will pass between each time the "mtime" changes for a file that we can not stat.
     * @return string the input with an mtime before the extension.
     */
    public static function mTime($fileUrl, $documentRoot = null, $refreshTimeout = 10800)
    {
        //if (empty($documentRoot) and defined('ABSPATH')) { $documentRoot = ABSPATH; } // wordpress
		if (empty($documentRoot) and defined('BASE_PATH')) { $documentRoot = BASE_PATH; } // sol5

        $urlParts = parse_url($fileUrl);
        $words = explode('.', $fileUrl);
        $ext = array_pop($words);
        $min = array_pop($words);
		if (! ((strtolower($ext) == 'json') or (strtolower($ext) == 'js') or (strtolower($ext) == 'css'))) {
			return $fileUrl;
		}
        if (! isset($urlParts['path'])) {
			return $fileUrl;

        }
        $absPath = $documentRoot . $urlParts['path'];
		$stat = stat($absPath);
        $mtime = is_file($absPath)
            ? $stat['mtime']
            : sprintf('%010d', time() / $refreshTimeout);

        $minExt = $min === 'min' ? ".min.$ext" : ".$ext";
        return str_replace($minExt, ".$mtime$minExt", $fileUrl);
    }

}

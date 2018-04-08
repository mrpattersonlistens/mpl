<?php
/**
 * Library of general-purpose functions for HTML output
 */
 
defined('INTERNAL_SCRIPT') || die;

/**
 * A url comparison using this flag will return true if the base URLs match, params are ignored.
 */
define('URL_MATCH_BASE', 0);
/**
 * A url comparison using this flag will return true if the base URLs match and the params of url1 are part of url2.
 */
define('URL_MATCH_PARAMS', 1);
/**
 * A url comparison using this flag will return true if the two URLs are identical, except for the order of the params.
 */
define('URL_MATCH_EXACT', 2);


/**
 * Add quotes to HTML characters.
 *
 * Returns $var with HTML characters (like "<", ">", etc.) properly quoted.
 *
 * @param string $var the string potentially containing HTML characters
 * @return string
 */
function s($var) {

    if ($var === false) {
        return '0';
    }

    // When we move to PHP 5.4 as a minimum version, change ENT_QUOTES on the
    // next line to ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, and remove the
    // 'UTF-8' argument. Both bring a speed-increase.
    return preg_replace('/&amp;#(\d+|x[0-9a-f]+);/i', '&#$1;', htmlspecialchars($var, ENT_QUOTES, 'UTF-8'));
}

/**
 * Remove query string from url.
 *
 * Takes in a URL and returns it without the querystring portion.
 *
 * @param string $url the url which may have a query string attached.
 * @return string The remaining URL.
 */
function strip_querystring($url) {
    if ($commapos = strpos($url, '?')) {
        return substr($url, 0, $commapos);
    } else {
        return $url;
    }
}

/**
 * Returns the cleaned local URL of the HTTP_REFERER less the URL query string parameters if required.
 *
 * @param bool $stripquery if true, also removes the query part of the url.
 * @return string The resulting referer or empty string.
 */
function get_local_referer($stripquery = true) {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = clean_param($_SERVER['HTTP_REFERER'], PARAM_LOCALURL);
        if ($stripquery) {
            return strip_querystring($referer);
        } else {
            return $referer;
        }
    } else {
        return '';
    }
}

/**
 * Class for creating and manipulating urls.
 */
class url {
    /**
     * Scheme, ex.: http, https
     * @var string
     */
    protected $scheme = '';
    /**
     * Hostname.
     * @var string
     */
    protected $host = '';
    /**
     * Port number, empty means default 80 or 443 in case of http.
     * @var int
     */
    protected $port = '';
    /**
     * Username for http auth.
     * @var string
     */
    protected $user = '';
    /**
     * Password for http auth.
     * @var string
     */
    protected $pass = '';
    /**
     * Script path.
     * @var string
     */
    protected $path = '';
    /**
     * Optional slash argument value.
     * @var string
     */
    protected $slashargument = '';
    /**
     * Anchor, may be also empty, null means none.
     * @var string
     */
    protected $anchor = null;
    /**
     * Url parameters as associative array.
     * @var array
     */
    protected $params = array();
    /**
     * Create new instance of url.
     *
     * @param url|string $url - url means make a copy of another
     *      url and change parameters, string means full url or shortened
     *      form (ex.: '/course/view.php'). It is strongly encouraged to not include
     *      query string because it may result in double encoded values. Use the
     *      $params instead.
     * @param array $params these params override current params or add new
     * @param string $anchor The anchor to use as part of the URL if there is one.
     * @throws mpl_exception
     */
    public function __construct($url, array $params = null, $anchor = null) {
        global $CFG;
        if ($url instanceof url) {
            $this->scheme = $url->scheme;
            $this->host = $url->host;
            $this->port = $url->port;
            $this->user = $url->user;
            $this->pass = $url->pass;
            $this->path = $url->path;
            $this->slashargument = $url->slashargument;
            $this->params = $url->params;
            $this->anchor = $url->anchor;
        } else {
            // Detect if anchor used.
            $apos = strpos($url, '#');
            if ($apos !== false) {
                $anchor = substr($url, $apos);
                $anchor = ltrim($anchor, '#');
                $this->set_anchor($anchor);
                $url = substr($url, 0, $apos);
            }
            // Normalise shortened form of our url ex.: '/course/view.php'.
            if (strpos($url, '/') === 0) {
                // We must not use httpswwwroot here, because it might be url of other page,
                // devs have to use httpswwwroot explicitly when creating new url.
                $url = $CFG->wwwroot.$url;
            }
            // Now fix the admin links if needed, no need to mess with httpswwwroot.
            // Parse the $url.
            $parts = parse_url($url);
            if ($parts === false) {
                throw new mpl_exception('Invalid URL');
            }
            if (isset($parts['query'])) {
                // Note: the values may not be correctly decoded, url parameters should be always passed as array.
                parse_str(str_replace('&amp;', '&', $parts['query']), $this->params);
            }
            unset($parts['query']);
            foreach ($parts as $key => $value) {
                $this->$key = $value;
            }
            // Detect slashargument value from path - we do not support directory names ending with .php.
            $pos = strpos($this->path, '.php/');
            if ($pos !== false) {
                $this->slashargument = substr($this->path, $pos + 4);
                $this->path = substr($this->path, 0, $pos + 4);
            }
        }
        $this->params($params);
        if ($anchor !== null) {
            $this->anchor = (string)$anchor;
        }
    }
    /**
     * Add an array of params to the params for this url.
     *
     * The added params override existing ones if they have the same name.
     *
     * @param array $params Defaults to null. If null then returns all params.
     * @return array Array of Params for url.
     * @throws coding_exception
     */
    public function params(array $params = null) {
        $params = (array)$params;
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                throw new coding_exception('Url parameters can not have numeric keys!');
            }
            if (!is_string($value)) {
                if (is_array($value)) {
                    throw new coding_exception('Url parameters values can not be arrays!');
                }
                if (is_object($value) and !method_exists($value, '__toString')) {
                    throw new coding_exception('Url parameters values cannot be objects, unless __toString() is defined!');
                }
            }
            $this->params[$key] = (string)$value;
        }
        return $this->params;
    }
    /**
     * Remove all params if no arguments passed.
     * Remove selected params if arguments are passed.
     *
     * Can be called as either remove_params('param1', 'param2')
     * or remove_params(array('param1', 'param2')).
     *
     * @param string[]|string $params,... either an array of param names, or 1..n string params to remove as args.
     * @return array url parameters
     */
    public function remove_params($params = null) {
        if (!is_array($params)) {
            $params = func_get_args();
        }
        foreach ($params as $param) {
            unset($this->params[$param]);
        }
        return $this->params;
    }
    /**
     * Remove all url parameters.
     *
     * @return void
     */
    public function remove_all_params() {
        $this->params = array();
        $this->slashargument = '';
    }
    /**
     * Add a param to the params for this url.
     *
     * The added param overrides existing one if they have the same name.
     *
     * @param string $paramname name
     * @param string $newvalue Param value. If new value specified current value is overriden or parameter is added
     * @return mixed string parameter value, null if parameter does not exist
     */
    public function param($paramname, $newvalue = '') {
        if (func_num_args() > 1) {
            // Set new value.
            $this->params(array($paramname => $newvalue));
        }
        if (isset($this->params[$paramname])) {
            return $this->params[$paramname];
        } else {
            return null;
        }
    }
    /**
     * Merges parameters and validates them
     *
     * @param array $overrideparams
     * @return array merged parameters
     * @throws coding_exception
     */
    protected function merge_overrideparams(array $overrideparams = null) {
        $overrideparams = (array)$overrideparams;
        $params = $this->params;
        foreach ($overrideparams as $key => $value) {
            if (is_int($key)) {
                throw new coding_exception('Overridden parameters cannot have numeric keys!');
            }
            if (is_array($value)) {
                throw new coding_exception('Overridden parameters values can not be arrays!');
            }
            if (is_object($value) and !method_exists($value, '__toString')) {
                throw new coding_exception('Overridden parameters values cannot be objects, unless __toString() is defined!');
            }
            $params[$key] = (string)$value;
        }
        return $params;
    }
    /**
     * Get the params as as a query string.
     *
     * @param bool $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output params, these
     *      override existing ones with the same name.
     * @return string query string that can be added to a url.
     */
    public function get_query_string($escaped = true, array $overrideparams = null) {
        $arr = array();
        if ($overrideparams !== null) {
            $params = $this->merge_overrideparams($overrideparams);
        } else {
            $params = $this->params;
        }
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $index => $value) {
                    $arr[] = rawurlencode($key.'['.$index.']')."=".rawurlencode($value);
                }
            } else {
                if (isset($val) && $val !== '') {
                    $arr[] = rawurlencode($key)."=".rawurlencode($val);
                } else {
                    $arr[] = rawurlencode($key);
                }
            }
        }
        if ($escaped) {
            return implode('&amp;', $arr);
        } else {
            return implode('&', $arr);
        }
    }
    /**
     * Shortcut for printing of encoded URL.
     *
     * @return string
     */
    public function __toString() {
        return $this->out(true);
    }
    /**
     * Output url.
     *
     * If you use the returned URL in HTML code, you want the escaped ampersands. If you use
     * the returned URL in HTTP headers, you want $escaped=false.
     *
     * @param bool $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string Resulting URL
     */
    public function out($escaped = true, array $overrideparams = null) {
        global $CFG;
        if (!is_bool($escaped)) {
            debugging('Escape parameter must be of type boolean, '.gettype($escaped).' given instead.');
        }
        $uri = $this->out_omit_querystring().$this->slashargument;
        $querystring = $this->get_query_string($escaped, $overrideparams);
        if ($querystring !== '') {
            $uri .= '?' . $querystring;
        }
        if (!is_null($this->anchor)) {
            $uri .= '#'.$this->anchor;
        }
        return $uri;
    }
    /**
     * Returns url without parameters, everything before '?'.
     *
     * @param bool $includeanchor if {@link self::anchor} is defined, should it be returned?
     * @return string
     */
    public function out_omit_querystring($includeanchor = false) {
        $uri = $this->scheme ? $this->scheme.':'.((strtolower($this->scheme) == 'mailto') ? '':'//'): '';
        $uri .= $this->user ? $this->user.($this->pass? ':'.$this->pass:'').'@':'';
        $uri .= $this->host ? $this->host : '';
        $uri .= $this->port ? ':'.$this->port : '';
        $uri .= $this->path ? $this->path : '';
        if ($includeanchor and !is_null($this->anchor)) {
            $uri .= '#' . $this->anchor;
        }
        return $uri;
    }
    /**
     * Compares this url with another.
     *
     * @param url $url The url object to compare
     * @param int $matchtype The type of comparison (URL_MATCH_BASE, URL_MATCH_PARAMS, URL_MATCH_EXACT)
     * @return bool
     */
    public function compare(url $url, $matchtype = URL_MATCH_EXACT) {
        $baseself = $this->out_omit_querystring();
        $baseother = $url->out_omit_querystring();
        // Append index.php if there is no specific file.
        if (substr($baseself, -1) == '/') {
            $baseself .= 'index.php';
        }
        if (substr($baseother, -1) == '/') {
            $baseother .= 'index.php';
        }
        // Compare the two base URLs.
        if ($baseself != $baseother) {
            return false;
        }
        if ($matchtype == URL_MATCH_BASE) {
            return true;
        }
        $urlparams = $url->params();
        foreach ($this->params() as $param => $value) {
            if ($param == 'sesskey') {
                continue;
            }
            if (!array_key_exists($param, $urlparams) || $urlparams[$param] != $value) {
                return false;
            }
        }
        if ($matchtype == URL_MATCH_PARAMS) {
            return true;
        }
        foreach ($urlparams as $param => $value) {
            if ($param == 'sesskey') {
                continue;
            }
            if (!array_key_exists($param, $this->params()) || $this->param($param) != $value) {
                return false;
            }
        }
        if ($url->anchor !== $this->anchor) {
            return false;
        }
        return true;
    }
    /**
     * Sets the anchor for the URI (the bit after the hash)
     *
     * @param string $anchor null means remove previous
     */
    public function set_anchor($anchor) {
        if (is_null($anchor)) {
            // Remove.
            $this->anchor = null;
        } else if ($anchor === '') {
            // Special case, used as empty link.
            $this->anchor = '';
        } else if (preg_match('|[a-zA-Z\_\:][a-zA-Z0-9\_\-\.\:]*|', $anchor)) {
            // Match the anchor against the NMTOKEN spec.
            $this->anchor = $anchor;
        } else {
            // Bad luck, no valid anchor found.
            $this->anchor = null;
        }
    }
    /**
     * Sets the scheme for the URI (the bit before ://)
     *
     * @param string $scheme
     */
    public function set_scheme($scheme) {
        // See http://www.ietf.org/rfc/rfc3986.txt part 3.1.
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*$/', $scheme)) {
            $this->scheme = $scheme;
        } else {
            throw new coding_exception('Bad URL scheme.');
        }
    }
    /**
     * Sets the url slashargument value.
     *
     * @param string $path usually file path
     * @param string $parameter name of page parameter if slasharguments not supported
     * @param bool $supported usually null, then it depends on $CFG->slasharguments, use true or false for other servers
     * @return void
     */
    public function set_slashargument($path, $parameter = 'file', $supported = null) {
        global $CFG;
        if (is_null($supported)) {
            $supported = !empty($CFG->slasharguments);
        }
        if ($supported) {
            $parts = explode('/', $path);
            $parts = array_map('rawurlencode', $parts);
            $path  = implode('/', $parts);
            $this->slashargument = $path;
            unset($this->params[$parameter]);
        } else {
            $this->slashargument = '';
            $this->params[$parameter] = $path;
        }
    }
    // Static factory methods.
    /**
     * General file url.
     *
     * @param string $urlbase the script serving the file
     * @param string $path
     * @param bool $forcedownload
     * @return url
     */
    public static function make_file_url($urlbase, $path, $forcedownload = false) {
        $params = array();
        if ($forcedownload) {
            $params['forcedownload'] = 1;
        }
        $url = new url($urlbase, $params);
        $url->set_slashargument($path);
        return $url;
    }
    /**
     * Returns URL a relative path from $CFG->wwwroot
     *
     * Can be used for passing around urls with the wwwroot stripped
     *
     * @param boolean $escaped Use &amp; as params separator instead of plain &
     * @param array $overrideparams params to add to the output url, these override existing ones with the same name.
     * @return string Resulting URL
     * @throws coding_exception if called on a non-local url
     */
    public function out_as_local_url($escaped = true, array $overrideparams = null) {
        global $CFG;
        $url = $this->out($escaped, $overrideparams);
        $httpswwwroot = str_replace("http://", "https://", $CFG->wwwroot);
        // Url should be equal to wwwroot or httpswwwroot. If not then throw exception.
        if (($url === $CFG->wwwroot) || (strpos($url, $CFG->wwwroot.'/') === 0)) {
            $localurl = substr($url, strlen($CFG->wwwroot));
            return !empty($localurl) ? $localurl : '';
        } else if (($url === $httpswwwroot) || (strpos($url, $httpswwwroot.'/') === 0)) {
            $localurl = substr($url, strlen($httpswwwroot));
            return !empty($localurl) ? $localurl : '';
        } else {
            throw new coding_exception('url::out_as_local_url() called on a non-local URL');
        }
    }
    /**
     * Returns the 'path' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return '/my/file/is/here.txt'.
     *
     * By default the path includes slash-arguments (for example,
     * '/myfile.php/extra/arguments') so it is what you would expect from a
     * URL path. If you don't want this behaviour, you can opt to exclude the
     * slash arguments. (Be careful: if the $CFG variable slasharguments is
     * disabled, these URLs will have a different format and you may need to
     * look at the 'file' parameter too.)
     *
     * @param bool $includeslashargument If true, includes slash arguments
     * @return string Path of URL
     */
    public function get_path($includeslashargument = true) {
        return $this->path . ($includeslashargument ? $this->slashargument : '');
    }
    /**
     * Returns a given parameter value from the URL.
     *
     * @param string $name Name of parameter
     * @return string Value of parameter or null if not set
     */
    public function get_param($name) {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        } else {
            return null;
        }
    }
    /**
     * Returns the 'scheme' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return 'http' (without the colon).
     *
     * @return string Scheme of the URL.
     */
    public function get_scheme() {
        return $this->scheme;
    }
    /**
     * Returns the 'host' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return 'www.example.org'.
     *
     * @return string Host of the URL.
     */
    public function get_host() {
        return $this->host;
    }
    /**
     * Returns the 'port' portion of a URL. For example, if the URL is
     * http://www.example.org:447/my/file/is/here.txt?really=1 then this will
     * return '447'.
     *
     * @return string Port of the URL.
     */
    public function get_port() {
        return $this->port;
    }
}

/**
 * Redirects the user to another page, after printing a notice.
 *
 * This function calls the OUTPUT redirect method, echo's the output and then dies to ensure nothing else happens.
 *
 * <strong>Good practice:</strong> You should call this method before starting page
 * output by using any of the OUTPUT methods.
 *
 * @param url|string $url A url to redirect to. Strings are not to be trusted!
 * @param string $message The message to display to the user
 * @param int $delay The delay before redirecting (defaults to 3 secs)
 * @param string $messagetype The type of notification to show the message in. See constants on \core\output\notification.
 * @throws mpl_exception
 */
function redirect($url, $message='', $delay=null, $messagetype = NOTIFICATION_INFO) {
    global $OUTPUT, $PAGE, $CFG;

    if ($delay === null) {
        $delay = -1;
    }

    if ($PAGE) {
        $PAGE->set_title('This page will redirect.');
    }
    
    if ($url instanceof url) {
        $url = $url->out(false);
    }
    
    // Determine whether redirect should be disabled because debugging messages have been printed
    $debugdisableredirect = false;
    do {
        if (defined('DEBUGGING_PRINTED')) {
            // Some debugging already printed, no need to look more.
            $debugdisableredirect = true;
            break;
        }

        if (empty($CFG->debugdisplay) or empty($CFG->debug)) {
            // No errors should be displayed.
            break;
        }

        if (!function_exists('error_get_last') or !$lasterror = error_get_last()) {
            break;
        }

        if (!($lasterror['type'] & $CFG->debug)) {
            // Last error not interesting.
            break;
        }

        // Watch out here, @hidden() errors are returned from error_get_last() too.
        if (headers_sent()) {
            // We already started printing something - that means errors likely printed.
            $debugdisableredirect = true;
            break;
        }

        if (ob_get_level() and ob_get_contents()) {
            // There is something waiting to be printed, hopefully it is the errors,
            // but it might be some error hidden by @ too - such as the timezone mess from setup.php.
            $debugdisableredirect = true;
            break;
        }
    } while (false);

    // Technically, HTTP/1.1 requires Location: header to contain the absolute path.
    // (In practice browsers accept relative paths - but still, might as well do it properly.)
    // This code turns relative into absolute.
    if (!preg_match('|^[a-z]+:|i', $url)) {
        // Get host name http://www.wherever.com.
        $hostpart = preg_replace('|^(.*?[^:/])/.*$|', '$1', $CFG->wwwroot);
        if (preg_match('|^/|', $url)) {
            // URLs beginning with / are relative to web server root so we just add them in.
            $url = $hostpart.$url;
        } else {
            // URLs not beginning with / are relative to path of current script, so add that on.
            $url = $hostpart.preg_replace('|\?.*$|', '', me()).'/../'.$url;
        }
        // Replace all ..s.
        while (true) {
            $newurl = preg_replace('|/(?!\.\.)[^/]*/\.\./|', '/', $url);
            if ($newurl == $url) {
                break;
            }
            $url = $newurl;
        }
    }

    // Sanitise url - we can not rely on URL cleaning
    // because it does not support all valid external URLs.
    $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);
    $url = str_replace('"', '%22', $url);
    $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
    $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />', FORMAT_HTML));
    $encodedurl = str_replace('&amp;', '&', $encodedurl);

    if (!empty($message)) {
        if (!$debugdisableredirect && !headers_sent()) {
            // A message has been provided, and the headers have not yet been sent.
            // Display the message as a notification on the subsequent page.
            $PAGE->notify($message, $messagetype);
            $message = null;
            $delay = 0;
        } else {
            if ($delay === -1 || !is_numeric($delay)) {
                $delay = 3;
            }
            $message = clean_text($message);
        }
    } else {
        $message = 'This page will automatically redirect.';
        $delay = 0;
    }

    // Make sure the session is closed properly, this prevents problems in IIS
    // and also some potential PHP shutdown issues.
    // \session\manager::write_close();

    if ($delay == 0 && !$debugdisableredirect && !headers_sent()) {
        // 302 might not work for POST requests, 303 is ignored by obsolete clients.
        @header($_SERVER['SERVER_PROTOCOL'] . ' 303 See Other');
        @header('Location: '.$url);
        echo renderer::plain_redirect_message($encodedurl);
        exit;
    }

    // Include a redirect message, even with a HTTP redirect, because that is recommended practice.
    if ($PAGE) {
        echo $OUTPUT->redirect_message($encodedurl, $message, $delay, $debugdisableredirect, $messagetype);
        exit;
    } else {
        echo renderer::early_redirect_message($encodedurl, $message, $delay);
        exit;
    }
}

/**
 * Returns the name of the current script, WITH the querystring portion.
 *
 * This function is necessary because PHP_SELF and REQUEST_URI and SCRIPT_NAME
 * return different things depending on a lot of things like your OS, Web
 * server, and the way PHP is compiled (ie. as a CGI, module, ISAPI, etc.)
 * <b>NOTE:</b> This function returns false if the global variables needed are not set.
 *
 * @return mixed String or false if the global variables needed are not set.
 */
function me() {
    global $ME;
    return $ME;
}

/**
 * Guesses the full URL of the current script.
 *
 * This function is using $PAGE->url, but may fall back to $FULLME which
 * is constructed from  PHP_SELF and REQUEST_URI or SCRIPT_NAME
 *
 * @return mixed full page URL string or false if unknown
 */
function qualified_me() {
    global $FULLME, $PAGE, $CFG;
    if (isset($PAGE) and $PAGE->has_set_url()) {
        // This is the only recommended way to find out current page.
        return $PAGE->url->out(false);
    } else {
        if ($FULLME === null) {
            return false;
        }
        if (!empty($CFG->sslproxy)) {
            // Return only https links when using SSL proxy.
            return preg_replace('/^http:/', 'https:', $FULLME, 1);
        } else {
            return $FULLME;
        }
    }
}
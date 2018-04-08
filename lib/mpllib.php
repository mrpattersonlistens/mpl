<?php
/*
 * Library of general functions
 */

defined('INTERNAL_SCRIPT') || die;

// Parameter constants - every call to optional_param(), required_param()
// or clean_param() should have a specified type of parameter.

/**
 * PARAM_ALPHA - contains only english ascii letters a-zA-Z.
 */
define('PARAM_ALPHA',    'alpha');

/**
 * PARAM_ALPHAEXT the same contents as PARAM_ALPHA plus the chars in quotes: "_-" allowed
 * NOTE: originally this allowed "/" too, please use PARAM_SAFEPATH if "/" needed
 */
define('PARAM_ALPHAEXT', 'alphaext');

/**
 * PARAM_ALPHANUM - expected numbers and letters only.
 */
define('PARAM_ALPHANUM', 'alphanum');

/**
 * PARAM_ALPHANUMEXT - expected numbers, letters only and _-.
 */
define('PARAM_ALPHANUMEXT', 'alphanumext');

/**
 * PARAM_BASE64 - Base 64 encoded format
 */
define('PARAM_BASE64',   'base64');

/**
 * PARAM_BOOL - converts input into 0 or 1, use for switches in forms and urls.
 */
define('PARAM_BOOL',     'bool');

/**
 * PARAM_FILE - safe file name, all dangerous chars are stripped, protects against XSS, SQL injections and directory traversals
 */
define('PARAM_FILE',   'file');

/**
 * PARAM_FLOAT - a real/floating point number.
 */
define('PARAM_FLOAT',  'float');

/**
 * PARAM_HOST - expected fully qualified domain name (FQDN) or an IPv4 dotted quad (IP address)
 */
define('PARAM_HOST',     'host');

/**
 * PARAM_INT - integers only, use when expecting only numbers.
 */
define('PARAM_INT',      'int');

/**
 * PARAM_LOCALURL - expected properly formatted URL as well as one that refers to the local server itself. (NOT orthogonal to the
 * others! Implies PARAM_URL!)
 */
define('PARAM_LOCALURL', 'localurl');

/**
 * PARAM_RAW specifies a parameter that is not cleaned/processed in any way except the discarding of the invalid utf-8 characters
 */
define('PARAM_RAW', 'raw');

/**
 * PARAM_RAW_TRIMMED like PARAM_RAW but leading and trailing whitespace is stripped.
 */
define('PARAM_RAW_TRIMMED', 'raw_trimmed');

/**
 * PARAM_SAFEDIR - safe directory name, suitable for include() and require()
 */
define('PARAM_SAFEDIR',  'safedir');

/**
 * PARAM_SAFEPATH - several PARAM_SAFEDIR joined by "/", suitable for include() and require(), plugin paths, etc.
 */
define('PARAM_SAFEPATH',  'safepath');

/**
 * PARAM_SEQUENCE - expects a sequence of numbers like 8 to 1,5,6,4,6,8,9.  Numbers and comma only.
 */
define('PARAM_SEQUENCE',  'sequence');

/**
 * PARAM_TEXT - general plain text, no other html tags. Please note '<', or '>' are allowed here.
 */
define('PARAM_TEXT',  'text');

/**
 * PARAM_URL - expected properly formatted URL. Please note that domain part is required, http://localhost/ is not accepted but
 * http://localhost.localdomain/ is ok.
 */
define('PARAM_URL',      'url');


// Input scrubber
function clean_text($text) {
	$data = trim($text);
	$text = stripslashes($text);
	$text = htmlspecialchars($text);
	return $text;
}

function pr($var, $title = '', $var_dump = false) {
    if (!empty($title)) {
        $title = "<h3>$title</h3>";
    }
    if ($var_dump) {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        // Remove the first line of var_dump, which is a trace to this function
        $pos = strpos($output, "\n");
        if ($pos !== false) {
            $output = substr($output, $pos);
        }
    } else {
        $output = print_r($var, true);
    }
    return "$title<pre>$output</pre>";
}

/**
 * Returns a particular value for the named variable, taken from
 * POST or GET, otherwise returning a given default.
 *
 * @param string $parname the name of the page parameter we want
 * @param mixed  $default the default value to return if nothing is found
 * @param string $type expected type of parameter
 * @return mixed
 * @throws mpl_exception
 */
function optional_param($parname, $default, $type) {
    if (func_num_args() != 3 or empty($parname) or empty($type)) {
        throw new invalid_parameter_exception('optional_param requires $parname, $default + $type to be specified (parameter: '.$parname.')');
    }

    // POST has precedence.
    if (isset($_POST[$parname])) {
        $param = $_POST[$parname];
    } else if (isset($_GET[$parname])) {
        $param = $_GET[$parname];
    } else {
        return $default;
    }

    return clean_param($param, $type);
}

/**
 * Used by {@link optional_param()} and {@link required_param()} to
 * clean the variables and/or cast to specific types, based on
 * an options field.
 *
 * @param mixed $param the variable we are cleaning
 * @param string $type expected format of param after cleaning.
 * @return mixed
 * @throws mpl_exception
 */
function clean_param($param, $type) {
    global $CFG;

    if (is_array($param)) {
        throw new coding_exception('clean_param() can not process arrays, please use clean_param_array() instead.');
    } else if (is_object($param)) {
        if (method_exists($param, '__toString')) {
            $param = $param->__toString();
        } else {
            throw new coding_exception('clean_param() can not process objects, please use clean_param_array() instead.');
        }
    }
    
    switch ($type) {
        case PARAM_RAW:
            // No cleaning at all.
            $param = fix_utf8($param);
            return $param;

        case PARAM_RAW_TRIMMED:
            // No cleaning, but strip leading and trailing whitespace.
            $param = fix_utf8($param);
            return trim($param);

        case PARAM_INT:
            // Convert to integer.
            return (int)$param;

        case PARAM_FLOAT:
            // Convert to float.
            return (float)$param;

        case PARAM_ALPHA:
            // Remove everything not `a-z`.
            return preg_replace('/[^a-zA-Z]/i', '', $param);

        case PARAM_ALPHAEXT:
            // Remove everything not `a-zA-Z_-` (originally allowed "/" too).
            return preg_replace('/[^a-zA-Z_-]/i', '', $param);

        case PARAM_ALPHANUM:
            // Remove everything not `a-zA-Z0-9`.
            return preg_replace('/[^A-Za-z0-9]/i', '', $param);

        case PARAM_ALPHANUMEXT:
            // Remove everything not `a-zA-Z0-9_-`.
            return preg_replace('/[^A-Za-z0-9_-]/i', '', $param);

        case PARAM_SEQUENCE:
            // Remove everything not `0-9,`.
            return preg_replace('/[^0-9,]/i', '', $param);

        case PARAM_BOOL:
            // Convert to 1 or 0.
            $tempstr = strtolower($param);
            if ($tempstr === 'on' or $tempstr === 'yes' or $tempstr === 'true') {
                $param = 1;
            } else if ($tempstr === 'off' or $tempstr === 'no'  or $tempstr === 'false') {
                $param = 0;
            } else {
                $param = empty($param) ? 0 : 1;
            }
            return $param;

        case PARAM_NOTAGS:
            // Strip all tags.
            $param = fix_utf8($param);
            return strip_tags($param);

        case PARAM_TEXT:
            // Leave only tags needed for multilang.
            $param = fix_utf8($param);
            // If the multilang syntax is not correct we strip all tags because it would break xhtml strict which is required
            // for accessibility standards please note this cleaning does not strip unbalanced '>' for BC compatibility reasons.
            do {
                if (strpos($param, '</lang>') !== false) {
                    // Old and future mutilang syntax.
                    $param = strip_tags($param, '<lang>');
                    if (!preg_match_all('/<.*>/suU', $param, $matches)) {
                        break;
                    }
                    $open = false;
                    foreach ($matches[0] as $match) {
                        if ($match === '</lang>') {
                            if ($open) {
                                $open = false;
                                continue;
                            } else {
                                break 2;
                            }
                        }
                        if (!preg_match('/^<lang lang="[a-zA-Z0-9_-]+"\s*>$/u', $match)) {
                            break 2;
                        } else {
                            $open = true;
                        }
                    }
                    if ($open) {
                        break;
                    }
                    return $param;

                } else if (strpos($param, '</span>') !== false) {
                    // Current problematic multilang syntax.
                    $param = strip_tags($param, '<span>');
                    if (!preg_match_all('/<.*>/suU', $param, $matches)) {
                        break;
                    }
                    $open = false;
                    foreach ($matches[0] as $match) {
                        if ($match === '</span>') {
                            if ($open) {
                                $open = false;
                                continue;
                            } else {
                                break 2;
                            }
                        }
                        if (!preg_match('/^<span(\s+lang="[a-zA-Z0-9_-]+"|\s+class="multilang"){2}\s*>$/u', $match)) {
                            break 2;
                        } else {
                            $open = true;
                        }
                    }
                    if ($open) {
                        break;
                    }
                    return $param;
                }
            } while (false);
            // Easy, just strip all tags, if we ever want to fix orphaned '&' we have to do that in format_string().
            return strip_tags($param);
            
        case PARAM_FILE:
            // Strip all suspicious characters from filename.
            $param = fix_utf8($param);
            $param = preg_replace('~[[:cntrl:]]|[&<>"`\|\':\\\\/]~u', '', $param);
            if ($param === '.' || $param === '..') {
                $param = '';
            }
            return $param;

        case PARAM_PATH:
            // Strip all suspicious characters from file path.
            $param = fix_utf8($param);
            $param = str_replace('\\', '/', $param);

            // Explode the path and clean each element using the PARAM_FILE rules.
            $breadcrumb = explode('/', $param);
            foreach ($breadcrumb as $key => $crumb) {
                if ($crumb === '.' && $key === 0) {
                    // Special condition to allow for relative current path such as ./currentdirfile.txt.
                } else {
                    $crumb = clean_param($crumb, PARAM_FILE);
                }
                $breadcrumb[$key] = $crumb;
            }
            $param = implode('/', $breadcrumb);

            // Remove multiple current path (./././) and multiple slashes (///).
            $param = preg_replace('~//+~', '/', $param);
            $param = preg_replace('~/(\./)+~', '/', $param);
            return $param;

        case PARAM_HOST:
            // Allow FQDN or IPv4 dotted quad.
            $param = preg_replace('/[^\.\d\w-]/', '', $param );
            // Match ipv4 dotted quad.
            if (preg_match('/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})/', $param, $match)) {
                // Confirm values are ok.
                if ( $match[0] > 255
                     || $match[1] > 255
                     || $match[3] > 255
                     || $match[4] > 255 ) {
                    // Hmmm, what kind of dotted quad is this?
                    $param = '';
                }
            } else if ( preg_match('/^[\w\d\.-]+$/', $param) // Dots, hyphens, numbers.
                       && !preg_match('/^[\.-]/',  $param) // No leading dots/hyphens.
                       && !preg_match('/[\.-]$/',  $param) // No trailing dots/hyphens.
                       ) {
                // All is ok - $param is respected.
            } else {
                // All is not ok...
                $param='';
            }
            return $param;

        case PARAM_URL:          // Allow safe ftp, http, mailto urls.
            $param = fix_utf8($param);
            include_once($CFG->dirroot . '/lib/validateurlsyntax.php');
            if (!empty($param) && validateUrlSyntax($param, 's?H?S?F?E?u-P-a?I?p?f?q?r?')) {
                // All is ok, param is respected.
            } else {
                // Not really ok.
                $param ='';
            }
            return $param;

        case PARAM_LOCALURL:
            // Allow http absolute, root relative and relative URLs within wwwroot.
            $param = clean_param($param, PARAM_URL);
            if (!empty($param)) {

                // Simulate the HTTPS version of the site.
                $httpswwwroot = str_replace('http://', 'https://', $CFG->wwwroot);

                if ($param === $CFG->wwwroot) {
                    // Exact match;
                } else if (!empty($CFG->loginhttps) && $param === $httpswwwroot) {
                    // Exact match;
                } else if (preg_match(':^/:', $param)) {
                    // Root-relative, ok!
                } else if (preg_match('/^' . preg_quote($CFG->wwwroot . '/', '/') . '/i', $param)) {
                    // Absolute, and matches our wwwroot.
                } else if (!empty($CFG->loginhttps) && preg_match('/^' . preg_quote($httpswwwroot . '/', '/') . '/i', $param)) {
                    // Absolute, and matches our httpswwwroot.
                } else {
                    // Relative - let's make sure there are no tricks.
                    if (validateUrlSyntax('/' . $param, 's-u-P-a-p-f+q?r?')) {
                        // Looks ok.
                    } else {
                        $param = '';
                    }
                }
            }
            return $param;

        case PARAM_BASE64:
            if (!empty($param)) {
                // PEM formatted strings may contain letters/numbers and the symbols
                //   forward slash: /
                //   plus sign:     +
                //   equal sign:    =.
                if (0 >= preg_match('/^([\s\w\/\+=]+)$/', trim($param))) {
                    return '';
                }
                $lines = preg_split('/[\s]+/', $param, -1, PREG_SPLIT_NO_EMPTY);
                // Each line of base64 encoded data must be 64 characters in length, except for the last line which may be less
                // than (or equal to) 64 characters long.
                for ($i=0, $j=count($lines); $i < $j; $i++) {
                    if ($i + 1 == $j) {
                        if (64 < strlen($lines[$i])) {
                            return '';
                        }
                        continue;
                    }

                    if (64 != strlen($lines[$i])) {
                        return '';
                    }
                }
                return implode("\n", $lines);
            } else {
                return '';
            }

        default:
            // Doh! throw error, switched parameters in optional_param or another serious problem.
            throw new mpl_exception("Unknown param type: $type");
    }
}

/**
 * Makes sure array contains only the allowed types, this function does not validate array key names!
 *
 * <code>
 * $options = clean_param($options, PARAM_INT);
 * </code>
 *
 * @param array $param the variable array we are cleaning
 * @param string $type expected format of param after cleaning.
 * @param bool $recursive clean recursive arrays
 * @return array
 * @throws coding_exception
 */
function clean_param_array(array $param = null, $type, $recursive = false) {
    // Convert null to empty array.
    $param = (array)$param;
    foreach ($param as $key => $value) {
        if (is_array($value)) {
            if ($recursive) {
                $param[$key] = clean_param_array($value, $type, true);
            } else {
                throw new coding_exception('clean_param_array can not process multidimensional arrays when $recursive is false.');
            }
        } else {
            $param[$key] = clean_param($value, $type);
        }
    }
}
    

/**
 * Makes sure the data is using valid utf8, invalid characters are discarded.
 *
 * Note: this function is not intended for full objects with methods and private properties.
 *
 * @param mixed $value
 * @return mixed with proper utf-8 encoding
 */
function fix_utf8($value) {
    if (is_null($value) or $value === '') {
        return $value;

    } else if (is_string($value)) {
        if ((string)(int)$value === $value) {
            // Shortcut.
            return $value;
        }
        // No null bytes expected in our data, so let's remove it.
        $value = str_replace("\0", '', $value);

        // Note: this duplicates min_fix_utf8() intentionally.
        static $buggyiconv = null;
        if ($buggyiconv === null) {
            $buggyiconv = (!function_exists('iconv') or @iconv('UTF-8', 'UTF-8//IGNORE', '100'.chr(130).'€') !== '100€');
        }

        if ($buggyiconv) {
            if (function_exists('mb_convert_encoding')) {
                $subst = mb_substitute_character();
                mb_substitute_character('');
                $result = mb_convert_encoding($value, 'utf-8', 'utf-8');
                mb_substitute_character($subst);

            } else {
                // Warn admins on admin/index.php page.
                $result = $value;
            }

        } else {
            $result = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }

        return $result;

    } else if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = fix_utf8($v);
        }
        return $value;

    } else if (is_object($value)) {
        // Do not modify original.
        $value = clone($value);
        foreach ($value as $k => $v) {
            $value->$k = fix_utf8($v);
        }
        return $value;

    } else {
        // This is some other type, no utf-8 here.
        return $value;
    }
}

/**
 * This function makes the return value of ini_get consistent if you are
 * setting server directives through the .htaccess file in apache.
 *
 * Current behavior for value set from php.ini On = 1, Off = [blank]
 * Current behavior for value set from .htaccess On = On, Off = Off
 * Contributed by jdell @ unr.edu
 *
 * @param string $ini_get_arg The argument to get
 * @return bool True for on false for not
 */
function ini_get_bool($ini_get_arg) {
    $temp = ini_get($ini_get_arg);

    if ($temp == '1' or strtolower($temp) == 'on') {
        return true;
    }
    return false;
}



/**
 * Set a key in the global configuration table
 *
 * Set a key/value pair in both this session's {@link $CFG} global variable
 * and in the 'config' database table for future sessions.
 *
 * A NULL value will delete the entry.
 *
 * @param string $name the key to set
 * @param string $value the value to set (without magic quotes)
 * @return bool true or exception
 */
// function set_config($name, $value) {
//     global $CFG, $DB;

//     if (!array_key_exists($name, $CFG->config_php_settings)) {
//         // So it's defined for this invocation at least.
//         if (is_null($value)) {
//             unset($CFG->$name);
//         } else {
//             // Settings from db are always strings.
//             $CFG->$name = (string)$value;
//         }
//     }

//     if ($DB->get_field('config', 'name', array('name' => $name))) {
//         if ($value === null) {
//             $DB->delete_records('DELETE FROM config WHERE name = ?', array($name));
//         } else {
//             $DB->set_field('config', 'value', $value, array('name' => $name));
//         }
//     } else {
//         if ($value !== null) {
//             $insert = 'INSERT INTO config (name, value)
//                       VALUES (:name, :value)';
//             $DB->insert_record($insert, array(':name' => $name, ':value' => $value));
//         }
//     }

//     return true;
// }

/**
 * Returns most reliable client address
 *
 * @param string $default If an address can't be determined, then return this
 * @return string The remote IP address
 */
function getremoteaddr($default='0.0.0.0') {
    global $CFG;

    if (empty($CFG->getremoteaddrconf)) {
        // This will happen, for example, just after the upgrade, as the
        // user is redirected to the admin screen.
        $variablestoskip = 0;
    } else {
        $variablestoskip = $CFG->getremoteaddrconf;
    }
    if (!($variablestoskip & GETREMOTEADDR_SKIP_HTTP_CLIENT_IP)) {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $address = cleanremoteaddr($_SERVER['HTTP_CLIENT_IP']);
            return $address ? $address : $default;
        }
    }
    if (!($variablestoskip & GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR)) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedaddresses = explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
            $address = $forwardedaddresses[0];

            if (substr_count($address, ":") > 1) {
                // Remove port and brackets from IPv6.
                if (preg_match("/\[(.*)\]:/", $address, $matches)) {
                    $address = $matches[1];
                }
            } else {
                // Remove port from IPv4.
                if (substr_count($address, ":") == 1) {
                    $parts = explode(":", $address);
                    $address = $parts[0];
                }
            }

            $address = cleanremoteaddr($address);
            return $address ? $address : $default;
        }
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $address = cleanremoteaddr($_SERVER['REMOTE_ADDR']);
        return $address ? $address : $default;
    } else {
        return $default;
    }
}

/**
 * Cleans an ip address.
 *
 * @param string $addr IPv4 or IPv6 address
 * @param bool $compress use IPv6 address compression
 * @return string normalised ip address string, null if error
 */
function cleanremoteaddr($addr, $compress=false) {
    $addr = trim($addr);

    // TODO: maybe add a separate function is_addr_public() or something like this.

    if (strpos($addr, ':') !== false) {
        // Can be only IPv6.
        $parts = explode(':', $addr);
        $count = count($parts);

        if (strpos($parts[$count-1], '.') !== false) {
            // Legacy ipv4 notation.
            $last = array_pop($parts);
            $ipv4 = cleanremoteaddr($last, true);
            if ($ipv4 === null) {
                return null;
            }
            $bits = explode('.', $ipv4);
            $parts[] = dechex($bits[0]).dechex($bits[1]);
            $parts[] = dechex($bits[2]).dechex($bits[3]);
            $count = count($parts);
            $addr = implode(':', $parts);
        }

        if ($count < 3 or $count > 8) {
            return null; // Severly malformed.
        }

        if ($count != 8) {
            if (strpos($addr, '::') === false) {
                return null; // Malformed.
            }
            // Uncompress.
            $insertat = array_search('', $parts, true);
            $missing = array_fill(0, 1 + 8 - $count, '0');
            array_splice($parts, $insertat, 1, $missing);
            foreach ($parts as $key => $part) {
                if ($part === '') {
                    $parts[$key] = '0';
                }
            }
        }

        $adr = implode(':', $parts);
        if (!preg_match('/^([0-9a-f]{1,4})(:[0-9a-f]{1,4})*$/i', $adr)) {
            return null; // Incorrect format - sorry.
        }

        // Normalise 0s and case.
        $parts = array_map('hexdec', $parts);
        $parts = array_map('dechex', $parts);

        $result = implode(':', $parts);

        if (!$compress) {
            return $result;
        }

        if ($result === '0:0:0:0:0:0:0:0') {
            return '::'; // All addresses.
        }

        $compressed = preg_replace('/(:0)+:0$/', '::', $result, 1);
        if ($compressed !== $result) {
            return $compressed;
        }

        $compressed = preg_replace('/^(0:){2,7}/', '::', $result, 1);
        if ($compressed !== $result) {
            return $compressed;
        }

        $compressed = preg_replace('/(:0){2,6}:/', '::', $result, 1);
        if ($compressed !== $result) {
            return $compressed;
        }

        return $result;
    }

    // First get all things that look like IPv4 addresses.
    $parts = array();
    if (!preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $addr, $parts)) {
        return null;
    }
    unset($parts[0]);

    foreach ($parts as $key => $match) {
        if ($match > 255) {
            return null;
        }
        $parts[$key] = (int)$match; // Normalise 0s.
    }

    return implode('.', $parts);
}

/**
 * get_performance_info() pairs up with init_performance_info()
 * loaded in setup.php. Returns an array with 'html' and 'txt'
 * values ready for use, and each of the individual stats provided
 * separately as well.
 *
 * @return array
 */
function get_performance_info() {
    global $CFG, $PERF, $DB, $PAGE;

    $info = array();
    $info['html'] = '';         // Holds userfriendly HTML representation.
    $info['txt']  = $_SERVER['PHP_SELF'] . ' '; // Holds log-friendly representation.

    $info['realtime'] = microtime_diff($PERF->starttime, microtime());

    $info['html'] .= '<p>'.$info['realtime']." secs</p>\n";
    $info['txt'] .= 'time: '.$info['realtime'].'s ';

    if (function_exists('memory_get_usage')) {
        $info['memory_total'] = memory_get_usage();
        $info['memory_growth'] = memory_get_usage() - $PERF->startmemory;
        $info['html'] .= '<p>RAM: '.display_size($info['memory_total'])."</p>\n";
        $info['txt']  .= 'memory_total: '.$info['memory_total'].'B (' . display_size($info['memory_total']).') memory_growth: '.
            $info['memory_growth'].'B ('.display_size($info['memory_growth']).') ';
    }

    if (function_exists('memory_get_peak_usage')) {
        $info['memory_peak'] = memory_get_peak_usage();
        $info['html'] .= '<p>RAM peak: '.display_size($info['memory_peak'])."</p>\n";
        $info['txt']  .= 'memory_peak: '.$info['memory_peak'].'B (' . display_size($info['memory_peak']).') ';
    }

    $inc = get_included_files();
    $info['includecount'] = count($inc);
    $info['html'] .= '<p>Included '.$info['includecount']." files</p>\n";
    $info['txt']  .= 'includecount: '.$info['includecount'].' ';

    if (empty($PAGE)) {
        // We can not track more performance before PAGE init.
        return $info;
    }

    if (!empty($PERF->logwrites)) {
        $info['logwrites'] = $PERF->logwrites;
        $info['html'] .= '<p>Log DB writes '.$info['logwrites']."</p>\n";
        $info['txt'] .= 'logwrites: '.$info['logwrites'].' ';
    }

    $info['dbqueries'] = $DB->perf_get_reads().'/'.($DB->perf_get_writes() - $PERF->logwrites);
    $info['html'] .= '<p>DB reads/writes: '.$info['dbqueries']."</p>\n";
    $info['txt'] .= 'db reads/writes: '.$info['dbqueries'].' ';

    $info['dbtime'] = round($DB->perf_get_queries_time(), 5);
    $info['html'] .= '<p>DB queries time: '.$info['dbtime']." secs</p>\n";
    $info['txt'] .= 'db queries time: ' . $info['dbtime'] . 's ';
    
    $info['dirroot'] = $CFG->dirroot;
    $info['html'] .= '<p>$CFG->dirroot: '.$info['dirroot']."</p>\n";
    $info['txt'] .= '$CFG->dirroot: ' . $info['dirroot'].' ';
    
    $info['wwwroot'] = $CFG->wwwroot;
    $info['html'] .= '<p>$CFG->wwwroot: '.$info['wwwroot']."</p>\n";
    $info['txt'] .= '$CFG->wwwroot: ' . $info['wwwroot'].' ';

    // if (function_exists('posix_times')) {
    //     $ptimes = posix_times();
    //     if (is_array($ptimes)) {
    //         foreach ($ptimes as $key => $val) {
    //             $info[$key] = $ptimes[$key] -  $PERF->startposixtimes[$key];
    //         }
    //         $info['html'] .= "<p>ticks: $info[ticks] | user: $info[utime] | sys: $info[stime] | cuser: $info[cutime] | csys: $info[cstime]</p> ";
    //         $info['txt'] .= "ticks: $info[ticks] user: $info[utime] sys: $info[stime] cuser: $info[cutime] csys: $info[cstime] ";
    //     }
    // }

    // Grab the load average for the last minute.
    // /proc will only work under some linux configurations
    // while uptime is there under MacOSX/Darwin and other unices.
    // if (is_readable('/proc/loadavg') && $loadavg = @file('/proc/loadavg')) {
    //     list($serverload) = explode(' ', $loadavg[0]);
    //     unset($loadavg);
    // } else if ( function_exists('is_executable') && is_executable('/usr/bin/uptime') && $loadavg = `/usr/bin/uptime` ) {
    //     if (preg_match('/load averages?: (\d+[\.,:]\d+)/', $loadavg, $matches)) {
    //         $serverload = $matches[1];
    //     } else {
    //         trigger_error('Could not parse uptime output!');
    //     }
    // }
    // if (!empty($serverload)) {
    //     $info['serverload'] = $serverload;
    //     $info['html'] .= '<p>Load average: '.$info['serverload'].'</p>';
    //     $info['txt'] .= "serverload: {$info['serverload']} ";
    // }

    // Display size of session if session started.
    // if ($si = \session\manager::get_performance_info()) {
    //     $info['sessionsize'] = $si['size'];
    //     $info['html'] .= $si['html'];
    //     $info['txt'] .= $si['txt'];
    // }

    return $info;
}

/**
 * Converts bytes into display form
 *
 * @static string $gb Localized string for size in gigabytes
 * @static string $mb Localized string for size in megabytes
 * @static string $kb Localized string for size in kilobytes
 * @static string $b Localized string for size in bytes
 * @param int $size  The size to convert to human readable form
 * @return string
 */
function display_size($size) {

    static $gb, $mb, $kb, $b;

    if (empty($gb)) {
        $gb = 'GB';
        $mb = 'MB';
        $kb = 'KB';
        $b  = 'bytes';
    }

    if ($size >= 1073741824) {
        $size = round($size / 1073741824 * 10) / 10 . $gb;
    } else if ($size >= 1048576) {
        $size = round($size / 1048576 * 10) / 10 . $mb;
    } else if ($size >= 1024) {
        $size = round($size / 1024 * 10) / 10 . $kb;
    } else {
        $size = intval($size) .' '. $b; // File sizes over 2GB can not work in 32bit PHP anyway.
    }
    return $size;
}

/**
 * Calculate the difference between two microtimes
 *
 * @param string $a The first Microtime
 * @param string $b The second Microtime
 * @return string
 */
function microtime_diff($a, $b) {
    list($adec, $asec) = explode(' ', $a);
    list($bdec, $bsec) = explode(' ', $b);
    return $bsec - $asec + $bdec - $adec;
}

/**
 * Given an array, returns an instance of object stdClass
 * with array_key => array_value = property->value.
 * NOT RECURSIVE. Only the top level of the array is converted
 * to object properties.
 */
class array_to_object {
    function __construct($array) {
        $object = new stdClass();
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $object->$key = $value;
            }
        } else {
            $object->$array = $array;
        }
        return $object;
    }
}
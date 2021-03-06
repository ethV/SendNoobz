<?php
/**
 * JAMSS - Joomla! Anti-Malware Scan Script
 * @version 1.0.7
 *
 * @author Bernard Toplak [WarpMax] <bernard@orion-web.hr>
 * @link http://www.orion-web.hr
 *
 * This script should be used for searching the infected or malware/backdoor
 * files in Joomla! installations.
 *
 * ALL COMMENTS AND SUGGESTIONS ARE WELCOME!
 *
 *
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License, version 3 (GPL-3.0)
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 */
define('SCRIPT', 'JAMSS - Joomla! Anti-Malware Scan Script');
define('VERSION', '1.0.7');
define('CREDITS', 'Development of this script was sponsored by <a href="http://www.orion-web.hr/">ORION Informatics</a>');
define('MENU_TEXT', 'Welcome to the Joomla! Anti Malware Scan Script');
define('SUPPORT_URL', 'http://github.com/btoplak/');
define('NL', '<br />');

/* PHP Version Test
Changed php version check and die message to indicate the scripts need for a minimum php version of 5.2.7 -- PhilD 04-06-2013*/

if (version_compare(PHP_VERSION, '5.2.7', '<')) {
die( 'You are using PHP Version: ' . PHP_VERSION . '
You have to deploy at least PHP 5.2.7 to be able to use this script!');
}


/* * * * * * * * * * * * * * *  SETTINGS  * * * * * * * * * * * * * * */
ini_set('max_execution_time', '0'); // supress problems with timeouts
ini_set('set_time_limit', '0'); // supress problems with timeouts
ini_set('display_errors', '0'); // show/hide errors
define('JOOMLA_SEARCH', TRUE); // should script verify valid Joomla! dir ?
                               // set to FALSE if you use it on non-Joomla site

/* WordPress */
/*
define('WPLOCALE', 'en_US');
// relative path
define('ABSPATH', './');
*/
/* * * * * * * * * * * * * *  END SETTINGS  * * * * * * * * * * * * * * */

// get Joomla version array, or NULL if no Joomla found
$joomla = whichJoomla();
// if no Joomla found and JOOMLA_SEARCH was enabled, then end the script with message
if (is_null($joomla) && JOOMLA_SEARCH)
    die('No Joomla CMS found here! Please check you have put the file into Joomla webroot folder.');

/*
 *  not scanning JS files in the early versions
 *  as it gives many false positives (eg. "eval")
 */
//$fileExt = 'php|js|txt|html|htaccess' ;
$fileExt = 'php|php3|php4|php5|phps|htm|html|htaccess|gif|js'; // file extensions

$ignoreDirs = '.|..|.DS_Store|.svn|.git'; // dirnames to ignore

$directory = '.'; // a directory to scan; default: current dir

/* * * * * * * * * * * * * *  WORDPRESS  * * * * * * * * * * * * * * */
if (defined('ABSPATH')) {
                               include(ABSPATH . 'wp-includes/version.php');
                               $apiurl = 'http://api.wordpress.org/core/checksums/1.0/?version=' . $wp_version . '&locale=' . WPLOCALE;
                               $response = file_get_contents($apiurl);
                               $checksums = json_decode($response);
                               $wp_md5 = (array)$checksums->checksums;
}
/* * * * * * * * * * * * * *  /WORDPRESS * * * * * * * * * * * * * * */

/* * * * * * * * * * * * * *  SETTINGS END  * * * * * * * * * * * * * */

if (isset($_GET['action']) && $_GET['action'] == 'autodestruct')
    deleteFile();

// counter reset
$count = 0;
$total_results = 0;

/* * * * *  Patterns Start * * * * */
$jamssStrings  = 'r0nin|m0rtix|upl0ad|r57shell|c99shell|shellbot|phpshell|void\.ru|';
$jamssStrings .= 'phpremoteview|directmail|bash_history|multiviews|cwings|vandal|bitchx|';
$jamssStrings .= 'eggdrop|guardservices|psybnc|dalnet|undernet|vulnscan|spymeta|raslan58|';
$jamssStrings .= 'Webshell|str_rot13|FilesMan|FilesTools|Web Shell|ifrm|bckdrprm|';
$jamssStrings .= 'hackmeplz|wrgggthhd|WSOsetcookie|Hmei7|Inbox Mass Mailer|HackTeam|Hackeado';
$jamssStrings .= 'Janissaries|Miyachung|ccteam|Adminer|OOO000000|$GLOBALS|findsysfolder';
$jamssStrings .= 'makeret\.ru';

// this patterns will be used if GET parameter ?deepscan=1 is set while calling jamss.php file
$jamssDeepSearchStrings = 'eval|base64_decode|base64_encode|gzdecode|gzdeflate|';
$jamssDeepSearchStrings .= 'gzuncompress|gzcompress|readgzfile|zlib_decode|zlib_encode|';
$jamssDeepSearchStrings .= 'gzfile|gzget|gzpassthru|iframe|strrev|lzw_decompress|strtr';
$jamssDeepSearchStrings .= 'exec|passthru|shell_exec|system|proc_|popen';

// the patterns to search for
$jamssPatterns = array(
    array('preg_replace\s*\(\s*[\"\']\s*(\W)(?-s).*\1[imsxADSUXJu\s]*e[imsxADSUXJu\s]*[\"\'].*\)', // [0] = RegEx search pattern
        'PHP: preg_replace Eval', // [1] = Name / Title
        '1', // [2] =  number
        'Detected preg_replace function that evaluates (executes) matched code. '
        . 'This means if PHP code is passed it will be executed.', // [3] = description
        'Part example code from http://sucuri.net/malware/backdoor-phppreg_replaceeval'), // [4] = More Information link
    array('c999*sh_surl',
        'Backdoor: PHP:C99:045',
        '2',
        'Detected the "C99? backdoor that allows attackers to manage (and '
        . 'reinfect) your site remotely. It is often used as part of a '
        . 'compromise to maintain access to the hacked sites.',
        'http://sucuri.net/malware/backdoor-phpc99045'),
    array('preg_match\s*\(\s*\"\s*/\s*bot\s*/\s*\"',
        'Backdoor: PHP:R57:01',
        '3',
        'Detected the "R57? backdoor that allows attackers to access, modify and '
        . 'reinfect your site. It is often hidden in the filesystem and hard to '
        . 'find without access to the server or logs.',
        'http://sucuri.net/malware/backdoor-phpr5701'),
    array('eval[\s/\*\#]*\(stripslashes[\s/\*\#]*\([\s/\*\#]*\$_(REQUEST|POST|GET)\s*\[\s*\\\s*[\'\"]\s*asc\s*\\\s*[\'\"]',
        'Backdoor: PHP:GENERIC:07',
        '5',
        'Detected a generic backdoor that allows attackers to upload files, delete '
        . 'files, access, modify and/or reinfect your site. It is often hidden '
        . 'in the filesystem and hard to find without access to the server or '
        . 'logs. It also includes uploadify scripts and similars that offer '
        . 'upload options without security. ',
        'http://sucuri.net/malware/backdoor-phpgeneric07'),
    /*array('https?\S{1,63}\.ru',
        'russian URL',
        '6',
        'Detected a .RU domain link, as there are many attacks leading the innocent visitors to .RU pages. Maybe i\'s valid link, but we leave it to you to check this out.',
    ),*/
    array('preg_replace\s*\(\s*[\"\'\???]\s*/\s*\.\s*\*\s*/\s*e\s*[\"\'\???]\s*,\s*[\"\'\???]\s*\\x65\\x76\\x61\\x6c',
        'Backdoor: PHP:Filesman:02',
        '7',
        'Detected the ???Filesman??? backdoor that allows attackers to access, modify '
        . 'and reinfect your site. It is often hidden in the filesystem and hard '
        . 'to find without access to the server or logs.',
        'http://sucuri.net/malware/backdoor-phpfilesman02'),
    array('(include|require)(_once)*\s*[\"\'][\w\W\s/\*]*php://input[\w\W\s/\*]*[\"\']',
        'PHP:\input include',
        '8',
        'Detected the method of reading input through PHP protocol handler in '
        . 'include/require statements.',),
    array('data:;base64',
        'data:;base64 include',
        '9',
        'Detected the method of executing base64 data in include.',),
    array('RewriteCond\s*%\{HTTP_REFERER\}',
        '.HTACCESS RewriteCond-Referer',
        '10',
        'Your .htaccess file has a conditional redirection based on "HTTP Referer".'
        . 'This means it redirects according to site/url from where your visitors '
        . 'came to your site. Such technique has been used for unwanted redirections '
        . 'after coming from Google or other search engines, so check this directive '
        . 'carefully.',),
    array('brute\s*force',
        '"Brute Force" words',
        '11',
        'Detected the "Brute Force" words mentioned in code. <u>Sometimes it\'s '
        . 'a "false positive"</u> because several developers like to mention it '
        . 'in they code, but it\'s worth double-checking if this file is untouched '
        . '(eg. compare it with one in original extension package).'),
    array('GIF89a.*[\r\n]*.*<\?php',
        'PHP file desguised as GIF image',
        '15',
        'Detected a PHP file that was most probably uploaded as an image via webform that loosely only checks file headers.',),
    array('\$ip[\w\W\s/\*]*=[\w\W\s/\*]*getenv\(["\']REMOTE_ADDR["\']\);[\w\W\s/\*]*[\r\n]\$message',
        'Probably malicious PHP script that "calls home"',
        '16',
        'Detected script variations often used to inform the attackers about found vulnerable website.',),
    array('(?:(?:eval|gzuncompress|gzinflate|base64_decode|str_rot13|strrev|strtr|preg_replace|rawurldecode|str_replace|assert|unpack|urldecode)[\s\/\*\w\W\(]*){2,};',
        'PHP: multiple encoded, most probably obfuscated code found',
        '17',
        'This pattern could be used in highly encoded, malicious code hidden under '
        . 'a loop of code obfuscation function calls. In most cases the decoded '
        . 'hacker code goes through an eval call to execute it. This pattern is '
        . 'also often used for legitimate purposes, e.g. storing configuration '
        . 'information or serialised object data. Please inspect the file manually '
        . 'and compare it with the one in the original extension or Joomla package '
        . 'to verify that this is not a false positive.',
        'Thanks to Dario Pintari?? (dario.pintaric[et}orion-web.hr for this report!'),
    array('<\s*iframe',
        'IFRAME element',
        '18',
        'Found IFRAME element in code. It\'s mostly benevolent, but often used '
        . 'for bad stuff, so please check if it\'s a valid code.'),
    array('strrev[\s/\*\#]*\([\s/\*\#]*[\'"]\s*tressa\s*[\'"]\s*\)',
        'Reversed string "assert"',
        '19',
        'Assert function name is being hidden behind strrev().'),
    array('is_writable[\s/\*\#]*\([\s/\*\#]*getcwd',
        'Is the current DIR Writable?',
        '20',
        'This could be harmless, but used in some malware'),
    array('(?:\\\\x[0-9A-Fa-f]{1,2}|\\\\[0-7]{1,3}){2,}',
        'At least two characters in hexadecimal or octal notation',
        '21',
        'Found at least two characters in hexadecimal or octal notation. It '
        . 'doesn\'t mean it is malicious, but it could be code hidding behind '
        . 'such notation.'),
    array('\$_F\s*=\s*__FILE__\s*;\s*\$_X\s*=',
        'SourceCop encoded code',
        '22',
        'Found the SourceCop encoded code. It is often used for malicious code
            hidding, so go and check the code with some online SourceCop decoders'),
    array('(?:exec|passthru|shell_exec|system|proc_|popen)[\w\W\s/\*]*\([\s/\*\#\'\"\w\W\-\_]*(?:\$_GET|\$_POST|\$_REQUEST)',
        'shell command execution from POST/GET variables',
        '23',
        'Found direct shell command execution getting variables from POST/GET,
            which is highly dangerous security flaw or a part of malicious webrootkit'),
    /*
     * This needs some extra tuning, doesn't work yet
     * 
    array('\$\w[\w\W\s/\*]*=[\w\W\s/\*]*`.*`',
        'PHP execution operator: backticks (``)',
        '24',
        'PHP execution operator found. Note that these are not single-quotes!
            PHP will attempt to execute the contents of the backticks as a shell
            command, which might indicate a part of a webrootkit'),
     * 
     */
    array('fsockopen\s*\(\s*[ \'\"](?:localhost|127\.0\.0\.1)[ \'\"]',
        'Opening socket to localhost',
        '25',
        'Found code opening socket to localhost, it\'s worth investigating more'),
    array('fsockopen\s*\(.*,\s*[ \'\"](?:25|587|465|475|2525)[ \'\"]',
        'Opening socket to known SMTP ports, possible SPAM script',
        '26',
        'Found opening socket to known SMTP ports, possible SPAM script'),
    array('(?:fopen|file|file_get_contents|readfile|popen)\s*\(\s*[ \'\"]*\s*(?:file|http[s]*|ftp[s]*|php|zlib|data|glob|phar|ssh2|rar|ogg|expect|\$POST|\$GET|\$REQUEST)',
        'Reading streams or superglobal variables with fopen wrappers present',
        '27',
        'Found functions reading data from streams/wrappers - please analyze the code'),
    array('array_(?:diff_ukey|diff_???uassoc|intersect_uassoc|udiff_uassoc|udiff_assoc|uintersect_assoc|???uintersect_???uassoc)\s*\(.*(?:\$_REQUEST|\$_POST|\$_GET).*;',
        'Callback function comming from REQUEST/POST/GET variable possible',
        '28',
        'Found possible local execution enabling-script receiving data from POST or GET requests'),
);

$jamssFileNames = array(
    'Probably an OpenFlashChart library demo file that has known input '
    . 'validation error (CVE-2009-4140)'
        => 'ofc_upload_image.php',
    'Probably an R57 shell'
        => 'r57.php',
    'PhpInfo() file? It is advisable to remove such file, as it could reveal too
        much info to potential attackers'
        => 'phpinfo.php',
    );

/* * * * *  Patterns End * * * * */


// check if DeepScan should be done
if (isset($_GET['deepscan'])) {
    $patterns = array_merge($jamssPatterns, explode('|', $jamssStrings), explode('|', $jamssDeepSearchStrings));
} else {
    $patterns = array_merge($jamssPatterns, explode('|', $jamssStrings));
}
$ext = explode('|', $fileExt);

/**
 * Get the list of the files in rootdir and all subdirs<br>
 *
 * @global string $ignoreDirs   directories to be ignored
 * @param string $dir   directory to scan for files
 * @return array    array with found files
 */
function get_filelist($dir) {
    global $ignoreDirs;
    global $wp_md5;
    $ignoreArr = explode('|', $ignoreDirs);

    $path = '';
    $toResolve = array($dir);
    while ($toResolve) {
        $thisDir = array_pop($toResolve);
        if ($dirContent = scandir($thisDir)) {
            foreach ($dirContent As $content) {
                if (!in_array($content, $ignoreArr)) { // skipping ignored dirs
                    $thisFile = "$thisDir/$content";
                    if (is_file($thisFile)) {
                        if(@$_GET['get_hash'] === 1) // if requested through URL
                            $path[$thisFile] = hash_file('sha256',$thisFile);
                        if (defined('ABSPATH')) {
                            $wprootPath = substr($thisFile, strlen(ABSPATH));
                            if (isset($wp_md5[$wprootPath]) && $wp_md5[$wprootPath] === md5_file($thisFile)) {
                                continue;
                            }
                        }
                        scan_file($thisFile);
                    } else {
                        $toResolve[] = $thisFile;
                    }
                }
            }
        }
    }

    // saving hashes to file (if requested)
    if($_GET['get_hash'] === 1)
        file_put_contents('jamss_hashes', json_encode($path));

}

/**
 * Scan given file for all malware patterns
 *
 * @global string $fileExt  file extension list to be scanned
 * @global array $patterns array of patterns to search for
 * @param string $path  path of the scanned file
 */
function scan_file($path) {
    global $ext, $patterns, $count, $total_results, $jamssFileNames;

    if (in_array(pathinfo($path, PATHINFO_EXTENSION), $ext)
            && filesize($path)/* skip empty ones */
            && !stripos($path, 'jamss.php')/* skip this file */) {

        if($malic_file_descr = array_search(pathinfo($path,PATHINFO_BASENAME), $jamssFileNames))
                echo '<hr><p><h3 class="pattern">Suspicious filename found :</h3>
                    File: <span class="file">',$path,'</span>',
                    " ---> <strong>Details:</strong>
                    <span class=\"pattern_desc\">\"$malic_file_descr\"</span></p>\n";

        if (!($content = file_get_contents($path))) {
            $error = 'Could not check '.$path;
            echo formatError($error);
        } else { // do a search for fingerprints
            foreach ($patterns As $pattern) {
                if (is_array($pattern)) { // it's a pattern
                    // RegEx modifiers: i=case-insensitive; s=dot matches also newlines; S=optimization
                    preg_match_all('#' . $pattern[0] . '#isS', $content, $found, PREG_OFFSET_CAPTURE);
                } else { // it's a string
                    preg_match_all('#' . $pattern . '#isS', $content, $found, PREG_OFFSET_CAPTURE);
                }
                $all_results = $found[0]; // remove outer array from results
                $results_count = count($all_results); // count the number of results
                $total_results += $results_count; // total results of all fingerprints

                if (!empty($all_results)) {
                    $count++;
                    if (is_array($pattern)) { // then it has some additional comments
                        echo "<hr><p><span class=\"pattern\">Pattern #$pattern[2] - $pattern[1]</span>
                            --> found $results_count occurence(s) in file <span class=\"file\">$path</span>",
                        NL,NL,
                        "<strong>Details: </strong> <span class=\"pattern_desc\">\"$pattern[3]\"</span></p>\n";
                        foreach ($all_results as $match) {
                            // output the line of malware code, but sanitize it before
                            // the offset is in $match[1]
                            echo '<span class="offset">Line #: ',calculate_line_number($match[1], $content),'</span>',
                            "<pre>... " . htmlentities(substr($content, $match[1], 200), ENT_QUOTES) . " ...</pre>\n";
                        }
                    } else { // it's a string, no comments available
                        echo "<hr><p>In file <span class=\"file\">$path</span>",
                        "-> we found $results_count occurence(s) of <span class=\"pattern\">String '$pattern'</span>", NL;
                        foreach ($all_results as $match) {
                            // output the line of malware code, but sanitize it before
                            echo '<span class="offset">Line #: ',calculate_line_number($match[1], $content),'</span>',
                            "<pre>... " . htmlentities(substr($content, $match[1], 200), ENT_QUOTES) . " ...</pre>\n";
                        }
                    }
                    echo "--> $path is a <b>", filetype($path), '</b>. It was last <b>accessed</b>: ', date(DATE_ATOM, fileatime($path)),
                            ', last <b>changed</b>: '. date(DATE_ATOM, filectime($path)),
                            ', last <b>modified</b>: ', date (DATE_ATOM, filemtime($path)), '.<br/>';
                    echo 'File permissions:', substr(sprintf('%o', fileperms($path)), -4), '<br/>';
                }
            }
            unset($content);
        }
    }
}


/**
 * Calculates the line number where pattern match was found
 *
 * @param int $offset The offset position of found pattern match
 * @param str $content The file content in string format
 * @return int Returns line number where the subject code was found
 */
function calculate_line_number($offset, $file_content) {
    list($first_part) = str_split($file_content, $offset); // fetches all the text before the match
    $line_nr = strlen($first_part) - strlen(str_replace("\n", "", $first_part)) + 1;
    return $line_nr;
}


/**
 * Function that deletes this file when finished
 * - borrowed and reworked from FPA script
 *
 */
function deleteFile() {

    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    // try to set script to 777 to make sure we have permission to delete
    chmod('jamss.php', 0777);
    unlink('jamss.php'); // Delete the file.

    /* Message and link to home page of site. */
    echo '<div id="slowScreenSplash" style="padding:20px;border: 2px solid #4D8000;background-color:#FFFAF0;border-radius: 10px;-moz-border-radius: 10px;-webkit-border-radius: 10px;margin: 0 auto; margin-top:50px;margin-bottom:20px;width:700px;position:relative;z-index:9999;top:10%;" align="center">';

    $page = ("http://$host$uri/");
    $filename = 'jamss.php';
    // Something went wrong and the script was not deleted
    if (file_exists($filename)) {
        chmod('jamss.php', 0644);
        echo "<p><font color='#FF0000' size='4'>Oops!</size></font color>";
        echo '<p><font color="#FF0000" size="3">Something went wrong with the delete process and the file </font color><font color="#000000"size="3">$filename</font color></size><font color="#FF0000"> still exists. </font color></p>';
        echo '<p><font color="#FF0000" size="3">For site security, please remove the file </font color><font color="#000000"size="3">$filename</font color></size><font color="#FF0000"> manually using your ftp program.</font color></p>';
        echo '<p>', CREDITS, '</p>';
    } else {
        echo '<p><font color="#000000" size="3">Thank You for using the JAMSS. </font color></p>';
        echo '<p>', CREDITS, '</p>';
    }
    echo '<a href="', $page, '">Go to your Home Page.</a>';

    exit;
// end delete script
}

/**
 * Find Joomla! version
 *
 * @return array Returns array with Joomla version information, or NULL if no Joomla found
 */
function whichJoomla() {
    $RELEASE = $DEV_LEVEL = $DEV_STATUS = NULL;
    $f1 = "./includes/version.php";
    $f2 = "./libraries/joomla/version.php";
    $f3 = "./libraries/cms/version/version.php";
    if (file_exists($f1)) { // Joomla 1.0 & 1.7
        $vFile = file_get_contents($f1);
    } elseif (file_exists($f2)) { // Joomla 1.5 & 1.6
        $vFile = file_get_contents($f2);
    } elseif (file_exists($f3)) { // Joomla 2.5 & 3.x
        $vFile = file_get_contents($f3);
    } else { // no Joomla found
        return NULL;
    }
    preg_match_all('|\$RELEASE\s*=.*\'(.*)\'|iS', $vFile, $RELEASE);
    preg_match_all('|\$DEV_LEVEL\s*=.*\'(.*)\'|iS', $vFile, $DEV_LEVEL);
    preg_match_all('|\$DEV_STATUS\s*=.*\'(.*)\'|iS', $vFile, $DEV_STATUS);
    $joomla['RELEASE'] = $RELEASE[1][0];
    $joomla['DEV_LEVEL'] = $DEV_LEVEL[1][0];
    $joomla['version_nr'] = $RELEASE[1][0] . '.' . $DEV_LEVEL[1][0];
    $joomla['version_text'] = $RELEASE[1][0] . '.' . $DEV_LEVEL[1][0] . ' ' . $DEV_STATUS[1][0];
    return $joomla;
}

/**
 * This function formats the HTML error output using admin template styles
 *
 * @param string $error the text for the error message
 * @return string formated HTML error
 */
function formatError($error) {
    global $joomla;
    switch ($joomla['RELEASE']) {
        case '1.0':
            $err_txt = '<div class="error"> '.$error.' </div>';
            break;

        case '1.5':
        case '1.6':
        case '1.7':
        case '2.5':
            $err_txt = '<div id="system-message-container">
                <dl id="system-message">
                <dt class="message">Error</dt>
                <dd class="message error">
                        <ul>
                                <li>'.$error.'</li>
                        </ul>
                </dd>
                </dl>
                </div>';
            break;

        case '3.0':
            $err_txt = '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">??</a>'.$error.'</div>';
            break;
    }
    return $err_txt;
}

/***** Versioned HTML/CSS setup *******/
switch ($joomla['RELEASE']) {
    case '1.0':
        $header_div1_id = 'header';
        $header_div2_id = 'joomla';
        $header_span1_class = 'version';
        $header_span2_class = 'title';
        $menu_div_id = 'menu';
        $content_div_id = 'centermain';
        $head = '
        <link type="image/x-icon" rel="shortcut icon" href="images/favicon.ico">
        <link type="text/css" rel="stylesheet" href="administrator/templates/joomla_admin/css/template_css.css">
        <link type="text/css" rel="stylesheet" href="administrator/templates/joomla_admin/css/theme.css">';
        $css = '
            pre { background-color: #F5F5F5; border-top: 1px #bbb solid; border-bottom: 1px #bbb solid; padding: 10px; }
            #footer { border-top: 1px #bbb solid; text-align: center; }';
        break;

    case '1.5':
        $header_div1_class = 'h_green';
        $header_div1_id = 'border-top';
        $header_span1_class = 'version';
        $header_span2_class = 'title';
        $menu_div_id = 'header-box';
        $content_div_id = 'element-box';
        $head = '
        <link type="image/x-icon" rel="shortcut icon" href="administrator/templates/khepri/favicon.ico">
        <link type="text/css" rel="stylesheet" href="administrator/templates/system/css/system.css">
        <link type="text/css" rel="stylesheet" href="administrator/templates/khepri/css/template.css">
        <link type="text/css" rel="stylesheet" href="administrator/templates/khepri/css/rounded.css">';
        $css = '
            pre { background-color: #F5F5F5; border-top: 1px #bbb solid; border-bottom: 1px #bbb solid; padding: 10px; }';
        break;

    case '1.6':
    case '1.7':
    case '2.5':
        $header_div1_class = 'h_blue';
        $header_div1_id = 'border-top';
        $header_span1_class = 'version';
        $header_span2_class = 'title';
        $menu_div_id = 'header-box';
        $content_div_id = 'element-box';
        $head = '
        <link type="image/vnd.microsoft.icon" rel="shortcut icon" href="administrator/templates/bluestork/favicon.ico">
        <link type="text/css" rel="stylesheet" href="administrator/templates/system/css/system.css">
        <link type="text/css" rel="stylesheet" href="administrator/templates/bluestork/css/template.css">';
        $css = '
            pre { background-color: #F5F5F5; border-top: 1px #bbb solid; border-bottom: 1px #bbb solid; padding: 10px; }';
        break;

    case '3.0':
        $head = '
        <link type="image/vnd.microsoft.icon" rel="shortcut icon" href="administrator/templates/isis/favicon.ico">
        <link type="text/css" rel="stylesheet" href="administrator/templates/isis/css/template.css">';
        $css = '
            ';
        break;
} ?>


<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta content="<?php echo SCRIPT, ' v.', VERSION; ?>" name="description">
        <meta content="<?php echo SCRIPT, ' v.', VERSION; ?>" name="generator">
        <title><?php echo SCRIPT, ' v.', VERSION; ?></title>
        <?php echo $head; ?>
        <style>
            #results { font-size: 12px; }
            .file { font-weight: bold; color: blue; }
            .pattern { font-weight: bold; color: red; }
            .pattern_desc { font-style: italic; color: blueviolet; }
            .offset { font-weight: bold; font-style: italic; }
            .end { font-size: 18px; font-weight: bold; color: cadetblue; }
            #border-top div div { background-image:none !important }
            #border-top .title { padding-left: 20px; }
            #header-box { padding: 0.35em 1em; }
            .h_blue .version { float: right; margin: 25px 20px 0; color: #fff; }
            /* all */
            #centermain, #element-box { padding: 30px !important; }
            /* 1.0 */
            .title a { color: #fff }
            #joomla { height: 38px; color: #fff; }
            #joomla .title { font-size: 1.364em; font-weight: bold; line-height: 38px; padding-left: 14px; }
            #joomla .version { float: right; margin: 20px 60px 0; }
            #menu { background-color: #F1F3F5; padding: 0.35em 1em; }
            /* 3.0 */
            .page-title a { color: #fff }
            .brand { height: 20px }
            #credits { float: right; margin-top: 5px; }
            <?php echo $css; ?>
        </style>
    </head>
    <body id="minwidth-body" class="admin com_cpanel">

      <?php if ($joomla['RELEASE'] == '3.0') { // header for 3.0  ?>

            <nav class="navbar navbar-inverse navbar-fixed-top">
                <div class="navbar-inner">
                    <div class="container-fluid">
                        <div class="brand">Joomla! version: <?php echo $joomla['version_text']; ?> </div>
                        <div id="credits"><?php echo CREDITS; ?> </div>
                    </div>
                </div>
            </nav>
            <header class="header">
                <div class="container-fluid">
                    <div class="row-fluid">
                        <div class="span10">
                            <h1 class="page-title"><a href="<?php echo SUPPORT_URL; ?>"> <?php echo SCRIPT, ' - v.', VERSION; ?> </a></h1>
                        </div>
                    </div>
                </div>
            </header>
            <div class="subhead"></div>
            <div style="margin-bottom: 20px"></div>
            <div class="container-fluid container-main">

      <?php } else { // J! 1.0 - 2.5 header ?>

                <div id="wrapper">
                    <div <?php if ($header_div1_id) echo 'id="', $header_div1_id, '"'; ?> <?php if ($header_div1_class) echo 'class="', $header_div1_class, '"'; ?> >
                        <div <?php if ($header_div2_id) echo 'id="', $header_div2_id, '"'; ?> <?php if ($header_div2_class) echo 'class="', $header_div2_class, '"'; ?> >
                            <div>
                                <span <?php if ($header_span1_class) echo 'class="', $header_span1_class, '"'; ?> >Joomla! version: <?php echo $joomla['version_text']; ?></span>
                                <span <?php if ($header_span2_class) echo 'class="', $header_span2_class, '"'; ?> ><a href="<?php echo SUPPORT_URL; ?>"><?php echo SCRIPT, ' - v.', VERSION; ?></a></span>
                            </div>
                        </div>
                    </div>
                    <div <?php if ($menu_div_id) echo 'id="', $menu_div_id, '"'; ?> ><?php echo MENU_TEXT; ?></div>
                    <div id="content-box">
                        <div class="border">
                            <div class="padding">
                                <div <?php if ($content_div_id) echo 'id="', $content_div_id, '"'; ?> >

      <?php  } // **** END of header

                                $before = microtime(true); // START benchmark
                                //var_dump($joomla);
                                // do the scan
                                ?>
                                    <div id="results">
                                        <h2>Here are the suspicious parts of code found in this scan process :</h2>
                                <?php
                                    get_filelist($directory);

                                ?>
                                    </div>
                                <?php
                                $after = microtime(true); // STOP benchmark
                                echo 'We found <b>', $total_results, ' suspicious malware code spots</b> in <u>', $count,' different files</u>!<br/>
                                    Please analyze the results and interpret them according to README file.<br>';
                                echo 'Scanning time was ', ($after - $before), ' sec! <br>';
                                ?>
                                <hr />

                                It is advisable to delete this script after using it. You can do it by <a href="jamss.php?action=autodestruct">clicking here</a>.<br/>
                                <br/>
                                Thank you for using JAMSS!

      <?php if ($joomla['RELEASE'] == '3.0') { // footer for 3.0 ?>

                                </div>
                                <div id="status" class="navbar navbar-fixed-bottom">
                                    <p class="copyright">The JAMSS script is NOT developed, approved, tested or verified by Joomla team, forum team, security team or anyone else!</p>
                                </div>

      <?php } else { // footer for 1.0 - 2.5  ?>

                            </div>
                        </div>
                    </div>
                </div>
                <div id="border-bottom"><div><div></div></div></div>
                <div id="footer" class="footer">
                    <p class="copyright">The JAMSS script is NOT developed, approved, tested or verified by Joomla team, forum team, security team or anyone else!<br /><br /><?php echo CREDITS; ?></p>
                </div>
            </div>

      <?php  } // **** END of footer  ?>

    </body>
</html>
<?php

namespace Sbp;

include_once __DIR__.'/functions.php';

class Sbp
{
    const COMMENT = '/* Generated By SBP */';
    const VALIDNAME = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
    const NUMBER = '(?:0[xbXB])?[0-9]*\.?[0-9]+(?:[eE](?:0[xbXB])?[0-9]*\.?[0-9]+)?';
    const VALIDVAR = '(?<!\$)\$+[^\$\n\r=]+([\[\{]((?>[^\[\{\]\}]+)|(?-2))*[\]\}])?(?![a-zA-Z0-9_\x7f-\xff\$\[\{])';
    const BRACES = '(\{((?>[^\{\}]+)|(?-2))*\})';
    const BRAKETS = '(\[((?>[^\[\]]+)|(?-2))*\])';
    const PARENTHESES = '(\(((?>[^\(\)]+)|(?-2))*\))';
    const CONSTNAME = '[A-Z_]+';
    const SUBST = '÷';
    const COMP = '`';
    const VALUE = 'µ';
    const CHAINER = '¤';
    const COMMENTS = '(?:\/\/|\#).*(?=\n)|\/\*(?:.|\n)*\*\/';
    const OPERATORS = '\|\||\&\&|or|and|xor|is|not|<>|lt|gt|<=|>=|\!==|===|\?\:';
    const PHP_WORDS = 'true|false|null|echo|print|static|yield|var|exit|as|case|default|clone|endswtch|endwhile|endfor|endforeach|callable|endif|enddeclare|final|finally|label|goto|const|global|namespace|instanceof|new|throw|include|require|include_once|require_once|use|exit|continue|return|break|extends|implements|abstract|public|protected|private|function|interface';
    const BLOKCS = 'if|else|elseif|try|catch|function|class|trait|switch|while|for|foreach|do';
    const ALLOW_ALONE_CUSTOM_OPERATOR = 'if|elseif|foreach|for|while|or|and|xor';
    const MUST_CLOSE_BLOKCS = 'try|catch|function|class|trait|switch|interface';
    const IF_BLOKCS = 'if|elseif|catch|switch|while|for|foreach';
    const START = '((?:^|[\n;\{\}])(?:(?:\/\/|\#).*(?=\n)|\/\*(?:.|\n)*\*\/\s*)*\s*)';
    const ABSTRACT_SHORTCUTS = 'abstract|abst|abs|a';
    const BENCHMARK_END = -1;

    const SAME_DIR = 0x01;

    protected static $prod = false;
    protected static $destination = 0x01;
    protected static $callbackWriteIn = null;
    protected static $lastParsedFile = null;
    protected static $plugins = array();

    public static function init()
    {
        static $coreLoaded = false;
        if (!$coreLoaded) {
            $coreLoaded = true;
            static::addPlugin('Sbp\Plugins\Core\PhpOpenerTag');
            static::addPlugin('Sbp\Plugins\Core\ReplaceStrings');
            static::addPlugin('Sbp\Plugins\Core\PHPUnit');
            static::addPlugin('Sbp\Plugins\Core\Constants');
            static::addPlugin('Sbp\Plugins\Core\ClassName');
            static::addPlugin('Sbp\Plugins\Core\Functions');
            static::addPlugin('Sbp\Plugins\Core\This');
            static::addPlugin('Sbp\Plugins\Core\Attributes');
            static::addPlugin('Sbp\Plugins\Core\Methods');
            static::addPlugin('Sbp\Plugins\Core\SwitchShortCuts');
            static::addPlugin('Sbp\Plugins\Core\Summons');
            static::addPlugin('Sbp\Plugins\Core\Comparisons');
            static::addPlugin('Sbp\Plugins\Core\ArrayShortSyntax');
            static::addPlugin('Sbp\Plugins\Core\Chainer');
            static::addPlugin('Sbp\Plugins\Core\Indentation');
            static::addPlugin('Sbp\Plugins\Core\Compiler');
            static::addPlugin('Sbp\Plugins\Core\DefinedFunction');
            static::addPlugin('Sbp\Plugins\Core\SemiColon');
            static::addPlugin('Sbp\Plugins\Core\IfBlock');
            static::addPlugin('Sbp\Plugins\Core\CompileFunctions');
            static::addPlugin('Sbp\Plugins\Core\CompileStrings');
            static::addPlugin('Sbp\Plugins\Core\UniqueParentheses');
            static::addPlugin('Sbp\Plugins\Core\Regex');
            static::addPlugin('Sbp\Plugins\Core\CustomOperators');
        }
    }

    public static function getLastParsedFile()
    {
        return static::$lastParsedFile;
    }

    public static function getValidStringSurrogates()
    {
        return static::validSubst('(?:'.implode('|', $GLOBALS['quotedStrings']).')');
    }

    public static function getValidComments()
    {
        return static::validSubst('(?:'.implode('|', $GLOBALS['commentStrings']).')');
    }

    public static function getHtmlCodes()
    {
        return static::validSubst('(?:'.implode('|', $GLOBALS['htmlCodes']).')');
    }

    public static function prod($on = true)
    {
        static::$prod = (bool) $on;
    }

    public static function dev($off = true)
    {
        static::$prod = !$off;
    }

    public static function addPlugin($plugin, $from = null, $to = null)
    {
        static::init();

        if (is_null($from)) {
            if (!class_exists($plugin)) {
                throw new SbpException('Invalid arguments, if the second argument is not specified, the plugin name must match a existing class and the class '.$plugin.' was not found.');
            }
            static::$plugins[$plugin] = null;
            $methods = get_class_methods($plugin);
            foreach ($methods as $method) {
                if (substr($method, 0, 2) !== '__') {
                    $method = $plugin.'::'.$method;
                    if (is_callable($method)) {
                        static::$plugins[$method] = $method;
                    }
                }
            }
            $vars = get_class_vars($plugin);
            foreach ($vars as $var => $value) {
                if (is_array($value)) {
                    if (count($value) === 2 && key($value) === 0) {
                        $value = array($value[0] => $value[1]);
                    }
                    foreach ($value as &$to) {
                        if (is_string($to) && substr($to, 0, 4) === '::__') {
                            $to = $plugin.$to;
                        }
                    }
                    static::$plugins[$plugin.'::$'.$var] = $value;
                }
            }

            return;
        }
        if (!is_null($to)) {
            if (is_array($from) || is_object($from)) {
                throw new SbpException('Invalid arguments, if the second argument is an array or an object, do not specified a third argument.');
            }
            $from = array($from => $to);
        }
        static::$plugins[$plugin] = $from;
    }

    public static function removePlugin($plugin)
    {
        unset(static::$plugins[$plugin]);
    }

    public static function hasPlugin($plugin)
    {
        return array_key_exists($plugin, static::$plugins);
    }

    public static function benchmarkEnd()
    {
        return static::benchmark(static::BENCHMARK_END);
    }

    protected static function getBenchmarkHtml(&$list)
    {
        $previous = null;
        $times = array_keys($list);
        $len = max(0, min(2, max(array_map(function ($key) {
            $key = explode('.', $key);

            return strlen(end($key)) - 3;
        }, $times))));
        $list[strval(microtime(true))] = 'End benchmark';
        $ul = '';
        foreach ($list as $time => $title) {
            $ul .= '<li>'.(is_null($previous) ? '' : '<b>'.number_format(($time - $previous) * 1000, $len).'ms</b>').$title.'</li>';
            $previous = $time;
        }

        $contents = ob_get_contents();
        ob_end_clean();

        return '<!doctype html>
            <html lang="en">
                <head>
                    <meta charset="UTF-8" />
                    <title>SBP - Benchmark</title>
                    <style type="text/css">
                    body
                    {
                        font-family: sans-serif;
                    }
                    li
                    {
                        margin: 40px;
                        position: relative;
                    }
                    li b
                    {
                        font-weight: bold;
                        position: absolute;
                        top: -30px;
                        left: -8px;
                    }
                    </style>
                </head>
                <body>
                    <h1>Benckmark</h1>
                    <ul>'.$ul.'</ul>
                    <p>All: <b>'.number_format((end($times) - reset($times)) * 1000, $len, ',', ' ').'ms</b></p>
                    <h1>Code source</h1>
                    <pre>'.htmlspecialchars($contents).'</pre>
                </body>
            </html>';
    }

    protected static function recordBenchmark(&$list, $title)
    {
        if ($title === static::BENCHMARK_END) {
            return static::getBenchmarkHtml($list);
        }
        $time = strval(microtime(true));
        if (!empty($title)) {
            $list = array($time => 'Start benchmark');
            ob_start();

            return;
        }
        if (is_array($list)) {
            $list[$time] = $title;
        }
    }

    public static function benchmark($title = '')
    {
        static $list = null;

        return static::recordBenchmark($list, $title);
    }

    public static function writeIn($directory = null, $callback = null)
    {
        if (is_null($directory)) {
            $directory = static::SAME_DIR;
        }
        if ($directory !== static::SAME_DIR) {
            $directory = rtrim($directory, '/\\');
            if (!file_exists($directory)) {
                throw new SbpException($directory.' : path not found');
            }
            if (!is_writable($directory)) {
                throw new SbpException($directory.' : persmission denied');
            }
            $directory .= DIRECTORY_SEPARATOR;
        }
        static::$destination = $directory;
        if (!is_null($callback)) {
            if (!is_callable($callback)) {
                throw new SbpException('Invalid callback');
            }
            static::$callbackWriteIn = $callback;
        }
    }

    public static function isSbp($file)
    {
        return
            strpos($file, $k = ' '.static::COMMENT) !== false ||
            (
                @file_exists($file) &&
                strpos(file_get_contents($file), $k) !== false
            );
    }

    public static function container($container, $file, $content = null, $basename = null, $name = null)
    {
        $content = file_get_contents($file);
        if (is_null($basename)) {
            $basename = basename($file);
        }
        if (is_null($name)) {
            $name = preg_replace('#\..+$#', '', $basename);
        }
        if (is_null($container)) {
            $container = preg_replace('#([/\\\\])(?:[^/\\\\]+)(\..+?)$#', '$1$2.container', realpath($file));
            $container = file_exists($container) ? file_get_contents($container) : '{content}';
        }
        $camelCase = preg_replace_callback('#[-_]([a-z])#', function ($match) { return strtoupper($match[1]); }, $name);
        $replace = array(
            '{file}' => $file,
            '{basename}' => $basename,
            '{name}' => $name,
            '{camelCase}' => $camelCase,
            '{CamelCase}' => ucfirst($camelCase),
        );
        $container = preg_replace_callback('#(\t*){content}#', function ($match) use ($content) {
            return $match[1].str_replace("\n", "\n".$match[1], $content);
        }, $container);

        return str_replace(array_keys($replace), array_values($replace), $container);
    }

    public static function parseWithContainer($container, $file, $content = null, $basename = null, $name = null)
    {
        $content = static::container($container, $file, '/*sbp-container-end*/'.$content, $basename, $name);
        $content = static::parse($content);
        $content = explode('/*sbp-container-end*/', $content, 2);
        $content[0] = strtr($content[0], "\r\n", '  ');

        return implode('', $content);
    }

    public static function execute($file, $container = null)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sbp-exe');
        file_put_contents($tmp, static::parseWithContainer($container, $file));

        $result = include $tmp;
        unlink($tmp);

        return $result;
    }

    public static function replaceString($match)
    {
        if (is_array($match)) {
            $match = $match[0];
        }
        $id = count($GLOBALS['replaceStrings']);
        $GLOBALS['replaceStrings'][$id] = $match;
        if (in_array(substr($match, 0, 1), array('/', '#'))) {
            $GLOBALS['commentStrings'][] = $id;
        } elseif (strpos($match, '?') === 0) {
            $GLOBALS['htmlCodes'][] = $id;
        } else {
            $GLOBALS['quotedStrings'][] = $id;
        }

        return static::COMP.static::SUBST.$id.static::SUBST.static::COMP;
    }

    protected static function validSubst($motif = '[0-9]+')
    {
        if ($motif === '(?:)') {
            $motif = '(?:[^\S\s])';
        }

        return preg_quote(static::COMP.static::SUBST).$motif.preg_quote(static::SUBST.static::COMP);
    }

    public static function stringRegex()
    {
        $antislash = preg_quote('\\');

        return '([\'"]).*(?<!'.$antislash.')(?:'.$antislash.$antislash.')*\\1';
    }

    public static function fileMatchnigLetter($file)
    {
        if (fileowner($file) === getmyuid()) {
            return 'u';
        }
        if (filegroup($file) === getmygid()) {
            return 'g';
        }

        return 'o';
    }

    public static function fileParse($from, $to = null)
    {
        if (is_null($to)) {
            $to = $from;
        }
        if (!is_readable($from)) {
            throw new SbpException($from.' is not readable, try :\nchmod '.static::fileMatchnigLetter($from).'+r '.$from, 1);
        }
        if (!is_writable($dir = dirname($to))) {
            throw new SbpException($dir.' is not writable, try :\nchmod '.static::fileMatchnigLetter($dir).'+w '.$dir, 1);
        }
        static::$lastParsedFile = $from;
        $writed = file_put_contents($to, static::parse(file_get_contents($from)));
        static::$lastParsedFile = null;

        return $writed;
    }

    public static function phpFile($file)
    {
        $callback = is_null(static::$callbackWriteIn)
            ? 'sha1'
            : static::$callbackWriteIn;

        return static::$destination === static::SAME_DIR
            ? $file.'.php'
            : static::$destination.$callback($file).'.php';
    }

    public static function fileExists($file, &$phpFile = null)
    {
        $file = preg_replace('#(\.sbp)?(\.php)?$#', '', $file);
        $sbpFile = $file.'.sbp.php';
        $callback = is_null(static::$callbackWriteIn)
            ? 'sha1'
            : static::$callbackWriteIn;

        $phpFile = static::phpFile($file);
        if (!file_exists($phpFile)) {
            if (file_exists($sbpFile)) {
                static::fileParse($sbpFile, $phpFile);

                return true;
            }
        } else {
            if (file_exists($sbpFile) && filemtime($sbpFile) > filemtime($phpFile)) {
                static::fileParse($sbpFile, $phpFile);
            }

            return true;
        }

        return false;
    }

    public static function sbpFromFile($file)
    {
        if (preg_match('#/*:(.+):*/#U', file_get_contents($file), $match)) {
            return $match[1];
        }
    }

    public static function includeFile($file)
    {
        if (static::$prod) {
            return include static::phpFile(preg_replace('#(\.sbp)?(\.php)?$#', '', $file));
        }
        if (!static::fileExists($file, $phpFile)) {
            throw new SbpException($file.' not found', 1);

            return false;
        }

        return include $phpFile;
    }

    public static function includeOnceFile($file)
    {
        if (static::$prod) {
            return include_once static::phpFile(preg_replace('#(\.sbp)?(\.php)?$#', '', $file));
        }
        if (!static::fileExists($file, $phpFile)) {
            throw new SbpException($file.' not found', 1);

            return false;
        }

        return include_once $phpFile;
    }

    public static function replace($content, $replace)
    {
        if (is_array($replace) && count($replace) === 2 && key($replace) === 0) {
            $replace = array($replace[0] => $replace[1]);
        }

        foreach ($replace as $search => $replace) {
            $catched = false;
            try {
                $content = (is_callable($replace)
                    ? preg_replace_callback($search, function ($matches) use ($replace) {
                        $result = call_user_func($replace, $matches, __CLASS__);

                        return is_array($result) ? static::replace($content, $result) : $result;
                    }, $content)
                    : (substr($search, 0, 1) === '#'
                        ? preg_replace($search, $replace, $content)
                        : str_replace($search, $replace, $content)
                    )
                );
            } catch (\Exception $e) {
                $catched = true;
                throw new SbpException('Replacement error: \''.$e->getMessage()."' in:\n".$search."\nwith:\n".var_export($replace, true), 1, $e);
            }
            if (!$catched && preg_last_error()) {
                throw new SbpException('PREG REGEX ERROR: '.preg_last_error()." in:\n".$search."\nwith:\n".var_export($replace, true), 1);
            }
        }

        return $content;
    }

    public static function arrayShortSyntax($match)
    {
        return 'array('.
            preg_replace('#,(\s*)$#', '$1', preg_replace('#^([\t ]*)('.static::VALIDNAME.')([\t ]*=)(.*[^,]),?(?=[\r\n]|$)#mU', '$1 \'$2\'$3>$4,', $match[1])).
        ')';
    }

    public static function replaceStrings($content)
    {
        foreach ($GLOBALS['replaceStrings'] as $id => $string) {
            $content = str_replace(static::COMP.static::SUBST.$id.static::SUBST.static::COMP, $string, $content);
        }

        return $content;
    }

    public static function includeString($string)
    {
        return static::replaceString(var_export(static::replaceStrings(trim($string)), true));
    }

    private static function loadPlugins($content)
    {
        foreach (static::$plugins as $name => $replace) {
            if (is_null($replace)) {
                continue;
            }
            if (is_string($replace) && !is_callable($replace)) {
                throw new SbpException($replace.' is not callable.', 1);
            }
            $pluginResult = is_array($replace)
                ? static::replace($content, $replace)
                : (is_callable($replace) || is_string($replace)
                    ? call_user_func($replace, $content, get_called_class())
                    : static::replace($content, (array) $replace)
                );
            $content = is_array($pluginResult)
                ? static::replace($content, $pluginResult)
                : $pluginResult;
        }

        return $content;
    }

    public static function parse($content)
    {
        static::init();

        $GLOBALS['replaceStrings'] = array();
        $GLOBALS['htmlCodes'] = array();
        $GLOBALS['quotedStrings'] = array();
        $GLOBALS['commentStrings'] = array();

        return static::loadPlugins($content);
    }
}

<?php

namespace sbp
{

	class sbpException extends \Exception {}

	class sbp
	{
		const COMMENT = '/* Generated By SBP */';
		const VALIDNAME = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
		const VALIDVAR = '(?<!\$)\$+[^\$\n\r]+([\[\{]((?>[^\[\{\]\}]+)|(?-2))*[\]\}])?(?![a-zA-Z0-9_\x7f-\xff\$\[\{])';
		const CONSTNAME = '[A-Z_]+';
		const SUBST = '÷';
		const COMP = '`';
		const COMMENTS = '\/\/.*(?=\n)|\/\*(?:.|\n)*\*\/';
		const OPERATORS = '\|\||\&\&|or|and|xor|is|not|<>|lt|gt|<=|>=|\!==|===|\?\:';
		const PHP_WORDS = 'true|false|null|echo|exit|include|require|include_once|require_once|use|exit|continue|return|break';
		const BLOKCS = 'if|else|elseif|try|catch|function|class|trait|switch|while|for|foreach|do';
		const MUST_CLOSE_BLOKCS = 'try|catch|function|class|trait|switch';
		const IF_BLOKCS = 'if|elseif|catch|switch|while|for|foreach';
		const START = '((?:^|[\n;\{\}])(?:\/\/.*(?=\n)|\/\*(?:.|\n)*\*\/\s*)*\s*)';
		const ABSTRACT_SHORTCUTS = 'abstract|abst|abs|a';

		const SAME_DIR = 0x01;

		static protected $prod = false;
		static protected $destination = self::SAME_DIR;
		static protected $callbackWriteIn = null;
		static protected $lastParsedFile = null;

		static public function prod($on = true)
		{
			static::$prod = !!$on;
		}

		static public function dev($off = true)
		{
			static::$prod = !$off;
		}

		static public function writeIn($directory = self::SAME_DIR, $callback = null)
		{
			if($directory !== self::SAME_DIR)
			{
				$directory = rtrim($directory, '/\\');
				if( ! file_exists($directory))
				{
					throw new sbpException($directory . " : path not found");
				}
				if( ! is_writable($directory))
				{
					throw new sbpException($directory . " : persmission denied");
				}
				$directory .= DIRECTORY_SEPARATOR;
			}
			self::$destination = $directory;
			if( ! is_null($callback))
			{
				if( ! is_callable($callback))
				{
					throw new sbpException("Invalid callback");
				}
				self::$callbackWriteIn = $callback;
			}
		}

		static public function isSbp($file)
		{
			return (
				strpos($file, $k = ' '.self::COMMENT) !== false ||
				(
					substr($file, 0, 1) === '/' &&
					@file_exists($file) &&
					strpos(file_get_contents($file), $k) !== false
				)
			);
		}

		static public function parseClass($match)
		{
			list($all, $start, $class, $extend, $implement, $end) = $match;
			$class = trim($class);
			if(in_array(substr($all, 0, 1), str_split(',(+-/*&|'))
			|| in_array($class, array_merge(
				array('else', 'try', 'default:', 'echo', 'print', 'exit', 'continue', 'break', 'return', 'do'),
				explode('|', self::PHP_WORDS)
			)))
			{
				return $all;
			}
			$className = preg_replace('#^(?:'.self::ABSTRACT_SHORTCUTS.')\s+#', '', $class, -1, $isAbstract);
			$codeLine = $start.($isAbstract ? 'abstract ' : '').'class '.$className.
				(empty($extend) ? '' : ' extends '.trim($extend)).
				(empty($implement) ? '' : ' implements '.trim($implement)).
				' '.trim($end);
			return $codeLine.str_repeat("\n", substr_count($all, "\n") - substr_count($codeLine, "\n"));
		}

		static private function findLastBlock(&$line, $block = array())
		{
			$pos = false;
			if(empty($block))
			{
				$block = explode('|', self::BLOKCS);
			}
			if(!is_array($block))
			{
				$block = array($block);
			}
			foreach($block as $word)
			{
				if(preg_match('#(?<![a-zA-Z0-9$_])'.$word.'(?![a-zA-Z0-9_])#s', $line, $match, PREG_OFFSET_CAPTURE))
				{
					$p = $match[0][1] + 1;
					if($pos === false || $p > $pos)
					{
						$pos = $p;
					}
				}
			}
			return $pos;
		}

		static public function isBlock(&$line, &$grouped, $iRead = 0)
		{
			if(substr(rtrim($line), -1) === ';')
			{
				return false;
			}
			$find = self::findLastBlock($line);
			$pos = $find ?: 0;
			$ouvre = substr_count($line, '(', $pos);
			$ferme = substr_count($line, ')', $pos);
			if($ouvre > $ferme)
			{
				return false;
			}
			if($ouvre < $ferme)
			{
				$c = $ferme - $ouvre;
				$content = ' '.implode("\n", array_slice($grouped, 0, $iRead));
				while($c !== 0)
				{
					$ouvre = strrpos($content, '(')?: 0;
					$ferme = strrpos($content, ')')?: 0;
					if($ouvre === 0 && $ferme === 0)
					{
						return false;
					}
					if($ouvre > $ferme)
					{
						$c--;
						$content = substr($content, 0, $ouvre);
					}
					else
					{
						$c++;
						$content = substr($content, 0, $ferme);
					}
				}
				$content = substr($content, 1);
				$find = self::findLastBlock($content);
				$pos = $find ?: 0;
				return $find !== false && substr_count($content, '{', $pos) === 0;
			}
			return $find !== false && substr_count($line, '{', $pos) === 0;
		}

		static public function contentTab($match)
		{
			return $match[1].str_replace("\n", "\n".$match[1], $GLOBALS['sbpContentTab']);
		}

		static public function container($container, $file, $content, $basename = null, $name = null)
		{
			$basename = $basename ?: basename($file);
			$name = $name ?: preg_replace('#\..+$#', '', $basename);
			$camelCase = preg_replace_callback('#[-_]([a-z])#', function ($match) { return strtoupper($match[1]); }, $name);
			$replace = array(
				'{file}' => $file,
				'{basename}' => $basename,
				'{name}' => $name,
				'{camelCase}' => $camelCase,
				'{CamelCase}' => ucfirst($camelCase)
			);
			$GLOBALS['sbpContentTab'] = $content;
			$container = preg_replace_callback('#(\t*){content}#', array(get_class(), 'contentTab'), $container);
			unset($GLOBALS['sbpContentTab']);
			return str_replace(array_keys($replace), array_values($replace), $container);
		}

		static public function parseWithContainer($container, $file, $content, $basename = null, $name = null)
		{
			$content=self::container($container,$file,'/*sbp-container-end*/'.$content,$fin);
			$content=self::parse($content);
			$content=explode('/*sbp-container-end*/', $content, 2);
			$content[0]=strtr($content[0],"\r\n","  ");
			return implode('',$content);
		}

		static public function replaceString($match)
		{
			if(is_array($match))
			{
				$match = $match[0];
			}
			$id = count($GLOBALS['replaceStrings']);
			$GLOBALS['replaceStrings'][$id] = $match;
			if(strpos($match, '/') === 0)
			{
				$GLOBALS['commentStrings'][] = $id;
			}
			elseif(strpos($match, '?') === 0)
			{
				$GLOBALS['htmlCodes'][] = $id;
			}
			else
			{
				$GLOBALS['quotedStrings'][] = $id;
			}
			return self::COMP.self::SUBST.$id.self::SUBST.self::COMP;
		}

		static protected function stringRegex()
		{
			$antislash = preg_quote('\\');
			return '([\'"]).*(?<!'.$antislash.')(?:'.$antislash.$antislash.')*\\1';
		}

		static protected function validSubst($motif = '[0-9]+')
		{
			return preg_quote(self::COMP.self::SUBST).$motif.preg_quote(self::SUBST.self::COMP);
		}

		static public function fileMatchnigLetter($file)
		{
			if(fileowner($file) === getmyuid())
			{
				return 'u';
			}
			if(filegroup($file) === getmygid())
			{
				return 'g';
			}
			return 'o';
		}

		static public function fileParse($from, $to = null)
		{
			if(is_null($to))
			{
				$to = $from;
			}
			if(!is_readable($from))
			{ 
				throw new sbpException($from." is not readable, try :\nchmod ".static::fileMatchnigLetter($from)."+r ".$from, 1);
				return false;
			}
			if(!is_writable($dir = dirname($to)))
			{ 
				throw new sbpException($dir." is not writable, try :\nchmod ".static::fileMatchnigLetter($dir)."+w ".$dir, 1);
				return false;
			}
			static::$lastParsedFile = $from;
			$writed = file_put_contents($to, self::parse(file_get_contents($from)));
			static::$lastParsedFile = null;
			return $writed;
		}

		static public function phpFile($file)
		{
			$callback = (is_null(static::$callbackWriteIn) ?
				'sha1' :
				static::$callbackWriteIn
			);
			return (self::$destination === self::SAME_DIR ?
				$file.'.php' :
				self::$destination.$callback($file).'.php'
			);
		}

		static public function fileExists($file, &$phpFile = null)
		{
			$file = preg_replace('#(\.sbp)?(\.php)?$#', '', $file);
			$sbpFile = $file.'.sbp.php';
			$callback = (is_null(static::$callbackWriteIn) ?
				'sha1' :
				static::$callbackWriteIn
			);
			$phpFile = static::phpFile($file);
			if(!file_exists($phpFile))
			{
				if(file_exists($sbpFile))
				{
					self::fileParse($sbpFile, $phpFile);
					return true;
				}
			}
			else
			{
				if(file_exists($sbpFile) && filemtime($sbpFile) > filemtime($phpFile))
				{
					self::fileParse($sbpFile, $phpFile);
				}
				return true;
			}
			return false;
		}

		static public function sbpFromFile($file)
		{
			if(preg_match('#/*:(.+):*/#U', file_get_contents($file), $match))
			{
				return $match[1];
			}
		}

		static public function includeFile($file)
		{
			if(static::$prod)
			{
				return include(static::phpFile(preg_replace('#(\.sbp)?(\.php)?$#', '', $file)));
			}
			if(!static::fileExists($file, $phpFile))
			{
				throw new sbpException($file." not found", 1);
				return false;
			}
			return include($phpFile);
		}

		static public function includeOnceFile($file)
		{
			if(static::$prod)
			{
				return include_once(static::phpFile(preg_replace('#(\.sbp)?(\.php)?$#', '', $file)));
			}
			if(!static::fileExists($file, $phpFile))
			{
				throw new sbpException($file." not found", 1);
				return false;
			}
			return include_once($phpFile);
		}

		static protected function replace($content, $replace)
		{
			foreach($replace as $search => $replace)
			{
				$content = (is_callable($replace) ?
					preg_replace_callback($search, $replace, $content) :
					(substr($search, 0, 1) === '#' ?
						preg_replace($search, $replace, $content) :
						str_replace($search, $replace, $content)
					)
				);
			}
			return $content;
		}

		static public function arrayShortSyntax($match)
		{
			return 'array(' .
				preg_replace('#,(\s*)$#', '$1', preg_replace('#^([\t ]*)('.self::VALIDNAME.')([\t ]*=)(.*[^,]),?(?=[\r\n]|$)#mU', '$1 \'$2\'$3>$4,', $match[1])) .
			')';
		}

		static public function replaceStrings($content)
		{
			foreach($GLOBALS['replaceStrings'] as $id => $string)
			{
				$content = str_replace(self::COMP.self::SUBST.$id.self::SUBST.self::COMP, $string, $content);
			}
			return $content;
		}

		static public function includeString($string)
		{
			return static::replaceString(var_export(static::replaceStrings(trim($string)), true));
		}

		static public function parse($content)
		{
			$GLOBALS['replaceStrings'] = array();
			$GLOBALS['htmlCodes'] = array();
			$GLOBALS['quotedStrings'] = array();
			$GLOBALS['commentStrings'] = array();

			$content = static::replace(

				/*****************************************/
				/* Mark the compiled file with a comment */
				/*****************************************/
				'<?php '.self::COMMENT.(is_null(static::$lastParsedFile) ? '' : '/*:'.static::$lastParsedFile.':*/').' ?>'.
				$content, array(


				/***************************/
				/* Complete PHP shrot-tags */
				/***************************/
				'#<\?(?!php)#'
					=> '<?php',


				/***************************/
				/* Remove useless PHP tags */
				/***************************/
				'#\?><\?php#'
					=> '',


				/*******************************/
				/* Escape the escape-character */
				/*******************************/
				self::SUBST
					=> self::SUBST.self::SUBST,


				/*************************************************************/
				/* Save the comments, quoted string and HTML out of PHP tags */
				/*************************************************************/
				'#'.self::COMMENTS.'|'.self::stringRegex().'|\?>.+<\?php#sU'
					=> array(get_class(), 'replaceString'),


				/*************************************/
				/* should key-word fo PHPUnit assert */
				/*************************************/
				'#(?<=\s|^)should\s+not(?=\s)$#mU'
					=> 'should not',

				'#^(\s*)(\S.*\s)?should\snot\s(.*[^;]);*\s*$#mU'
					=> function ($match)
					{
						list($all, $spaces, $before, $after) = $match;
						return $spaces . '>assertFalse(' .
							$before .
							preg_replace('#
								(?<![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff$])
								(?:be|return)
								(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff])
							#x', 'is', $after) . ', ' .
							static::includeString($all) .
						');';
					},

				'#^(\s*)(\S.*\s)?should(?!\snot)\s(.*[^;]);*\s*$#mU'
					=> function ($match)
					{
						list($all, $spaces, $before, $after) = $match;
						return $spaces . '>assertTrue(' .
							$before .
							preg_replace('#
								(?<![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff$])
								(?:be|return)
								(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff])
							#x', 'is', $after) . ', ' .
							static::includeString($all) .
						');';
					},
			));

			$validSubst = self::validSubst('(?:'.implode('|', $GLOBALS['quotedStrings']).')');
			$validComments = self::validSubst('(?:'.implode('|', $GLOBALS['commentStrings']).')');

			$__file = is_null(static::$lastParsedFile) ? null : realpath(static::$lastParsedFile);
			if($__file === false)
			{
				$__file = static::$lastParsedFile;
			}
			$__dir = is_null($__file) ? null : dirname($__file);
			$__file = var_export($__file, true);
			$__dir = var_export($__dir, true);

			$content = static::replace($content, array(

				/*********/
				/* Class */
				/*********/
				'#
				(
					(?:^|\S\s*)
					\n[\t ]*
				)
				(
					(?:
						(?:'.self::ABSTRACT_SHORTCUTS.')
						\s+
					)?
					\\\\?
					(?:'.self::VALIDNAME.'\\\\)*
					'.self::VALIDNAME.'
				)
				(?:
					(?::|\s+:\s+|\s+extends\s+)
					(
						\\\\?
						'.self::VALIDNAME.'
						(?:\\\\'.self::VALIDNAME.')*
					)
				)?
				(?:
					(?:<<<|\s+<<<\s+|\s+implements\s+)
					(
						\\\\?
						'.self::VALIDNAME.'
						(?:\\\\'.self::VALIDNAME.')*
						(?:
							\s*,\s*
							\\\\?
							'.self::VALIDNAME.'
							(?:\\\\'.self::VALIDNAME.')*
						)*
					)
				)?
				(
					\s*
					(?:{(?:.*})?)?
					\s*\n
				)
				#xi'
					=> array(get_class(), 'parseClass'),


				/************************/
				/* Constantes spéciales */
				/************************/
				'#(?<![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff$]|::|->)__FILE(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff])#'
					=> $__file,

				'#(?<![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff$]|::|->)__DIR(?![a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff])#'
					=> $__dir,


				/*************/
				/* Constants */
				/*************/
				'#'.self::START.'('.self::CONSTNAME.')\s*=#'
					=> '$1const $2 =',

				'#\#('.self::CONSTNAME.')\s*=([^;]+);#'
					=> 'define("$1",$2);',

				'#([\(;\s\.+/*=])~:('.self::CONSTNAME.')#'
					=> '$1self::$2',

				'#([\(;\s\.+/*=]):('.self::CONSTNAME.')#'
					=> '$1static::$2',


				/*************/
				/* Functions */
				/*************/
				'#'.self::START.'<(?![\?=])#'
					=> '$1return ',

				'#'.self::START.'@f\s+('.self::VALIDNAME.')#'
					=> '$1if-defined-function $2',

				'#'.self::START.'f\s+('.self::VALIDNAME.')#'
					=> '$1function $2',

				'#(?<![a-zA-Z0-9_])f°\s*\(#'
					=> 'function(',

				'#(?<![a-zA-Z0-9_])f°\s*(\$|use|\{|\n|$)#'
					=> 'function $1',


				/****************/
				/* > to $this-> */
				/****************/
				'#([\(;\s\.+/*:+\/\*\?\&\|\!\^\~\[\{]\s*|return(?:\(\s*|\s+)|[=-]\s+)>(\$?'.self::VALIDNAME.')#'
					=> '$1$this->$2',


				/**************/
				/* Attributes */
				/**************/
				'#'.self::START.'-\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1private $2',

				'#'.self::START.'\+\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1public $2',

				'#'.self::START.'\*\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1protected $2',

				'#'.self::START.'s-\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1static private $2',

				'#'.self::START.'s\+\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1static public $2',

				'#'.self::START.'s\*\s*(('.$validComments.'\s*)*\$'.self::VALIDNAME.')#U'
					=> '$1static protected $2',


				/***********/
				/* Methods */
				/***********/
				'#'.self::START.'\*\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1protected function $2',

				'#'.self::START.'-\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1private function $2',

				'#'.self::START.'\+\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1public function $2',

				'#'.self::START.'s\*\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1static protected function $2',

				'#'.self::START.'s-\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1static private function $2',

				'#'.self::START.'s\+\s*(('.$validComments.'\s*)*'.self::VALIDNAME.')#U'
					=> '$1static public function $2',


				/**********/
				/* Switch */
				/**********/
				'#(\n\s*(?:'.$validComments.'\s*)*)(\S.*)\s+\:=#U'
					=> "$1switch($2)",

				'#(\n\s*(?:'.$validComments.'\s*)*)(\S.*)\s+\:\:#U'
					=> "$1case $2:",

				'#(\n\s*(?:'.$validComments.'\s*)*)d\:#'
					=> "$1default:",

				':;'
					=> "break;",


				/***********/
				/* Summons */
				/***********/
				'#(\$.*\S)\s*\*\*=\s*('.self::VALIDNAME.')\s*\(\s*\)#U'
					=> "$1 = $2($1)",

				'#(\$.*\S)\s*\*\*=\s*('.self::VALIDNAME.')\s*\(#U'
					=> "$1 = $2($1, ",

				'#([\(;\s\.+/*=\r\n]\s*)('.self::VALIDNAME.')\s*\(\s*\*\*\s*(\$[^\),]+)#'
					=> "$1$3 = $2($3",

				'#(\$.*\S)\s*\(\s*('.self::OPERATORS.')=\s*(\S)#U'
					=> "$1 = ($1 $2 $3",

				'#(\$.*\S)\s*('.self::OPERATORS.')=\s*(\S)#U'
					=> "$1 = $1 $2 $3",

				'#('.self::VALIDVAR.')\s*\!\?==\s*(\S[^;\n\r]*);#U'
					=> "if(!isset($1)) { $1 = $4; }",

				'#('.self::VALIDVAR.')\s*\!\?==\s*(\S[^;\n\r]*)(?=[;\n\r]|\$)#U'
					=> "if(!isset($1)) { $1 = $4; }",

				'#('.self::VALIDVAR.')\s*\!\?=\s*(\S[^;\n\r]*);#U'
					=> "if(!$1) { $1 = $4; }",

				'#('.self::VALIDVAR.')\s*\!\?=\s*(\S[^;\n\r]*)(?=[;\n\r]|\$)#U'
					=> "if(!$1) { $1 = $4; }",

				'#('.self::VALIDVAR.')\s*<->\s*('.self::VALIDVAR.')#U'
					=> "\$_sv = $4; $1 = \$_sv; unset(\$_sv)",

				'#('.self::VALIDVAR.')((?:\!\!|\!|~)\s*)(?=[\r\n;])#U'
					=> "$1 = $4$1",


				/***************/
				/* Comparisons */
				/***************/
				'#\seq\s#'
					=> " == ",

				'#\sne\s#'
					=> " != ",

				'#\sis\s#'
					=> " === ",

				'#\snot\s#'
					=> " !== ",

				'#\slt\s#'
					=> " < ",

				'#\sgt\s#'
					=> " > ",


				/**********************/
				/* Array short syntax */
				/**********************/
				'#{(\s*(?:\n+[\t ]*'.self::VALIDNAME.'[\t ]*=[^\n]+)*\s*)}#'
					=> array(get_class(), 'arrayShortSyntax'),

			));
			$content = explode("\n", $content);
			$curind = array();
			$previousRead = '';
			$previousWrite = '';
			$iRead = 0;
			$iWrite = 0;
			foreach($content as $index => &$line)
			{
				if(trim($line) !== '')
				{
					$espaces = strlen(str_replace("\t", '    ', $line))-strlen(ltrim($line));
					$c = empty($curind) ? -1 : end($curind);
					if($espaces > $c)
					{
						if(self::isBlock($previousRead, $content, $iRead))
						{
							if(substr(rtrim($previousRead), -1) !== '{'
							&& substr(ltrim($line), 0, 1) !== '{')
							{
								$curind[] = $espaces;
								$previousRead .= '{';
							}
						}
					}
					else if($espaces < $c)
					{
						if($c = substr_count($line, '}'))
						{
							$curind = array_slice($curind, 0, -$c);
						}
						while($espaces < ($pop = end($curind)))
						{
							if(trim($previousWrite, "\t }") === '')
							{
								if(strpos($previousWrite, '}') === false)
								{
									$previousWrite = str_repeat(' ', $espaces);
								}
								$previousWrite .= '}';
							}
							else
							{
								$s = strlen(ltrim($line));
								if($s && ($d = strlen($line) - $s) > 0)
								{
									$line = substr($line, 0, $d).'} '.substr($line, $d);
								}
								else
								{
									$line = '}'.$line;
								}
							}
							array_pop($curind);
						}
					}
					else
					{
						if(preg_match('#(?<![a-zA-Z0-9_\x7f-\xff$])('.self::MUST_CLOSE_BLOKCS.')(?![a-zA-Z0-9_\x7f-\xff])#', $previousRead))
						{
							$previousRead .= '{}';
						}
					}
					$previousRead = &$line;
					$iRead = $index;
				}
				$previousWrite = &$line;
				$iWrite = $index;
			}
			$content = implode("\n", $content);
			if(!empty($curind))
			{
				if(substr($content, -1) === "\n")
				{
					$content .= str_repeat('}', count($curind)) . "\n";
				}
				else
				{
					$content .= "\n" . str_repeat('}', count($curind));
				}
			}
			$beforeSemiColon = '(' . $validSubst . '|\+\+|--|[a-zA-Z0-9_\x7f-\xff]!|[a-zA-Z0-9_\x7f-\xff]~|!!|[a-zA-Z0-9_\x7f-\xff\)\]])(?<!<\?php|<\?)';
			$content = static::replace($content, array(

				'#if-defined-(function\s+('.self::VALIDNAME.')([^\{]*)(?:\{((?>[^\{\}]+)|(?-4))*\}))#U'
					=> 'if(function_exists(\'$2\')) { $1 }',

				/******************************/
				/* Complete with a semi-colon */
				/******************************/
				'#' . $beforeSemiColon . '(\s*(?:' . $validComments . '\s*)*[\n\r]+\s*(?:' . $validComments . '\s*)*)(?=[a-zA-Z0-9_\x7f-\xff\$\}]|$)#U'
					=> '$1;$2',

				'#' . $beforeSemiColon . '(\s*(?:' . $validComments . '\s*)*)$#U'
					=> '$1;$2',

				'#' . $beforeSemiColon . '(\s*(?:' . $validComments . '\s*)*\?>)$#U'
					=> '$1;$2',

			));
			$content = static::replaceStrings($content);
			$content = static::replace($content, array(

				"\r" => ' ',

				self::SUBST.self::SUBST
					=> self::SUBST,

				'#(?<![a-zA-Z0-9_\x7f-\xff\$])('.self::IF_BLOKCS.')(?:\s+(\S.*))?\s*\{#U'
					=> '$1 ($2) {',

				'#(?<![a-zA-Z0-9_\x7f-\xff\$])(function\s+'.self::VALIDNAME.')(?:\s+(array\s.+|[A-Z\$\&].+))?\s*\{#U'
					=> '$1 ($2) {',

				'#(?<![a-zA-Z0-9_\x7f-\xff\$])function\s*(array\s.+|[A-Z\$\&].+)?\s*\{#U'
					=> 'function ($1) {',

				'#(?<![a-zA-Z0-9_\x7f-\xff\$])function\s+use(?![a-zA-Z0-9_\x7f-\xff])#U'
					=> 'function () use',

				'#(?<![a-zA-Z0-9_\x7f-\xff\$])(function.*[^a-zA-Z0-9_\x7f-\xff\$])use\s*((array\s.+|[A-Z\$\&].+)\{)#U'
					=> '$1 ) use ( $2',

				'#\((\([^\(\)]+\))\)#'
					=> '$1',

				'#(catch\s*\([^\)]+\)\s*)([^\s\{])#'
					=> '$1{} $2',

			));
			return $content;
		}
	}
}

namespace
{

	function sbp_include($file, $once = false)
	{
		$method = $once ? 'includeOnceFile' : 'includeFile';
		return sbp\sbp::$method($file);
	}


	function sbp_include_once($file)
	{
		return sbp\sbp::includeOnceFile($file);
	}


	function sbp($file, $once = false)
	{
		return sbp_include($file, $once);
	}


	function sbp_include_if_exists($file, $once = false)
	{
		try
		{
			return sbp_include($file, $once);
		}
		catch(sbp\sbpException $e)
		{
			return false;
		}
	}

}

?>
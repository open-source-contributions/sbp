<?php

namespace
{

	class sbpException extends Exception {}

	class sbp
	{
		const COMMENT = '/* Generated By SBP */';
		const VALIDNAME = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
		const CONSTNAME = '[A-Z_]+';
		const SUBST = '`';
		const COMP = '÷';
		const COMMENTS = '\/\/.*(?=\n)|\/\*(?:.|\n)*\*\/';
		const OPERATORS = '\|\||\&\&|or|and|xor|is|not|<>|lt|gt|<=|>=|\!==|===|\?\:';
		const START = '((?:^|[\n;\{\}])(?:\/\/.*(?=\n)|\/\*(?:.|\n)*\*\/\s*)*\s*)';

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
			if(in_array(substr($match[0], 0, 1), str_split(',(+-/*&|'))
			|| in_array($match[2], array('else', 'try', 'default:', 'echo', 'print', 'exit', 'continue', 'break', 'return', 'do')))
			{
				return $match[0];
			}
			return $match[1].'class '.trim($match[2]).(empty($match[3]) ? '' : ' extends '.trim($match[3])).' '.trim($match[4]).str_repeat("\n", substr_count($match[0], "\n", 1));
		}

		static private function findLastBlock(&$line, $block = array())
		{
			$pos = false;
			if(empty($block))
			{
				$block = array('if', 'else', 'elseif', 'try', 'catch', 'function', 'class', 'trait', 'switch', 'while', 'for', 'foreach', 'do');
			}
			if(!is_array($block))
			{
				$block = array($block);
			}
			foreach($block as $word)
			{
				if(preg_match('#^(.*)[^a-zA-Z0-9_]'.$word.'[^a-zA-Z0-9_]#s', ' '.$line.' ', $match))
				{
					$p = strlen($match[1]);
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
			$container = preg_replace_callback('#(\t*){content}#', array('sbp', 'contentTab'), $container);
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
			$id = count($GLOBALS['replaceStrings']);
			$GLOBALS['replaceStrings'][$id] = $match[0];
			if(strpos($match[0], '/') === 0)
			{
				$GLOBALS['commentStrings'][] = $id;
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
			file_put_contents($to, self::parse(file_get_contents($from)));
			return true;
		}

		static public function fileExists($file)
		{
			$sbpFile = substr($file, 0, -4).'.sbp.php';
			if(!file_exists($file))
			{
				if(file_exists($sbpFile))
				{
					self::fileParse($sbpFile, $file);
					return true;
				}
			}
			else
			{
				if(file_exists($sbpFile) && filemtime($sbpFile) > filemtime($file))
				{
					self::fileParse($sbpFile, $file);
				}
				return true;
			}
			return false;
		}

		static public function includeFile($file)
		{
			if(!self::fileExists($file))
			{
				throw new sbpException($file." not found", 1);
				return false;
			}
			return include($file);
		}

		static public function parse($content)
		{
			$GLOBALS['replaceStrings'] = array();
			$GLOBALS['commentStrings'] = array();
			$content = str_replace(self::SUBST, self::SUBST.self::SUBST, $content);
			$content = preg_replace('#<\?(?!php)#', '<?php', $content);
			$content = preg_replace('#^(\s*<\?php)(\s)#', '$1 '.self::COMMENT.'$2', $content, 1, $count);
			$content = preg_replace_callback('#'.self::COMMENTS.'|'.self::stringRegex().'|\?>.*<\?php#sU', array('sbp', 'replaceString'), $content);
			//$validsubst = self::validSubst();
			$validComments = self::validSubst('(?:'.implode('|', $GLOBALS['commentStrings']).')');
			if(!$count)
			{
				$content = '<?php '.self::COMMENT.' ?>'.$content;
			}
			foreach(array(
				/*********/
				/* Class */
				/*********/
				'#((?:^|\S\s*)\n[\t ]*)('.self::VALIDNAME.')(?:\s*:\s*('.self::VALIDNAME.'))?(\s*(?:{(?:.*})?)?\s*\n)#i'
					=> array('sbp', 'parseClass'),


				/**************/
				/* Constantes */
				/**************/
				'#'.self::START.'('.self::CONSTNAME.')\s*=#'
					=> '$1const $2 =',

				'#\#('.self::CONSTNAME.')\s*=([^;]+);#'
					=> 'define("$1",$2);',

				'#([\(;\s\.+/*=])~:('.self::CONSTNAME.')#'
					=> '$1self::$2',

				'#([\(;\s\.+/*=]):('.self::CONSTNAME.')#'
					=> '$1static::$2',

				'#'.self::START.'<(?![\?=])#'
					=> '$1return ',

				'#'.self::START.'f\s+('.self::VALIDNAME.')#'
					=> '$1function $2',

				'#(?<![a-zA-Z0-9_])f°\s*\(#'
					=> 'function(',

				'#([\(;\s\.+/*=:+\/\*\?]\s*|return\s*|-\s+)>(\$?'.self::VALIDNAME.')#'
					=> '$1 $this->$2',


				/*************/
				/* Attributs */
				/*************/
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


				/************/
				/* Méthodes */
				/************/
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


				/***************/
				/* Assignation */
				/***************/
				'#(\$.*\S)\s*\*\*=\s*('.self::VALIDNAME.')\s*\(\s*\)#U'
					=> "$1 = $2($1)",

				'#(\$.*\S)\s*\*\*=\s*('.self::VALIDNAME.')\s*\(#U'
					=> "$1 = $2($1, ",

				'#([\(;\s\.+/*=])('.self::VALIDNAME.')\s*\(\s*\*\*\s*(\$[^\),]+)#'
					=> "$1$3 = $2($3",

				'#(\$.*\S)\s*\(\s*('.self::OPERATORS.')=\s*(\S)#U'
					=> "$1 = ($1 $2 $3",

				'#(\$.*\S)\s*('.self::OPERATORS.')=\s*(\S)#U'
					=> "$1 = $1 $2 $3",

				'#(\$.*\S)\s*\!\?=\s*(\S[^;]*;)#U'
					=> "if(!$1) { $1 = $2 }",

				'#(\$.*\S)(\!\!|\!|~);#U'
					=> "$1 = $2$1;",

				'#\sis\s#'
					=> " == ",

				'#\snot\s#'
					=> " == ",

				'#\slt\s#'
					=> " < ",

				'#\sgt\s#'
					=> " > "

			) as $search => $replace)
			{
				$content = (is_array($replace) ?
					preg_replace_callback($search, $replace, $content) :
					(substr($search, 0, 1) === '#' ?
						preg_replace($search, $replace, $content) :
						str_replace($search, $replace, $content)
					)
				);
			}
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
					$espaces = strlen(str_replace("\t", '	', $line))-strlen(ltrim($line));
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
					$previousRead = &$line;
					$iRead = $index;
				}
				$previousWrite = &$line;
				$iWrite = $index;
			}
			$content = implode("\n", $content);
			foreach($GLOBALS['replaceStrings'] as $id => $string)
			{
				$content = str_replace(self::COMP.self::SUBST.$id.self::SUBST.self::COMP, $string, $content);
			}
			$content = str_replace(self::SUBST.self::SUBST, self::SUBST, $content);
			return str_replace("\r", ' ', $content);
		}
	}


	function sbp_include($file)
	{
		return sbp::includeFile($file);
	}


	function sbp($file)
	{
		return sbp::includeFile($file);
	}


	function sbp_include_if_exists($file)
	{
		try
		{
			return sbp::includeFile($file);
		}
		catch(sbpException $e)
		{
			return false;
		}
	}

}

?>

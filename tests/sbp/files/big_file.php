<?php /* Generated By SBP */ 

namespace Extra\Bidule {
	
	trait Truc{
	
		public function essaye ($var) {
	
			return  "(' " . ${'var'}[0]->{"ceci"} . " ')";
    }}
	trait Chose extends Truc{
	
		public function __destruct () {
	
			return $this->essaye(array((object) array(
				 'ceci' => "Hello wordl!"
			)));
    }}
	interface Famille extends Yap;

		protected $attr = 0;
	
	class Fille extends Mere implements Famille, Autre, \Chose\Oups {

		public function sayHello () {
	
			echo "Hello world!";
    }}
	class \Yap\Yop extends Yiip\Yup implements \Gui\Hby {

		static private function lala(\Yap\Uui $r, array $o = array(5), $i = null) {

			$r = substr($r, 2, 4);
			return  $r;
}
}


function § () {
	$args = func_get_args();
	if (isset($args[1]) && is_numeric($args[1])) {
		$translated = call_user_func_array('trans_choice', $args);
		if (! isset($args[4]) && $args[0] === $translated) {
			$translated = trans($args[0], $args[1], isset($args[2]) ? $args[2] : array(), isset($args[3]) ? $args[3] : 'messages', Language::altLang());
	} } else{
		$translated = call_user_func_array('trans', $args);
		if (isset($args[0]) && ! isset($args[3]) && $args[0] === $translated) {
			$translated = trans($args[0], isset($args[1]) ? $args[1] : array(), isset($args[2]) ? $args[2] : 'messages', Language::altLang());
	} } return  $translated;

}
function normalize ($string, $lowerCase = true) {
	$a = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
	$b = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
	$string = utf8_decode($string);
	$string = strtr($string, utf8_decode($a), $b);
	if ($lowerCase) {
		$string = strtolower($string);
    }
	$string = utf8_encode($string);
	return  $string;

}
function array_maps ($maps, array $array) {
	if (!is_array($maps)) {
		$maps = explode(',', $maps);
    }
	foreach ($maps as $map) {
		$array = array_map($map, $array);
    }
	return  $array;

}
function scanUrl ($url, $followLinks = false, $recursions = 0) {
	return  Crawler::scanUrl($url, $followLinks, $recursions);

}
function ip2bin ($ip = null) {
	return  bin2hex(inet_pton(is_null($ip) ? Request::getClientIp() : $ip));

}
function replace ($replacement, $to, $string = null) {
	if (is_null($string)) {
		if (!is_array($replacement)) {
			if (!is_array($to)) {
				throw new InvalidArgumentException("Signatures possibles : string, string, string / array, string / array, string, string / string, array");
				return  false;
			} return  replace($to, strval($replacement));
        }
		$string = $to;
		$to = null;
	} if (!is_null($to)) {
		$replacement = (array) $replacement;
		$to = (array) $to;
		$count = count($replacement);
		$countTo = count($to);
		if ($count < $countTo) {
			$to = array_slice($to, 0, $count);
        }
		else if ($count > $countTo) {
			$last = last($to);
			for ($i = $countTo; $i < $count; $i++) {
				array_push($to, $last);
        }}
		$replacement = array_combine((array) $replacement, (array) $to);
    }
	foreach ($replacement as $from => $to) {
		if (is_callable($to)) {
			$string = preg_replace_callback($from, $to, $string);
        }
		else{
			try{
				// Si possible, on utilise les RegExep
				$string = preg_replace($from, $to, $string);
            }
			catch (ErrorException $e) {
				// Sinon on rempalcement simplement la chaîne
				$string = str_replace($from, $to, $string);
    }}}
	return  $string;

}
function accents2entities ($string) {
	return  strtr($string, array(
		'é' => '&eacute;',
		'è' => '&egrave;',
		'ê' => '&ecirc;',
		'ë' => '&euml;',
		'à' => '&agrave;',
		'ä' => '&auml;',
		'ù' => '&ugrave;',
		'û' => '&ucirc;',
		'ü' => '&uuml;',
		'ô' => '&ocirc;',
		'ò' => '&ograve;',
		'ö' => '&ouml;',
		'ï' => '&iuml;',
		'ç' => '&ccedil;',
		'ñ' => '&ntild;',
		'É' => '&Eacute;',
	));

}
function utf8 ($string) {
	$string = str_replace('Ã ', '&agrave; ', $string);
	if (strpos($string, 'Ã') !== false and strpos(utf8_decode($string), 'Ã') === false) {
		$string = utf8_decode(accents2entities($string));
	} if (!mb_check_encoding($string, 'UTF-8') and mb_check_encoding(utf8_encode($string), 'UTF-8')) {
		$string = utf8_encode(accents2entities($string));
	} return  $string;

}
function flashAlert ($textKey, $type = 'danger') {
	Session::flash('alert', $textKey);
	Session::flash('alert-type', $type);
	if ($type === 'danger') {
		Input::flash();

}}
function fileLastTime ($file) {
	return  max(filemtime($file), filectime($file));

}
function checkAssets ($state = null) {
	static $_state = null;
	if (!is_null($state)) {
		$_state = !!$state;
	} elseif (is_null($_state)) {
		$_state = Config::get('app.debug');
	} return  $_state;

}
function style () {
	$args = func_get_args();
	if (checkAssets()) {
		$stylusFile = CssParser::stylusFile($args[0]);
		$cssFile = CssParser::cssFile($args[0], $isALib);
		$time = 0;
		if (file_exists($stylusFile)) {
			$time = DependancesCache::lastTime($stylusFile, 'fileLastTime');
			if (!file_exists($cssFile) || $time > fileLastTime($cssFile)) {
				(new CssParser($stylusFile))->out($cssFile);
			} $time -= 1363188938;
		} $args[0] = 'css/' . ($isALib ? 'lib/' : '') . $args[0] . '.css' . ($time ? '?' . $time : '');
	} else{
		$args[0] = 'css/' . (!file_exists(app_path() . '/../public/css/' . $args[0] . '.css') ? 'lib/' : '') . $args[0] . '.css';
	} return  call_user_func_array(array('HTML', 'style'), $args);

}
function script () {
	$args = func_get_args();
	if (checkAssets()) {
		$coffeeFile = JsParser::coffeeFile($args[0]);
		$jsFile = JsParser::jsFile($args[0], $isALib);
		$time = 0;
		if (file_exists($coffeeFile)) {
			$time = DependancesCache::lastTime($coffeeFile, 'fileLastTime');
			if (!file_exists($jsFile) || $time > fileLastTime($jsFile)) {
				(new JsParser($coffeeFile))->out($jsFile);
			} $time -= 1363188938;
		} $args[0] = 'js/' . ($isALib ? 'lib/' : '') . $args[0] . '.js' . ($time ? '?' . $time : '');
	} else{
		$args[0] = 'js/' . (!file_exists(app_path() . '/../public/js/' . $args[0] . '.js') ? 'lib/' : '') . $args[0] . '.js';
	} return  call_user_func_array(array('HTML', 'script'), $args);

}
function image ($path, $alt = null, $width = null, $height = null, $attributes = array(), $secure = null) {
	$time = 0;
	$complete = function ($ext  ) use ( &$path, &$asset, &$publicFile) {
		$asset .= '.' . $ext;
		$publicFile .= '.' . $ext;
		$path .='.' . $ext;
	} ;
	$asset = app_path() . '/assets/images/' . $path;
	$publicFile = app_path() . '/../public/img/' . $path;
	if (checkAssets()) {
		if (!file_exists($asset) && !file_exists($publicFile)) {
			if (file_exists($asset . '.png') || file_exists($publicFile . '.png')) {
				$complete('png');
			} elseif (file_exists($asset . '.jpg') || file_exists($publicFile . '.jpg')) {
				$complete('jpg');
			} elseif (file_exists($asset . '.gif') || file_exists($publicFile . '.gif')) {
				$complete('gif');
		} } if (file_exists($asset)) {
			$time = fileLastTime($asset);
			if (!file_exists($publicFile) || $time > fileLastTime($publicFile)) {
				copy($asset, $publicFile);
			} $time -= 1363188938;
	} } else{
		if (!file_exists($publicFile)) {
			if (file_exists($publicFile . '.png')) {
				$complete('png');
			} elseif (file_exists($publicFile . '.jpg')) {
				$complete('jpg');
			} elseif (file_exists($publicFile . '.gif')) {
				$complete('gif');
	} } } $image = '/img/' . $path . ($time ? '?' . $time : '');
	if (! is_null($alt) || ! is_null($width) || ! is_null($height) || $attributes !== array() || ! is_null($secure)) {
		if (is_array($alt)) {
			$attributes = $alt;
			$alt = null;
		} elseif (is_array($width)) {
			$attributes = $width;
			$width = null;
		} elseif (is_array($height)) {
			$attributes = $height;
			$height = null;
		} if (! is_null($width)) {
			$attributes['width'] = $width;
		} if (! is_null($height)) {
			$attributes['height'] = $height;
		} $image = HTML::image($image, $alt, $attributes, $secure);
	} return  $image;

}
function lang () {
	return  Lang::locale();

}
function starRate ($id = '', $params = '') {
	return  (new StarPush($id))
		->images(StarPush::GRAY_STAR, StarPush::BLUE_STAR, StarPush::GREEN_STAR)
		->get($params);

}
function array_undot ($array) {
	$results = array();
	foreach ($array as $key => $value) {
		$dot = strpos($key, '.');
		if ($dot === false) {
			$results[$key] = $value;
		} else{
			list($first, $second) = explode('.', $key, 2);
			if (! isset($results[$first])) {
				$results[$first] = array();
			} $results[$first][$second] = $value;
	} } return  array_map(function ($value) {
		return  is_string($value) ? $value : array_undot($value);
	} , $results);

}
function backUri ($currentUri) {
	$uri = Request::server('REQUEST_URI');
	if ($uri === $currentUri) {
		$uri = Request::server('HTTP_REFERER');
	} return  $uri;

}
if (!function_exists('http_negotiate_language')) {
	function http_negotiate_language ($available_languages, &$result = null) {
		$http_accept_language = Request::server('HTTP_ACCEPT_LANGUAGE', '');
		preg_match_all(
			"/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
			"(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
			$http_accept_language,
			$hits,
			PREG_SET_ORDER
		);
		$bestlang = $available_languages[0];
		$bestqval = 0;
		foreach ($hits as $arr) {
			$langprefix = strtolower($arr[1]);
			if (!empty($arr[3])) {
				$langrange = strtolower($arr[3]);
				$language = $langprefix . "-" . $langrange;
            }
			else{
				$language = $langprefix;
            }
			$qvalue = 1.0;
			if (!empty($arr[5])) {
				$qvalue = floatval($arr[5]);
            }
			if (in_array($language, $available_languages) && ($qvalue > $bestqval)) {
				$bestlang = $language;
				$bestqval = $qvalue;
            }
			else if (in_array($langprefix, $available_languages) && (($qvalue*0.9) > $bestqval)) {
				$bestlang = $langprefix;
				$bestqval = $qvalue*0.9;
        }}
		return  $bestlang;
}}
class Form extends Illuminate\Support\Facades\Form {

	static public function open ($options, $second = null) {
		$options = is_array($options) ? $options : array('url' => $options);
		if (! is_null($second)) {
			$options = array_merge($options, $second);
		} return  parent::open($options);
    }
	static public function input ($type, $name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		if (is_string($options)) {
			$options = array('class' => $options);
		} if (!is_null($placeholder)) {
			$options['placeholder'] = $placeholder;
		} if (!$autocomplete) {
			$options['autocomplete'] = 'off';
		} return  parent::input($type, $name, $value, $options);
    }
	static public function text ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('text', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function pass ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('password', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function password ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('password', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function checkbox ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('checkbox', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function radio ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('radio', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function email ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('email', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function number ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('number', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function color ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('color', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function date ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('date', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function dateTime ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('datetime', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function localDateTime ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('datetime-local', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function dateTimeLocal ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('datetime-local', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function file ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('file', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function month ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('month', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function range ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('range', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function search ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('search', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function tel ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('tel', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function time ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('time', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function url ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('url', $name, $value, $options, $placeholder, $autocomplete);
    }
	static public function week ($name, $value = null, $options = array(), $placeholder = null, $autocomplete = true) {
		return  static::input('week', $name, $value, $options, $placeholder, $autocomplete);

}}
class JsParser {

	const YES = 'yes|true|on|1';
	const NO = 'no|false|off|0';

	protected $coffeeFile;

	public function __construct ($coffeeFile) {
		$this->coffeeFile = $coffeeFile;
    }
	public function out ($jsFile) {
		return  file_put_contents(
			$jsFile,
			$this->parse($this->coffeeFile)
		);
    }
	static public function resolveRequire ($coffeeFile, $firstFile = null) {
		if (is_null($firstFile)) {
			$firstFile = $coffeeFile;
		} return  preg_replace_callback(
			'#\/\/-\s*require\s*\(?\s*([\'"])(.*(?<!\\\\)(?:\\\\{2})*)\\1(?:[ \t]*,[ \t]*(' . static::YES . '|' . static::NO . '))?[ \t]*\)?[ \t]*(?=[\n\r]|$)#i',
			function ($match  ) use ( $coffeeFile, $firstFile) {
				$file = stripslashes($match[2]);
				$file = preg_match('#^(http|https|ftp|sftp|ftps):\/\/#', $file) ?
					$file :
					static::findFile($file);
				$isCoffee = empty($match[3]) ?
					ends_with($file, '.coffee') :
					in_array(strtolower($match[3]), explode('|', static::YES));
				DependancesCache::add($firstFile, $file);
				$file = static::resolveRequire($file, $firstFile);
				if (! $isCoffee) {
					$file = "`$file`";
				} return  $file;
			} ,
			file_get_contents($coffeeFile)
		);
    }
	public function parse ($coffeeFile) {
		DependancesCache::flush($coffeeFile);
		$code = CoffeeScript\Compiler::compile(
			static::resolveRequire($coffeeFile),
			array(
				'filename' => $coffeeFile,
				'bare' => true
			)
		);
		if (! Config::get('app.debug')) {
			$code = preg_replace('#;(?:\\r\\n|\\r|\\n)\\h*#', ';', $code);
			$code = preg_replace('#(?:\\r\\n|\\r|\\n)\\h*#', ' ', $code);
		} return  $code;
    }
	static protected function findFile ($file) {
		if (file_exists($file)) {
			return  $file;
		} $coffeeFile = static::coffeeFile($file);
		if (file_exists($coffeeFile)) {
			return  $coffeeFile;
		} return  static::jsFile($file);
    }
	static public function coffeeFile ($file, &$isALib = null) {
		$files = array(
			app_path() . '/assets/scripts/' . $file . '.coffee',
			app_path() . '/../public/js/lib/' . $file . '.coffee',
		);
		foreach ($files as $iFile) {
			if (file_exists($iFile)) {
				$isALib = str_contains($iFile, 'lib/');
				return  $iFile;
		} } return  array_get($files, 0);
    }
	static public function jsFile ($file, &$isALib = null) {
		$jsDir = app_path() . '/../public/js/';
		foreach (array($jsDir, $jsDir . 'lib/') as $dir) {
			foreach (array('coffee', 'js') as $ext) {
				if (file_exists($dir . $file . '.' . $ext)) {
					$isALib = ends_with($dir, 'lib/');
					return  $dir . $file . '.js';
		} } } return  app_path() . '/../public/js/' . $file . '.js';

}}
class HomeController extends BaseController {

	public function searchBar () {
		return $this->view('home');
    }
	public function searchResultForm ($page = 1, $q = null, $resultsPerPage = null) {
		return $this->searchResult($page, $q, $resultsPerPage, true);
    }
	public function searchResult ($page = 1, $q = null, $resultsPerPage = null, $form = false) {
		$q = is_null($q) ? Request::get('q', $page) : urldecode($q);
		$data = CrawledContent::getSearchResult($q)
				->paginatedData($page, $resultsPerPage, array(
				'q' => $q,
				'pageUrl' => '/%d/'.urlencode($q).'{keepResultsPerPage}',
				'resultsPerPageUrl' => '/'.$page.'/'.urlencode($q).'/%d'
			));
		if ($form) {
			LogSearch::log($q, $data['nbResults']);
		} return $this->view('result', $data);
    }
	public function goOut ($search_query, $crawledContent) {
		$id = $crawledContent->id;

		LogOutgoingLink::create(array(
			'search_query' => $search_query,
			'crawled_content_id' => $id
		));
		$count = Cache::get('crawled_content_id:'.$id.'_log_outgoing_link_count');
		if ($count) {
			$count++;
		} else{
			$count = LogOutgoingLink::where('crawled_content_id', $id)->count();
		} Cache::put('crawled_content_id:'.$id.'_log_outgoing_link_count', $count, CrawledContent::REMEMBER);

		return  Redirect::to($crawledContent->url);
    }
	public function delete ($crawledContent) {
		if (! User::current()->isModerator()) {
			Session::flash('back-url', '/delete/' . $crawledContent->id);
			return  Redirect::to('/user/login');
        }
		return $this->view('delete', array(
			'result' => $crawledContent
		));
    }
	public function deleteConfirm ($crawledContent) {
		if (! User::current()->isModerator()) {
			return  Redirect::to('/user/login');
		} $crawledContent->delete();
		flashAlert('global.delete-succeed', 'success');

		return  Redirect::to('/');
    }
	public function addUrl () {
		Session::regenerateToken();
		if (! User::current()->isContributor()) {
			return  Redirect::to('/user/login');
		} $url = Input::get('url');
		$state = scanUrl($url);
		return $this->view('home', array(
			'url' => $url,
			'state' => $state
		));
    }
	public function mostPopular ($page, $resultsPerPage = null) {
		return $this->view('result',
			CrawledContent::popular()
				->select(
					'crawled_contents.id',
					'url', 'title', 'content', 'language',
					DB::raw('COUNT(log_outgoing_links.id) AS count')
				)
				->orderBy('count', 'desc')
				->paginatedData($page, $resultsPerPage, array(
					'q' => '',
					'pageUrl' => '/most-popular/%d{keepResultsPerPage}',
					'resultsPerPageUrl' => '/most-popular/'.$page.'/%d'
				))
			);
    }
	public function history ($page, $resultsPerPage = null) {
		$data = LogSearch::mine()
			->paginatedData($page, $resultsPerPage, array(
				'q' => '',
				'pageUrl' => '/history/%d{keepResultsPerPage}',
				'resultsPerPageUrl' => '/history/'.$page.'/%d'
			));
		$data['resultsGroups'] = $data['results']->groupBy(function ($element) {
			return $element->created_at->uRecentDate;
		} );
		return $this->view('history', $data);
}}
/**
 * Contenu récupéré par le crawler
 */
class CrawledContent extends Model {

	protected $collection = 'crawled_content';
	protected $softDelete = true;
	protected $fillable = array('url', 'title', 'content', 'language');

	const SAME_LANGUAGE = 8;
	const SAME_PRIMARY_LANGUAGE = 4;

	/**
	 * Retourne les résultats d'une recherche
	 *
	 * @param string $query : l'expression à rechercher
	 *
	 * @return CrawledContent $resultsContainigQuery
	 */
	static public function getSearchResult ($query) {
		$calledClass = get_called_class();
		return self::search($query, $values) // $values contient les mots contenus dans la chaîne $query sous forme d'array
			->select(
				'crawled_contents.id',
				'url', 'title', 'content', 'language', 'deleted_at',
				DB::raw('COUNT(log_outgoing_links.id) AS count'),
				DB::raw(
					self::caseWhen(DB::raw('language'), array(
						Lang::locale() => static::SAME_LANGUAGE
					), 0) . ' + ' .
					self::caseWhen(self::substr(DB::raw('language'), 1, 2), array(
						substr(Lang::locale(), 0, 2) => static::SAME_PRIMARY_LANGUAGE
					), 0) . ' +
					COUNT(DISTINCT key_words.id) * ' . static::KEY_WORD_SCORE . ' + ' .
					self::findAndCount(DB::raw('content'), $query).' * ' . static::COMPLETE_QUERY_SCORE . ' + '.
					self::findAndCount(DB::raw('content'), $values).' * ' . static::ONE_WORD_SCORE . '
					AS score
				')
			)
			->leftJoin('log_outgoing_links', 'log_outgoing_links.crawled_content_id', '=', 'crawled_contents.id')
			->leftJoin('crawled_content_key_word', 'crawled_content_key_word.crawled_content_id', '=', 'crawled_contents.id')
			->leftJoin('key_words', function ($join  ) use ( $calledClass, $values) {
				$join->on('crawled_content_key_word.key_word_id', '=', 'key_words.id')
					->on('key_words.word', 'in', DB::raw('(' . implode(', ', array_maps(array('normalize', 'strtolower', array($calledClass, 'quote')), $values)) . ')'));
			} )
			->groupBy('crawled_contents.id')
			->orderBy('score', 'desc');
    }
	/**
	 * Retourne les résultats sur lesquels quelqu'un a déjà cliqué au moins une fois (lié à 1 ou plusieurs LogOutgoingLink)
	 *
	 * @return CrawledContent $popularResults
	 */
	static public function popular () {
		return static::leftJoin('log_outgoing_links', 'log_outgoing_links.crawled_content_id', '=', 'crawled_contents.id')
			->whereNotNull('log_outgoing_links.id')
			->groupBy('crawled_contents.id');
    }
	public function keyWords () {
		return $this->belongsToMany('KeyWord');
    }
	public function scan () {
		scanUrl($this->attributes['url']);
    }
	public function getOutgoingLinkAttribute () {
		return '/out/'. (empty(self::$lastQuerySearch) ? '-' : self::$lastQuerySearch) . '/' . $this->id;
    }
	public function getUrlAndLanguageAttribute () {
		return $this->url . (empty($this->language) ? '' : '(' . $this->language . ')');
    }
	public function link ($label, array $attributes = array()) {
		return HTML::link($this->outgoingLink, $label, $attributes);
    }
	public function getCountAttribute () {
		return Cache::get('crawled_content_id:' . $this->id . '_log_outgoing_link_count', array_get($this->attributes, 'count', 0));
    }
	public function resume ($length = 800) {
		$content = trim(Cache::get('CrawledContent-' . $this->id . '-content', array_get($this->attributes, 'content', '')));
		if (strlen($content) > $length) {
			$content = substr($content, 0, $length);
			$content = substr($content, 0, strrpos($content, ' ')) . '...';
		} $closeStrongTag = substr_count($content, '<strong>') - substr_count($content, '</strong>');
		$content .= str_repeat('</strong>', $closeStrongTag);
		return utf8($content);
    }
	public function getContentAttribute () {
		return $this->resume();
    }
	public function getTitleAttribute () {
		return utf8(Cache::get('CrawledContent-' . $this->id . '-title', array_get($this->attributes, 'title', '')));

}}
?>
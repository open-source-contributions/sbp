<?php

use sbp\sbpException;

class sbp extends \sbp\sbp
{
	const TEST_GET_BENCHMARK_HTML = 'test-get-benchmark-html';
	const TEST_GET_LIST = 'test-get-list';

	static public function testContent($from)
	{
		static::$lastParsedFile = $from;
		$content = self::parse(file_get_contents($from));
		static::$lastParsedFile = null;
		return $content;
	}

	static public function benchmark($title = '')
	{
		static $list = null;
		if($title === static::TEST_GET_BENCHMARK_HTML)
		{
			return static::getBenchmarkHtml($list);
		}
		if($title === static::TEST_GET_LIST)
		{
			return $list;
		}
		return static::recordBenchmark($list, $title);
	}
}

class sbpTest extends \PHPUnit_Framework_TestCase
{
	protected function assertParse($from, $to, $message = null)
	{
		if(is_null($message))
		{
			$message = "sbp::parse(\"$from\") do not return \"$to\"";
		}
		$explode = explode("\n", $parsed = sbp::parse("<?\n".$from), 2);
		$from = str_replace(array("\n", "\r", "\t", ' '), '', trim(end($explode)));
		$to = str_replace(array("\n", "\r", "\t", ' '), '', trim($to));
		return $this->assertTrue($from === $to, $message.", it return\"$parsed\"\n\n");
	}

	protected function assertParseFile($from, $message = null)
	{
		if(is_null($message))
		{
			$message = "sbp::fileParse(\"$from\") do match the compiled file";
		}
		$out = trim(file_get_contents($from));
		$in = trim(sbp::testContent(preg_replace('#^(.+)(/[^/]+)$#', '$1/.src$2', $from)));
		$to = str_replace(array("\n", "\r", "\t", ' '), '', $out);
		$from = str_replace(array("\n", "\r", "\t", ' '), '', $in);
		$to = preg_replace('#/\*.*\*/#U', '', $to);
		$from = preg_replace('#/\*.*\*/#U', '', $from);
		if($from !== $to)
		{
			echo "\n";
			$in = preg_split('#\r\n|\r|\n#', $in);
			$out = preg_split('#\r\n|\r|\n#', $out);
			foreach($in as $key => $line)
			{
				if(preg_replace('#/\*.*\*/#U', '', trim($line)) === preg_replace('#/\*.*\*/#U', '', trim($out[$key])))
				{
					echo " ".str_replace("\t", '    ', $line)."\n";
				}
				else
				{
					echo "-".str_replace("\t", '    ', $line)."\n";
					echo "+".str_replace("\t", '    ', $out[$key])."\n";
				}
			}
		}
		return $this->assertTrue($from === $to, $message);
	}

	public function testParse()
	{
		$this->assertParse("ANameSpace\\BClass:CNameSpace\\DClass\n\t- \$var = 'a'", "class ANameSpace\\BClass extends CNameSpace\\DClass {\n\tprivate \$var = 'a';\n}");
	}

	public function testPlugin()
	{
		sbp_add_plugin('jQuery', '$(', 'new jQuery(');
		$this->assertParse("\$result = \$('#element')->animate({\n\tleft = 400\n\ttop = 200\n});", "\$result = new jQuery('#element')->animate(array(\n\t'left' => 400,\n\t'top' => 200\n));");
		sbp_remove_plugin('jQuery');
		$this->assertParse("\$result = \$('#element')->animate({\n\tleft = 400\n\ttop = 200\n});", "\$result = \$('#element')->animate(array(\n\t'left' => 400,\n\t'top' => 200\n));");
	}

	public function testBenchmark()
	{
		// $marker = 'Marker';
		// sbp::benchmark();
		// sbp::benchmark($marker);
		// $content = sbp::benchmark(sbp::TEST_GET_BENCHMARK_HTML);
		// $this->assertTrue(stripos($content, '<html') !== false && strpos($content, $marker) !== false);
	}

	public function testParseFile()
	{
		foreach(scandir($dir = __DIR__ . '/files/') as $file)
		{
			if(substr($file, 0, 1) !== '.' && is_file($file = $dir . $file))
			{
				$this->assertParseFile($file);
			}
		}
	}
}

?>
<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2005  Sean Kerr.                                       |
// |                                                                           |
// | For the full copyright and license information, please view the LICENSE   |
// | file that was distributed with this source code. You can also view the    |
// | LICENSE file online at http://www.agavi.org/LICENSE.txt                   |
// |   vi: set noexpandtab:                                                    |
// |   Local Variables:                                                        |
// |   indent-tabs-mode: t                                                     |
// |   End:                                                                    |
// +---------------------------------------------------------------------------+

/**
 * LoggingConfigHandler allows you to register loggers with the system.
 *
 * @package    agavi
 * @subpackage config
 *
 * @author    Sean Kerr (skerr@mojavi.org)
 * @author    Bob Zoller (bob@agavi.org)
 * @copyright (c) Authors
 * @since     0.9.1
 * @version   $Id$
 */
class LoggingConfigHandler extends IniConfigHandler
{

	// +-----------------------------------------------------------------------+
	// | METHODS                                                               |
	// +-----------------------------------------------------------------------+

	/**
	 * Execute this configuration handler.
	 *
	 * @param string An absolute filesystem path to a configuration file.
	 *
	 * @return string Data to be written to a cache file.
	 *
	 * @throws <b>ConfigurationException</b> If a requested configuration file
	 *                                       does not exist or is not readable.
	 * @throws <b>ParseException</b> If a requested configuration file is
	 *                               improperly formatted.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	public function & execute ($config)
	{

		// set our required categories list and initialize our handler
		$categories = array('required_categories' => array('loggers'));

		$this->initialize($categories);

		// parse the ini
		$ini = $this->parseIni($config);

		// init our data, includes, methods, appenders and appenders arrays
		$data       = array();
		$loggers    = array();
		$appenders  = array();

		// get a list of loggers and their registered appenders/params
		foreach ($ini['loggers'] as $logger => &$list) {
			if (!isset($loggers[$logger])) {
				// create our method
				$loggers[$logger] = array();
			}

			if (trim($list) == '') {
				// we have an empty list of appenders
				continue;
			}

			// load appenders list
			$this->loadLogger($config, $logger, $loggers, $appenders, $ini, $list);
		}

		// load appenders
		$this->loadAppenders($config, $loggers, $appenders, $ini, $list);

		$this->generateRegistration($data, $loggers, $appenders);

		// compile data
		$retval = "<?php\n" .
				  "// auto-generated by LoggingConfigHandler\n" .
				  "// date: %s\n%s\n?>";
		$retval = sprintf($retval, date('m/d/Y H:i:s'),
						  implode("\n", $data));

		return $retval;

	}

	// -------------------------------------------------------------------------

	/**
	 * Generate raw cache data.
	 *
	 * @param string A request method.
	 * @param array  The data array where our cache code will be appended.
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter data.
	 * @param array  A validators array.
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function generateRegistration (&$data, &$loggers, &$appenders)
	{

		/*
		$layout = new $appender['layout']['class'];
		...
		$appender = new $appender['class'];
		$appender->setLayout($layout);
		...
		$logger = new Logger;
		$logger->setAppender($appender_name, $appender);
		...
		LoggerManager::setLogger($name, $logger);
		...
		*/

		$used_layouts = array();
		$layouts_out = array();
		$used_appenders = array();
		$appenders_out = array();
		$loggers_out = array();
		foreach ($appenders as $name => &$appender) {
			if (!isset($used_layouts[$appender['layout']])) {
				$string = '$%s = new %s;';
				$layouts_out[] = sprintf($string, strtolower($appender['layout']), $appender['layout']);
				$used_layouts[$appender['layout']] = true;
			}
			if (!isset($used_appenders[$name])) {
				$string = '$%s = new %s;';
				$appenders_out[] = sprintf($string, strtolower($name), $appender['class']);
				if (isset($appender['params'])) {
					$string = '$%s->initialize(%s);';
					$appenders_out[] = sprintf($string, strtolower($name), var_export($appender['params'], true));
				}
				$string = '$%s->setLayout($%s);';
				$appenders_out[] = sprintf($string, strtolower($name), strtolower($appender['layout']));
				$used_appenders[$name] = true;
			}
		}
		unset($used_layouts);
		unset($used_appenders);

		foreach ($loggers as $name => &$logger) {
			$string = '$%s = new Logger;';
			$loggers_out[] = sprintf($string, strtolower($name));
			foreach ($logger as &$appender) {
				$string = '$%s->setAppender("%s", $%s);';
				$loggers_out[] = sprintf($string, strtolower($name), $appender, strtolower($appender));
			}
			$string = 'LoggerManager::setLogger("%s", $%s);';
			$loggers_out[] = sprintf($string, $name, strtolower($name));
		}

		$data = array_merge($data, $layouts_out, $appenders_out, $loggers_out);
	}

	// -------------------------------------------------------------------------

	/**
	 * Load the linear list of attributes from the [appenders] category.
	 *
	 * @param string The configuration file name (for exception usage).
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter appenders in which to
	 *               store loaded information.
	 * @param array  An associative array of validator data.
	 * @param array  The loaded ini configuration that we'll use for
	 *               verification purposes.
	 * @param string A comma delimited list of file/parameter names.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadAppenders (&$config, &$loggers, &$appenders, &$ini, &$list)
	{

		foreach (array_keys($appenders) as $appender) {
			if (!isset($ini[$appender]['class']) || !isset($ini[$appender]['layout'])) {
				$error = 'Configuration file "%s" has section "%s" without a class/layout key';
				$error = sprintf($error, $config, $appender);
				throw new ParseException($error);
			}

			$entry = array();

			foreach ($ini[$appender] as $key => &$value) {

				// get the file or parameter name and the associated info
				if (!preg_match('/^([^\.]+)\.?(.*?)$/', $key, $match)) {
					// can't parse current key
					$error = 'Configuration file "%s" specifies invalid key "%s"';
					$error = sprintf($error, $config, $key);
					throw new ParseException($error);
				}
	
				switch (strtoupper($match[1])) {
					case 'PARAM':
						$entry['params'][$match[2]] = $this->replacePath($this->replaceConstants($value));
						break;
					case 'CLASS':
						$entry['class'] = $value;
						break;
					case 'LAYOUT':
						$entry['layout'] = $value;
						break;
					default:
						$error = 'Configuration file "%s" specifies invalid key "%s"';
						$error = sprintf($error, $config, $match[1]);
						throw new ParseException($error);
				}
			}
			$appenders[$appender] = &$entry;
		}
	}

	// -------------------------------------------------------------------------

	/**
	 * Load all request methods and the file/parameter names that will be
	 * validated from the [methods] category.
	 *
	 * @param string The configuration file name (for exception usage).
	 * @param string A request method.
	 * @param array  An associative array of request method data.
	 * @param array  An associative array of file/parameter appenders in which to
	 *               store loaded information.
	 * @param array  The loaded ini configuration that we'll use for
	 *               verification purposes.
	 * @param string A comma delimited list of file/parameter appenders.
	 *
	 * @return void
	 *
	 * @author Sean Kerr (skerr@mojavi.org)
	 * @since  0.9.0
	 */
	private function loadLogger (&$config, &$logger, &$loggers, &$appenders, &$ini, &$list)
	{

		// explode the list of names
		$array = explode(',', $list);

		// loop through the names
		foreach ($array as $name) {

			$name = trim($name);

			// make sure we have the required status of this file or parameter
			if (!isset($ini[$name])) {

				// missing section
				$error = 'Configuration file "%s" specifies name ' .
						 '"%s", but it has no section';
				$error = sprintf($error, $config, $name);

				throw new ParseException($error);

			}

			if (!isset($appenders[$name])) {
				$appenders[$name] = array();
			}

			// add this appender to the current request method
			$loggers[$logger][] = $name;

		}

	}

}

?>

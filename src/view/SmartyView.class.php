<?php

// +---------------------------------------------------------------------------+
// | This file is part of the Agavi package.                                   |
// | Copyright (c) 2003-2006 the Agavi Project.                                |
// | Based on the Mojavi3 MVC Framework, Copyright (c) 2003-2005 Sean Kerr.    |
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
 *
 * @package    agavi
 * @subpackage view
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @author     zembla {@link http://forum.mojavi.org/index.php?showuser=329}
 * @author     Agavi Project <info@agavi.org>
 * @copyright  (c) Authors
 * @since      0.9.0
 *
 * @version    $Id$
 */
abstract class SmartyView extends View
{
	const COMPILE_DIR = 'templates';
	const COMPILE_SUBDIR = 'smarty';
	const CACHE_DIR = 'content';

	protected static
		$smarty = null;

	public function initialize($context)
	{
		if (!class_exists('Smarty')) {

			// if SMARTY_DIR constant is defined, we'll use it
			if ( defined('SMARTY_DIR') ) {
				require(SMARTY_DIR . 'Smarty.class.php');
			}
			// otherwise we resort to include_path
			else {
				require('Smarty.class.php');
			}
		}

		$this->smarty = new Smarty();
		$this->smarty->clear_all_assign();
		$this->smarty->clear_config();
		$this->smarty->config_dir   = AG_CONFIG_DIR;

		$compileDir = AG_CACHE_DIR . DIRECTORY_SEPARATOR . self::COMPILE_DIR . DIRECTORY_SEPARATOR . self::COMPILE_SUBDIR;
		@mkdir($compileDir, null, true);
		$this->smarty->compile_dir = $compileDir;

		$cacheDir = AG_CACHE_DIR . DIRECTORY_SEPARATOR . self::CACHE_DIR;
		@mkdir($cacheDir, null, true);
		$this->smarty->cache_dir = $cacheDir;

		$this->smarty->plugins_dir  = array("plugins","plugins_local");

		return(parent::initialize($context));
	}

	public function clearAttributes()
	{
		$this->smarty->clear_all_assign();
	}

	public function getAttributeNames()
	{
		return array_keys($this->smarty->get_template_vars());
	}

	public function hasAttribute($name)
	{
		return !is_null($this->smarty->get_template_vars($name));
	}

	public function & getAttribute($name)
	{
		return $this->smarty->get_template_vars($name);
	}

	public function & removeAttribute($name)
	{
		$retval = $this->smarty->get_template_vars($name);
		$this->smarty->clear_assign($name);
		return $retval;
	}

	public function setAttribute($name, $value)
	{
		$this->smarty->assign($name, $value);
	}

	public function appendAttribute($name, $value)
	{
		$this->smarty->append($name, $value);
	}

	public function setAttributeByRef($name, &$value)
	{
		$this->smarty->assign_by_ref($name, $value);
	}

	public function appendAttributeByRef($name, &$value)
	{
		$this->smarty->append_by_ref($name, $value);
	}

	public function setAttributes($attributes)
	{
		$this->smarty->assign($attributes);
	}

	public function setAttributesByRef(&$attributes)
	{
		foreach ($attributes as $key => &$value)
		{
			$this->setAttributeByRef($key, $value);
		}
	}

	public function & getEngine()
	{
		return $this->smarty;
	}

	public function & render()
	{
		$retval = null;

		// execute pre-render check
		$this->preRenderCheck();

		// get the render mode
		$mode = $this->getContext()->getController()->getRenderMode();

		$this->getEngine()->template_dir = $this->getDirectory();

		if ($mode == View::RENDER_CLIENT && !$this->isDecorator()) {
			// render directly to the client
			$this->getEngine()->display($this->getTemplate());
		} else if ($mode != View::RENDER_NONE) {
			// render to variable
			$retval = $this->getEngine()->fetch($this->getTemplate());

			// now render our decorator template, if one exists
			if ($this->isDecorator()) {
				$retval =& $this->decorate($retval);
			}

			if ($mode == View::RENDER_CLIENT) {
				echo($retval);
				$retval = null;
			}
		}
		return $retval;
	}

	public function & decorate(&$content)
	{
		// call our parent decorate() method
		parent::decorate($content);

		// render the decorator template and return the result
		$decoratorTemplate = $this->getDecoratorDirectory() . '/' . $this->getDecoratorTemplate();

		$retval = $this->getEngine()->fetch($decoratorTemplate);

		return $retval;
	}

}

?>
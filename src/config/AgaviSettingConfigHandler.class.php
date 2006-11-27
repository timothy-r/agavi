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
 * AgaviSettingConfigHandler handles the settings.xml file
 *
 * @package    agavi
 * @subpackage config
 *
 * @author     Sean Kerr <skerr@mojavi.org>
 * @copyright  (c) Authors
 * @since      0.9.0
 *
 * @version    $Id$
 */
class AgaviSettingConfigHandler extends AgaviConfigHandler
{

	/**
	 * Execute this configuration handler.
	 *
	 * @param      string An absolute filesystem path to a configuration file.
	 * @param      string An optional context in which we are currently running.
	 *
	 * @return     string Data to be written to a cache file.
	 *
	 * @throws     <b>AgaviUnreadableException</b> If a requested configuration
	 *                                             file does not exist or is not
	 *                                             readable.
	 * @throws     <b>AgaviParseException</b> If a requested configuration file is
	 *                                        improperly formatted.
	 *
	 * @author     Sean Kerr <skerr@mojavi.org>
	 * @since      0.9.0
	 */
	public function execute($config, $context = null)
	{

		$configurations = $this->orderConfigurations(AgaviConfigCache::parseConfig($config, false, $this->getValidationFile(), $this->parser)->configurations, AgaviConfig::get('core.environment'));

		// init our data array
		$data = array();

		foreach($configurations as $cfg) {
			// let's do our fancy work
			if($cfg->hasChildren('system_actions')) {
				foreach($cfg->system_actions->getChildren() as $action) {
					$name = $action->getAttribute('name');
					$data[sprintf('actions.%s_module', $name)] = $action->module->getValue();
					$data[sprintf('actions.%s_action', $name)] = $action->action->getValue();
				}
			}

			if(isset($cfg->settings)) {
				foreach($cfg->settings as $setting)
				{
					$data['core.' . $setting->getAttribute('name')] = $this->literalize($setting->getValue());
				}
			}
			
			if($cfg->hasChildren('exception_templates')) {
				foreach($cfg->exception_templates->getChildren() as $exception_template) {
					$tpl = $this->replaceConstants($exception_template->getValue());
					if(!is_readable($tpl)) {
						throw new AgaviConfigurationException('Exception template "' . $tpl . '" does not exist or is unreadable');
					}
					if($exception_template->hasAttribute('context')) {
						foreach(array_map('trim', explode(' ', $exception_template->getAttribute('context'))) as $ctx) {
							$data['exception.templates.' . $ctx] = $tpl;
						}
					} else {
						$data['exception.default_template'] = $this->replaceConstants($tpl);
					}
				}
			}
		}

		$code = 'AgaviConfig::import(' . var_export($data, true) . ');';

		// compile data
		$retval = "<?php\n" .
				  "// auto-generated by ".__CLASS__."\n" .
				  "// date: %s GMT\n%s\n?>";
		$retval = sprintf($retval, gmdate('m/d/Y H:i:s'), $code);

		return $retval;

	}

}

?>
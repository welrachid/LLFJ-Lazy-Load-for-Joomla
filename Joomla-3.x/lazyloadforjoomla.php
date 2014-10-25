<?php
/**
 * @Copyright
 * @package     LLFJ - Lazy Load for Joomla!
 * @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 * @version     3-7 - 2014-10-02
 * @link        http://joomla-extensions.kubik-rubik.de/llfj-lazy-load-for-joomla
 *
 * @license     GNU/GPL
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');

class PlgSystemLazyLoadForJoomla extends JPlugin
{
    protected $_execute;

    function __construct(&$subject, $config)
    {
        // First check whether version requirements are met for this specific version
        if($this->checkVersionRequirements(false, '3.2', 'Lazy Load For Joomla!', 'plg_system_lazyloadforjoomla', JPATH_ADMINISTRATOR))
        {
            parent::__construct($subject, $config);
            $this->loadLanguage('', JPATH_ADMINISTRATOR);
            $this->_execute = true;
        }
    }

    /**
     * Do all checks whether the plugin has to be loaded and load needed JavaScript instructions
     */
    public function onBeforeCompileHead()
    {
        // First run all exclusion checks
        if($this->params->get('exclude_editor'))
        {
            if(class_exists('JEditor', false))
            {
                $this->_execute = false;
            }
        }

        if($this->params->get('exclude_bots') AND $this->_execute == true)
        {
            $this->excludeBots();
        }

        if($this->params->get('exclude_components') AND $this->_execute == true)
        {
            $this->excludeComponents();
        }

        if($this->params->get('exclude_urls') AND $this->_execute == true)
        {
            $this->excludeUrls();
        }

        if($this->params->get('viewslist') AND $this->_execute == true)
        {
            $this->checkLoadedViews();
        }

        // If all exclusion checks passed successfully, then load the needed data
        if($this->_execute == true)
        {
            if($this->params->get('framework_type') == 1)
            {
                JHtml::_('behavior.framework');
                $head[] = '<script type="text/javascript" src="'.JURI::base().'plugins/system/lazyloadforjoomla/assets/js/lazyloadforjoomla.js"></script>';
                $head[] = '<script type="text/javascript">window.addEvent("domready",function() {var lazyloader = new LazyLoad();});</script>';
            }
            else
            {
                JHtml::_('jquery.framework');
                $head[] = '<script type="text/javascript" src="'.JURI::base().'plugins/system/lazyloadforjoomla/assets/js/lazyloadforjoomla-jquery.js"></script>';
                $head[] = '<script type="text/javascript">jQuery(function() {jQuery("img").lazyload();});</script>';
            }

            $head = "\n".implode("\n", $head)."\n";
            JFactory::getDocument()->addCustomTag($head);
        }
    }

    /**
     * Trigger onAfterRender executes the main plugin procedure
     */
    public function onAfterRender()
    {
        if($this->_execute == true)
        {
            $blankimage = JURI::base().'plugins/system/lazyloadforjoomla/assets/images/blank.gif';
            $body = JFactory::getApplication()->getBody(false);
            $pattern_image = "@<img[^>]*src=[\"\']([^\"\']*)[\"\'][^>]*>@";

            // Remove JavaScript template replacement files first
            if(strpos($body, '<script type="text/template"') !== false)
            {
                preg_match_all($pattern_image, preg_replace('@<script type="text/template".*</script>@isU', '', $body), $matches);
            }
            else
            {
                preg_match_all($pattern_image, $body, $matches);
            }

            if($this->params->get('exclude_imagenames') AND !empty($matches))
            {
                $this->excludeImageNames($matches);
            }

            if(!empty($matches))
            {
                $base = JUri::base();
                $base_path = JUri::base(true);

                foreach($matches[0] as $key => $match)
                {
                    // Check for correct image path - important for Joomla! version 3.3.4 and higher - no regular expressions for better performance
                    if(strpos($matches[1][$key], 'http://') === false AND strpos($matches[1][$key], 'https://') === false)
                    {
                        if(!empty($base_path))
                        {
                            if(strpos($matches[1][$key], $base_path) === false)
                            {
                                $match = str_replace($matches[1][$key], $base_path.'/'.$matches[1][$key], $match);
                            }
                        }
                        else
                        {
                            if(strpos($matches[1][$key], $base) === false)
                            {
                                $match = str_replace($matches[1][$key], $base.$matches[1][$key], $match);
                            }
                        }
                    }

                    $match_lazy = str_replace('src=', 'src="'.$blankimage.'" data-src=', $match);
                    $body = str_replace($matches[0][$key], $match_lazy, $body);
                }

                JFactory::getApplication()->setBody($body);
            }
        }
    }

    /**
     * Excludes the execution in specified components if option is selected
     */
    private function excludeComponents()
    {
        $option = JFactory::getApplication()->input->getWord('option');
        $exclude_components = array_map('trim', explode("\n", $this->params->get('exclude_components')));
        $hit = false;

        foreach($exclude_components as $exclude_component)
        {
            if($option == $exclude_component)
            {
                $hit = true;
                break;
            }
        }

        if($this->params->get('exclude_components_toggle'))
        {
            if($hit == false)
            {
                $this->_execute = false;
            }
        }
        else
        {
            if($hit == true)
            {
                $this->_execute = false;
            }
        }
    }

    /**
     * Excludes the execution in specified URLs if option is selected
     */
    private function excludeUrls()
    {
        $url = JUri::getInstance()->toString();
        $exclude_urls = array_map('trim', explode("\n", $this->params->get('exclude_urls')));
        $hit = false;

        foreach($exclude_urls as $exclude_url)
        {
            if($url == $exclude_url)
            {
                $hit = true;
                break;
            }
        }

        if($this->params->get('exclude_urls_toggle'))
        {
            if($hit == false)
            {
                $this->_execute = false;
            }
        }
        else
        {
            if($hit == true)
            {
                $this->_execute = false;
            }
        }
    }

    /**
     * Excludes the execution in specified image names if option is selected
     *
     * @param $matches
     */
    private function excludeImageNames(&$matches)
    {
        $exclude_image_names = array_map('trim', explode("\n", $this->params->get('exclude_imagenames')));
        $exclude_imagenames_toggle = $this->params->get('exclude_imagenames_toggle');
        $matches_temp = array();

        foreach($exclude_image_names as $exclude_image_name)
        {
            $count = 0;

            foreach($matches[1] as $match)
            {
                if(preg_match('@'.preg_quote($exclude_image_name).'@', $match))
                {
                    if(empty($exclude_imagenames_toggle))
                    {
                        unset($matches[0][$count]);
                    }
                    else
                    {
                        $matches_temp[] = $matches[0][$count];
                    }
                }

                $count++;
            }
        }

        if($exclude_imagenames_toggle)
        {
            unset($matches[0]);
            $matches[0] = $matches_temp;
        }
    }

    /**
     * Excludes the execution for specified bots if option is selected
     */
    private function excludeBots()
    {
        $exclude_bots = array_map('trim', explode(",", $this->params->get('botslist')));
        $agent = $_SERVER['HTTP_USER_AGENT'];

        foreach($exclude_bots as $exclude_bot)
        {
            if(preg_match('@'.$exclude_bot.'@i', $agent))
            {
                $this->_execute = false;
                break;
            }
        }
    }

    /**
     * Stops the execution if view is loaded which is entered in the settings (e.g. tmpl=component)
     */
    private function checkLoadedViews()
    {
        $exclude_views = array_map('trim', explode(",", $this->params->get('viewslist')));

        if(in_array(JFactory::getApplication()->input->getWord('tmpl', ''), $exclude_views))
        {
            $this->_execute = false;
        }
    }

    /**
     * Checks whether all requirements are met for the execution
     * Written generically to be used in all Kubik-Rubik Joomla! Extensions
     *
     * @param $admin                 Allow backend execution - true or false
     * @param $version_min           Minimum required Joomla! version - e.g. 3.2
     * @param $extension_name        Name of the extension of the warning message
     * @param $extension_system_name System name of the extension for the language file loading - e.g. plg_system_xxx
     * @param $jpath                 Path of the language file - JPATH_ADMINISTRATOR or JPATH_SITE
     *
     * @return bool
     */
    private function checkVersionRequirements($admin, $version_min, $extension_name, $extension_system_name, $jpath)
    {
        $execution = true;
        $version = new JVersion();

        if(!$version->isCompatible($version_min))
        {
            $execution = false;
            $backend_message = true;
        }

        if(empty($admin))
        {
            if(JFactory::getApplication()->isAdmin())
            {
                $execution = false;

                if(!empty($backend_message))
                {
                    $this->loadLanguage($extension_system_name, $jpath);
                    JFactory::getApplication()->enqueueMessage(JText::sprintf('KR_JOOMLA_VERSION_REQUIREMENTS_NOT_MET', $extension_name, $version_min), 'warning');
                }
            }
        }

        return $execution;
    }
}

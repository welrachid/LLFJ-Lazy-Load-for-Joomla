<?php
/**
 *  @Copyright
 *  @package     LLFJ - Lazy Load for Joomla!
 *  @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 *  @version     2.5-5 - 24-Sep-2012
 *  @link        http://joomla-extensions.kubik-rubik.de/llfj-lazy-load-for-joomla
 *
 *  @license GNU/GPL
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
jimport('joomla.plugin.plugin');

class plgSystemLazyLoadForJoomla extends JPlugin
{
    protected $_execute;

    function __construct(&$subject, $config)
    {
        $app = JFactory::getApplication();

        if($app->isAdmin())
        {
            return;
        }

        parent::__construct($subject, $config);
        $this->loadLanguage('', JPATH_ADMINISTRATOR);
        $this->_execute = true;
    }

    public function onBeforeCompileHead()
    {
        if($this->params->get('exclude_editor'))
        {
            if(class_exists('JEditor'))
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

        if($this->_execute == true)
        {
            $document = JFactory::getDocument();

            $head[] = '<script type="text/javascript" src="'.JURI::base().'plugins/system/lazyloadforjoomla/lazyloadforjoomla.js"></script>';
            $head[] = '<script type="text/javascript">window.addEvent("domready",function() {var lazyloader = new LazyLoad();});</script>';

            $head = "\n".implode("\n", $head)."\n";
            $document->addCustomTag($head);
        }
    }

    public function onAfterRender()
    {
        if($this->_execute == true)
        {
            $blankimage = JURI::base().'plugins/system/lazyloadforjoomla/blank.gif';
            $body = JResponse::getBody();

            $pattern = "@<img[^>]*src=[\"\']([^\"\']*)[\"\'][^>]*>@";
            preg_match_all($pattern, $body, $matches);

            if($this->params->get('exclude_imagenames') AND !empty($matches))
            {
                $this->excludeImageNames($matches);
            }

            if(!empty($matches))
            {
                foreach($matches[0] as $match)
                {
                    $matchlazy = str_replace('src=', 'src="'.$blankimage.'" data-src=', $match);
                    $body = str_replace($match, $matchlazy, $body);
                }

                JResponse::setBody($body);
            }
        }
    }

    private function excludeComponents()
    {
        $option = JRequest::getWord('option');
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

    private function excludeUrls()
    {
        $url = JFactory::getURI()->toString();
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

}

/*
 *  @Copyright
 *  @package     LLFJ - Lazy Load for Joomla!
 *  @author      Viktor Vogel {@link http://www.kubik-rubik.de}
 *  @version     3-7 - 2014-10-02
 *  @link        http://joomla-extensions.kubik-rubik.de/llfj-lazy-load-for-joomla
 *
 * Script: LazyLoad
 * Script by: David Walsh (http://davidwalsh.name)
 * Version: 2.2
 * License: MIT-style license
 * Website: http://davidwalsh.name/lazyload-plugin
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

var LazyLoad=new Class({Implements:[Options,Events],options:{range:200,elements:"img",container:window,mode:"vertical",realSrcAttribute:"data-src",useFade:true},initialize:function(e){this.setOptions(e);this.container=document.id(this.options.container);this.elements=document.id(this.container==window?document.body:this.container).getElements(this.options.elements);this.largestPosition=0;var t=this.options.mode=="vertical"?"y":"x";var n=this.container!=window&&this.container!=document.body?this.container:"";this.elements=this.elements.filter(function(e){if(this.options.useFade)e.setStyle("opacity",0);var r=e.getPosition(n)[t];if(r<this.container.getSize()[t]+this.options.range){this.loadImage(e);return false}return true},this);var r=function(e){var i=this.container.getScroll()[t];if(i>this.largestPosition){this.elements=this.elements.filter(function(e){if(i+this.options.range+this.container.getSize()[t]>=e.getPosition(n)[t]){this.loadImage(e);return false}return true},this);this.largestPosition=i}this.fireEvent("scroll");if(!this.elements.length){this.container.removeEvent("scroll",r);this.fireEvent("complete")}}.bind(this);this.container.addEvent("scroll",r)},loadImage:function(e){if(this.options.useFade){e.addEvent("load",function(){e.fade(1)})}e.set("src",e.get(this.options.realSrcAttribute));this.fireEvent("load",[e])}})
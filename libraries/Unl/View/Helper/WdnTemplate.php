<?php

/**
 * This view helper can be used to create a layout view script based on the WDN Template.
 */
class Unl_View_Helper_WdnTemplate extends Zend_View_Helper_Abstract
{
    /**
     * Using the following custom layout attributes, builds a WDN Template Page.
     * template: Which WDN template to use (default: Fixed).
     * siteTitle: The title of the site.
     * pageTitle: The title of the current page.
     * siteAbbreviation: An abbreviated version of the site title.
     * navLinks: An array of "link arrays" use to build the site's navigation.
     * intermediateBreadcrumbs: An array of "link arrays" used to build breadcrumbs between "UNL" and your site.
     * breadcrumbs: An array of "link arrays" used to build the breadcrumbs following your site.
     * leftColLinks: An array of "link arrays" used to populate "Related Links".
     * contactInfo: HTML that will be placed in "Contact Us".
     * footerContent: HTML that will be placed in the footer.
     * googleAnalyticsId: A Google Analytics ID that will be used to track your site.
     * loginPath: The path to the login action, with a leading slash.
     * logoutPath: The path to the logout action, with a leading slash.
     * 
     * @return string
     */
	public function WdnTemplate()
	{
	    $layout = $this->view->layout();
        $this->view->doctype('XHTML1_TRANSITIONAL');
        
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        
        $staticBaseUrl = '';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $staticBaseUrl = 'https://';
        } else {
            $staticBaseUrl = 'http://';
        }
        $staticBaseUrl .= $_SERVER['HTTP_HOST']
                        . $baseUrl . '/';
        
        if (!$layout->siteTitle) {
            $layout->siteTitle = 'Site';
        }
        
        if (!$layout->pageTitle) {
            $layout->pageTitle = '';
        }
        
        $this->view->headLink(array('rel' => 'home',
                                    'href' => $staticBaseUrl,
                                    'title' => $layout->siteTitle));

        if (!is_array($layout->bodyClasses)) {
            $layout->bodyClasses = array();
        }

        require_once 'UNL/Templates.php';
        require_once 'UNL/Templates/CachingService/Null.php';
        UNL_Templates::setCachingService(new UNL_Templates_CachingService_Null());
        UNL_Templates::$options['version'] = UNL_Templates::VERSION3x1;

        $config = Unl_Application::getOptions();
        if (isset($config['unl']['templates']['options']) && is_array($config['unl']['templates']['options'])) {
            UNL_Templates::$options = array_merge(UNL_Templates::$options, $config['unl']['templates']['options']);
        }
        
        if (!$layout->template) {
            if (UNL_Templates::$options['version'] == UNL_Templates::VERSION3x1) {
                $layout->template = 'Local';
            } else {
                $layout->template = 'Fixed';
            }
        }
        
        $template = UNL_Templates::factory($layout->template, array('sharedcodepath' => 'sharedcode'));
        
        if (in_array(UNL_Templates::$options['version'], array(UNL_Templates::VERSION3x1, '3x1'))) {
            $template->titlegraphic = $layout->siteTitle;
            $template->pagetitle = '<h1>' . $layout->pageTitle . '</h1>';
        } else {
            $template->titlegraphic = '<h1>' . $layout->siteTitle . '</h1>';
            $template->pagetitle = '<h2>' . $layout->pageTitle . '</h2>';
        }
        
        $template->navlinks = $this->_processLinks($layout->navLinks); 
        $template->maincontentarea = $layout->content . "\n";
        $template->head .= "\n" . $this->view->headLink()->__toString()
        			     . "\n" . $this->view->headMeta()->__toString()
        			     . "\n" . $this->view->headScript()->__toString()
        			     . "\n" . $this->view->headStyle()->__toString()
        			     . '<script type="text/javascript">'
        			     . "WDN.jQuery('html').data('baseUrl', '" . $this->view->baseUrl() . "');"
        			     . "WDN.jQuery(function() {WDN.jQuery('body').data('baseUrl', '" . $this->view->baseUrl() . "');});"
        			     . '</script>';
        $template->loadSharedCodeFiles();
        
        
        // Assemble Breadcrumbs and HTML Title
        $breadcrumbs = array();
        $htmlTitle = array();
        $breadcrumbs[] = array('text' => 'UNL', 'href' => 'http://www.unl.edu/');
        $htmlTitle[] = 'UNL';
        if (is_array($layout->intermediateBreadcrumbs)) {
            foreach ($layout->intermediateBreadcrumbs as $breadcrumb) {
                $breadcrumbs[] = $breadcrumb;
                $htmlTitle[] = $breadcrumb['text'];
            }
        }
        if ($layout->siteAbbreviation && ($layout->pageTitle || $layout->breadcrumbs)) {
    		$breadcrumbs[] = array('text' => $layout->siteAbbreviation, 'href' => $staticBaseUrl);
    		$htmlTitle[] = $layout->siteAbbreviation;
        } else {
    		$breadcrumbs[] = array('text' => $layout->siteTitle, 'href' => $staticBaseUrl);
    		$htmlTitle[] = $layout->siteTitle;
        }
		
        if (is_array($layout->breadcrumbs)) {
            foreach ($layout->breadcrumbs as $breadcrumb) {
                $breadcrumbs[] = $breadcrumb;
            }
        }
        
        if ($layout->pageTitle) {
            $breadcrumbs[] = array('text' => $layout->pageTitle);
            $htmlTitle[] = $layout->pageTitle; 
        }
        
        $template->breadcrumbs = $this->_processLinks($breadcrumbs);
        
        if (in_array(UNL_Templates::$options['version'], array(UNL_Templates::VERSION3x1, '3x1'))) {
            $template->doctitle = '<title>' . implode(' | ', array_reverse($htmlTitle)) . '</title>';
        } else {
            $template->doctitle = '<title>' . implode(' | ', $htmlTitle) . '</title>';
        }
        
        if (!$layout->leftColLinks) {
            $layout->leftColLinks = array();
        }
        $template->leftcollinks = '<h3>Related Links</h3>' . PHP_EOL;
        if ($layout->leftColLinks) {
            $template->leftcollinks .= $this->_processLinks($layout->leftColLinks);
        }
        
        $contactUs = $layout->contactInfo;
        $template->contactinfo = <<<EOF
<h3>Contact Us</h3>
<p>
	$contactUs
</p>

EOF;
        
        $template->footercontent = $layout->footerContent . PHP_EOL
								 . '<script type="text/javascript">' . PHP_EOL 
                                 . (isset($layout->loginPath) ? "WDN.idm.setLoginURL('{$baseUrl}{$layout->loginPath}');" . PHP_EOL : '')
                                 . (isset($layout->logoutPath) ? "WDN.idm.setLogoutURL('{$baseUrl}{$layout->logoutPath}');" . PHP_EOL : '');

        if ($layout->googleAnalyticsId) {
            $gaId = $layout->googleAnalyticsId;
            $template->footercontent .= <<<EOF
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '$gaId']); //replace with your unique tracker id
_gaq.push(['_setDomainName', '.unl.edu']);
_gaq.push(['_setAllowLinker', true]);
_gaq.push(['_setAllowHash', false]);
_gaq.push(['_trackPageview']);

EOF;
        }
        $template->footercontent .= '</script>' . PHP_EOL;

        foreach ($layout->bodyClasses as $bodyClass) {
            $template->__params['class']['value'] .= " $bodyClass";
        }

        $html = $template->toHtml();
        return $html;
	}
	
	/**
	 * Transforms an array of "link arrays" into an HTML unordered list.
	 * A "link array" has the following keys
	 *   text (required): The text for the link.
	 *   href: The target URL for the link.  
	 *   childen: an array of link arrays.
	 * @param array $links
	 * @return string
	 */
	protected function _processLinks($links)
	{
	    if (!$links || !is_array($links)) {
	        return;
	    }
	    $html = '<ul>' . PHP_EOL;
	    foreach ($links as $link) {
	        if (is_array($link)) {
    	        $html .= '<li>'
                       . (isset($link['href']) ? '<a href="' . $this->_processUrl($link['href']) . '">' : '')
                       . $link['text']
                       . (isset($link['href']) ? '</a>' : '');
                if (isset($link['children'])) {
                    $html .= PHP_EOL . $this->_processLinks($link['children']);
                }
    			$html .= '</li>' . PHP_EOL;
	        } else {
	            $html .= '<li>'
	                   . $link
	                   . '</li>' . PHP_EOL;
	        }
	    }
	    $html .= '</ul>' . PHP_EOL;

	    return $html;
	}
	
	/**
	 * Transforms path-relative URLs into site-relative URLs.
	 * All other URLs are returned unmodified.
	 * @param string $url
	 * @return string
	 */
	protected function _processUrl($url)
	{
	    $parts = parse_url($url);
	    if (isset($parts['host'])) {
	        return $url;
	    }
	    
	    if (substr($parts['path'], 0, 1) == '/') {
	        return $url;
	    }
	    
	    return $this->view->baseUrl($url);
	}
}

<?php
/***************************************************************************
 *
 * Plugin Name:  NC State Branding Bar
 * Plugin URI:   http://ot.ncsu.edu
 * Description:  Creates an NC State Branding bar at the top of whatever WP theme you select.
 * Version:      1.0.3
 * Author:       OIT Outreach Technology
 * Author URI:   http://ot.ncsu.edu
 **************************************************************************/

/**
 * Set the library as part of the include path
 */

define( 'NCSUBRANDBAR_PATH', plugin_dir_path(__FILE__) );

/**
 * Create the Ncstate Branding Bar plugin
 */
class NcstateBrandingBar
{
    /**
     * Object for Ncstate_Brand_Bar
     *
     * @var Ncstate_Brand_Bar|null
     */
    protected $_bb = null;

    /**
     * Flag to display bar or not
     *
     * @var boolean
     */
    protected $_display = false;

    /**
     * Tag to prepend the branding bar code to.  Use jQuery CSS selectors
     * so "div#page" or "body".
     *
     * @var string
     */
    protected $_position = 'body';

    /**
     * Constructor
     */
    public function __construct()
    {
        // Use the Ncstate_Brand_Bar class
        require_once NCSUBRANDBAR_PATH . 'library/Ncstate/Brand/Bar.php';
        $this->_bb = new Ncstate_Brand_Bar();

        // Load the settings
        $options = get_option('ncstate-branding-bar');
        if (!is_array($options)) {
            $options = array();
        }

        // Set display option
        if (isset($options['display'])) {
            $this->_display = $options['display'];
            unset($options['display']);
        }

        // Set position option
        if (isset($options['position'])) {
            $this->_position = $options['position'];
            unset($options['position']);
        }

        $this->_bb->setOptions($options);

        // Register WP hooks
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_post_ncstate-branding-bar', array($this, 'formSubmit'));

        // Register the bar to display if setting is enabled
        if ($this->_display) {
            add_action('wp_footer', array($this, 'outputBar'));
            wp_register_style('ncstate-branding-bar', $this->_bb->getStylesheetUrl());
            wp_enqueue_style('ncstate-branding-bar');
            wp_enqueue_script('jquery');
        }
    }
	
	/* Code for pulling rendered html and css from web comm brand bar and pulling out what we need */
	
	public function getLogoOnly() {
		$url = $this->_bb->getIframeHtml();
		$frameContents = file_get_contents($url);
		$frameContentsXml = simplexml_load_string($frameContents);
		$linkArray = $frameContentsXml->head->link->attributes();
		$urlPrefix = substr($url, 0, strpos($url, "index.php"));
		
		$css = $this->parseCSS($urlPrefix . $linkArray['href'][0]);
		 
		$backgroundValue = $css['div#utility_bar div.logo']['background'];
		$backgroundValueClean = substr($backgroundValue, 0, strpos($backgroundValue, ")"));
		$backgroundValueClean = strstr($backgroundValueClean, '/');
		 
		$logoPath = $urlPrefix . $backgroundValueClean;	
		
		return $logoPath;	
	}
	
	public function parseCSS($file){
		$css = file_get_contents($file);
		preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $css, $arr);
		$result = array();
		foreach ($arr[0] as $i => $x){
			$selector = trim($arr[1][$i]);
			$rules = explode(';', trim($arr[2][$i]));
			$rules_arr = array();
			foreach ($rules as $strRule){
				if (!empty($strRule)){
					$rule = explode(":", $strRule);
					$rules_arr[trim($rule[0])] = trim($rule[1]);
				}
			}
	 
			$selectors = explode(',', trim($selector));
			foreach ($selectors as $strSel){
				$result[$strSel] = $rules_arr;
			}
		}
		return $result;
	}

    /**
     * Creates an admin menu item in the settings list
     *
     */
    public function addAdminMenu() {
        add_submenu_page(
            'options-general.php',
            __('NC State Branding Bar', 'ncstate-branding-bar'),
            __('NC State Branding Bar', 'ncstate-branding-bar'),
            'edit_plugins',
            'ncstate-branding-bar',
            array($this, 'settingsPage')
        );
    }

    /**
     * Handles the submission of the form, then redirects back to
     * plugin configuration page.
     *
     */
    public function formSubmit() {
        
        check_admin_referer('ncstate-branding-bar');

        $options = get_option('ncstate-branding-bar');
        if (!is_array($options)) {
            $options = array();
        }

        $options = array(
            'siteUrl'        => $_POST['nbb_siteUrl'],
            'color'          => $_POST['nbb_color'],
            'centered'       => (bool)$_POST['nbb_centered'],
            'secure'         => (bool)$_POST['nbb_secure'],
            'display'        => (bool)$_POST['nbb_display'],
            'noIframePrompt' => stripslashes($_POST['nbb_noIframePrompt']),
            'position'       => $_POST['nbb_position']
        );

        update_option('ncstate-branding-bar', $options);

        wp_safe_redirect(add_query_arg('updated', 'true', wp_get_referer()));
    }

    /**
     * Displays the form for configuring the bar.
     *
     * @uses form.phtml
     */
    public function settingsPage() {
        $options = $this->_bb->getOptions();

        $colorOptions = $this->_bb->getColorOptions();

        require_once NCSUBRANDBAR_PATH . 'library/Ncstate/Version.php';
        
        require_once NCSUBRANDBAR_PATH . 'form.phtml';
    }

    /**
     * Outputs the HTML for the branding bar.
     *
     */
   public function outputBar()
    {
		
		echo '<style type="text/css">
            #ncstate-branding-bar-container{
                display:none;
            }
            #ncstate-branding-bar-container h2 {
                position: absolute;
                left: -10000px;
            }
            .ncstate-branding-bar-off-screen{
                left:-999px;
                position:absolute;
                top:auto;
                width:1px;
                height:1px;
                overflow:scroll;
                z-index:-999;
            }
			#ncstate-responsive-branding-bar {
				width:100%;}
			
            @media only screen and (min-width: 761px){
                #ncstate-branding-bar-container {
                    padding: 0px;
                    line-height: 0px;
                    margin: 0px;
                    display: block;
                }
                #ncstate-branding-bar-container h2 {
                    position: absolute;
                    left: -10000px;
                }
                #ncstate-responsive-branding-bar{
                    display:none;
                }
                #ncstate-branding-bar-container{
                    display:block;
                }
            }
            </style>';

        echo '<script type="text/javascript">
            jQuery("document").ready(function() {
                jQuery("' . $this->_position . '").prepend(jQuery("#ncstate-branding-bar-container"));
                jQuery("' . $this->_position . '").prepend(jQuery("#ncstate-responsive-branding-bar"));
            });
            </script>
        ';


        echo '<div id="ncstate-responsive-branding-bar">';
		echo '</div>';
        echo '<div id="ncstate-branding-bar-container">';
        echo '   <h2>NC State Branding Bar</h2>';
		//echo '<img src="' . $this->getLogoOnly() . '" />';
        echo $this->_bb->getIframeHtml();
        echo '</div>';
    }
	
	
}

// Start this plugin
add_action(
    'plugins_loaded',
    create_function('', '$ncstateBrandingBar = new NcstateBrandingBar();'),
    15
);
<?php

namespace Laposta\SignupEmbed;

use Laposta\SignupEmbed\Container\Container;
use Laposta\SignupEmbed\Service\AdminMenu;
use Laposta\SignupEmbed\Service\RequestHelper;

class Plugin
{
    const SHORTCODE_RENDER_FORM = 'laposta_signup_embed_form';
    const SLUG_SETTINGS = 'laposta_signup_embed_settings';

    const TRANSIENT_LISTS = 'laposta_lists';
    const TRANSIENT_STATUS = 'laposta_status';

    const OPTION_GROUP = 'laposta_signup_embed';
    const OPTION_API_KEY = 'laposta-api_key';
    const OPTION_LISTS_SETTINGS = 'laposta_signup_embed_lists_settings';

	const DEFAULT_CAPABILITY = 'manage_options';
	const FILTER_MENU_POSITION = 'laposta_signup_embed_menu_position';
    const FILTER_SETTINGS_PAGE_CAPABILITY = 'laposta_signup_embed_settings_page_capability';

    /**
     * @var Container
     */
    protected $c;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $rootUrl;

    /**
     * @var string
     */
    protected $pluginBaseName;

	/**
	 * @var string
	 */
	protected $name;


	public function __construct(Container $container)
    {
        $this->c = $container;

        $this->rootDir = realpath(__DIR__.'/..');
        $this->rootUrl = plugin_dir_url($this->rootDir.'/laposta-signup-embed.php');
        $this->pluginBaseName = plugin_basename($this->rootDir.'/laposta-signup-embed.php');
		$this->name = 'Laposta Signup Embed';

        $this->defineConstants();
        $this->init();
    }

    protected function defineConstants()
    {
        define('LAPOSTA_SIGNUP_EMBED_ROOT_DIR', $this->rootDir);
        define('LAPOSTA_SIGNUP_EMBED_TEMPLATE_DIR', $this->rootDir.DIRECTORY_SEPARATOR.'templates');
        define('LAPOSTA_SIGNUP_EMBED_ASSETS_URL', $this->rootUrl.'assets');
        define('LAPOSTA_SIGNUP_EMBED_AJAX_ACTION', 'laposta_signup_embed_ajax');
        define('LAPOSTA_SIGNUP_EMBED_AJAX_URL', admin_url('admin-ajax.php').'?action='.LAPOSTA_SIGNUP_EMBED_AJAX_ACTION);
        define('LAPOSTA_SIGNUP_EMBED_ASSETS_VERSION', LAPOSTA_SIGNUP_EMBED_VERSION);

    }

    public function init()
    {
        if (is_admin()) {
            add_action('admin_init', [$this, 'adminInit']);
            add_filter("plugin_action_links_{$this->pluginBaseName}", [$this, 'setPluginActionLinks']);
			new AdminMenu($this->c, $this->rootUrl, $this->name, 'Laposta Embed');
        } else {
            add_action('wp_head', [$this->c->getFormController(), 'addToEveryPage'], 99);
            add_shortcode(self::SHORTCODE_RENDER_FORM, [$this->c->getFormController(), 'renderFormByShortcode']);
        }

        $this->addAjaxRoutes();
    }

    public function adminInit()
    {
        register_setting(self::OPTION_GROUP, self::OPTION_API_KEY);
        register_setting(self::OPTION_GROUP, self::OPTION_LISTS_SETTINGS);
    }

    public function setPluginActionLinks($links) {
        $settingsLink = '<a href="options-general.php?page='.self::SLUG_SETTINGS.'">Settings</a>';
        array_unshift($links, $settingsLink);
        return $links;
    }

    public function addAjaxRoutes()
    {
        add_action("wp_ajax_".LAPOSTA_SIGNUP_EMBED_AJAX_ACTION, [$this, 'handleAjaxRequest']);
        add_action("wp_ajax_nopriv_".LAPOSTA_SIGNUP_EMBED_AJAX_ACTION, [$this, 'handleAjaxRequest']);
    }

    public function handleAjaxRequest()
    {
        $route = isset($_GET['route']) ? sanitize_key($_GET['route']) : null;
        if (!$route) {
            die();
        }

        $actualCapability = apply_filters(self::FILTER_SETTINGS_PAGE_CAPABILITY, self::DEFAULT_CAPABILITY);
        $actualCapability = is_string($actualCapability) ? $actualCapability : self::DEFAULT_CAPABILITY;
        switch ($route) {
            case 'settings_reset_cache':
                if (user_can(wp_get_current_user(), $actualCapability)) {
                    return $this->c->getSettingsController()->ajaxResetCache();
                }
                break;
        }

        RequestHelper::returnJson(['status' => 'error', 'message' => 'Route error'], 400);
    }

    /**
     * @return Container
     */
    public function getC(): Container
    {
        return $this->c;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * @return string
     */
    public function getRootUrl(): string
    {
        return $this->rootUrl;
    }

    /**
     * @return string
     */
    public function getPluginBaseName(): string
    {
        return $this->pluginBaseName;
    }

}

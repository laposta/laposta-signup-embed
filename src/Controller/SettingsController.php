<?php

namespace Laposta\SignupEmbed\Controller;

use Laposta\SignupEmbed\Container\Container;
use Laposta\SignupEmbed\Plugin;

class SettingsController extends BaseController
{

    /**
     * @var Container
     */
    protected $c;

    public function __construct(Container $container)
    {
        $this->c = $container;
        $this->init();
    }

    public function init()
    {
        add_action('update_option_'.Plugin::OPTION_API_KEY, [&$this, 'afterApiKeyUpdate'], 10, 3);
    }

    /**
     * @param mixed $oldValue
     * @param mixed $value
     * @param string $optionName
     */
    public function afterApiKeyUpdate($oldValue, $value, $optionName)
    {
        $this->c->getDataService()->emptyAllCache();
    }

    public function renderSettings()
    {
        $dataService = $this->c->getDataService();

        $apiKey = $dataService->getApiKey();
        $lists = $apiKey ? $dataService->getLists() : [];
        $status = $apiKey ? $dataService->getStatus() : null;

        $this->addAssets();
        $this->showTemplate('/settings/settings.php', [
            'optionGroup' => Plugin::OPTION_GROUP,
            'apiKey' => $apiKey,
            'lists' => $lists,
            'status' => $status,
            'statusMessage' => $dataService->getStatusMessage(),
            'refreshCacheUrl' => LAPOSTA_SIGNUP_EMBED_AJAX_URL.'&route=settings_reset_cache',
        ]);
    }

    public function ajaxResetCache()
    {
        $dataService = $this->c->getDataService();
        $dataService->emptyAllCache();
    }

    public function addAssets()
    {
        wp_enqueue_style('laposta-signup-embed.lse-settings', LAPOSTA_SIGNUP_EMBED_ASSETS_URL.'/css/lse-settings.css', [], LAPOSTA_SIGNUP_EMBED_ASSETS_VERSION);
        wp_enqueue_script('laposta-signup-embed.lse-settings.LseSettings', LAPOSTA_SIGNUP_EMBED_ASSETS_URL.'/js/lse-settings/LseSettings.js', [], LAPOSTA_SIGNUP_EMBED_ASSETS_VERSION, true);
        wp_enqueue_script('laposta-signup-embed.lse-settings.main', LAPOSTA_SIGNUP_EMBED_ASSETS_URL.'/js/lse-settings/main.js', [], LAPOSTA_SIGNUP_EMBED_ASSETS_VERSION, true);
    }

    public function getTemplateDir()
    {
        return LAPOSTA_SIGNUP_EMBED_TEMPLATE_DIR;
    }
}
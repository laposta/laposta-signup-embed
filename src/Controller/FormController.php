<?php

namespace Laposta\SignupEmbed\Controller;

use Laposta\SignupEmbed\Container\Container;
use Laposta\SignupEmbed\Plugin;
use Laposta\SignupEmbed\Service\DataService;

class FormController extends BaseController
{
    /**
     * @var Container
     */
    protected $c;

    public function __construct(Container $container)
    {
        $this->c = $container;
    }

    public function renderFormByShortcode($atts = [])
    {
        $dataService = $this->c->getDataService();

        if (!$atts || !isset($atts['list_id'])) {
            return $this->getRenderedTemplate('/form/form-error.php', [
                'errorMessage' => 'list_id ontbreekt',
            ]);
        }

        $listId = sanitize_text_field($atts['list_id']);
        $list = $dataService->getListById($listId);
        if (!$list) {
            return $this->getRenderedTemplate('/form/form-error.php', [
                'errorMessage' => 'list_id kon niet worden opgehaald.',
            ]);
        }

        $listsSettings = $this->getListSettings();
        $listSetting = $listsSettings[$listId] ?? null;
        if ($listSetting && (!isset($listSetting['showOption']) || $listSetting['showOption'] === DataService::SHOW_OPTION_NEVER)) {
            return '';
        }

        $html = $this->getRenderedTemplate('/form/form.php', [
            'listId' => $listId,
            'accountId' => sanitize_text_field($list['account_id']),
        ]);

        return "<div class='lse-wrapper'>$html</div>";
    }

    public function addToEveryPage()
    {
        $dataService = $this->c->getDataService();

        $listsSettings = get_option(Plugin::OPTION_LISTS_SETTINGS);
        $listsSettings = $this->decodeListSettings($listsSettings);
        if (!$listsSettings) {
            return;
        }

        $html = '';
        foreach ($listsSettings as $listId => $listSettings) {
            if ($listSettings['showOption'] !== DataService::SHOW_OPTION_ALWAYS) {
                continue;
            }

            $list = $dataService->getListById($listId);
            if (!$list) {
                continue;
            }

            $html .= $this->getRenderedTemplate('/form/form.php', [
                'listId' => $listId,
                'accountId' => sanitize_text_field($list['account_id']),
            ]);
        }

        echo wp_kses($html, [
            'script' => [
                'async' => [],
                'src' => [],
            ]
        ]);
    }

    public function getTemplateDir()
    {
        return LAPOSTA_SIGNUP_EMBED_TEMPLATE_DIR;
    }

    protected function getListSettings(): array
    {
        $listsSettings = get_option(Plugin::OPTION_LISTS_SETTINGS);

        return $this->decodeListSettings($listsSettings) ?: [];
    }

    protected function decodeListSettings($listsSettings): ?array
    {
        if (!$listsSettings) {
            return null;
        }

        try {
            $decoded = json_decode($listsSettings, true);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}

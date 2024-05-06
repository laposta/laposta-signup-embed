<?php

namespace Laposta\SignupEmbed\Service;

use Laposta\SignupEmbed\Plugin;
use Laposta\SignupEmbed\Container\Container;
use Laposta_List;

class DataService
{
    const STATUS_NO_CURL = 'no_curl';
    const STATUS_NO_API_KEY = 'no_api_key';
    const STATUS_INVALID_API_KEY = 'invalid_api_key';
    const STATUS_NO_LISTS = 'no_lists';
    const STATUS_INVALID_REQUEST = 'invalid_request';
    const STATUS_OK = 'ok';

    const SHOW_OPTION_NEVER = 'never';
    const SHOW_OPTION_ALWAYS = 'always';
    const SHOW_OPTION_SHORTCODE = 'shortcode';

    /**
     * @var Container
     */
    protected $c;

    /**
     * @var array|null
     */
    protected $lists;

    /**
     * @var string|null
     */
    protected $status;

    /**
     * @var int
     */
    protected $cacheDuration = 60 * 60 * 24 * 365;

    public function __construct(Container $container)
    {
        $this->c = $container;
    }

    /**
     * Only init library if asked for
     * @return bool
     */
    public function initLaposta(): bool
    {
        if ($this->getApiKey()) {
            $this->c->initLaposta();
        }

        return class_exists('\\Laposta');
    }

    public function getApiKey(): ?string
    {
        return get_option(Plugin::OPTION_API_KEY, null);
    }

    /**
     * Get the status. Note that this should be always called AFTER ::getLists.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        if (!function_exists('curl_init')) {
            // we just check this actively, because caching this causes a bit of a pain
            return self::STATUS_NO_CURL;
        }

        if ($this->status) {
            return $this->status;
        }

        $this->status = get_transient(Plugin::TRANSIENT_STATUS);

        return $this->status ?: null;
    }

    public function setStatus(?string $status)
    {
        $this->status = $status;
        set_transient(Plugin::TRANSIENT_STATUS, $status, $this->cacheDuration);
    }

    /**
     * Get the status message. Note that this should be always called AFTER ::getLists.
     *
     * @return string|null
     */
    public function getStatusMessage()
    {
        if (!$this->getStatus()) {
            return '';
        }

        switch ($this->getStatus()) {
            case self::STATUS_NO_API_KEY:
                return 'Nog geen api-key ingevuld.';
            case self::STATUS_NO_CURL:
                return 'Deze plugin heeft de php-curl extensie nodig, maar deze is niet geinstalleerd.';
            case self::STATUS_INVALID_API_KEY:
                return 'Dit is geen geldige api-key.';
            case self::STATUS_NO_LISTS:
                return 'Geen lijsten gevonden.';
            default:
                return 'Onbekende fout';
        }
    }

    public function getLists(): ?array
    {
        if ($this->lists) {
            return $this->lists;
        }

        $this->lists = get_transient(Plugin::TRANSIENT_LISTS);
        if ($this->lists) {
            return $this->lists;
        }

        if ($this->getStatus()) {
            // attempted to fetch lists with api key already, but no list received. Note that cache is cleared after a new api key has been submitted
            return null;
        }

        if (!$this->getApiKey()) {
            $this->setStatus(self::STATUS_NO_API_KEY);

            return null;
        }

        if (!$this->initLaposta()) {
            // failed to init laposta
            return null;
        }

        $lapostaList = new Laposta_List();
        try {
            $result = $lapostaList->all();
            if (!$result['data']) {
                $this->setStatus(self::STATUS_NO_LISTS);
            } else {
                $items = $result['data'];
                $this->lists = [];
                foreach ($items as $item) {
                    $this->lists[] = $item['list'];
                }
                set_transient(Plugin::TRANSIENT_LISTS, $this->lists, $this->cacheDuration);
                $this->setStatus(self::STATUS_OK);

                return $this->lists;
            }
        } catch (\Throwable $e) {
            $error = @$e->json_body['error'];
            if ($error) {
                if ($error['type'] === 'invalid_request') {
                    $this->setStatus(self::STATUS_INVALID_API_KEY);
                }
            }
            if (!$this->status) {
                $this->setStatus('error-api: '.print_r($e, 1));
            }
        }

        return null;
    }

    public function getListById(string $listId): ?array
    {
        foreach($this->getLists() as $list) {
            if ($list['list_id'] === $listId) {
                return $list;
            }
        }

        return null;
    }

    public function emptyAllCache()
    {
        delete_transient(Plugin::TRANSIENT_LISTS);
        delete_transient(Plugin::TRANSIENT_STATUS);
    }

    /**
     * @return float|int
     */
    public function getCacheDuration()
    {
        return $this->cacheDuration;
    }

    /**
     * @param float|int $cacheDuration
     */
    public function setCacheDuration($cacheDuration): void
    {
        $this->cacheDuration = $cacheDuration;
    }
}
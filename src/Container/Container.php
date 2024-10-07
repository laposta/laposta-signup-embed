<?php

namespace Laposta\SignupEmbed\Container;

use Laposta\SignupEmbed\Controller\FormController;
use Laposta\SignupEmbed\Controller\SettingsController;
use Laposta\SignupEmbed\Plugin;
use Laposta\SignupEmbed\Service\DataService;

class Container
{
    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @var SettingsController
     */
    protected $settingsController;

    /**
     * @var FormController
     */
    protected $formController;

    /**
     * @var DataService
     */
    protected $dataService;

    public function getPlugin()
    {
        if (!class_exists('Laposta\\SignupEmbed\\Plugin')) {
			$this->requireAdminMenu();
            require_once realpath(__DIR__.'/..').'/Plugin.php';
            $this->plugin = new Plugin($this);
        }

        return $this->plugin;
    }

    public function initLaposta()
    {
        if (!class_exists('\\Laposta')) {
            require_once realpath(__DIR__.'/../../includes/laposta-api-php-1.6/lib/').'/Laposta.php';
        }
        \Laposta::setApiKey($this->getDataService()->getApiKey());
    }

    public function getDataService()
    {
        if (!class_exists('Laposta\\SignupEmbed\\Service\\DataService')) {
            require_once realpath(__DIR__.'/../Service').'/DataService.php';
            $this->dataService = new DataService($this);
        }

        return $this->dataService;
    }

	public function requireAdminMenu()
	{
		if (!class_exists('Laposta\\SignupEmbed\\Service\\AdminMenu')) {
			require_once realpath(__DIR__.'/../Service').'/AdminMenu.php';
		}
	}

	protected function requireBaseController()
    {
        if (!class_exists('Laposta\\SignupEmbed\\Controller\\BaseController')) {
            require_once realpath(__DIR__.'/../Controller').'/BaseController.php';
        }
    }

    public function getSettingsController()
    {
        if (!class_exists('Laposta\\SignupEmbed\\Controller\\SettingsController')) {
            $this->requireBaseController();
            require_once realpath(__DIR__.'/../Controller').'/SettingsController.php';
            $this->settingsController = new SettingsController($this);
        }

        return $this->settingsController;
    }

    public function getFormController()
    {
        if (!class_exists('Laposta\\SignupEmbed\\Controller\\FormController')) {
            $this->requireBaseController();
            require_once realpath(__DIR__.'/../Controller').'/FormController.php';
            $this->formController = new FormController($this);
        }

        return $this->formController;
    }
}
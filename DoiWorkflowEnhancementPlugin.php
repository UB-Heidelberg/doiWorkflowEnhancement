<?php

/**
 * @file plugins/generic/doiWorkflowEnhancement/DoiWorkflowEnhancementPlugin.php
 */

namespace APP\plugins\generic\doiWorkflowEnhancement;


use APP\core\Application;
use APP\plugins\generic\doiWorkflowEnhancement\pages\DoiWorkflowEnhancementHandler;
use APP\template\TemplateManager;
use PKP\core\JSONMessage;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\template\PKPTemplateManager;

class DoiWorkflowEnhancementPlugin extends GenericPlugin
{
	private const PAGE_HANDLER = 'doiworkflow';
    private string $doiWorkflowUrl = '';

    /** @var string Name of the application */
    public string $application;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->application = Application::get()->getName();
    }

    /**
	 * @copydoc Plugin::getDisplayName()
	 */
	public function getDisplayName(): string|array|null
	{
		return __('plugins.generic.doiWorkflowEnhancement.title');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 */
	public function getDescription(): string|array|null
	{
		return __('plugins.generic.doiWorkflowEnhancement.description');
	}

    /**
     * @see Plugin::getActions()
     */
    public function getActions($request, $actionArgs): array
    {
        $actions = parent::getActions($request, $actionArgs);

        if (!$this->getEnabled() ) {
            return $actions;
        }

        $router = $request->getRouter();
        $linkAction = new LinkAction(
            'settings',
            new AjaxModal(
                $router->url(
                    $request,
                    null,
                    null,
                    'manage',
                    null,
                    [
                        'verb' => 'settings',
                        'plugin' => $this->getName(),
                        'category' => 'generic',
                    ]
                ),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        );
        array_unshift($actions, $linkAction);

        return $actions;
    }

    /**
     * @see Plugin::manage()
     */
    public function manage($args, $request): JSONMessage
    {
        if ($request->getUserVar('verb') === 'settings') {
            $form = new DoiWorkflowEnhancementSettingsForm($this);

            if ($request->getUserVar('save')) {
                $form->readInputData();
                if ($form->validate()) {
                    $form->execute();
                    return new JSONMessage(true);
                }
            }

            $form->initData();
            return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }

	/**
	 * @copydoc Plugin::register()
	 */
	public function register($category, $path, $mainContextId = null): bool
	{
        $success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled()) {
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            $this->doiWorkflowUrl = $request->getDispatcher()->url($request, PKPApplication::ROUTE_PAGE, $context->getPath(), self::PAGE_HANDLER, 'ajax');

            Hook::add('TemplateResource::getFilename', [$this, '_overridePluginTemplates']);
            Hook::add('DoiListPanel::setConfig', $this->callbackDoiListPanelConfig(...));
            Hook::add('DoisHandler::setListPanelArgs', $this->callbackDoiListPanelArgs(...));
            Hook::add('LoadHandler', $this->setPageHandler(...));

            $templateMgr = TemplateManager::getManager($request);
            if (!$this->showButtonAssignDOIs()) {
                $this->addCSS($request, $templateMgr);
            }
		}
		return $success;
	}

    public function addCSS($request, $templateMgr): void
    {
        $cssPath = $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'public/build/style.css';
        $templateMgr->addStyleSheet(
            'DoiWorkflowEnhancementStyles',
            $cssPath,
            [
                'contexts' => 'backend',
                'priority' => PKPTemplateManager::STYLE_SEQUENCE_CORE,
            ]
        );
    }

    public function callbackDoiListPanelConfig(string $hookName, array $params): bool
    {
        $request = Application::get()->getRequest();

        /** @var array $config */
        $config =& $params[0];
        $config['doiWorkflowEnhancementPluginUrl'] = $this->doiWorkflowUrl;

        // Provide required locale keys
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setLocaleKeys([
            'plugins.generic.doiWorkflowEnhancement.button.assignDoi',
        ]);

        return Hook::CONTINUE;
    }

    public function callbackDoiListPanelArgs(string $hookName, array $params): bool
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        /** @var array $config */
        $commonArgs =& $params[0];
        $commonArgs['doiWorkflowEnhancementPluginUrl'] = $this->doiWorkflowUrl;

        return Hook::CONTINUE;
    }

    /**
     * Get setting for bulk action assign DOIs
     */
    public function showButtonAssignDOIs(): bool
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $assignDois = $this->getSetting($context->getId(), 'assignDOIs');
        return $assignDois ? (bool) $assignDois : false;
    }

    /**
     * Route requests to custom page handler
     */
    private function setPageHandler(string $hookName, array $params): bool
    {
        $page =& $params[0];
        $handler =& $params[3];
        if ($page === self::PAGE_HANDLER) {
            $handler = new DoiWorkflowEnhancementHandler($this);
            return true;
        }
        return false;
    }
}
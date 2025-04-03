<?php
/**
 * @file plugins/generic/doiWorkflowEnhancement/DoiWorkflowEnhancementSettingsForm.php
 */

namespace APP\plugins\generic\doiWorkflowEnhancement;

use APP\core\Application;
use APP\notification\NotificationManager;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;
use PKP\notification\PKPNotification;

class DoiWorkflowEnhancementSettingsForm extends Form
{
    public DoiWorkflowEnhancementPlugin $plugin;

    /**
     * Constructor
     *
     * @param DoiWorkflowEnhancementPlugin $plugin object
     */
    public function __construct(DoiWorkflowEnhancementPlugin $plugin)
    {
        parent::__construct($plugin->getTemplateResource('settings.tpl'));
        $this->plugin = $plugin;
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
    * @copydoc Form::init
    */
    public function initData(): void
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();
        $this->setData('assignDOIs', $this->plugin->getSetting($contextId, 'assignDOIs'));
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData(): void
    {
        $this->readUserVars([
            'assignDOIs',
        ]);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false): ?string
    {
        $context = $request->getContext();
        $contextId = $context->getId();

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'assignDOIs' => $this->getData('assignDOIs'),
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context->getId();
        $this->plugin->updateSetting($contextId, 'assignDOIs', $this->getData('assignDOIs'));

        $notificationMgr = new NotificationManager();
        $user = $request->getUser();
        $notificationMgr->createTrivialNotification($user->getId(), PKPNotification::NOTIFICATION_TYPE_SUCCESS, ['contents' => __('common.changesSaved')]);

        return parent::execute(...$functionArgs);
    }
}

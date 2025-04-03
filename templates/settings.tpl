{**
 * plugins/generic/doiWorkflowEnhancement/templates/settings.tpl
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#doiWorkflowEnhancementSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="doiWorkflowEnhancementSettingsForm" method="post" action="{url router=PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}

	{fbvFormArea id="doiWorkflowEnhancementSettings"}
		{fbvFormSection list=true title="plugins.generic.doiWorkflowEnhancement.settings.title.bulkActions"}
			<p>{translate key="plugins.generic.doiWorkflowEnhancement.settings.title.bulkActionsDescription"}</p>
			{fbvFormSection list=true}
				{fbvElement type="checkbox" id="assignDOIs" value="1" checked=$assignDOIs label="plugins.generic.doiWorkflowEnhancement.settings.label.assignDOIs"}
			{/fbvFormSection}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons}
</form>

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
			{if $application === 'omp'}
				{fbvFormSection list=true}
					{fbvElement type="checkbox" id="assignDOIs" value="1" checked=$assignDOIs label="plugins.generic.doiWorkflowEnhancement.settings.label.assignDOIs"}
				{/fbvFormSection}
			{elseif $application === 'ojs2'}
				{fbvFormSection list=true}
					{fbvElement type="checkbox" id="assignArticleDOIs" value="1" checked=$assignArticleDOIs label="plugins.generic.doiWorkflowEnhancement.settings.label.assignArticleDOIs"}
					{fbvElement type="checkbox" id="assignIssueDOIs" value="1" checked=$assignIssueDOIs label="plugins.generic.doiWorkflowEnhancement.settings.label.assignIssueDOIs"}
				{/fbvFormSection}
			{/if}
		{/fbvFormSection}
	{/fbvFormArea}
	{fbvFormButtons}
</form>

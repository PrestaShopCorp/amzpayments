{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{if isset($postSuccess)}
	{foreach from=$postSuccess item=ps}
		<div class="alert alert-success">{$ps}</div>
	{/foreach}
{/if}

{if isset($postErrors)}
	{foreach from=$postErrors item=pe}
		<div class="alert alert-warning">{$pe}</div>
	{/foreach}
{/if}

<div class="panel" id="amzVersionChecker">
	<div class="panel-heading">
		<i class="icon-cogs"></i>
		{l s='Version-Checker'}
	</div>
	<div class="row">
		<div class="col-xs-12">
			<p style="text-align: center" id="versionCheck">
				<img src="{$smarty.const._PS_BASE_URL_}{$smarty.const.__PS_BASE_URI__}modules/{$module_name}/views/img/loading_indicator.gif" />
				<br /><br />
				{l s='We check if there is a new version of the plugin available.' mod='amzpayments'}
				<br /><br />
			</p>
			<p style="text-align: center" id="versionCheckResult">
				{l s='Your version: ' mod='amzpayments'} <strong>{$current_version}</strong>
				<br /><br />
			</p>			
		</div>
	</div>
</div>

<script language="javascript">
	{literal}
	$(document).ready(function() {
		$.post("../modules/amzpayments/ajax.php",
		{
			action: "versionCheck",
			asv: "{/literal}{$current_version}{literal}",
			psv: "{/literal}{$smarty.const._PS_VERSION_}{literal}",
			ref: location.host
		}, 
		function(data) {	
			if (data.newversion == 1) {
				$("#versionCheckResult").append("{/literal}{l s='There is a new version available: ' mod='amzpayments'}{literal}<strong>" + data.newversion_number + "</strong><br /><br /><a href=\"http://www.patworx.de/Amazon-Advanced-Payment-APIs/PrestaShop\" target=\"_blank\">&gt; Download</a>");
			} else {
				$("#versionCheckResult").append("{/literal}{l s='Everything is fine - you are using the latest version' mod='amzpayments'}{literal}");
			}
			$("#versionCheck").hide();
		}, "json"
		);
	});
	{/literal}
</script>


{$configform}

<div class="panel">
	<div class="panel-heading">
		<i class="icon-info"></i>
		{l s='URL and Configuration Infos' mod='amzpayments'}
	</div>
	<div class="row">
		<div class="col-xs-12">
			<p>
				{l s='Allowed Return URLs - Enter these URLs in your Amazon SellerCentral Configuration-Panel!' mod='amzpayments'}
			</p>
			<ul>
				<li>{$allowed_return_url_1}</li>
				<li>{$allowed_return_url_2}</li>
			</ul>
			<p>
				{l s='Allowed JavaScript Origins - Enter these URLs in your Amazon SellerCentral Configuration-Panel!' mod='amzpayments'}
			</p>
			<ul>
				<li>{$allowed_js_origins}</li>
			</ul>
			<p>
				{l s='You can integrate the "Login with Amazon"-Button at any part of your template. Just use the following HTML-Code, but be aware to always (!) use a unique value for the attribute "id":' mod='amzpayments'}
			</p>
			<code> &lt;div id=&quot;&quot; class=&quot;amazonLoginWr&quot;&gt;&lt;/div&gt; </code>
		</div>
	</div>
</div>

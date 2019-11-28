{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="payWithAmazonMainDiv"{if $hide_button} style="display:none;"{/if}>
	<div id="payWithAmazonDiv" class="{if $create_account}amz_create_account{/if} {if isset($buttonEnhancement) && $buttonEnhancement}button_enhanced{/if}">
		{if isset($buttonEnhancement) && $buttonEnhancement}
			<p>{l s='Pay securely using your Amazon account information.' mod='amzpayments'}</p>
		{/if}
	</div>
</div>
{literal}<script> bindCartButton('payWithAmazonDiv'); </script>{/literal}
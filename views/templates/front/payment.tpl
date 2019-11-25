{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
{nocache}

    <p>{l s='Please wait...' mod='amzpayments'}</p>

	<script>
        {literal}
        OffAmazonPayments.initConfirmationFlow(
            '{/literal}{$sellerId|escape:'htmlall':'UTF-8'}{literal}',
            '{/literal}{$orderReferenceId|escape:'htmlall':'UTF-8'}{literal}',
            function(confirmationFlow) {
            	{/literal}{if $isNoPSD2}{literal}
            	window.location.href = '{/literal}{$redirection|escape:'htmlall':'UTF-8'}{literal}';
            	{/literal}{else}{literal}
				confirmationFlow.success();
				{/literal}{/if}{literal}
            }
        );
        {/literal}
	</script>

{/nocache}
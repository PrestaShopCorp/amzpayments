{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
{extends file='page.tpl'}

{block name='page_content'}

    <p>{l s='Please wait...' mod='amzpayments'}</p>

	<script>
        {literal}
        OffAmazonPayments.initConfirmationFlow(
            '{/literal}{$sellerId}{literal}',
            '{/literal}{$orderReferenceId}{literal}',
            function(confirmationFlow) {
            	{/literal}{if $isNoPSD2}{literal}
            	window.location.href = '{/literal}{$redirection}{literal}';
            	{/literal}{else}{literal}
				confirmationFlow.success();
				{/literal}{/if}{literal}
            }
        );
        {/literal}
	</script>

{/block}
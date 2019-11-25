{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="payWithAmazonProductDetailDiv_{$id_product_amz_widget}"{if $hide_button} style="display:none;"{/if}>
	<div id="payWithAmazonProductDiv_{$id_product_amz_widget}" data-checkout="1">
	</div>
	{if isset($buttonEnhancement) && $buttonEnhancement}
		<div class="button_enhanced">
			<p>{l s='Pay securely using your Amazon account information' mod='amzpayments'}</p>
		</div>
		<div style="clear:both;"></div>
	{/if}
</div>
{literal}<script> bindBuyNowButton('payWithAmazonProductDiv_{/literal}{$id_product_amz_widget}{literal}'); </script>{/literal}
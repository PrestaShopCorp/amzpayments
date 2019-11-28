{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="amzOverlay"><img src="{$amz_module_path|escape:'htmlall':'UTF-8'}views/img/loading_indicator.gif" /></div>
<div class="amz_cart_widgets_summary" id="amz_cart_widgets_summary">
	{include file="$tpl_dir./shopping-cart.tpl"}
</div>

<div class="amz_widgets">
	<div id="addressBookWidgetDiv">
	</div>
	
	<div id="walletWidgetDiv">
	</div>
		
	<div id="addressMissings">		
	</div>
	<input type="button" id="submitAddress" class="exclusive" value="{l s='Save' mod='amzpayments'}" name="submitAddress" style="display: none;">
</div>
<div class="amz_cart_widgets">
	<div id="amz_carriers" style="display: none;">
		{include file="$tpl_dir./order-carrier.tpl"}
	</div>	
</div>
<div style="clear:both"></div>

<div style="float: right">
	{if $show_amazon_account_creation_allowed}
		{if $force_account_creation}
			<input type="hidden" id="connect_amz_account" value="1" name="connect_amz_account" />
		{else}
			<p class="checkbox">
				<input type="checkbox" id="connect_amz_account" value="1" name="connect_amz_account" {if $preselect_create_account}checked="checked"{/if}/>
				<label for="connect_amz_account">
					{l s='Create customer account.' mod='amzpayments'}
					<br />
					<span style="font-size: 10px;">{l s='You don\'t need to do anything. We create the account with the data of your current order.' mod='amzpayments'}</span>
				</label>
			</p>
		{/if}
	{/if}
	<input type="button" id="amz_execute_order" class="exclusive" value="{l s='Buy now' mod='amzpayments'}" name="Submit" disabled="disabled">
</div>
<div style="clear:both"></div>

{if $sandboxMode}

{/if}

{literal}
<script> 
var amazonCarrierErrorMessage = '{/literal}{l s='There are no carriers that deliver to the address you selected in your address book, please select another one.' mod='amzpayments'}{literal}';
var isFirstRun = true;
var amazonOrderReferenceId = '{/literal}{$amz_session|escape:'htmlall':'UTF-8'}{literal}';
var amzWidgetReadonly = false;	
jQuery(document).ready(function($) {
	var amzAddressSelectCounter = 0;
	
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '{/literal}{$sellerID|escape:'htmlall':'UTF-8'}{literal}',
		{/literal}{if $amz_session == ''}{literal}
		onOrderReferenceCreate: function(orderReference) {			
			 amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
             $.ajax({
                 type: 'GET',
                 url: REDIRECTAMZ,
                 data: 'allow_refresh=1&ajax=true&method=setsession&amazon_id=' + orderReference.getAmazonOrderReferenceId(),
                 success: function(htmlcontent){
                	 
                 }
        	});
		},
        {/literal}{/if}{literal}
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session|escape:'htmlall':'UTF-8'}{literal}', {/literal}{/if}{literal}
		onAddressSelect: function(orderReference) {
			if (isFirstRun) {
				setTimeout(function() { 
					$("#carrier_area").hide();
					updateAddressSelection(amazonOrderReferenceId); 
					isFirstRun = false; 
					setTimeout(function() {
						updateAddressSelection(amazonOrderReferenceId);
						$("#carrier_area").fadeIn();
					}, 1000); 
				}, 1000);
			} else {
				updateAddressSelection(amazonOrderReferenceId);		
			}
		},
		design: {
			designMode: 'responsive'
		},
		{/literal}{if $trigger_payment_change}{literal}
		displayMode: "Read",		
		{/literal}{/if}{literal}
		onError: function(error) {
			console.log(error.getErrorCode());
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDiv");
	
	walletWidget = new OffAmazonPayments.Widgets.Wallet({
		sellerId: '{/literal}{$sellerID|escape:'htmlall':'UTF-8'}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session|escape:'htmlall':'UTF-8'}{literal}', {/literal}{/if}{literal}
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect: function(orderReference) {
		},
		onError: function(error) {
			console.log(error.getErrorMessage());
		}
	});
	walletWidget.setPresentmentCurrency("{/literal}{$currency->iso_code|escape:'htmlall':'UTF-8'}{literal}");
	walletWidget.bind("walletWidgetDiv");	
	
});

function reCreateWalletWidget() {
	$("#walletWidgetDiv").html('');
	walletWidget = new OffAmazonPayments.Widgets.Wallet({
		sellerId: '{/literal}{$sellerID|escape:'htmlall':'UTF-8'}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session|escape:'htmlall':'UTF-8'}{literal}', {/literal}{/if}{literal}
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect: function(orderReference) {
			$("#cgv").trigger('change');
		},
		onError: function(error) {
			console.log(error.getErrorMessage());
		}
	});
	walletWidget.setPresentmentCurrency("{/literal}{$currency->iso_code|escape:'htmlall':'UTF-8'}{literal}");
	walletWidget.bind("walletWidgetDiv");		
}
function reCreateAddressBookWidget() {
	$("#addressBookWidgetDiv").html('');
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '{/literal}{$sellerID|escape:'htmlall':'UTF-8'}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session|escape:'htmlall':'UTF-8'}{literal}', {/literal}{/if}{literal}
		onAddressSelect: function(orderReference) {
			updateAddressSelection(amazonOrderReferenceId);			
		},
		displayMode: (amzWidgetReadonly ? "Read" : "Edit"),
		design: {
			designMode: 'responsive'
		},
		onError: function(error) {		
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDiv");	
}
</script>
{/literal}
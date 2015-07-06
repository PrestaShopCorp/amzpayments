{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="amzOverlay"><img src="{$amz_module_path}views/img/loading_indicator.gif" /></div>
<div class="amz_cart_widgets_summary" id="amz_cart_widgets_summary">
	{include file="$tpl_dir./shopping-cart.tpl"}
</div>

<div class="amz_widgets">
	<div id="addressBookWidgetDiv">
	</div>
	
	<div id="walletWidgetDiv">
	</div>
</div>
<div class="amz_cart_widgets">
	<div id="amz_carriers" style="display: none;">
		{include file="$tpl_dir./order-carrier.tpl"}
	</div>	
</div>
<div style="clear:both"></div>

<div style="float: right">
	{if $show_amazon_account_creation_allowed}
		<p class="checkbox">
			<input type="checkbox" id="connect_amz_account" value="1" name="connect_amz_account" />
			<label for="connect_amz_account">{l s='Amazon-Konto mit Shop verbinden' mod='amzpayments'}</label>
		</p>
	{/if}
	<input type="button" id="amz_execute_order" class="exclusive" value="{l s='Buy now' mod='amzpayments'}" name="Submit" disabled="disabled">
</div>
<div style="clear:both"></div>

{if $sandboxMode}

{/if}

{literal}
<script> 
var isFirstRun = true;
var amazonOrderReferenceId = '{/literal}{$amz_session}{literal}';	
jQuery(document).ready(function($) {
	var amzAddressSelectCounter = 0;
	
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '{/literal}{$sellerID}{literal}',
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
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
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
			size : {width:'400px', height:'260px'}
		},
		onError: function(error) {
			console.log(error.getErrorCode());
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDiv");
	
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: '{/literal}{$sellerID}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
		design: {
			size : {width:'400px', height:'260px'}
		},
		onPaymentSelect: function(orderReference) {
		},
		onError: function(error) {
			console.log(error.getErrorMessage());
		}
	}).bind("walletWidgetDiv");
	
	function reCreateWalletWidget() {
		$("#walletWidgetDiv").html('');
		new OffAmazonPayments.Widgets.Wallet({
			sellerId: '{/literal}{$sellerID}{literal}',
			{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
			design: {
				size : {width:'400px', height:'260px'}
			},
			onPaymentSelect: function(orderReference) {
				$("#cgv").trigger('change');
			},
			onError: function(error) {
				console.log(error.getErrorMessage());
			}
		}).bind("walletWidgetDiv");		
	}
	function reCreateAddressBookWidget() {
		$("#addressBookWidgetDiv").html('');
		new OffAmazonPayments.Widgets.AddressBook({
			sellerId: '{/literal}{$sellerID}{literal}',
			{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
			onAddressSelect: function(orderReference) {
				updateAddressSelection(amazonOrderReferenceId);			
			},
			design: {
				size : {width:'400px', height:'260px'}
			},
			onError: function(error) {		
				console.log(error.getErrorMessage());
			}
		}).bind("addressBookWidgetDiv");	
	}
	
});
</script>
{/literal}
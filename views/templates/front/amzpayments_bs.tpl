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
<div id="amzOverlay"><img src="{$amz_module_path}views/img/loading_indicator.gif" /></div>

<section id="main">
	<header class="page-header">
		<h1>{l s='Pay with Amazon' mod='amzpayments'}</h1>
	</header>
	<section id="content" class="page-content">

		<div class="row">
			<div class="col-xs-12 col-sm-6" id="addressBookWidgetDivBs">
			</div>
			<div class="col-xs-12 col-sm-6" id="walletWidgetDivBs">
			</div>
			<div class="col-xs-12 col-sm-6" id="addressMissings">
			</div>
			<input type="button" id="submitAddress" class="exclusive" value="{l s='Save' mod='amzpayments'}" name="submitAddress" style="display: none;"> 			
		</div>
		
		<div class="row">
			<div class="col-xs-12 amz_cart_coupon">
				<div id="amz_coupon">
					{include file="module:amzpayments/views/templates/front/_coupon.tpl"}
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 amz_cart_widgets_bs">
				<div id="amz_carriers" style="display: none;">
					{include file="module:amzpayments/views/templates/front/_carriers.tpl"}
				</div>	
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12" id="amz_terms" style="display: none;">
				{include file="module:amzpayments/views/templates/front/_conditions.tpl"}
			</div>
		</div>
				
		{if $show_amazon_account_creation_allowed}
			{if $force_account_creation}
				<input type="hidden" id="connect_amz_account" value="1" name="connect_amz_account" />
			{else}
				<div class="row">
					<div class="col-xs-12" id="amz_connect_accounts_div" style="display: none;">
  			          <div class="pull-xs-left">
            			  <span class="custom-checkbox">
			                <input id="connect_amz_account" name="connect_amz_account" type="checkbox" value = "1" />
            			    <span><i class="material-icons checkbox-checked">&#xE5CA;</i></span>
              			  </span>
            		  </div>
            		  <div class="condition-label">
              			<label for="connect_amz_account">
                			{l s='Create customer account.' mod='amzpayments'}
							<br />
							<span style="font-size: 10px;">{l s='You don\'t need to do anything. We create the account with the data of your current order.' mod='amzpayments'}</span>
              			</label>
            		  </div>
					</div>
				</div>
			{/if}
		{/if}
		
		<div class="row">
			<div class="col-xs-12 amz_cart_widgets_summary amz_cart_widgets_summary_bs" id="amz_cart_widgets_summary"  style="display: none;"></div>
		</div>

		<div class="row">
			<div class="col-xs-12 text-right" id="amz_execute_order_div" style="display: none;">
				<input type="button" id="amz_execute_order" class="btn btn-primary disabled" value="{l s='Order with an obligation to pay' d='Shop.Theme.Checkout'}" name="Submit" disabled="disabled">
			</div>
		</div>
		<div style="clear:both"></div>

  		<div class="modal fade" id="modal">
    		<div class="modal-dialog" role="document">
      			<div class="modal-content">
      			</div>
    		</div>
  		</div>

	</section>
	<footer class="page-footer"></footer>
</section>


{if $sandboxMode}

{/if}

{literal}
<script> 
var isFirstRun = true;
var amazonOrderReferenceId = '{/literal}{$amz_session}{literal}';	
jQuery(document).ready(function($) {
	var amzAddressSelectCounter = 0;
	
	var redirectURL = LOGINREDIRECTAMZ;	
	
	options = { scope: 'payments:widget', popup: true, interactive: 'never' };
	amazon.Login.authorize(options, function(response) {
		if (response.error) { 
			loginOptions =  {scope: 'profile postal_code payments:widget payments:shipping_address payments:billing_address', popup: !useRedirect, state: '&toCheckout=1' };
			amazon.Login.authorize (loginOptions, (useRedirect ? redirectURL : function(response) {
				jQuery.ajax({
		    				type: 'GET',
		            	    url: REDIRECTAMZ,
		                	data: 'ajax=true&method=setsession&access_token=' + response.access_token,
			                success: function(htmlcontent){
			                  	window.location = REDIRECTAMZ + amazonOrderReferenceId;
			                }
				});
			})
			);
		}
	});
	
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
			designMode: 'responsive'
		},
		onError: function(error) {
			console.log(error.getErrorCode());
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDivBs");
	
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: '{/literal}{$sellerID}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect: function(orderReference) {
		},
		onError: function(error) {
			console.log(error.getErrorMessage());
		}
	}).bind("walletWidgetDivBs");
});

function reCreateWalletWidget() {
	$("#walletWidgetDivBs").html('');
	new OffAmazonPayments.Widgets.Wallet({
		sellerId: '{/literal}{$sellerID}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
		design: {
			designMode: 'responsive'
		},
		onPaymentSelect: function(orderReference) {
			$("#cgv").trigger('change');
		},
		onError: function(error) {
			console.log(error.getErrorMessage());
		}
	}).bind("walletWidgetDivBs");		
}
function reCreateAddressBookWidget() {
	$("#addressBookWidgetDivBs").html('');
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '{/literal}{$sellerID}{literal}',
		{/literal}{if $amz_session != ''}{literal}amazonOrderReferenceId: '{/literal}{$amz_session}{literal}', {/literal}{/if}{literal}
		onAddressSelect: function(orderReference) {
			updateAddressSelection(amazonOrderReferenceId);			
		},
		displayMode: "Read",
		design: {
			designMode: 'responsive'
		},
		onError: function(error) {		
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDivBs");	
}
</script>
{/literal}

{/block}
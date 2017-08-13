{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
{capture name=path}{l s='Select your address' mod='amzpayments'}{/capture}
{nocache}
<script>
{literal}
{/literal}
</script>

<h1>{l s='Thank you for using Login with Amazon in our Shop.' mod='amzpayments'}</h1>

<p>
	{l s='Please select a default address for your account creation. You will be able to choose a different shipping address during checkout.' mod='amzpayments'}
</p>

<div class="row">
	<div class="col-xs-12 col-sm-6" id="addressBookWidgetDivBs">
	</div>
	<div class="col-xs-12 col-sm-6" id="addressMissings">		
	</div>
</div>

<div class="row">
	<div class="col-xs-12">
		<button type="button" id="submitAddress" name="submitAddress" class="button btn btn-default button-medium" style="display:none;" data-continue="{l s='Continue' mod='amzpayments'}" data-change="{l s='Save changes' mod='amzpayments'}">
			<span>
				{l s='Continue' mod='amzpayments'}
			</span>
		</button>
	</div>
</div>

{literal}
<script>
jQuery(document).ready(function($) {
	new OffAmazonPayments.Widgets.AddressBook({
		sellerId: '{/literal}{$sellerID|escape:'htmlall':'UTF-8'}{literal}',
		onOrderReferenceCreate: function(orderReference) {			
			 amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
		},
		onAddressSelect: function(orderReference) {
			updateAddressSelection(amazonOrderReferenceId);
		},
		design: {
			designMode: 'responsive'
		},
		onError: function(error) {
			console.log(error.getErrorCode());
			console.log(error.getErrorMessage());
		}
	}).bind("addressBookWidgetDivBs");
});


function updateAddressSelection(amazonOrderReferenceId)
{
	$("#submitAddress").fadeOut();
	var idAddress_delivery = 0;
	var idAddress_invoice = idAddress_delivery;
	
	var additional_fields = '';
	$("#addressMissings .additional_field").each(function() {
		additional_fields += '&add[' + $(this).attr("name") + ']=' + $(this).val();		
	});
	
	
	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: '{/literal}{$ajaxSetAddressUrl|escape:'htmlall':'UTF-8'}{literal}' + '?rand=' + new Date().getTime(),
		async: true,
		cache: false,
		dataType : "json",
		data: 'amazonOrderReferenceId=' + amazonOrderReferenceId + '&allow_refresh=1&ajax=true&method=updateAddressesSelected&id_address_delivery=' + idAddress_delivery + '&id_address_invoice=' + idAddress_invoice + '&token=' + static_token + additional_fields,
		success: function(jsonData)
		{
			if (jsonData.hasError)
			{
				var errors = '';
				for(var error in jsonData.errors)
					if(error !== 'indexOf')
						errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
				alert(errors);
				
				if (jsonData.fields_html) {
					$("#addressMissings").empty();
					$("#addressMissings").html(jsonData.fields_html);
					$("#submitAddress span").text($("#submitAddress").attr("data-change"));
					$("#submitAddress").fadeIn();
					$("#submitAddress").unbind('click').on('click', function() { updateAddressSelection(amazonOrderReferenceId); });
				}
				
			}
			else
			{
				$("#submitAddress span").text($("#submitAddress").attr("data-continue"));
				$("#submitAddress").fadeIn();
				$("#submitAddress").unbind('click').on('click', function() { window.location.href = jsonData.redirect; });
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus !== 'abort')
				alert("TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
		}
	});
}

</script>
{/literal}

{/nocache}
/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

var requestIsRunning = false;
$(document).ready(function() {
	
	$("#amz_cart_widgets_summary .cart_navigation, #payWithAmazonDiv").hide();
	$('#cgv').trigger('change');
	
	$( document ).on("change", ".delivery_option_radio", function() {
		updateCarrierSelectionAndGift();
	});
	
});

setInterval(checkVoucherForm, 1000);
function checkVoucherForm()
{
	if ($("form#voucher").length > 0) {
		if ($("form#voucher").attr("action") != REDIRECTAMZ) { $("form#voucher").attr("action", REDIRECTAMZ); }
	}
}

function updateCarrierSelectionAndGift()
{
	if (!requestIsRunning) {
		requestIsRunning = true;
		
		var recyclablePackage = 0;
		var gift = 0;
		var giftMessage = '';
		
		var delivery_option_radio = $('.delivery_option_radio');
		var delivery_option_params = '&';
		$.each(delivery_option_radio, function(i) {
			if ($(this).prop('checked'))
				delivery_option_params += $(delivery_option_radio[i]).attr('name') + '=' + $(delivery_option_radio[i]).val() + '&';
		});
		if (delivery_option_params == '&')
			delivery_option_params = '&delivery_option=&';
	
		if ($('input#recyclable:checked').length)
			recyclablePackage = 1;
		if ($('input#gift:checked').length)
		{
			gift = 1;
			giftMessage = encodeURIComponent($('#gift_message').val());
		}
		
		$('#opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
		$.ajax({
			type: 'POST',
			headers: { "cache-control": "no-cache" },
			url: REDIRECTAMZ + '?rand=' + new Date().getTime(),
			async: true,
			cache: false,
			dataType : "json",
			data: 'ajax=true&method=updateCarrierAndGetPayments' + delivery_option_params + 'recyclable=' + recyclablePackage + '&gift=' + gift + '&gift_message=' + giftMessage + '&token=' + static_token ,
			success: function(jsonData)
			{	
				if (jsonData.hasError)
				{
					var errors = '';
					for(var error in jsonData.errors)						
						if(error !== 'indexOf')
							errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
					alert(errors);
					$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
				}
				else
				{
					updateCartSummary(jsonData.summary);
					updateHookShoppingCart(jsonData.summary.HOOK_SHOPPING_CART);
					updateHookShoppingCartExtra(jsonData.summary.HOOK_SHOPPING_CART_EXTRA);
					updateCarrierList(jsonData.carrier_data);
					$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
					refreshDeliveryOptions();
				}
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				if (textStatus !== 'abort')
					alert("TECHNICAL ERROR: unable to save carrier \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
				$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
			}
		});
		requestIsRunning = false;
	}
}

function updateCarrierList(json)
{
	var html = json.carrier_block;
	
	$('#carrier_area').replaceWith(html);	
	$("#amz_carriers").fadeIn('slow');
	bindInputs();
	/* update hooks for carrier module */
	$('#HOOK_BEFORECARRIER').html(json.HOOK_BEFORECARRIER);
}

function updateAddressSelection(amazonOrderReferenceId)
{
	var idAddress_delivery = 0;
	var idAddress_invoice = idAddress_delivery;

	var additional_fields = '';
	$("#addressMissings .additional_field").each(function() {
		additional_fields += '&add[' + $(this).attr("name") + ']=' + $(this).val();		
	});	
	
	$('#opc_account-overlay').fadeIn('slow');
	$('#opc_delivery_methods-overlay').fadeIn('slow');
	$('#opc_payment_methods-overlay').fadeIn('slow');
	
	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: REDIRECTAMZ + '&rand=' + new Date().getTime(),
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
					$("#submitAddress").fadeIn();
					$("#submitAddress").unbind('click').on('click', function() { updateAddressSelection(amazonOrderReferenceId); });
				}				
				$("#amz_execute_order").attr("disabled","disabled").addClass("disabled"); 
				$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
			}
			else
			{
				$("#submitAddress").fadeOut('fast', function() { $("#addressMissings").empty() });;
				if (jsonData.refresh)
					location.reload();
				$('#cart_summary .address_'+deliveryAddress).each(function() {
					$(this)
						.removeClass('address_'+deliveryAddress)
						.addClass('address_'+idAddress_delivery);
					$(this).attr('id', $(this).attr('id').replace(/_\d+$/, '_'+idAddress_delivery));
					if ($(this).find('.cart_unit span').length > 0 && $(this).find('.cart_unit span').attr('id').length > 0)
						$(this).find('.cart_unit span').attr('id', $(this).find('.cart_unit span').attr('id').replace(/_\d+$/, '_'+idAddress_delivery));

					if ($(this).find('.cart_total span').length > 0 && $(this).find('.cart_total span').attr('id').length > 0)
						$(this).find('.cart_total span').attr('id', $(this).find('.cart_total span').attr('id').replace(/_\d+$/, '_'+idAddress_delivery));

					if ($(this).find('.cart_quantity_input').length > 0 && $(this).find('.cart_quantity_input').attr('name').length > 0)
					{
						var name = $(this).find('.cart_quantity_input').attr('name')+'_hidden';
						$(this).find('.cart_quantity_input').attr('name', $(this).find('.cart_quantity_input').attr('name').replace(/_\d+$/, '_'+idAddress_delivery));
						if ($(this).find('[name='+name+']').length > 0)
							$(this).find('[name='+name+']').attr('name', name.replace(/_\d+_hidden$/, '_'+idAddress_delivery+'_hidden'));
					}

					if ($(this).find('.cart_quantity_delete').length > 0 && $(this).find('.cart_quantity_delete').attr('id').length > 0)
					{
						$(this).find('.cart_quantity_delete')
							.attr('id', $(this).find('.cart_quantity_delete').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
							.attr('href', $(this).find('.cart_quantity_delete').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
					}
					
					if ($(this).find('.cart_quantity_down').length > 0 && $(this).find('.cart_quantity_down').attr('id').length > 0)
					{
						$(this).find('.cart_quantity_down')
							.attr('id', $(this).find('.cart_quantity_down').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
							.attr('href', $(this).find('.cart_quantity_down').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
					}

					if ($(this).find('.cart_quantity_up').length > 0 && $(this).find('.cart_quantity_up').attr('id').length > 0)
					{
						$(this).find('.cart_quantity_up')
							.attr('id', $(this).find('.cart_quantity_up').attr('id').replace(/_\d+$/, '_'+idAddress_delivery))
							.attr('href', $(this).find('.cart_quantity_up').attr('href').replace(/id_address_delivery=\d+&/, 'id_address_delivery='+idAddress_delivery+'&'));
					}	
				});


				deliveryAddress = idAddress_delivery;
				if (window.ajaxCart !== undefined)
				{
					$('#cart_block_list dd, #cart_block_list dt').each(function(){
						if (typeof($(this).attr('id')) != 'undefined')
							$(this).attr('id', $(this).attr('id').replace(/_\d+$/, '_' + idAddress_delivery));
					});
				}
				
				if (typeof updateAddressId === "function") {
					first_item = $("#cart_summary .cart_item ");
					if (first_item.length > 0) {
						if (first_item.hasClass('address_' + jsonData.summary.delivery.id)) {
						} else {
							$(".cart_item").each(
									function() {
										if ($(this).attr("id").indexOf('product_') > -1) {
											var ids = $(this).attr('id').split('_');
											var id_product = ids[1];
											var id_product_attribute = ids[2];
											var old_id_address_delivery = ids[4];
											var new_id_address_delivery = jsonData.summary.delivery.id;											
											var line = $(this);
											updateAddressId(id_product, id_product_attribute, old_id_address_delivery, new_id_address_delivery);
										}
									}
								);
						}
					}
				}
				
				updateCarrierList(jsonData.carrier_data);
				if (typeof amazonCarrierErrorMessage !== 'undefined' || amazonCarrierErrorMessage !== null) {
					if ($("#noCarrierWarning").length > 0) {
						$("#noCarrierWarning").hide();
						$("#noCarrierWarning").html(amazonCarrierErrorMessage);
						$("#noCarrierWarning").show();
					}
				}
				updateCartSummary(jsonData.summary);
				updateHookShoppingCart(jsonData.HOOK_SHOPPING_CART);
				updateHookShoppingCartExtra(jsonData.HOOK_SHOPPING_CART_EXTRA);
				if ($('#gift-price').length == 1)
					$('#gift-price').html(jsonData.gift_price);
				if ($("#cgv").length > 0) {
					$("#cgv").trigger('change');
				}
				$('#amzOverlay, #opc_account-overlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus !== 'abort')
				alert("TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
			$('#amzOverlay, #opc_account-overlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
		}
	});
}

$("#amz_execute_order").live('click', function() {
	
	$('#amzOverlay').fadeIn('slow');
	
	var connectRequest = '';
	if ($("#connect_amz_account").length > 0) {
		if ($("#connect_amz_account").is(':checked') || $("#connect_amz_account").attr("type") == 'hidden') {
			connectRequest = '&connect_amz_account=' + $("#connect_amz_account").val();
		}
	}
	
	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: REDIRECTAMZ + '?rand=' + new Date().getTime(),
		async: true,
		cache: false,
		dataType : "json",
		data: 'amazonOrderReferenceId=' + amazonOrderReferenceId + '&allow_refresh=1&ajax=true&method=executeOrder&confirm=1&token=' + static_token + connectRequest,
		success: function(jsonData)
		{
			if (jsonData.hasError)
			{
				var errors = '';
				for(var error in jsonData.errors)

					if(error !== 'indexOf')
						errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
				alert(errors);
				
				if (typeof jsonData.redirection !== 'undefined') {
					if (jsonData.redirection.length > 0) {
						window.location.href = jsonData.redirection;
						return;
					}
				}
				$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
				$("form#voucher, .ajax_cart_block_remove_link, .cart_quantity_up, .cart_quantity_down, .cart_quantity_delete").remove();
				
				$("#opc_delivery_methods-overlay").css("height", $("#opc_delivery_methods").outerHeight()).css("width",  $("#opc_delivery_methods").outerWidth()).css("background", "none repeat scroll 0 0 rgba(99, 99, 99, 0.5)").css("position","absolute").css("z-index","1000").fadeIn();
				$("#amz_execute_order").attr("disabled","disabled").addClass("disabled"); 
				$('#gift, .delivery_option_radio, #recyclable').click(function(){
				    return false;
				});
				reCreateWalletWidget();
				reCreateAddressBookWidget();
			}
			else
			{
				window.location.href = jsonData.redirection;
			}
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			if (textStatus !== 'abort')
				alert("TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
			$('#amzOverlay, #opc_account-overlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
		}
	});	
	
	
});

function bindInputs()
{
	$('#message').blur(function() {
		$('#opc_delivery_methods-overlay').fadeIn('slow');
		$.ajax({
			type: 'POST',
			headers: { "cache-control": "no-cache" },
			url: REDIRECTAMZ + '?rand=' + new Date().getTime(),
			async: false,
			cache: false,
			dataType : "json",
			data: 'ajax=true&method=updateMessage&message=' + encodeURIComponent($('#message').val()) + '&token=' + static_token ,
			success: function(jsonData)
			{
				if (jsonData.hasError)
				{
					var errors = '';
					for(var error in jsonData.errors)
						if(error !== 'indexOf')
							errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
					alert(errors);
					$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
				}
			else
				$('#amzOverlay, #opc_delivery_methods-overlay').fadeOut('slow');
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				if (textStatus !== 'abort')
					alert("TECHNICAL ERROR: unable to save message \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
				$('#amzOverlay, #opc_delivery_methods-overlay').fadeOut('slow');
			}
		});
	});
	
	$('#recyclable').click(function() {
		updateCarrierSelectionAndGift();
	});
	
	$('#gift').click(function() {
		if ($('#gift').is(':checked'))
			$('#gift_div').show();
		else
			$('#gift_div').hide();
		updateCarrierSelectionAndGift();
	});
	
	if ($('#gift').is(':checked'))
		$('#gift_div').show();
	else
		$('#gift_div').hide();

	$('#gift_message').change(function() {
		updateCarrierSelectionAndGift();
	});
	
	if ($("#noCarrierWarning").length > 0) 
		$("#amz_execute_order").attr("disabled","disabled").addClass("disabled"); 
	else {
		if ($("#cgv").length > 0) {
			$("#cgv").trigger('change');			
		} else {
			$("#amz_execute_order").removeAttr("disabled").removeClass("disabled");
		}
	}
}

$('#cgv').live('change', function() {
	
	if ($(this).attr("checked") && $("#noCarrierWarning").length == 0 && $.trim($('#addressMissings').html()).length == 0) {
		$("#amz_execute_order").removeAttr("disabled").removeClass("disabled");
	} else {
		$("#amz_execute_order").attr("disabled","disabled").addClass("disabled");
	}
	updateTOSStatus();
});

function updateTOSStatus()
{
	var checked = '';
	if ($('#cgv:checked').length !== 0)
		checked = 1;
	else
		checked = 0;
	
	$.ajax({
		type: 'POST',
		headers: { "cache-control": "no-cache" },
		url: REDIRECTAMZ + '&rand=' + new Date().getTime(),
		async: true,
		cache: false,
		dataType : "json",
		data: 'ajax=true&method=updateTOSStatusAndGetPayments&checked=' + checked + '&token=' + static_token,
		success: function(json)
		{
		}
	});
}

function disableAmzWidget(wrObj){
	var width = wrObj.width();
	var height = wrObj.height();
	var offset = wrObj.offset();
	var blocker = $('<div style="width:'+width+'px; height:'+height+'px; position:absolute; top:'+offset.top+'px; left:'+offset.left+'px; background:#fff; opacity: 0.5; z-index:1000;">&nbsp;</div>');
	$('body').append(blocker);
}
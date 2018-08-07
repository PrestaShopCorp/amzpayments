/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

var requestIsRunning = false;
$(document).ready(function() {
	
	$("#amz_cart_widgets_summary .cart_navigation, #payWithAmazonDiv").hide();
	
	$( document ).on("change", ".delivery-options input[type=radio]", function() {
		updateCarrierSelectionAndGift();
	});
	
    $('.js-terms a').on('click', (event) => {
	    event.preventDefault();
	    var url = $(event.target).attr('href');
	    if (url) {
	      url += '?content_only=1';
	      $.get(url, function (content) {
	        $('#modal').find('.modal-content').html($(content).find('.page-cms').contents());
	      });
	    }
	    $('#modal').modal('show');
	});
	
	$("#amz_execute_order").on('click', function() {
		
		var redirectURL = LOGINREDIRECTAMZ;	
		
		$('#amzOverlay').fadeIn('slow');
		
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
				return false;
			} else {
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
					data: 'amazonOrderReferenceId=' + amazonOrderReferenceId + '&allow_refresh=1&ajax=true&method=executeOrder&confirm=1&' + connectRequest,
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
							/* $("#amz_execute_order").attr("disabled","disabled").addClass("disabled"); */ 
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
			}
		});
	});
	
});

function updateCarrierSelectionAndGift()
{
	if (!requestIsRunning) {
		requestIsRunning = true;
		
		var recyclablePackage = 0;
		var gift = 0;
		var giftMessage = '';
		
		var delivery_option_radio = $('.delivery-options input[type=radio]');
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
			data: 'ajax=true&method=updateCarrierAndGetPayments' + delivery_option_params + 'recyclable=' + recyclablePackage + '&gift=' + gift + '&gift_message=' + giftMessage ,
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
					updateCartSummary(jsonData.summary_block);
					updateCarrierList(jsonData.carrier_data);
					$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');					
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

function updateCartSummary(json)
{	
	$('#amz_cart_widgets_summary').html(json);
	$('#amz_cart_widgets_summary').fadeIn('slow');
	bindInputs();
}

function updateCouponDiv(json)
{
	$('#amz_coupon').html(json);
	$('#amz_coupon').fadeIn('slow');
	bindInputs();
}

function updateCarrierList(json)
{
	var html = json.carrier_block;
	
	$('#amz_carriers').html(html);
	$('#amz_carriers, #amz_execute_order_div, #amz_connect_accounts_div, #amz_terms').fadeIn('slow');
	bindInputs();
}

function updateAddressSelection(amazonOrderReferenceId)
{
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
			return false;
		} else {
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
				data: 'amazonOrderReferenceId=' + amazonOrderReferenceId + '&allow_refresh=1&ajax=true&method=updateAddressesSelected&id_address_delivery=' + idAddress_delivery + '&id_address_invoice=' + idAddress_invoice + additional_fields,
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
							$("#addressMissings").fadeIn();
							$("#submitAddress").fadeIn();
							$("#submitAddress").unbind('click').on('click', function() { updateAddressSelection(amazonOrderReferenceId); });
						}
						
						disable_order_execute_button();
						$('#amzOverlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
					}
					else
					{
						$("#addressMissings").fadeOut();
						$("#submitAddress").fadeOut('fast', function() {
							$("#addressMissings").empty();
							if (jsonData.refresh)
								location.reload();
							
							deliveryAddress = idAddress_delivery;
							
							updateCarrierList(jsonData.carrier_data);
							updateCartSummary(jsonData.summary_block);
							$('#amzOverlay, #opc_account-overlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');					
						});
					}
				},
				error: function(XMLHttpRequest, textStatus, errorThrown) {
					if (textStatus !== 'abort')
						alert("TECHNICAL ERROR: unable to save adresses \n\nDetails:\nError thrown: " + XMLHttpRequest + "\n" + 'Text status: ' + textStatus);
					$('#amzOverlay, #opc_account-overlay, #opc_delivery_methods-overlay, #opc_payment_methods-overlay').fadeOut('slow');
				}
			});
		}
	});
}


function bindInputs()
{
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
	
    $("#promo-input-btn").click(function() {
    	if (!requestIsRunning) {
    		requestIsRunning = true;
	    	var coupon_value = $('#promo-input').val();
	    	
			$.ajax({
				type: 'POST',
				headers: { "cache-control": "no-cache" },
				url: REDIRECTAMZ + '?rand=' + new Date().getTime(),
				async: true,
				cache: false,
				dataType : "json",
				data: 'ajax=true&method=addDiscount&coupon=' + coupon_value,
				success: function(jsonData)
				{
					if (jsonData.hasError) {
						var errors = '';
						for(var error in jsonData.errors) {
							if(error !== 'indexOf') {
								errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
							}
						}
						alert(errors);
					} else {
						updateCouponDiv(jsonData.coupon_block);
						updateCartSummary(jsonData.summary_block);						
					}				
					requestIsRunning = false;
				}
			});
    	}
    });
    
    $(".remove-voucher-a").click(function() {
    	if (!requestIsRunning) {
    		requestIsRunning = true;
	    	var coupon_id = $(this).data("voucher-id");
	    	
			$.ajax({
				type: 'POST',
				headers: { "cache-control": "no-cache" },
				url: REDIRECTAMZ + '?rand=' + new Date().getTime(),
				async: true,
				cache: false,
				dataType : "json",
				data: 'ajax=true&method=removeDiscount&coupon=' + coupon_id,
				success: function(jsonData)
				{
					if (jsonData.hasError) {
						var errors = '';
						for(var error in jsonData.errors) {
							if(error !== 'indexOf') {
								errors += $('<div />').html(jsonData.errors[error]).text() + "\n";
							}
						}
						alert(errors);
					} else {
						updateCouponDiv(jsonData.coupon_block);
						updateCartSummary(jsonData.summary_block);
					}
					requestIsRunning = false;
				}
			});	
    	}
    });	
	
	if ($(".delivery-options-list .alert").length > 0) {
		disable_order_execute_button();
	} else {
		if (!all_conditions_approved()) {
			disable_order_execute_button();
		} else {
			enable_order_execute_button();
		}
	}

	$('.conditions_to_approve_checkbox').bind('change', function() {		
		if ($(".delivery-options-list .alert").length > 0) {
			disable_order_execute_button();
		} else {
			if (!all_conditions_approved()) {
				disable_order_execute_button();
			} else {
				enable_order_execute_button();
			}
		}
	});	
}

function disable_order_execute_button() {
	$("#amz_execute_order").attr("disabled","disabled").addClass("disabled");	
}

function enable_order_execute_button() {
	$("#amz_execute_order").removeAttr("disabled").removeClass("disabled");	
}

function all_conditions_approved() {
	var is_ok = true;
	$(".conditions_to_approve_checkbox").each(function() {
		if (!$(this).prop('checked')) {
			is_ok = false;
		}
	});
	if ($.trim($('#addressMissings').html()).length == 0) {		
	} else {
		is_ok = false;
	}
	return is_ok;
}

function disableAmzWidget(wrObj){
	var width = wrObj.width();
	var height = wrObj.height();
	var offset = wrObj.offset();
	var blocker = $('<div style="width:'+width+'px; height:'+height+'px; position:absolute; top:'+offset.top+'px; left:'+offset.left+'px; background:#fff; opacity: 0.5; z-index:1000;">&nbsp;</div>');
	$('body').append(blocker);
}
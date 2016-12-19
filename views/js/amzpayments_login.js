/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

function getURLParameter(name, source) {
	return decodeURIComponent((new RegExp('[?|&|#]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(source)||[,""])[1].replace(/\+/g,'%20'))||null; 
}

function amazonLogout(){
    amazon.Login.logout();
	document.cookie = "amazon_Login_accessToken=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
	document.cookie = "amazon_Login_accessToken=; expires=Thu, 18 Aug 1979 00:00:00 GMT; path=/prestashop/";
	document.cookie = "amazon_Login_state_cache=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

var authRequest;    

jQuery(document).ready(function($){
	if ($("#authentication #SubmitCreate").length > 0 && LPA_MODE != 'pay') {
		$("#authentication #SubmitCreate").parent().append('<div class="amazonLoginWr" id="jsLoginAuthPage"></div>');
	}
	
	if (AMZACTIVE == '1') {
		initAmazon();
		$('.logout').click(function() {
			amazonLogout();
		});
	}
});

function initAmazon(){	

	if (jQuery("#form_onepagecheckoutps #opc_social_networks").length > 0) {
		if (jQuery("#payWithAmazonSocialDiv").length == 0) {
			jQuery("#opc_social_networks").append('<span id="payWithAmazonSocialDiv" class="amazonLoginWr"></span>');
		}
	}

	if($('.amazonLoginWr').length > 0){
	   $('.amazonLoginWr').each(function(){
	   		var amzBtnColor = AMZ_BUTTON_COLOR_LPA;
	   		if ($(this).attr("id") == "amazonLogin")
	   			amzBtnColor = AMZ_BUTTON_COLOR_LPA_NAVI;
	   		var redirectURL = LOGINREDIRECTAMZ;
	   		var redirectToCheckout = false;
	   		if ($(this).attr("id") == "jsLoginAuthPage" && location.href.indexOf('display_guest_checkout') > 1) {
	   			redirectURL = LOGINREDIRECTAMZ_CHECKOUT;
	   			redirectToCheckout = true;
	   		}
	        OffAmazonPayments.Button($(this).attr('id'), AMZSELLERID, {
	                type: AMZ_BUTTON_TYPE_LOGIN, 
	                size: AMZ_BUTTON_SIZE_LPA,
	                color: amzBtnColor,
	                language: AMZ_WIDGET_LANGUAGE,
	                authorization: function() {
	                loginOptions =  {scope: 'profile postal_code payments:widget payments:shipping_address payments:billing_address', popup: !useRedirect };
	                authRequest = amazon.Login.authorize (loginOptions, useRedirect ? redirectURL : null);
	            },    
	            onSignIn: function(orderReference) {
	                var amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
	                $.ajax({
	                    type: 'GET',
	                    url: SETUSERAJAX,
	                    data: 'ajax=true&method=setusertoshop&access_token=' + authRequest.access_token + '&amazon_id=' + amazonOrderReferenceId + (redirectToCheckout ? '&action=checkout' : null),
	                    success: function(htmlcontent){
	                        if (htmlcontent == 'error') {
	                            alert('An error occured - please try again or contact our support');
	                        } else {
	                            window.location = htmlcontent;
	                        }					   
	                    }
	                });				
	            },
	            onError: function(error) {
	                console.log(error); 
	            }
	        });
	        
	        
	  });
	}
	if (LPA_MODE == 'login_pay' || LPA_MODE == 'pay') {
		if($('#payWithAmazonDiv').length > 0){
			bindCartButton('payWithAmazonDiv');		   
		}
		if ($('#button_order_cart').length > 0 && $('#amz_cart_widgets_summary').length == 0) {
			$('#button_order_cart').before('<div id="payWithAmazonCartDiv"></div>');
			bindCartButton('payWithAmazonCartDiv');
		}

		if ($("#pay_with_amazon_list_button").length > 0) {
			$("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv"></span>');
			bindCartButton('payWithAmazonListDiv');			
		}
		
		var have_clicked = false;
		$("a.amzPayments").click(function() {
			if (!have_clicked) {
				have_clicked = true;
				$("#payWithAmazonListDiv img").trigger('click');
				setTimeout(function() { have_clicked = false; }, 1000);
			}
			return false;
		});
				
		setInterval(checkForAmazonListButton, 2000);
	}
}

function checkForAmazonListButton() {
	if (jQuery("#pay_with_amazon_list_button").length > 0) {
		if (jQuery.trim(jQuery("#pay_with_amazon_list_button").html()) == '') {
			jQuery("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv"></span>');
			bindCartButton('payWithAmazonListDiv');
		}
	}	
	if (jQuery("#HOOK_ADVANCED_PAYMENT").length > 0) {
		if (jQuery("#payWithAmazonListDiv").length == 0) {
			jQuery("#HOOK_ADVANCED_PAYMENT").append('<span id="payWithAmazonListDiv"></span>');
			bindCartButton('payWithAmazonListDiv');
		}
	}
	/* Adaption for onepagecheckout module */
	if (jQuery("#form_onepagecheckoutps #onepagecheckoutps_step_three").length > 0) {
		if (jQuery("#payWithAmazonPaymentOPC").length == 0) {
			jQuery("#onepagecheckoutps_step_three").append('<span id="payWithAmazonPaymentOPC"></span>');
			bindCartButton('payWithAmazonPaymentOPC');
		}
	}
	if (jQuery("#onepagecheckoutps_step_review #payWithAmazonMainDiv").length > 0) {
		jQuery("#onepagecheckoutps_step_review #payWithAmazonMainDiv").detach();
	}
}

function bindCartButton(div_id) {
	if (jQuery('#' + div_id).attr('data-is-set') != '1') {
		    OffAmazonPayments.Button(div_id, AMZSELLERID, {
	            type: AMZ_BUTTON_TYPE_PAY,
	            size: AMZ_BUTTON_SIZE_LPA,
	            color: AMZ_BUTTON_COLOR_LPA,
	            language: AMZ_WIDGET_LANGUAGE,
	            authorization: function() {
	            loginOptions =  {scope: 'profile postal_code payments:widget payments:shipping_address payments:billing_address', popup: !useRedirect };
	            authRequest = amazon.Login.authorize (loginOptions, (useRedirect ? LOGINREDIRECTAMZ_CHECKOUT : null));
	        },
	        onSignIn: function(orderReference) {
	            amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();		            
	            jQuery(div_id).html('');
	            jQuery.ajax({
	                    type: 'GET',
	                    url: REDIRECTAMZ,
	                    data: 'ajax=true&method=setsession&access_token=' + authRequest.access_token + '&amazon_id=' + amazonOrderReferenceId,
	                    success: function(htmlcontent){
	                    	window.location = REDIRECTAMZ + amazonOrderReferenceId;
	                    }
	            });
	        },
	        onError: function(error) {
	            console.log(error); 
	        }
	    });
	    jQuery('#' + div_id).attr('data-is-set', '1');
	}	
}
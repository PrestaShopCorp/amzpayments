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
	document.cookie = "amazon_Login_accessToken=; expires=Thu, 01 Jan 1970 00:00:00 GMT;secure";
	document.cookie = "amazon_Login_state_cache=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

var authRequest;    

jQuery(document).ready(function($){	
	if (AMZACTIVE == '1') {
		initAmazon();
		$('.logout').click(function() {
			amazonLogout();
		});
	}
});

function buildAmazonButtonContainer(){
	return '<div class="amzbuttoncontainer"><h3 class="page-subheading">' + AMZ_USE_ACCOUNT_HEAD + '</h3><p>' + AMZ_USE_ACCOUNT_BODY + '</p></div>';
}

function buildAmazonMiniCartButtonContainer(){
	if (AMZ_MINI_CART_ENHANCEMENT == '1' && AMZ_ADD_MINI_CART_BTN != '0') {
		return '<div class="button_enhanced_mini_cart_info"><p>' + AMZ_MINI_CART_INFO + '</p></div><div style="clear:both;"></div>';	
	}
	return '';
}

function initAmazon() {	

	if ($("#authentication #SubmitCreate").length > 0 && LPA_MODE != 'login' && AMZ_SHOW_REGISTRATION_PAGE == '1') {
		if ($("body#authentication").length > 0 && $("#order_step").length == 0) {
			$("#authentication #SubmitCreate").parent().append('<div class="amazonLoginToPay amazonLoginWr" id="jsLoginAmazonPay">' + buildAmazonButtonContainer() + '</div>');			
		} else {
			$("#authentication #SubmitCreate").parent().append('<div class="amazonLoginToPay" id="jsLoginAmazonPay">' + buildAmazonButtonContainer() + '</div>');
			bindCartButton('jsLoginAmazonPay');			
		}
	}
	
	if (jQuery("#form_onepagecheckoutps #opc_social_networks").length > 0) {
		if (jQuery("#payWithAmazonSocialDiv").length == 0) {
			jQuery("#opc_social_networks").append('<span id="payWithAmazonSocialDiv" class="amazonLoginWr"></span>');
		}
	}
	
	if (AMZ_SHOW_IN_CART_POPUP == '1') {
		if ($('#layer_cart .button-container').length > 0) {
			$('#layer_cart .button-container').append('<span id="payWithAmazonLayerCartDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
			bindCartButton('payWithAmazonLayerCartDiv');		
		}
	}

	if($('.amazonLoginWr').length > 0){
	   $('.amazonLoginWr').each(function(){
		 if ($(this).attr('data-is-set') != '1') {
	   		var amzBtnColor = AMZ_BUTTON_COLOR_LPA;
	   		if ($(this).attr("id") == "amazonLogin")
	   			amzBtnColor = AMZ_BUTTON_COLOR_LPA_NAVI;
	   		var redirectURL = LOGINREDIRECTAMZ;
	   		var redirectToCheckout = false;
	   		var redirectState = '';
	   		if ($(this).attr("id") == "jsLoginAuthPage" && location.href.indexOf('display_guest_checkout') > 1) {
	   			redirectState = '&toCheckout=1';
	   			redirectToCheckout = true;
	   		}
	        OffAmazonPayments.Button($(this).attr('id'), AMZSELLERID, {
	                type: AMZ_BUTTON_TYPE_LOGIN, 
	                size: AMZ_BUTTON_SIZE_LPA,
	                color: amzBtnColor,
	                language: AMZ_WIDGET_LANGUAGE,
	                authorization: function() {
	                loginOptions =  {scope: 'profile postal_code payments:widget payments:shipping_address payments:billing_address', popup: !useRedirect, state: redirectState };
	                authRequest = amazon.Login.authorize (loginOptions, useRedirect ? redirectURL : null);
	            },    
	            onSignIn: function(orderReference) {
	                var amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
	                document.cookie = "amazon_Login_accessToken=" + authRequest.access_token + ";path=/;secure";
	                acctk = '';
	                if (AMZ_NO_TOKEN_AJAX == '0') {
	                	acctk = '&access_token=' + authRequest.access_token;
	                }
	                $.ajax({
	                    type: 'POST',
	                    url: SETUSERAJAX,
	                    data: 'ajax=true&method=setusertoshop&amazon_id=' + amazonOrderReferenceId + (redirectToCheckout ? '&action=checkout' : null) + acctk,
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
	       $(this).attr('data-is-set', '1');
		} 
	  });
	  setTipr(".amazonLoginWr"); 
	}
	if (LPA_MODE == 'login_pay' || LPA_MODE == 'pay') {
		if($('#payWithAmazonDiv').length > 0){
			bindCartButton('payWithAmazonDiv');
		}
		if ($('#button_order_cart').length > 0 && $('#amz_cart_widgets_summary').length == 0) {
			if (AMZ_MINI_CART_ENHANCEMENT == '1' && AMZ_ADD_MINI_CART_BTN != '0') {
				$('#button_order_cart').before('<div class="button_enhanced_mini_cart">' + buildAmazonMiniCartButtonContainer() + '<div id="payWithAmazonCartDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></div><div style="clear:both;"></div></div>');		
			} else {
				$('#button_order_cart').before('<div id="payWithAmazonCartDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></div>');
			}
			bindCartButton('payWithAmazonCartDiv');
		}

		if ($("#pay_with_amazon_list_button").length > 0) {
			$("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
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
			if (AMZ_SHOW_AS_PAYMENT_METHOD == '1') {
				jQuery("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
				bindCartButton('payWithAmazonListDiv');
			}
		}
	}	
	if (jQuery("#HOOK_ADVANCED_PAYMENT").length > 0) {		
		if (jQuery("#payWithAmazonListDiv").length == 0) {
			if (AMZ_SHOW_AS_PAYMENT_METHOD == '1') {
				jQuery('<div class="col-xs-6 col-md-6" id="amzRowElement"><span id="payWithAmazonListDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span></div>').appendTo("#HOOK_ADVANCED_PAYMENT .row:first");
				bindCartButton('payWithAmazonListDiv');
			}
		}
	}
	/* Adaption for onepagecheckout module */
	if (jQuery("#form_onepagecheckoutps #onepagecheckoutps_step_three").length > 0) {
		if (jQuery("#payWithAmazonPaymentOPC").length == 0) {
			if (AMZ_SHOW_AS_PAYMENT_METHOD == '1') {
				jQuery("#onepagecheckoutps_step_three").append('<span id="payWithAmazonPaymentOPC" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
				bindCartButton('payWithAmazonPaymentOPC');
			}
		}
	}
}

function bindCartButton(div_id) {
	if (jQuery('#' + div_id).attr('data-is-set') != '1') {
		var redirectState = '';
   		var redirectURL = LOGINREDIRECTAMZ;
		if (useRedirect) {
			redirectState = '&toCheckout=1';
		}		
		OffAmazonPayments.Button(div_id, AMZSELLERID, {
	            type: AMZ_BUTTON_TYPE_PAY,
	            size: AMZ_BUTTON_SIZE_LPA,
	            color: AMZ_BUTTON_COLOR_LPA,
	            language: AMZ_WIDGET_LANGUAGE,
	            authorization: function() {
	            loginOptions =  {scope: 'profile postal_code payments:widget payments:shipping_address payments:billing_address', popup: !useRedirect, state: redirectState };
	            authRequest = amazon.Login.authorize (loginOptions, (useRedirect ? redirectURL : null));
	        },
	        onSignIn: function(orderReference) {
	            amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
	            if (jQuery('#' + div_id).hasClass('amz_create_account')) {
	                document.cookie = "amazon_Login_accessToken=" + authRequest.access_token + ";path=/;secure";
	                acctk = '';
	                if (AMZ_NO_TOKEN_AJAX == '0') {
	                	acctk = '&access_token=' + authRequest.access_token;
	                }
	                $.ajax({
	                    type: 'POST',
	                    url: SETUSERAJAX,
	                    data: 'ajax=true&method=setusertoshop&amazon_id=' + amazonOrderReferenceId + '&action=checkout' + acctk,
	                    success: function(htmlcontent){
	                        if (htmlcontent == 'error') {
	                            alert('An error occured - please try again or contact our support');
	                        } else {
	                            window.location = htmlcontent;
	                        }					   
	                    }
	                });
	            } else {
	                document.cookie = "amazon_Login_accessToken=" + authRequest.access_token + ";path=/;secure";
	                acctk = '';
	                if (AMZ_NO_TOKEN_AJAX == '0') {
	                	acctk = '&access_token=' + authRequest.access_token;
	                }
		            jQuery.ajax({
		                    type: 'POST',
		                    url: REDIRECTAMZ,
		                    data: 'ajax=true&method=setsession&amazon_id=' + amazonOrderReferenceId + acctk,
		                    success: function(htmlcontent){
		                    	window.location = REDIRECTAMZ + amazonOrderReferenceId;
		                    }
		            });
	            }
	        },
	        onError: function(error) {
	            console.log(error); 
	        }
	    });
		setTipr('#' + div_id);
	    jQuery('#' + div_id).attr('data-is-set', '1');
	}	
}

function setTipr(element) {
	if (jQuery("#amazonpay_tooltip").length > 0) {
		setTimeout(function() { jQuery(element).find("img").attr('data-tip', jQuery("#amazonpay_tooltip").html()).tipr(); }, 2000);	
	}
}
/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

function getURLParameter(name, source) {
	return decodeURIComponent((new RegExp('[?|&|#]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(source)||[,""])[1].replace(/\+/g,'%20'))||null; 
}

function amazonLogout(){
    amazon.Login.logout();
	document.cookie = "amazon_Login_accessToken=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/";
	document.cookie = "amazon_Login_state_cache=; expires=Thu, 01 Jan 1970 00:00:00 GMT";
}

var authRequest;    

jQuery(document).ready(function($){
	if ($("#authentication #SubmitCreate").length > 0 && AMZ_SHOW_REGISTRATION_PAGE == '1') {
		$("#authentication #SubmitCreate").parent().append('<div class="amazonLoginWr" id="jsLoginAuthPage">' + buildAmazonButtonContainer() + '</div>');
	}
	if ($("#authentication #customer-form button[type=submit]").length > 0 && AMZ_SHOW_REGISTRATION_PAGE == '1') {
		$("#authentication #customer-form button[type=submit]").parent().append('<div class="amazonLoginWr" id="jsLoginAuthCreationPage">' + buildAmazonButtonContainer() + '</div>');
	}
	if ($("#checkout #customer-form button[type=submit]").length > 0 && AMZ_SHOW_REGISTRATION_PAGE == '1') {
		$("#checkout #customer-form button[type=submit]").parent().append('<div class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '" id="jsLoginAuthCreationPage">' + buildAmazonButtonContainer() + '</div>');
		bindCartButton("jsLoginAuthCreationPage");
	}
	if ($("#checkout #login-form button[type=submit]").length > 0 && AMZ_SHOW_REGISTRATION_PAGE == '1') {
		$("#checkout #login-form button[type=submit]").parent().append('<div class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '" id="jsLoginAuthPage">' + buildAmazonButtonContainer() + '</div>');
		bindCartButton("jsLoginAuthPage");
	}
	if ($("#amazonLoginFormButton").length > 0) {
		$("#amazonLoginFormButton").append(buildAmazonButtonContainer());
	}
	
	if (AMZACTIVE == '1') {
		initAmazon();
    	$('.logout').click(function() {
			amazonLogout();
		});
	}
});

function buildAmazonButtonContainer(){
	return '<div class="amzbuttoncontainer"><h3>' + AMZ_USE_ACCOUNT_HEAD + '</h3><p>' + AMZ_USE_ACCOUNT_BODY + '</p></div>';
}

function initAmazon(){

	if($('.amazonLoginWr').length > 0){
		var amazonElementsSelected = [];
		var amazonElements = [];
		$('.amazonLoginWr').each(function(){
		   if (!amazonElementsSelected.includes($(this).attr("id"))) {
			   amazonElementsSelected.push($(this).attr("id"));
			   amazonElements.push($(this));
		   }
		});
		amazonElements = jQuery.unique(amazonElements);
		$(amazonElements).each(function(){
	   		var amzBtnColor = AMZ_BUTTON_COLOR_LPA;
	   		if ($(this).attr("id") == "amazonLogin")
	   			amzBtnColor = AMZ_BUTTON_COLOR_LPA_NAVI;
	   		var redirectURL = LOGINREDIRECTAMZ;
	   		var redirectToCheckout = false;
	   		var redirectState = '';
	   		if ($(this).attr("id") == "jsLoginAuthPage" || $(this).attr("id") == "jsLoginAuthCreationPage") {
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
	        
	        
	  });
	}
	if (LPA_MODE == 'login_pay') {
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
	if (AMZ_SHOW_IN_CART_POPUP == '1') {
		setInterval(checkForModalIntegration, 1000);
	}
	setTipr(".amazonLoginWr");
}

function checkForModalIntegration() {
    if ($('#blockcart-modal .cart-content-btn').length > 0) {
    	if ($('#blockcart-modal #payWithAmazonLayerCartDiv').length == 0) {
            $('#blockcart-modal .cart-content-btn').parent().append('<span id="payWithAmazonLayerCartDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
            bindCartButton('payWithAmazonLayerCartDiv');    		
    	}
    }
}


function checkForAmazonListButton() {
	if (jQuery("#pay_with_amazon_list_button").length > 0) {
		if (jQuery.trim(jQuery("#pay_with_amazon_list_button").html()) == '') {
			jQuery("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
			bindCartButton('payWithAmazonListDiv');
		}
	}	
	if (jQuery("#HOOK_ADVANCED_PAYMENT").length > 0) {
		if (jQuery("#payWithAmazonListDiv").length == 0) {
			jQuery("#HOOK_ADVANCED_PAYMENT").append('<span id="payWithAmazonListDiv" class="' + (AMZ_CREATE_ACCOUNT_EXP == '1' ? 'amz_create_account' : null) + '"></span>');
			bindCartButton('payWithAmazonListDiv');
		}
	}
	if (jQuery("input[data-module-name=amzpayments]").length > 0 && jQuery("#pay_with_amazon_list_button").length > 0) {
		if (jQuery("input[data-module-name=amzpayments]").prop("checked")) {
			jQuery("#payment-confirmation button[type=submit]").attr("disabled", "disabled");
		}
	}
}

function bindCartButton(div_id) {
	if (jQuery('#' + div_id).attr('data-is-set') != '1') {
		var redirectState = '';
		if (useRedirect) {
			redirectState = '&toCheckout=1';
		}
		var redirectURL = LOGINREDIRECTAMZ;
		var addToCheckoutInPopup = '';
		if (jQuery('#' + div_id).attr('data-checkout') == '1' && !useRedirect) {
			addToCheckoutInPopup = '&toCheckout=1';
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
	            jQuery(div_id).html('');
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
		                    	window.location = REDIRECTAMZ + amazonOrderReferenceId + addToCheckoutInPopup;
		                    }
		            });
	            }
	        },
	        onError: function(error) {
	            console.log(error); 
	        }
	    });
	    jQuery('#' + div_id).attr('data-is-set', '1');
	    setTipr('#' + div_id);
	}	
}

function bindBuyNowButton(div_id) {
	if ($("#add-to-cart-or-refresh button[type=submit]").length > 0) {
		bindCartButton(div_id);
		window.setTimeout(function() {
			$("#" + div_id).hide();	
			var newImg = $('<img />');
			newImg.attr("src", $("#" + div_id + " img").attr("src")).addClass('amzButtonProductdetail');
			if ($("#" + div_id).parent().find('.button_enhanced').length > 0) {
				$("#" + div_id).parent().find('.button_enhanced').append(newImg);				
			} else {
				$("#" + div_id).parent().append(newImg);				
			}
			newImg.on('click', function() {
				$("#add-to-cart-or-refresh button[type=submit]").trigger('click');
				setTimeout(function(){ $("#" + div_id + " img").trigger('click'); }, 1000);		
			});
		}, 1000);  
	}
}

function setTipr(element) {
	if (jQuery("#amazonpay_tooltip").length > 0) {
		setTimeout(function() { jQuery(element).find("img").attr('data-tip', jQuery("#amazonpay_tooltip").html()).tipr(); }, 2000);	
	}
}
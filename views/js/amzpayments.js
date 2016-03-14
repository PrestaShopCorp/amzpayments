/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

var amazonOrderReferenceId;
new OffAmazonPayments.Widgets.Button ({
		sellerId: AMZSELLERID,
		onSignIn: function(orderReference) {
			amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
			window.location = REDIRECTAMZ + amazonOrderReferenceId;
		},
		onError: function(error) {
		}
}).bind("payWithAmazonDiv");

jQuery(document).ready(function($) {
	if (AMZACTIVE == '1') {
		if ($('#button_order_cart').length > 0 && $('#amz_cart_widgets_summary').length == 0) {
			$('#button_order_cart').before('<div id="payWithAmazonCartDiv"></div>');
			new OffAmazonPayments.Widgets.Button ({
				sellerId: AMZSELLERID,
				buttonSettings: {size: AMZ_BUTTON_SIZE_PAY, color: AMZ_BUTTON_COLOR_PAY},
				onSignIn: function(orderReference) {
					amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
					window.location = REDIRECTAMZ + amazonOrderReferenceId;
				},
				onError: function(error) {
				}
			}).bind("payWithAmazonCartDiv");
		}
		if ($("#pay_with_amazon_list_button").length > 0) {
			$("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv"></span>');
			new OffAmazonPayments.Widgets.Button ({
				sellerId: AMZSELLERID,
				buttonSettings: {size: AMZ_BUTTON_SIZE_PAY, color: AMZ_BUTTON_COLOR_PAY},
				onSignIn: function(orderReference) {
					amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
					window.location = REDIRECTAMZ + amazonOrderReferenceId;
				},
				onError: function(error) {
				}
			}).bind("payWithAmazonListDiv");
		}
		
		var have_clicked = false;
		$("a.amzPayments").click(function() {
			if (!have_clicked) {
				have_clicked = true;
				eventFire(document.getElementById($("#payWithAmazonListDiv img").attr("id")), 'click');
				setTimeout(function() { have_clicked = false; }, 1000);
			}
			return false;
		});
				
		setInterval(checkForAmazonListButton(), 1000);
		
	}
});
function eventFire(el, etype){
	if (el.fireEvent) {
		el.fireEvent('on' + etype);
	} else {
		var evObj = document.createEvent('Events');
		evObj.initEvent(etype, true, false);
		el.dispatchEvent(evObj);
	}
}
function checkForAmazonListButton() {
	if (jQuery("#pay_with_amazon_list_button").length > 0) {
		if (jQuery.trim(jQuery("#pay_with_amazon_list_button").html()) == '') {
			jQuery("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv"></span>');
			new OffAmazonPayments.Widgets.Button ({
				sellerId: AMZSELLERID,
				buttonSettings: {size: AMZ_BUTTON_SIZE_PAY, color: AMZ_BUTTON_COLOR_PAY},
				onSignIn: function(orderReference) {
					amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
					window.location = REDIRECTAMZ + amazonOrderReferenceId;
				},
				onError: function(error) {
				}
			}).bind("payWithAmazonListDiv");
		}
	}		
	if (jQuery("#HOOK_ADVANCED_PAYMENT").length > 0) {
		if (jQuery("#payWithAmazonListDiv").length == 0) {
			jQuery("#HOOK_ADVANCED_PAYMENT").append('<span id="payWithAmazonListDiv"></span>');
			new OffAmazonPayments.Widgets.Button ({
				sellerId: AMZSELLERID,
				buttonSettings: {size: AMZ_BUTTON_SIZE_PAY, color: AMZ_BUTTON_COLOR_PAY},
				onSignIn: function(orderReference) {
					amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
					window.location = REDIRECTAMZ + amazonOrderReferenceId;
				},
				onError: function(error) {
				}
			}).bind("payWithAmazonListDiv");
		}
	}	
}
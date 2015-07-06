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
				onSignIn: function(orderReference) {
					amazonOrderReferenceId = orderReference.getAmazonOrderReferenceId();
					window.location = REDIRECTAMZ + amazonOrderReferenceId;
				},
				onError: function(error) {
				}
			}).bind("payWithAmazonListDiv");
		}
		
		$("a.amzPayments").click(function() {
			return false;
		});
				
		setInterval(checkForAmazonListButton(), 1000);
		
	}
});

function checkForAmazonListButton() {
	if (jQuery("#pay_with_amazon_list_button").length > 0) {
		if (jQuery.trim(jQuery("#pay_with_amazon_list_button").html()) == '') {
			jQuery("#pay_with_amazon_list_button").append('<span id="payWithAmazonListDiv"></span>');
			new OffAmazonPayments.Widgets.Button ({
				sellerId: AMZSELLERID,
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
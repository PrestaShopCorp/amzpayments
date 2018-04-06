/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2018 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

$(document).ready(function() {
	
	$("#checkout-addresses-step .delete-address, #checkout-addresses-step .add-address, a[data-link-action=different-invoice-address]").hide();
	
	if ($("#delivery-addresses .address-item").length > 0) {
		$("#delivery-addresses .address-item:not(.selected)").hide();	
	}
	
});

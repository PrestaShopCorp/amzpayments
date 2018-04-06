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
	if ($("#order label[for=id_address_delivery]").length > 0) {
		$("#order label[for=id_address_delivery]").closest(".row").hide();	
	}
	if ($("#order .addresses p.address_add").length > 0) {
		$("#order .addresses p.address_add").hide();
	}
	
});

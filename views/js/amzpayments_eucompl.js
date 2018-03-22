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
	if ($("#HOOK_ADVANCED_PAYMENT").length > 0) {
		if ($("a.payment_module_adv").length > 0) {
			$("a.payment_module_adv").each(function() {
				if ($(this).html().indexOf('amzpay') < 0 && $(this).html().indexOf('Amazon') < 0) {
					$(this).closest(".payment_module").hide();
				}
			});
		}		
	}
});

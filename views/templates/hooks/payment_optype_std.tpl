{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{literal}
<script>
jQuery(document).ready(function($) {
	if ($(".payment-option").length > 0) {
		$(".payment-option").each(function() {
			if ($(this).find("span input[type=radio]").first().attr('data-module-name') != 'amzpayments') {
				$(this).hide();
			} else if ($(this).find("input[type=radio]").first().attr('data-module-name') != 'amzpayments') {
				$(this).hide();
			}
		});		
	}
});
</script>
{/literal}
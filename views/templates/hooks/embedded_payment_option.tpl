{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

<div {*if $this_hide_button} style="display:none;"{/if*}>
	<span id="pay_with_amazon_list_button"></span>	
	<p>
		{l s='To pay with Amazon, please click on the Amazon Payments button and log in with your credentials.' mod='amzpayments'}
	</p>
</div>
<script type="text/javascript"> setInterval(checkForAmazonListButton(), 250); </script>
{if $this_hide_button}
{literal}
<script type="text/javascript"> 
jQuery(document).ready(function($) {
	if ($(".payment-option").length > 0) {
		$(".payment-option").each(function() {
			if ($(this).find("span input[type=radio]").first().attr('data-module-name') == 'amzpayments') {
				$(this).hide();
			} else if ($(this).find("input[type=radio]").first().attr('data-module-name') == 'amzpayments') {
				$(this).hide();
			}
		});		
	}
});
</script>
{/literal}
{/if}
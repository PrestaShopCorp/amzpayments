{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div class="row"{if $this_hide_button} style="display:none;"{/if}>
	<div class="col-xs-12">
		<p class="payment_module">	
			<a class="amzPaymentsPayNow" href="{$link->getModuleLink('amzpayments', 'payment', [], true)|escape:'html'}" style="background-image: url({$this_path_amzpayments|escape:'htmlall':'UTF-8'}views/img/amazonpayments.png); background-position: 10px center; background-repeat: no-repeat; ">				
				<span>{l s='Buy now with Amazon' mod='amzpayments'}</span>
			</a>
		</p>
    </div>
</div>
{literal}
<script>
var show_alt = '';
$(document).ready(function() {
	if ($(".payment_module").length > 0) {
		$(".payment_module").each(function() {
			if (!$(this).find("a").first().hasClass('amzPaymentsPayNow')) {
				$(this).hide();
			}
		});		
	}
});
</script>
{/literal}
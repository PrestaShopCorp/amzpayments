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
			<a class="amzPayments" style="background: url({$this_path_amzpayments|escape:'htmlall':'UTF-8'}views/img/amazonpayments.png) 15px 12px no-repeat #fbfbfb; ">				
				{l s='Pay with Amazon' mod='amzpayments'}&nbsp;<span>{l s='(Comfortable and save payment with your amazon-account)' mod='amzpayments'}</span>
				<span id="pay_with_amazon_list_button"></span>
			</a>	
		</p>
    </div>
</div>
<script type="text/javascript"> setInterval(checkForAmazonListButton(), 1000); </script>
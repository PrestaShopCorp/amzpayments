{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
{nocache}
<script>
{literal}

$(document).ready(function() {	
   
});
{/literal}
</script>

<h1>{l s='Thank you for your login with Amazon Payments' mod='amzpayments'}</h1>

<div class="row">
	<div class="col-xs-12 col-sm-6">
		<form action="{$link->getModuleLink('amzpayments', 'connect_accounts')|escape:'html':'UTF-8'}" method="post" id="login_form" class="box">
			<input type="hidden" name="action" value="tryConnect" />
			<input type="hidden" name="email" value="{$amzConnectEmail|escape:'htmlall':'UTF-8'}" />
			{if $toCheckout}<input type="hidden" name="toCheckout" value="1" />{/if}
			{if $fromCheckout}<input type="hidden" name="fromCheckout" value="1" />{/if}
			<p>{l s='There is already a customer account with this e-mail-address in our shop. Please enter your password to connect it with your Amazon-account.' mod='amzpayments'}</p>
			<div class="form_content clearfix">				
				<div class="form-group">
					<label for="passwd">{l s='Password' mod='amzpayments'}</label>
					<span><input class="is_required validate account_input form-control" type="password" data-validate="isPasswd" id="passwd" name="passwd" value="{if isset($smarty.post.passwd)}{$smarty.post.passwd|stripslashes|escape:'htmlall':'UTF-8'}{/if}" /></span>
				</div>
				<p class="submit">
					<button type="submit" id="SubmitLogin" name="SubmitLogin" class="button btn btn-default button-medium">
						<span>
							<i class="icon-lock left"></i>
							{l s='Connect accounts' mod='amzpayments'}
						</span>
					</button>
				</p>
			</div>
		</form>
	</div>
</div>



{/nocache}
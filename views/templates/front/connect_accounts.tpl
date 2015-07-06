{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
{nocache}
<script>
{literal}

$(document).ready(function() {	
   
});
{/literal}
</script>

<h1>{l s='Vielen Dank für Ihre Anmeldung mit Amazon Payments' mod='amzpayments'}</h1>

<div class="row">
	<div class="col-xs-12 col-sm-6">
		<form action="{$link->getModuleLink('amzpayments', 'connect_accounts')|escape:'html':'UTF-8'}" method="post" id="login_form" class="box">
			<input type="hidden" name="action" value="tryConnect" />
			<input type="hidden" name="email" value="{$smarty.session.amzConnectEmail}" />
			{if $toCheckout}<input type="hidden" name="toCheckout" value="1" />{/if}
			<p>{l s='In unserem Shop existiert bereits ein Benutzerkonto mit dieser E-Mail-Addresse. Bitte geben Sie Ihr Passwort ein, um dieses mit Ihrem Amazon-Konto zu verknüpfen.' mod='amzpayments'}</p>
			<div class="form_content clearfix">				
				<div class="form-group">
					<label for="passwd">{l s='Password' mod='amzpayments'}</label>
					<span><input class="is_required validate account_input form-control" type="password" data-validate="isPasswd" id="passwd" name="passwd" value="{if isset($smarty.post.passwd)}{$smarty.post.passwd|stripslashes}{/if}" /></span>
				</div>
				<p class="submit">
					<button type="submit" id="SubmitLogin" name="SubmitLogin" class="button btn btn-default button-medium">
						<span>
							<i class="icon-lock left"></i>
							{l s='Konten jetzt verknüpfen' mod='amzpayments'}
						</span>
					</button>
				</p>
			</div>
		</form>
	</div>
</div>



{/nocache}
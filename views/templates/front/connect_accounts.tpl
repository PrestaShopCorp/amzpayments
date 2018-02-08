{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{extends file='page.tpl'}

{block name='page_content'}
{nocache}
<script>
{literal}

$(document).ready(function() {	
   
});
{/literal}
</script>

<section id="main">
	<header class="page-header">
		<h1>{l s='Thank you for your login with Amazon Payments' mod='amzpayments'}</h1>
	</header>
	<section id="content" class="page-content">
		<form action="{$link->getModuleLink('amzpayments', 'connectaccounts')|escape:'html':'UTF-8'}" method="post" id="login_form" class="box">
			<input type="hidden" name="action" value="tryConnect" />
			<input type="hidden" name="email" value="{$amzConnectEmail}" />
			{if $toCheckout}<input type="hidden" name="toCheckout" value="1" />{/if}
			{if $fromCheckout}<input type="hidden" name="fromCheckout" value="1" />{/if}
			<p>{l s='There is already a customer account with this e-mail-address in our shop. Please enter your password to connect it with your Amazon-account.' mod='amzpayments'}</p>
			<div class="form_content clearfix">				
				<div class="form-group">
					<label for="passwd">{l s='Password' mod='amzpayments'}</label>
					<span><input class="is_required validate account_input form-control" type="password" data-validate="isPasswd" id="passwd" name="passwd" value="{if isset($smarty.post.passwd)}{$smarty.post.passwd|stripslashes}{/if}" /></span>
				</div>
				<p class="submit">
					<button type="submit" id="SubmitLogin" name="SubmitLogin" class="btn btn-primary">{l s='Connect accounts' mod='amzpayments'}</button>
				</p>
			</div>
		</form>
	</section>
	<footer class="page-footer"></footer>
</section>

{/nocache}

{/block}
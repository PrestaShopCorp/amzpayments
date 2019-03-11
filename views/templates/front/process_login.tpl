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
var toCheckout = '';
var accessToken = getURLParameter("access_token", $(location).attr('href'));

if (typeof accessToken === 'string' && accessToken.match(/^Atza/)) {
	document.cookie = "amazon_Login_accessToken=" + accessToken + ";path=/;secure";
}

acctk = '';
if (AMZ_NO_TOKEN_AJAX == '0') {
	acctk = '&access_token=' + accessToken;
}

var state = getURLParameter("state", $(location).attr('href'));
if (state == '&toCheckout=1') {
	toCheckout = '&action=checkout';
}
{/literal}
{if $toCheckout}
toCheckout = '&action=checkout';
{/if}
{literal}

$(document).ready(function() {	
    $.ajax({
		type: 'POST',
		url: SETUSERAJAX,
		data: 'ajax=true' + toCheckout + '{/literal}{if $fromCheckout}&action=fromCheckout{/if}{literal}&method=setusertoshop' + acctk,
		success: function(htmlcontent) {
			if (htmlcontent == 'error') {
				alert('An error occured - please try again or contact our support');
			} else {
				window.location = htmlcontent;
			}					   
		 }
	});	
});
{/literal}
</script>

<section id="main">
	<header class="page-header">
		<h1>
			{if $fromCheckout}			
				{l s='Thank you. Your order has been successful. We now create your account.' mod='amzpayments'}
			{else}
				{l s='Thank you for your login with Amazon Payments' mod='amzpayments'}
			{/if}
		</h1>
	</header>
	<section id="content" class="page-content">
		<h3>{l s='You will be redirected in a few seconds...' mod='amzpayments'}</h3>
	</section>
	<footer class="page-footer"></footer>
</section>

{/nocache}
{/block}
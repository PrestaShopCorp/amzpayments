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
var accessToken = getURLParameter("access_token", $(location).attr('href'));
$(document).ready(function() {	
    $.ajax({
		type: 'GET',
		url: SETUSERAJAX,
		data: 'ajax=true{/literal}{if $toCheckout}&action=checkout{/if}{if $fromCheckout}&action=fromCheckout{/if}{literal}&method=setusertoshop&access_token=' + accessToken,
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

{if $fromCheckout}
<h1>
	{l s='Vielen Dank, Ihre Bestellung war erfolgreich. Wir verknüpfen nun Ihr Amazon Account mit unserem Shop.' mod='amzpayments'}
</h1>
{else}
<h1>
	{l s='Vielen Dank für Ihre Anmeldung mit Amazon Payments' mod='amzpayments'}
</h1>
{/if}

<h3>{l s='Sie werden in wenigen Sekunden weitergeleitet...' mod='amzpayments'}</h3>
{/nocache}
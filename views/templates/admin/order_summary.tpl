{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

<h3>{l s='Amazon Summary' mod='amzpayments'}</h3>

<table class="table">
	<tr>
		<td style="width: 20%">
			<strong>{l s='Authorised amount' mod='amzpayments'}</strong>
		</td>
		<td>
			{$authorized_amount|escape:'html':'UTF-8'}
		</td>
	</tr>
	<tr>
		<td>
			<strong>{l s='Withdrawn amount' mod='amzpayments'}</strong>
		</td>
		<td>
			{$captured_amount|escape:'html':'UTF-8'}
		</td>
	</tr>
	<tr>
		<td>
			<strong>{l s='Refunded amount' mod='amzpayments'}</strong>
		</td>
		<td>
			{$refunded_amount|escape:'html':'UTF-8'}	
		</td>
	</tr>
</table>
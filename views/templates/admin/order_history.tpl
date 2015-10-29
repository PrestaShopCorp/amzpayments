{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

<h3>{l s='Amazon Payments history' mod='amzpayments'}</h3>
<table class="table">
	<thead>
		<tr>
			<th>
				{l s='Transaction type' mod='amzpayments'}
			</th>
			<th>
				{l s='Amount' mod='amzpayments'}
			</th>
			<th>
				{l s='Time' mod='amzpayments'}
			</th>
			<th>
				{l s='Status' mod='amzpayments'}
			</th>
			<th>
				{l s='Last Change' mod='amzpayments'}
			</th>
			<th>
				{l s='Amazon transaction ID' mod='amzpayments'}
			</th>
			<th>
				{l s='Valid until' mod='amzpayments'}
			</th>
		</tr>
	</thead>
	<tbody>
	{foreach from=$rs item=r}
		<tr>
			<td>
				{$r.transaction_type|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.amount|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.date|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.status|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.last_change|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.tx_id|escape:'htmlall':'UTF-8'}
			</td>
			<td>
				{$r.tx_expiration|escape:'htmlall':'UTF-8'}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>
<div>
	<a href="#" class="amzAjaxLink btn btn-default button" data-action="refreshOrder" data-orderRef="{$order_ref|escape:'htmlall':'UTF-8'}">{l s='Update' mod='amzpayments'}</a>
	{if $reference_status == 'Open' || $reference_status == 'Suspended'}
		<a href="#" class="amzAjaxLink btn btn-default button" data-action="cancelOrder" data-orderRef="{$order_ref|escape:'htmlall':'UTF-8'}">{l s='Cancel order' mod='amzpayments'}</a>
		<a href="#" class="amzAjaxLink btn btn-default button" data-action="closeOrder" data-orderRef="{$order_ref|escape:'htmlall':'UTF-8'}">{l s='Close order' mod='amzpayments'}</a>
	{/if}
</div>
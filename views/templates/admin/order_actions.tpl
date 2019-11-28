{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

<h3>{l s='Actions' mod='amzpayments'}</h3>

{if isset($open_auth)}

	<h4>{l s='Capture authorised payments' mod='amzpayments'}</h4>
	
    <table class="table">
		<thead>
			<tr class="headline">
				<th class="amzAmountCell">
					{l s='Amount' mod='amzpayments'}
				</th>
				<th>
					{l s='Time' mod='amzpayments'}
				</th>
				<th>
					{l s='Amazon transaction ID' mod='amzpayments'}
				</th>
				<th>
					{l s='Valid until' mod='amzpayments'}
				</th>
				<th>
					{l s='Actions' mod='amzpayments'}
				</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$open_auth item=r}
			<tr>
				<td class="amzAmountCell">
					{$r.amount|escape:'html':'UTF-8'}
				</td>
				<td>
					{$r.date|escape:'html':'UTF-8'}
				</td>
				<td>
					{$r.tx_id|escape:'html':'UTF-8'}
				</td>
				<td>
					{$r.tx_expiration|escape:'html':'UTF-8'}
				</td>
				<td>
					<div>
						<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="captureTotalFromAuth" data-authid="{$r.tx_id|escape:'html':'UTF-8'}">{l s='Capture full amount' mod='amzpayments'}</a>
					</div>
					<div>
						<input type="text" class="amzAmountField" value="{$r.amount|escape:'html':'UTF-8'}" />
						<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="captureAmountFromAuth" data-authid="{$r.tx_id|escape:'html':'UTF-8'}">{l s='Capture partial amount' mod='amzpayments'}</a>
					</div>
				</td>
			</tr>			
		{/foreach}
		</tbody>
	</table>
	
{/if}

{if isset($authorize_tab)}

	<h4>{l s='Authorise payment' mod='amzpayments'}</h4>
	
	<table style="width:100%" class="table">
		<thead>
			<tr class="headline">
				<th class="amzAmountCell">
					{l s='Amount not yet authorised' mod='amzpayments'}
				</th>
				<th class="amzAmountCell">
					{l s='Maximum possible' mod='amzpayments'}
				</th>
				<th>
					{l s='Actions' mod='amzpayments'}
				</th>
			</tr>
		</thead>
		<tbody>
        	<tr>
				<td class="amzAmountCell">
					{$amount_left_to_authorize|escape:'html':'UTF-8'}
				</td>
				<td class="amzAmountCell">
					{$amount_maximum|escape:'html':'UTF-8'}
				</td>
				<td>
				{if $amount_left_to_authorize_raw > 0}
					<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="authorizeAmount" data-amount="{$amount_left_to_authorize_raw|escape:'html':'UTF-8'}" data-orderRef="{$order_ref|escape:'html':'UTF-8'}">{l s='Authorise payment' mod='amzpayments'}</a>
				{/if}
					<div>
						<nobr>
							<input type="text" class="amzAmountField" value="{$amount_field|escape:'html':'UTF-8'}" />
							<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="authorizeAmountFromField" data-orderRef="{$order_ref|escape:'html':'UTF-8'}">
								{if $amount_left_to_authorize_raw > 0}
									{l s='Authorise payment' mod='amzpayments'}
								{else}
									{l s='Authorise more' mod='amzpayments'}
								{/if}
							</a>
						</nobr>
					</div>
				</td>
			</tr>	
		</tbody>
	</table>

{/if}

{if isset($refunds_tab)}

	<h4>{l s='Refunds' mod='amzpayments'}</h4>
	
	<table class="table">
		<thead>
			<tr class="headline">
				<th class="amzAmountCell">
					{l s='Amount' mod='amzpayments'}
				</th>
				<th class="amzAmountCell">
					{l s='Refunded' mod='amzpayments'}
				</th>
				<th class="amzAmountCell">
					{l s='Still possible' mod='amzpayments'}
				</th>
				<th>
					{l s='Time' mod='amzpayments'}
				</th>
				<th>
					{l s='Status' mod='amzpayments'}
				</th>
				<th>
					{l s='Last change' mod='amzpayments'}
				</th>
				<th>
					{l s='Amazon transaction ID' mod='amzpayments'}
				</th>
				<th>
					{l s='Actions' mod='amzpayments'}
				</th>
			</tr>
		</thead>
		<tbody>	
		{foreach from=$captures item=r}
			<tr>
				<td class="amzAmountCell">
					{$r.amount|escape:'html':'UTF-8'}
				</td>
				<td class="amzAmountCell">
					{$r.amount_refunded|escape:'html':'UTF-8'}
				</td>
				<td class="amzAmountCell">
					{$r.amount_possible|escape:'html':'UTF-8'}
				</td>
				<td>
					{$r.date|escape:'html':'UTF-8'}
				</td>
				<td>
					<span class="{$r.status_class|escape:'html':'UTF-8'}">{$r.status|escape:'html':'UTF-8'}</span>
				</td>
				<td>
					{$r.last_change|escape:'html':'UTF-8'}
				</td>
				<td>
					{$r.tx_id|escape:'html':'UTF-8'}
				</td>
					
				<td>
				{if $r.total_refund_button}
					<div>
						<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="refundAmount" data-amount="{$r.total_refund_button_value|escape:'html':'UTF-8'}" data-captureid="{$r.tx_id|escape:'html':'UTF-8'}">{l s='Issue complete refund' mod='amzpayments'}</a>
					</div>
				{/if}					
					<div>
						<nobr>
							<input type="text" class="amzAmountField" value="{if isset($field_value)}{$field_value|escape:'html':'UTF-8'}{/if}" />
							<a href="#" class="amzAjaxLink btn btn-default button amzButton" data-action="refundAmountFromField" data-captureid="{$r.tx_id|escape:'html':'UTF-8'}">
								{if $r.total_refund_button}
									{l s='Issue partial refund' mod='amzpayments'}
								{else}
									{l s='Refund more' mod='amzpayments'}
								{/if}
							</a>
						</nobr>
					</div>
				</td>
			</tr>	
		{/foreach}
		</tbody>
	</table>
					

{/if}
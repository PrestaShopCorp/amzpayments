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

			{if $field_name eq 'vat_number'}
				<div id="vat_number">
					<div class="form-group">
						<label for="vat-number">{l s='VAT number' mod='amzpayments'}</label>
						<input type="text" class="form-control additional_field" id="vat-number" name="vat_number" value="{$field_value|escape:'html':'UTF-8'}" />
					</div>
				</div>
			{elseif $field_name eq 'dni'}
			<div class="form-group dni">
				<label for="dni">{l s='Identification number' mod='amzpayments'}</label>
				<input class="form-control additional_field" type="text" name="dni" id="dni" value="{$field_value|escape:'html':'UTF-8'}" />
				<span class="form_info">{l s='DNI / NIF / NIE' mod='amzpayments'}</span>
			</div>
			{elseif $field_name eq 'phone'}
				<div class="form-group phone-number">
					<label for="phone">{l s='Home phone' mod='amzpayments'}</label>
					<input class="form-control additional_field" type="tel" id="phone" name="phone" value="{$field_value|escape:'html':'UTF-8'}"  />
				</div>
				<div class="clearfix"></div>
			{elseif $field_name eq 'phone_mobile'}
				<div class="form-group">
					<label for="phone_mobile">{l s='Mobile phone' mod='amzpayments'}</label>
					<input class="form-control additional_field" type="tel" id="phone_mobile" name="phone_mobile" value="{$field_value|escape:'html':'UTF-8'}" />
				</div>
			{elseif $field_name eq 'id_state'}
			<div class="id_state form-group">
				<label for="id_state">{l s='State' mod='amzpayments'}</label>
				<select name="id_state" id="id_state" class="form-control additional_field">
					<option value="-1">-</option>
					{foreach from=$states item=state}
						<option value="{$state.id_state|escape:'html':'UTF-8'}" {if $state.id_state == $field_value}selected{/if}>{$state.name|escape:'html':'UTF-8'}</option>
					{/foreach}
				</select>
			</div>		
			{else}
				<div id="{$field_name|escape:'html':'UTF-8'}">
					<div class="form-group">
						<label for="vat-number">{$field_name_translated|escape:'html':'UTF-8'}</label>
						<input type="text" class="form-control additional_field" id="{$field_name|escape:'html':'UTF-8'}" name="{$field_name|escape:'html':'UTF-8'}" value="{$field_value|escape:'html':'UTF-8'}" />
					</div>
				</div>			
			{/if}	
{/nocache}
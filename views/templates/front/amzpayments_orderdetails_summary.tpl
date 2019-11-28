{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="order-detail-content" class="table_block">
	<table id="cart_summary" class="std">
		<thead>
			<tr>
				<th class="cart_product first_item">{l s='Product' mod='amzpayments'}</th>
				<th class="cart_description item">{l s='Description' mod='amzpayments'}</th>
				<th class="cart_availability item">{l s='Avail.' mod='amzpayments'}</th>
				<th class="cart_unit item">{l s='Unit price' mod='amzpayments'}</th>
				<th class="cart_quantity item">{l s='Qty' mod='amzpayments'}</th>
				<th class="cart_total last_item">{l s='Total' mod='amzpayments'}</th>
			</tr>
		</thead>
		<tfoot>
			{if $use_taxes}
				{if $priceDisplay}
					<tr class="cart_total_price">
						<td colspan="5">{if $display_tax_label}{l s='Total products (tax excl.)' mod='amzpayments'}{else}{l s='Total products' mod='amzpayments'}{/if}</td>
						<td class="price" id="total_product">{displayPrice price=$total_products}</td>
					</tr>
				{else}
					<tr class="cart_total_price">
						<td colspan="5">{if $display_tax_label}{l s='Total products (tax incl.)' mod='amzpayments'}{else}{l s='Total products' mod='amzpayments'}{/if}</td>
						<td class="price" id="total_product">{displayPrice price=$total_products_wt}</td>
					</tr>
				{/if}
			{else}
				<tr class="cart_total_price">
					<td colspan="5">{l s='Total products' mod='amzpayments'}</td>
					<td class="price" id="total_product">{displayPrice price=$total_products}</td>
				</tr>
			{/if}
			<tr class="cart_total_voucher" {if $total_wrapping == 0}style="display:none"{/if}>
				<td colspan="5">
				{if $use_taxes}
					{if $priceDisplay}
						{if $display_tax_label}{l s='Total gift wrapping (tax excl.):' mod='amzpayments'}{else}{l s='Total gift wrapping cost:' mod='amzpayments'}{/if}
					{else}
						{if $display_tax_label}{l s='Total gift wrapping (tax incl.)' mod='amzpayments'}{else}{l s='Total gift wrapping cost:' mod='amzpayments'}{/if}
					{/if}
				{else}
					{l s='Total gift wrapping cost:' mod='amzpayments'}
				{/if}
				</td>
				<td class="price-discount price" id="total_wrapping">
				{if $use_taxes}
					{if $priceDisplay}
						{displayPrice price=$total_wrapping_tax_exc}
					{else}
						{displayPrice price=$total_wrapping}
					{/if}
				{else}
					{displayPrice price=$total_wrapping_tax_exc}
				{/if}
				</td>
			</tr>
			{if $total_shipping_tax_exc <= 0 && !isset($virtualCart)}
				<tr class="cart_total_delivery">
					<td colspan="5">{l s='Shipping:' mod='amzpayments'}</td>
					<td class="price" id="total_shipping">{l s='Free Shipping!' mod='amzpayments'}</td>
				</tr>
			{else}
				{if $use_taxes && $total_shipping_tax_exc != $total_shipping}
					{if $priceDisplay}
						<tr class="cart_total_delivery" {if $shippingCost <= 0} style="display:none"{/if}>
							<td colspan="5">{if $display_tax_label}{l s='Total shipping (tax excl.)' mod='amzpayments'}{else}{l s='Total shipping' mod='amzpayments'}{/if}</td>
							<td class="price" id="total_shipping">{displayPrice price=$shippingCostTaxExc}</td>
						</tr>
					{else}
						<tr class="cart_total_delivery"{if $shippingCost <= 0} style="display:none"{/if}>
							<td colspan="5">{if $display_tax_label}{l s='Total shipping (tax incl.)' mod='amzpayments'}{else}{l s='Total shipping' mod='amzpayments'}{/if}</td>
							<td class="price" id="total_shipping" >{displayPrice price=$shippingCost}</td>
						</tr>
					{/if}
				{else}
					<tr class="cart_total_delivery"{if $shippingCost <= 0} style="display:none"{/if}>
						<td colspan="5">{l s='Total shipping' mod='amzpayments'}</td>
						<td class="price" id="total_shipping" >{displayPrice price=$shippingCostTaxExc}</td>
					</tr>
				{/if}
			{/if}
			<tr class="cart_total_voucher" {if $total_discounts == 0}style="display:none"{/if}>
				<td colspan="5">
				{if $use_taxes}
					{if $priceDisplay}
						{if $display_tax_label}{l s='Total vouchers (tax excl.)' mod='amzpayments'}{else}{l s='Total vouchers' mod='amzpayments'}{/if}
					{else}
						{if $display_tax_label}{l s='Total vouchers (tax incl.)' mod='amzpayments'}{else}{l s='Total vouchers' mod='amzpayments'}{/if}
					{/if}
				{else}
					{l s='Total vouchers' mod='amzpayments'}
				{/if}
				</td>
				<td class="price-discount price" id="total_discount">
				{if $use_taxes}
					{if $priceDisplay}
						{displayPrice price=$total_discounts_tax_exc*-1}
					{else}
						{displayPrice price=$total_discounts*-1}
					{/if}
				{else}
					{displayPrice price=$total_discounts_tax_exc*-1}
				{/if}
				</td>
			</tr>
			{if $use_taxes}
				{if $priceDisplay && $total_tax != 0}
					<tr class="cart_total_tax">
						<td colspan="5">{l s='Total tax:' mod='amzpayments'}</td>
						<td class="price" id="total_tax" >{displayPrice price=$total_tax}</td>
					</tr>
				{/if}
			<tr class="cart_total_price">
				<td colspan="5" id="cart_voucher" class="cart_voucher">
				{if $voucherAllowed}
					{if isset($errors_discount) && $errors_discount}
						<ul class="error">
						{foreach from=$errors_discount key=k item=error}
							<li>{$error|escape:'htmlall':'UTF-8' mod='amzpayments'}</li>
						{/foreach}
						</ul>
					{/if}
				{/if}
				</td>
				<td colspan="2" class="price total_price_container" id="total_price_container">
					<p>{l s='Total' mod='amzpayments'}</p>
					<span id="total_price">{displayPrice price=$total_price}</span>
				</td>
			</tr>
			{else}
			<tr class="cart_total_price">
				<td colspan="5" id="cart_voucher" class="cart_voucher">
				{if $voucherAllowed}
				<div id="cart_voucher" class="table_block">
					{if isset($errors_discount) && $errors_discount}
						<ul class="error">
						{foreach from=$errors_discount key=k item=error}
							<li>{$error|escape:'htmlall':'UTF-8' mod='amzpayments'}</li>
						{/foreach}
						</ul>
					{/if}
					{if $voucherAllowed}
					<form action="{if $opc}{$link->getPageLink('order-opc', true)|escape:'htmlall':'UTF-8'}{else}{$link->getPageLink('order', true)|escape:'htmlall':'UTF-8'}{/if}" method="post" id="voucher">
						<fieldset>
							<p class="title_block"><label for="discount_name">{l s='Vouchers' mod='amzpayments'}</label></p>
							<p>
								<input type="text" id="discount_name" name="discount_name" value="{if isset($discount_name) && $discount_name}{$discount_name|escape:'htmlall':'UTF-8'}{/if}" />
							</p>
							<p class="submit"><input type="hidden" name="submitDiscount" /><input type="submit" name="submitAddDiscount" value="{l s='ok' mod='amzpayments'}" class="button" /></p>
						{if $displayVouchers}
							<p id="title" class="title_offers">{l s='Take advantage of our offers:' mod='amzpayments'}</p>
							<div id="display_cart_vouchers">
							{foreach from=$displayVouchers item=voucher}
								<span onclick="$('#discount_name').val('{$voucher.name|escape:'htmlall':'UTF-8'}');return false;" class="voucher_name">{$voucher.name|escape:'htmlall':'UTF-8'}</span> - {$voucher.description|escape:'htmlall':'UTF-8'} <br />
							{/foreach}
							</div>
						{/if}
						</fieldset>
					</form>
					{/if}
				</div>
				{/if}
				</td>
				<td colspan="2" class="price total_price_container" id="total_price_container">
					<p>{l s='Total' mod='amzpayments'}</p>
					<span id="total_price">{displayPrice price=$total_price_without_tax}</span>
				</td>
			</tr>
			{/if}
		</tfoot>
		<tbody>
		{foreach from=$products item=product name=productLoop}
			{assign var='productId' value=$product.id_product}
			{assign var='productAttributeId' value=$product.id_product_attribute}
			{assign var='quantityDisplayed' value=0}
			{assign var='cannotModify' value=1}
			{assign var='odd' value=$product@iteration%2}
			{assign var='noDeleteButton' value=1}
			{* Display the product line *}
			{include file="$tpl_dir./shopping-cart-product-line.tpl"}
			{* Then the customized datas ones*}
			{if isset($customizedDatas.$productId.$productAttributeId)}
				{foreach from=$customizedDatas.$productId.$productAttributeId[$product.id_address_delivery] key='id_customization' item='customization' mod='amzpayments'}
					<tr id="product_{$product.id_product|escape:'htmlall':'UTF-8'}_{$product.id_product_attribute|escape:'htmlall':'UTF-8'}_{$id_customization|escape:'htmlall':'UTF-8'}" class="alternate_item cart_item">
						<td colspan="4">
							{foreach from=$customization.datas key='type' item='datas' mod='amzpayments'}
								{if $type == $CUSTOMIZE_FILE}
									<div class="customizationUploaded">
										<ul class="customizationUploaded">
											{foreach from=$datas item='picture' mod='amzpayments'}
												<li>
													<img src="{$pic_dir|escape:'htmlall':'UTF-8'}{$picture.value|escape:'htmlall':'UTF-8'}_small" alt="" class="customizationUploaded" />
												</li>
											{/foreach}
										</ul>
									</div>
								{elseif $type == $CUSTOMIZE_TEXTFIELD}
									<ul class="typedText">
										{foreach from=$datas item='textField' name='typedText' mod='amzpayments'}
											<li>
												{if $textField.name}
													{l s='%s:' sprintf=$textField.name mod='amzpayments'}
												{else}
													{l s='Text #%s:' sprintf=$smarty.foreach.typedText.index+1 mod='amzpayments'}
												{/if}
												{$textField.value|escape:'htmlall':'UTF-8'}
											</li>
										{/foreach}
									</ul>
								{/if}
							{/foreach}
						</td>
						<td class="cart_quantity">
							{if isset($cannotModify) AND $cannotModify == 1}
								<span style="float:left">{if $quantityDisplayed == 0 AND isset($customizedDatas.$productId.$productAttributeId)}{$customizedDatas.$productId.$productAttributeId|@count|escape:'htmlall':'UTF-8'}{else}{$product.cart_quantity-$quantityDisplayed|escape:'htmlall':'UTF-8'}{/if}</span>
							{else}
								<div style="float:right">
									<a rel="nofollow" class="cart_quantity_delete" id="{$product.id_product}_{$product.id_product_attribute}_{$id_customization}" href="{$link->getPageLink('cart', true, NULL, "delete=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;token={$token_cart}")|escape:'htmlall':'UTF-8' mod='amzpayments'}"><img src="{$img_dir}icon/delete.gif" alt="{l s='Delete' mod='amzpayments'}" title="{l s='Delete this customization' mod='amzpayments'}" width="11" height="13" class="icon" /></a>
								</div>
								<div id="cart_quantity_button" style="float:left">
								<a rel="nofollow" class="cart_quantity_up" id="cart_quantity_up_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}" href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;token={$token_cart}")|escape:'htmlall':'UTF-8' mod='amzpayments'}" title="{l s='Add' mod='amzpayments'}"><img src="{$img_dir}icon/quantity_up.gif" alt="{l s='Add' mod='amzpayments'}" width="14" height="9" /></a><br />
								{if $product.minimal_quantity < ($customization.quantity -$quantityDisplayed) OR $product.minimal_quantity <= 1}
								<a rel="nofollow" class="cart_quantity_down" id="cart_quantity_down_{$product.id_product}_{$product.id_product_attribute}_{$id_customization}" href="{$link->getPageLink('cart', true, NULL, "add=1&amp;id_product={$product.id_product|intval}&amp;ipa={$product.id_product_attribute|intval}&amp;id_customization={$id_customization}&amp;op=down&amp;token={$token_cart}")|escape:'htmlall':'UTF-8' mod='amzpayments'}" title="{l s='Subtract' mod='amzpayments'}">
									<img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/quantity_down.gif" alt="{l s='Subtract' mod='amzpayments'}" width="14" height="9" />
								</a>
								{else}
								<a class="cart_quantity_down" style="opacity: 0.3;" id="cart_quantity_down_{$product.id_product|escape:'htmlall':'UTF-8'}_{$product.id_product_attribute|escape:'htmlall':'UTF-8'}_{$id_customization|escape:'htmlall':'UTF-8'}" href="#" title="{l s='Subtract' mod='amzpayments'}">
									<img src="{$img_dir|escape:'htmlall':'UTF-8'}icon/quantity_down.gif" alt="{l s='Subtract' mod='amzpayments'}" width="14" height="9" />
								</a>
								{/if}
								</div>
								<input type="hidden" value="{$customization.quantity|escape:'htmlall':'UTF-8'}" name="quantity_{$product.id_product|escape:'htmlall':'UTF-8'}_{$product.id_product_attribute|escape:'htmlall':'UTF-8'}_{$id_customization|escape:'htmlall':'UTF-8'}_hidden"/>
								<input size="2" type="text" value="{$customization.quantity|escape:'htmlall':'UTF-8'}" class="cart_quantity_input" name="quantity_{$product.id_product|escape:'htmlall':'UTF-8'}_{$product.id_product_attribute|escape:'htmlall':'UTF-8'}_{$id_customization|escape:'htmlall':'UTF-8'}"/>
							{/if}
						</td>
						<td class="cart_total"></td>
					</tr>
					{assign var='quantityDisplayed' value=$quantityDisplayed+$customization.quantity}
				{/foreach}
				{* If it exists also some uncustomized products *}
				{if $product.quantity-$quantityDisplayed > 0}{include file="$tpl_dir./shopping-cart-product-line.tpl"}{/if}
			{/if}
		{/foreach}
		{assign var='last_was_odd' value=$product@iteration%2}
		{foreach $gift_products as $product}
			{assign var='productId' value=$product.id_product}
			{assign var='productAttributeId' value=$product.id_product_attribute}
			{assign var='quantityDisplayed' value=0}
			{assign var='odd' value=($product@iteration+$last_was_odd)%2}
			{assign var='ignoreProductLast' value=isset($customizedDatas.$productId.$productAttributeId)}
			{assign var='cannotModify' value=1}
			{* Display the gift product line *}
			{include file="./shopping-cart-product-line.tpl" productLast=$product@last productFirst=$product@first}
		{/foreach}
		</tbody>
	{if count($discounts)}
		<tbody>
		{foreach from=$discounts item=discount name=discountLoop}
			<tr class="cart_discount {if $smarty.foreach.discountLoop.last}last_item{elseif $smarty.foreach.discountLoop.first}first_item{else}item{/if}" id="cart_discount_{$discount.id_discount|escape:'htmlall':'UTF-8'}">
				<td class="cart_discount_name" colspan="2">{$discount.name|escape:'htmlall':'UTF-8'}</td>
				<td class="cart_discount_description" colspan="3">{$discount.description|escape:'htmlall':'UTF-8'}</td>
				<td class="cart_discount_price">
					<span class="price-discount">
						{if $discount.value_real > 0}
							{if !$priceDisplay}
								{displayPrice price=$discount.value_real*-1}
							{else}
								{displayPrice price=$discount.value_tax_exc*-1}
							{/if}
						{/if}
					</span>
				</td>
			</tr>
		{/foreach}
		</tbody>
	{/if}
	</table>
</div>
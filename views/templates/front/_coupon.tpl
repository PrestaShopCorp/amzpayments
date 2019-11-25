{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{if $cart.vouchers.allowed}
  <div class="block-promo">
    <div class="cart-voucher">
      {if $cart.vouchers.added}
        <h3>
        	{l s='Redeemed vouchers' mod='amzpayments'}
        </h3>
        <div class="promo-list">
          <ul class="promo-name card-block">
            {foreach from=$cart.vouchers.added item=voucher}
              <li class="cart-summary-line">
                <span class="label">{$voucher.name}</span>
                <a href="JavaScript:void(0)" data-voucher-id="{$voucher.id_cart_rule}" data-link-action="remove-voucher" class="remove-voucher-a"><i class="material-icons">{l s='delete' d='Shop.Theme.Actions'}</i></a>
                <div class="pull-xs-right">
                  {$voucher.reduction_formatted}
                </div>
              </li>
            {/foreach}
          </ul>
        </div>
      {/if}
      <p>
        <a class="collapse-button promo-code-button" data-toggle="collapse" href="#promo-code" aria-expanded="false" aria-controls="promo-code">
          {l s='Have a promo code?' d='Shop.Theme.Checkout'}
        </a>
      </p>    
      <div class="promo-code collapse" id="promo-code">
        <input id="promo-input" class="promo-input" type="text" name="discount_name" placeholder="{l s='Promo code' d='Shop.Theme.Checkout'}">
        <button type="button" class="btn btn-primary" id="promo-input-btn"><span>{l s='Add' d='Shop.Theme.Actions'}</span></button>        
      </div>
    </div>
  </div>
{/if}
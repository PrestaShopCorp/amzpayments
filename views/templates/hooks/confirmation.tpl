{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<p>{l s='Your order on' mod='amzpayments'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='amzpayments'}
	<br /><br />
	{l s='You have chosen the Amazon Payments method.' mod='amzpayments'}
	<br /><br /><span class="bold">{l s='Your order will be sent very soon.' mod='amzpayments'}</span>
	<br /><br />{l s='For any questions or for further information, please contact our' mod='amzpayments'} <a href="{$link->getPageLink('contact-form', true)}">{l s='customer support' mod='amzpayments'}</a>.
</p>

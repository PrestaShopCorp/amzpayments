{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}
<div id="payWithAmazonMainDiv"{if $hide_button} style="display:none;"{/if}>
	<div id="payWithAmazonDiv">
		{if $preBuildButton}
			<img src="{$btn_url|escape:'htmlall':'UTF-8'}?sellerId={$sellerID|escape:'htmlall':'UTF-8'}&size={$size|escape:'htmlall':'UTF-8'}&color={$color|escape:'htmlall':'UTF-8'}" style="cursor: pointer;"/>
		{/if}
	</div>
</div>
{literal}<script> bindCartButton('payWithAmazonDiv'); </script>{/literal}
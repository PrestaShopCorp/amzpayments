{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

<script src="../modules/amzpayments/views/js/admin.js"></script>
<input type="hidden" class="amzAjaxHandler" value="../modules/amzpayments/ajax.php" />
<br />
<div class="panel">
	<div class="row">
		<h3>
			<i class="icon-money"></i>
			{$displayName}
		</h3>
		<div class="amzAdminWr amzContainer16" data-orderRef="{$amazon_order_reference_id}">
			<div class="panel amzAdminOrderHistoryWr">
				<div class="amzAdminOrderHistory">
					{$orderHistory}
				</div>
			</div>
			<div class="panel amzAdminOrderSummary">
				{$orderSummary}
			</div>
			<div class="panel amzAdminOrderActions">
				{$orderActions}
			</div>
		</div>
	</div>
</div>
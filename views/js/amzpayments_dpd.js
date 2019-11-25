/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

jQuery(document).ready(function($){
	
	function setDPDActions() {
		$("#dpdfrance_relais_container .dpdfrance_lignepr form").unbind();
		$("#dpdfrance_relais_container .dpdfrance_lignepr form").on('submit',			
			function() {
			    var current_form = $(this);
				$.ajax({
					type: 'POST',
					url: current_form.attr('action'),
					data: { dpdfrance_relay_id_opc: current_form.find('input[type=submit]').val(), dpdfrance_relay_id: current_form.find('input[type=submit]').val() },
					success: function() {
						$("input[name=dpdfrance_relay_id_opc]").removeClass('dpdfrance_relais_buttonok').addClass('dpdfrance_relais_buttonchoose');
						current_form.find('input[type=submit]').removeClass('dpdfrance_relais_buttonchoose').addClass('dpdfrance_relais_buttonok');
					}
				});
				return false;
			}
		);
		
		$("#div_dpdfrance_predict_gsm form").unbind();
		$("#div_dpdfrance_predict_gsm form").on('submit',
			function() {
			    var current_form = $(this);
				$.ajax({
					type: 'POST',
					dataType: "json",
					url: REDIRECTAMZ + '?rand=' + new Date().getTime(),					
					data: { ajax: 'true', method: 'dpd_predict', dpdfrance_predict_gsm_dest: current_form.find('input[name=dpdfrance_predict_gsm_dest]').val() },
					success: function(data) {
						if (data.isSuccess) {
							current_form.find('input[type=submit]').attr('id', 'dpdfrance_predict_gsm_button_opc_ok');
						} else {
							alert(data.message);
						}
					}
				});
				return false;				
			}
		);
		
	}

	setInterval(setDPDActions, 1000);	
	
});


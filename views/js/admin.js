/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*/

var blockHistoryReload;
var blockActionReload;
var ajaxHandler;
var lastHistory;
var lastActions;
var lastSummary;
var requestIsRunning = false;

$(document).ready(function(){
    ajaxHandler = $('.amzAjaxHandler').val();   
    $(".amzContainer15").parent("fieldset").parent("div").css("overflow", "scroll");
    
    $("#simple_path_parse").click(function() {
    	var json = $.trim($("#simple_path").val());
    	try {
    	    jsonData = $.parseJSON(json);
    	    if (jsonData === null) {
    	    	alert('Parsing error: Please enter a correct string!');
    	    } else {
    	    	if (typeof jsonData.merchant_id != 'undefined') { $("#AMZ_MERCHANT_ID").val(jsonData.merchant_id); }
    	    	if (typeof jsonData.access_key != 'undefined') { $("#ACCESS_KEY").val(jsonData.access_key); }
    	    	if (typeof jsonData.secret_key != 'undefined') { $("#SECRET_KEY").val(jsonData.secret_key); }
    	    	if (typeof jsonData.client_id != 'undefined') { $("#AMZ_CLIENT_ID").val(jsonData.client_id); }
    	    	$("#simple_path").val('');
    	    	alert('Parsing successful.');
    	    }
    	} catch (e) {
    	    alert('Parsing error: Please check your pasted data!');
    	}
    });
    
});

$(document).on('click', '.amzAjaxLink', function(e){
	e.preventDefault();
	if (!requestIsRunning) {
		requestIsRunning = true;
	    var action = $(this).attr('data-action');
	    var authId = $(this).attr('data-authid');
	    var captureId = $(this).attr('data-captureid');
	    var orderRef = $(this).attr('data-orderRef');
	    var amount = $(this).attr('data-amount');
	    if(action == 'captureAmountFromAuth'){
	        var amount = parseFloat($(this).parent().find('.amzAmountField').val().replace(',', '.'));
	    }else if(action == 'refundAmountFromField'){
	        var amount = parseFloat($(this).parent().find('.amzAmountField').val().replace(',', '.'));
	        action = 'refundAmount';
	    }
	    else if(action == 'authorizeAmountFromField'){
	        var amount = parseFloat($(this).parent().find('.amzAmountField').val().replace(',', '.'));
	        action = 'authorizeAmount';
	    }
	   
	    $.post(ajaxHandler, {action:action, authId:authId, amount:amount, orderRef:orderRef, captureId:captureId}, function(data){
	        /*var responseDiv = $('<div style="display:none;"/>').html(data);
	        $('body').append(responseDiv);
	        responseDiv.dialog();*/
	        amzRefresh();
	        requestIsRunning = false;
	    });
	}
});
function amzReloadLoop(wr){
    setTimeout(function(){amzReloadOrder(wr); amzReloadLoop(wr);}, 5000);
}
function amzReloadOrder(wr){
    var orderRef = wr.attr('data-orderRef');
    amzReloadHistory(orderRef, wr.find('.amzAdminOrderHistory'));
    amzReloadActions(orderRef, wr.find('.amzAdminOrderActions'));
    amzReloadSummary(orderRef, wr.find('.amzAdminOrderSummary'));
}

function amzReloadHistory(orderRef, target){
    $.post(ajaxHandler, {action:'getHistory', orderRef:orderRef}, function(data){
        if(lastHistory != data){
            target.html(data);
            lastHistory = data;
        }
        target.closest('.amzAdminWr').css('opacity', 1);
    });
}

function amzReloadActions(orderRef, target){
    $.post(ajaxHandler, {action:'getActions', orderRef:orderRef}, function(data){
        if(lastActions != data){
            target.html(data);
            lastActions = data;
        }
        target.closest('.amzAdminWr').css('opacity', 1);
    });
}
function amzReloadSummary(orderRef, target){
    $.post(ajaxHandler, {action:'getSummary', orderRef:orderRef}, function(data){
        if(lastSummary != data){
            target.html(data);
            lastSummary = data;
        }
        target.closest('.amzAdminWr').css('opacity', 1);
    });
}

function amzRefresh(){
    $('.amzAdminWr').each(function(){
        $(this).css('opacity', 0.6);
        amzReloadOrder($(this));
    });
}

/*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2018 patworx multimedia GmbH
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
});

$(document).ready(function(){
	
	if ($("#amzconnect").length > 0) {
		
		connect_area = '#fieldset_0 .form-wrapper';
		config_area = '#fieldset_1_1';
		advanced_config_area = '#fieldset_2_2, #fieldset_3_3, #fieldset_4_4, #fieldset_5_5';
		mode_area = '#fieldset_6_6';
		banners_area = '#fieldset_7_7';
		buttons_area = '#fieldset_8_8';
		enhancement_area = '#fieldset_9_9';
		
		$(connect_area).detach().appendTo('#amzconnect #amzconnectform').show();
		$(config_area).detach().prependTo('#amzconfiguration').show();
		$(advanced_config_area).detach().appendTo('#amzconfiguration #advancedconfig').show();
		$(mode_area).detach().insertAfter('#advancedconfig').show();
		$(buttons_area).detach().prependTo('#amzpromote').show();
		$(banners_area).detach().prependTo('#amzpromote').show();
		$(enhancement_area).detach().prependTo('#amzpromote').show();
		
		$("#returnedurl").detach().appendTo($("#POPUP_on").closest('.form-group')).show();
		$("button[name=submitAmzpaymentsModule]").first().clone().attr('name','submitAmzpaymentsModuleConnect').appendTo($("#REGION").parent());
		
		$("#execution_states").detach().prependTo($("#AUTHORIZATION_MODE").closest('.form-group'));
		$("#payment_states").detach().prependTo($("#AMZ_ORDER_STATUS_ID").closest('.form-group'));
		$("#email_state").detach().prependTo($("#SEND_MAILS_ON_DECLINE_on").closest('.form-group'));
		$("#amazon_notification").detach().prependTo($("#IPN_STATUS_on").closest('.form-group'));
		
		$("#restorehooks").detach().appendTo($("#AMZ_ORDER_PROCESS_TYPE").closest('div'));
				
		$("button[name=submitAmzpaymentsModuleConnect]").on('click', function() {
			$("#missingerror, #jsonerror").hide();
			var json = $.trim($("#jsonMWS").val());
			if (json == '') {
				$("#keysverification").hide();
	    		$("#waitforverification").fadeIn();
				return true;
			} else {
		    	try {
		    	    jsonData = $.parseJSON(json);
		    	    if (jsonData === null) {
		    	    	$("#jsonerror").fadeIn();
		    	    } else {
		    	    	missingfields = [];
		    	    	if (typeof jsonData.merchant_id != 'undefined' && jsonData.merchant_id != '') { 
		    	    		$("#AMZ_MERCHANT_ID").val(jsonData.merchant_id);
		    	    		setFormGroupChecking($("#AMZ_MERCHANT_ID"));
		    	    	} else {
		    	    		missingfields.push("AMZ_MERCHANT_ID");
		    	    		setFormGroupError($("#AMZ_MERCHANT_ID"));
		    	    	}
		    	    	if (typeof jsonData.access_key != 'undefined' && jsonData.access_key != '') {
		    	    		$("#ACCESS_KEY").val(jsonData.access_key);
		    	    		setFormGroupChecking($("#ACCESS_KEY"));
		    	    	} else {
		    	    		missingfields.push("ACCESS_KEY");	    
		    	    		setFormGroupError($("#ACCESS_KEY"));	    		
		    	    	}
		    	    	if (typeof jsonData.secret_key != 'undefined' && jsonData.secret_key != '') {
		    	    		$("#SECRET_KEY").val(jsonData.secret_key);
		    	    		setFormGroupChecking($("#SECRET_KEY"));
		    	    	} else {
		    	    		missingfields.push("SECRET_KEY");
		    	    		setFormGroupError($("#SECRET_KEY"));	 
		    	    	}
		    	    	if (typeof jsonData.client_id != 'undefined' && jsonData.client_id != '') {
		    	    		$("#AMZ_CLIENT_ID").val(jsonData.client_id);
		    	    		setFormGroupChecking($("#AMZ_CLIENT_ID"));
		    	    	} else {
		    	    		missingfields.push("AMZ_CLIENT_ID");
		    	    		setFormGroupError($("#AMZ_CLIENT_ID"));	    	    		
		    	    	}
		    	    	if (missingfields.length > 0) {
		    	    		missingfieldslabels = [];
		    	    		for (i = 0; i < missingfields.length; i++) {
		    	    			missingfieldslabels.push($("#" + missingfields[i]).closest('.form-group').find('label').html());
		    	    		}
		    	    		$("#missing_fields").html(missingfieldslabels.join(', '));
			    	    	$("#missingerror").fadeIn();
		    	    	} else {
		    	    		$("#jsonMWS").val('');
		    				$("#keysverification").hide();
		    	    		$("#waitforverification").fadeIn();
		    	    		return true;
		    	    	}
		    	    }
		    	} catch (e) {
	    	    	$("#jsonerror").fadeIn();
		    	}		
			}
			return false;
		});
		
		function setFormGroupSuccess(elem) {
			elem.closest('.form-group').removeClass('has-error has-warning has-success').addClass('has-success');
		}
		
		function setFormGroupChecking(elem) {
			elem.closest('.form-group').removeClass('has-error has-warning has-success').addClass('has-warning');
		}
		
		function setFormGroupError(elem) {
			elem.closest('.form-group').removeClass('has-error has-warning has-success').addClass('has-error');		
		}
		
		$("#amazon_notification").parent().find('p.help-block').first().append($("#help-addon-notifications").html());
		
		$("input[name=AMZ_CONFIG_SETTING_MODE]").on('change', function() { setAdvancedConfigMode(); });
		setAdvancedConfigMode();
		$("input[name=POPUP]").on('change', function() { setRedirectURLsVisibility(); });
		setRedirectURLsVisibility();
		
		$("#AMZ_PROMO_HEADER_STYLE, #AMZ_PROMO_PRODUCT_STYLE, #AMZ_PROMO_FOOTER_STYLE").on('change', function() { setPromoBannerImages(); });
		setPromoBannerImages();
		
		$("#BUTTON_COLOR_LPA, #BUTTON_SIZE_LPA, #TYPE_PAY, #BUTTON_COLOR_LPA_NAVI, #TYPE_LOGIN").on('change', function() { setPayButton(); });
		setPayButton();
		
	    ajaxHandler = $('.amzAjaxHandler').val();   
	    
	    $("#start_troubleshooter").click(function() {
	    	$(this).find('strong').hide();
	    	$(this).find('img').show();
	    	$(".troubleshooter-content-results").html('');
	    	tsurl = $(this).attr("data-url");
	        $.post(
	        	tsurl,
	        	{},
	        	function(data){
	        		$("#start_troubleshooter").find('strong').show();
	        		$("#start_troubleshooter").find('img').hide();
	        		$(".troubleshooter-content-results").html(data.troubleshooter);
	            }	            
	        );
	    });

	    var CommonlyFacedProblems = '';
	    $.post(
	    	$("input[name=commonlyfacedproblems_url]").val(),
	    	{},
	    	function (data) {
	    		if (data.error != 'true') {
	    			CommonlyFacedProblems = data.CommonlyFacedProblems;
	    			var selectbox = $("<select>").addClass('');
	    			var option = $("<option>").val('-1').html('-'); 
    				selectbox.append(option);
	    			for (i = 0; i < data.CommonlyFacedProblems.length; i++) {
	    				var option = $("<option>").val(i).html(data.CommonlyFacedProblems[i].Title); 
	    				selectbox.append(option);
	    			}
	    			selectbox.on('change', function() {
	    				if ($(this).val() == '-1') {
	    					$(".commonly_results").html('');
	    					return;
	    				}
	    				var problem = CommonlyFacedProblems[$(this).val()];
	    				var rootcause = $("<div/>").append($('<h4/>').html(data.l.rootcause));
	    				var resolution = $("<div/>").append($('<h4/>').html(data.l.resolution));
	    				var detailedinformation = $("<div/>").append($('<h4/>').html(data.l.detailedinformation));
	    				
	    				var resolutions = $("<ol/>");
		    			for (i = 0; i < problem.ResolutionStepByStep.length; i++) {
		    				var listelement = $("<li/>").html(problem.ResolutionStepByStep[i].Step);
		    				resolutions.append(listelement);
		    			}	    				
	    				rootcause.append($("<div/>").html(problem.RootCause));
	    				resolution.append($("<div/>").html(resolutions));
	    				detailedinformation.append($("<div/>").html(problem.DetailledInfo));
	    				
	    				$(".commonly_results").html('');
	    				$(".commonly_results").append(rootcause).append(resolution).append(detailedinformation);
	    			});
	    			$(".commonly_select").append(selectbox);
	    		} else {	    			
	    			$(".commonly_select").html('No data available.');
	    		}	    			
	    	},
	    	'json'
	    );
	    
	    $('.carousel').carousel({
	        interval: 4000
	    });
	    $('#videoprestashopyoutube, .videoreturnurls, #videojavascriptorigins').hide();

	
	    $("#showvideoprestashopyoutube").click(function() {
	    	$('#videoprestashopyoutube').parent('.responsive-video').show();
	        $('#videoprestashopyoutube').show();
	        $('#carrouselAmazonPay').hide();
	    });
	    
	    $(".showvalidationnotificationvideo").click(function() {
	    	$('.validationnotificationvideo').toggle();	    	
	    });
	    
	    $(".showvalidationnotificationvideo2").click(function() {
	    	$('.validationnotificationvideo2').toggle();	    	
	    });
	    
	    $("#showvideoaccesskeys").click(function() {
	    	$('#videoaccesskeys').toggle();
	    });
	    
	    $("#showvideonotification").click(function() {
	    	$('#videonotification').toggle();
	    });
	    
	    $("#showvideojavascriptorigins").click(function() {
	    	$('#videojavascriptorigins').toggle();
	    });
	    
	    $(".showvideoreturnurls, #showvideoreturnurls2").click(function() {
	    	$(this).closest('div').find('.videoreturnurls').toggle();
	    });
	    
	    $("#showvideoURLs").click(function() {
	    	$('#videoURLs').toggle();
	    });
	    
	    
	    
	    new Clipboard('.clipper');
	    
	    function setAdvancedConfigMode() {
	    	if ($("input[name=AMZ_CONFIG_SETTING_MODE]:checked").val() == '1') {
	    		$("#advancedconfig").show();
	    	} else {
	    		$("#advancedconfig").hide();
	    	}
	    }
	    function setRedirectURLsVisibility() {
	    	if ($("input[name=POPUP]:checked").val() == '0') {
	    		$("#returnedurl").show();
	    	} else {
	    		$("#returnedurl").hide();
	    	}
	    }
	    function setPromoBannerImages() {
	    	if ($("select[name=AMZ_PROMO_HEADER_STYLE]").val() == '1') { stylesetting = 'dark'; } else { stylesetting = 'light'; }
	    	if ($("#AMZ_PROMO_HEADER_STYLE_IMG").length > 0) { $("#AMZ_PROMO_HEADER_STYLE_IMG").remove(); }    	
	    	$("select[name=AMZ_PROMO_HEADER_STYLE]").closest('.form-group').append('<div id="AMZ_PROMO_HEADER_STYLE_IMG">' + $("#banner_" + stylesetting + "_header").html() + '</div>');    
	    	
	    	if ($("select[name=AMZ_PROMO_PRODUCT_STYLE]").val() == '1') { stylesetting = 'dark'; } else { stylesetting = 'light'; }
	    	if ($("#AMZ_PROMO_PRODUCT_STYLE_IMG").length > 0) { $("#AMZ_PROMO_PRODUCT_STYLE_IMG").remove(); }    	
	    	$("select[name=AMZ_PROMO_PRODUCT_STYLE]").closest('.form-group').append('<div id="AMZ_PROMO_PRODUCT_STYLE_IMG">' + $("#banner_" + stylesetting + "_product").html() + '</div>');    
	    	
	    	if ($("select[name=AMZ_PROMO_FOOTER_STYLE]").val() == '1') { stylesetting = 'dark'; } else { stylesetting = 'light'; }
	    	if ($("#AMZ_PROMO_FOOTER_STYLE_IMG").length > 0) { $("#AMZ_PROMO_FOOTER_STYLE_IMG").remove(); }    	
	    	$("select[name=AMZ_PROMO_FOOTER_STYLE]").closest('.form-group').append('<div id="AMZ_PROMO_FOOTER_STYLE_IMG">' + $("#banner_" + stylesetting + "_footer").html() + '</div>');
	    	
	    	if ($("#AMZ_PROMO_ENHANCEMENT_PRODUCT_IMG").length > 0) { $("#AMZ_PROMO_ENHANCEMENT_PRODUCT_IMG").remove(); }    	
	    	$("input[name=AMZ_BUTTON_ENHANCEMENT_PRODUCT]").closest('.form-group').append('<div id="AMZ_PROMO_ENHANCEMENT_PRODUCT_IMG" class="col-xs-12">' + $("#enhancement_product").html() + '</div>');
	    	
	    	if ($("#AMZ_PROMO_ENHANCEMENT_CART_IMG").length > 0) { $("#AMZ_PROMO_ENHANCEMENT_CART_IMG").remove(); }    	
	    	$("input[name=AMZ_BUTTON_ENHANCEMENT_CART]").closest('.form-group').append('<div id="AMZ_PROMO_ENHANCEMENT_CART_IMG" class="col-xs-12">' + $("#enhancement_cart").html() + '</div>');
	    }
	    function setPayButton() {
	    	baseUrl = $("input[name=button_img_dir_base]").val();
	    	langVal = $("input[name=button_img_lang_var]").val();
	    	if ($("#TYPE_LOGIN").val() == 'LwA') {	    	
	    		loginUrl = baseUrl + 'Login' + langVal + '/' + $("#TYPE_LOGIN").val();
	    	} else {
	    		loginUrl = baseUrl + '/' + $("#TYPE_LOGIN").val();
	    	}
	    	payUrl = baseUrl + 'AmazonPay' + langVal + '/' + $("#TYPE_PAY").val();
	    	sizeString = $("#BUTTON_SIZE_LPA").val();
	    	sizeString = sizeString.charAt(0).toUpperCase() + sizeString.slice(1);
	    	if (sizeString == 'X-large') {
	    		sizeString = 'xLarge';
	    	}
	    	colorPwa = $("#BUTTON_COLOR_LPA").val();
	    	colorLpa = $("#BUTTON_COLOR_LPA_NAVI").val();
	    	
	    	loginUrl = loginUrl + sizeString + colorLpa + (($("#TYPE_LOGIN").val() == 'LwA') ? langVal : '') + '.png';
	    	payUrl = payUrl + sizeString + colorPwa + langVal + '.png';
	    	
	    	if ($("#TYPE_PAY_BUTTON_IMG").length > 0) { $("#TYPE_PAY_BUTTON_IMG").remove(); }    	
	    	$("select[name=TYPE_PAY]").closest('.form-group').append('<div id="TYPE_PAY_BUTTON_IMG"><img src="' + payUrl + '" /></div>');
	
	    	if ($("#TYPE_LPA_BUTTON_IMG").length > 0) { $("#TYPE_LPA_BUTTON_IMG").remove(); }    	
	    	$("select[name=TYPE_LOGIN]").closest('.form-group').append('<div id="TYPE_LPA_BUTTON_IMG"><img src="' + loginUrl + '" /></div>');    	
	    	
	    }
	    
	}
	    
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
	    	if (typeof data == 'string') {
	    		if (data.substr(0,6) == 'ERROR:') {
	    			alert(data);
	    		}
	    	}	    	
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


/*!
 * clipboard.js v1.7.1
 * https://zenorocha.github.io/clipboard.js
 *
 * Licensed MIT Â© Zeno Rocha
 */
!function(t){if("object"==typeof exports&&"undefined"!=typeof module)module.exports=t();else if("function"==typeof define&&define.amd)define([],t);else{var e;e="undefined"!=typeof window?window:"undefined"!=typeof global?global:"undefined"!=typeof self?self:this,e.Clipboard=t()}}(function(){var t,e,n;return function t(e,n,o){function i(a,c){if(!n[a]){if(!e[a]){var l="function"==typeof require&&require;if(!c&&l)return l(a,!0);if(r)return r(a,!0);var s=new Error("Cannot find module '"+a+"'");throw s.code="MODULE_NOT_FOUND",s}var u=n[a]={exports:{}};e[a][0].call(u.exports,function(t){var n=e[a][1][t];return i(n||t)},u,u.exports,t,e,n,o)}return n[a].exports}for(var r="function"==typeof require&&require,a=0;a<o.length;a++)i(o[a]);return i}({1:[function(t,e,n){function o(t,e){for(;t&&t.nodeType!==i;){if("function"==typeof t.matches&&t.matches(e))return t;t=t.parentNode}}var i=9;if("undefined"!=typeof Element&&!Element.prototype.matches){var r=Element.prototype;r.matches=r.matchesSelector||r.mozMatchesSelector||r.msMatchesSelector||r.oMatchesSelector||r.webkitMatchesSelector}e.exports=o},{}],2:[function(t,e,n){function o(t,e,n,o,r){var a=i.apply(this,arguments);return t.addEventListener(n,a,r),{destroy:function(){t.removeEventListener(n,a,r)}}}function i(t,e,n,o){return function(n){n.delegateTarget=r(n.target,e),n.delegateTarget&&o.call(t,n)}}var r=t("./closest");e.exports=o},{"./closest":1}],3:[function(t,e,n){n.node=function(t){return void 0!==t&&t instanceof HTMLElement&&1===t.nodeType},n.nodeList=function(t){var e=Object.prototype.toString.call(t);return void 0!==t&&("[object NodeList]"===e||"[object HTMLCollection]"===e)&&"length"in t&&(0===t.length||n.node(t[0]))},n.string=function(t){return"string"==typeof t||t instanceof String},n.fn=function(t){return"[object Function]"===Object.prototype.toString.call(t)}},{}],4:[function(t,e,n){function o(t,e,n){if(!t&&!e&&!n)throw new Error("Missing required arguments");if(!c.string(e))throw new TypeError("Second argument must be a String");if(!c.fn(n))throw new TypeError("Third argument must be a Function");if(c.node(t))return i(t,e,n);if(c.nodeList(t))return r(t,e,n);if(c.string(t))return a(t,e,n);throw new TypeError("First argument must be a String, HTMLElement, HTMLCollection, or NodeList")}function i(t,e,n){return t.addEventListener(e,n),{destroy:function(){t.removeEventListener(e,n)}}}function r(t,e,n){return Array.prototype.forEach.call(t,function(t){t.addEventListener(e,n)}),{destroy:function(){Array.prototype.forEach.call(t,function(t){t.removeEventListener(e,n)})}}}function a(t,e,n){return l(document.body,t,e,n)}var c=t("./is"),l=t("delegate");e.exports=o},{"./is":3,delegate:2}],5:[function(t,e,n){function o(t){var e;if("SELECT"===t.nodeName)t.focus(),e=t.value;else if("INPUT"===t.nodeName||"TEXTAREA"===t.nodeName){var n=t.hasAttribute("readonly");n||t.setAttribute("readonly",""),t.select(),t.setSelectionRange(0,t.value.length),n||t.removeAttribute("readonly"),e=t.value}else{t.hasAttribute("contenteditable")&&t.focus();var o=window.getSelection(),i=document.createRange();i.selectNodeContents(t),o.removeAllRanges(),o.addRange(i),e=o.toString()}return e}e.exports=o},{}],6:[function(t,e,n){function o(){}o.prototype={on:function(t,e,n){var o=this.e||(this.e={});return(o[t]||(o[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){function o(){i.off(t,o),e.apply(n,arguments)}var i=this;return o._=e,this.on(t,o,n)},emit:function(t){var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),o=0,i=n.length;for(o;o<i;o++)n[o].fn.apply(n[o].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),o=n[t],i=[];if(o&&e)for(var r=0,a=o.length;r<a;r++)o[r].fn!==e&&o[r].fn._!==e&&i.push(o[r]);return i.length?n[t]=i:delete n[t],this}},e.exports=o},{}],7:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","select"],r);else if(void 0!==o)r(n,e("select"));else{var a={exports:{}};r(a,i.select),i.clipboardAction=a.exports}}(this,function(t,e){"use strict";function n(t){return t&&t.__esModule?t:{default:t}}function o(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var i=n(e),r="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},a=function(){function t(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}return function(e,n,o){return n&&t(e.prototype,n),o&&t(e,o),e}}(),c=function(){function t(e){o(this,t),this.resolveOptions(e),this.initSelection()}return a(t,[{key:"resolveOptions",value:function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.action=e.action,this.container=e.container,this.emitter=e.emitter,this.target=e.target,this.text=e.text,this.trigger=e.trigger,this.selectedText=""}},{key:"initSelection",value:function t(){this.text?this.selectFake():this.target&&this.selectTarget()}},{key:"selectFake",value:function t(){var e=this,n="rtl"==document.documentElement.getAttribute("dir");this.removeFake(),this.fakeHandlerCallback=function(){return e.removeFake()},this.fakeHandler=this.container.addEventListener("click",this.fakeHandlerCallback)||!0,this.fakeElem=document.createElement("textarea"),this.fakeElem.style.fontSize="12pt",this.fakeElem.style.border="0",this.fakeElem.style.padding="0",this.fakeElem.style.margin="0",this.fakeElem.style.position="absolute",this.fakeElem.style[n?"right":"left"]="-9999px";var o=window.pageYOffset||document.documentElement.scrollTop;this.fakeElem.style.top=o+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,this.container.appendChild(this.fakeElem),this.selectedText=(0,i.default)(this.fakeElem),this.copyText()}},{key:"removeFake",value:function t(){this.fakeHandler&&(this.container.removeEventListener("click",this.fakeHandlerCallback),this.fakeHandler=null,this.fakeHandlerCallback=null),this.fakeElem&&(this.container.removeChild(this.fakeElem),this.fakeElem=null)}},{key:"selectTarget",value:function t(){this.selectedText=(0,i.default)(this.target),this.copyText()}},{key:"copyText",value:function t(){var e=void 0;try{e=document.execCommand(this.action)}catch(t){e=!1}this.handleResult(e)}},{key:"handleResult",value:function t(e){this.emitter.emit(e?"success":"error",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})}},{key:"clearSelection",value:function t(){this.trigger&&this.trigger.focus(),window.getSelection().removeAllRanges()}},{key:"destroy",value:function t(){this.removeFake()}},{key:"action",set:function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:"copy";if(this._action=e,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function t(){return this._action}},{key:"target",set:function t(e){if(void 0!==e){if(!e||"object"!==(void 0===e?"undefined":r(e))||1!==e.nodeType)throw new Error('Invalid "target" value, use a valid Element');if("copy"===this.action&&e.hasAttribute("disabled"))throw new Error('Invalid "target" attribute. Please use "readonly" instead of "disabled" attribute');if("cut"===this.action&&(e.hasAttribute("readonly")||e.hasAttribute("disabled")))throw new Error('Invalid "target" attribute. You can\'t cut text from elements with "readonly" or "disabled" attributes');this._target=e}},get:function t(){return this._target}}]),t}();t.exports=c})},{select:5}],8:[function(e,n,o){!function(i,r){if("function"==typeof t&&t.amd)t(["module","./clipboard-action","tiny-emitter","good-listener"],r);else if(void 0!==o)r(n,e("./clipboard-action"),e("tiny-emitter"),e("good-listener"));else{var a={exports:{}};r(a,i.clipboardAction,i.tinyEmitter,i.goodListener),i.clipboard=a.exports}}(this,function(t,e,n,o){"use strict";function i(t){return t&&t.__esModule?t:{default:t}}function r(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}function a(t,e){if(!t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return!e||"object"!=typeof e&&"function"!=typeof e?t:e}function c(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}function l(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}var s=i(e),u=i(n),f=i(o),d="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},h=function(){function t(t,e){for(var n=0;n<e.length;n++){var o=e[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(t,o.key,o)}}return function(e,n,o){return n&&t(e.prototype,n),o&&t(e,o),e}}(),p=function(t){function e(t,n){r(this,e);var o=a(this,(e.__proto__||Object.getPrototypeOf(e)).call(this));return o.resolveOptions(n),o.listenClick(t),o}return c(e,t),h(e,[{key:"resolveOptions",value:function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{};this.action="function"==typeof e.action?e.action:this.defaultAction,this.target="function"==typeof e.target?e.target:this.defaultTarget,this.text="function"==typeof e.text?e.text:this.defaultText,this.container="object"===d(e.container)?e.container:document.body}},{key:"listenClick",value:function t(e){var n=this;this.listener=(0,f.default)(e,"click",function(t){return n.onClick(t)})}},{key:"onClick",value:function t(e){var n=e.delegateTarget||e.currentTarget;this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new s.default({action:this.action(n),target:this.target(n),text:this.text(n),container:this.container,trigger:n,emitter:this})}},{key:"defaultAction",value:function t(e){return l("action",e)}},{key:"defaultTarget",value:function t(e){var n=l("target",e);if(n)return document.querySelector(n)}},{key:"defaultText",value:function t(e){return l("text",e)}},{key:"destroy",value:function t(){this.listener.destroy(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)}}],[{key:"isSupported",value:function t(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:["copy","cut"],n="string"==typeof e?[e]:e,o=!!document.queryCommandSupported;return n.forEach(function(t){o=o&&!!document.queryCommandSupported(t)}),o}}]),e}(u.default);t.exports=p})},{"./clipboard-action":7,"good-listener":4,"tiny-emitter":6}]},{},[8])(8)});
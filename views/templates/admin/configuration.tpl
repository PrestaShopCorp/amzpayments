{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{capture name=imgdir_assign assign=imgdir}https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Prestashop/img/{/capture}
{capture name=videodir_assign assign=videodir}https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Prestashop/video/{/capture}
{capture name=langdir_assign assign=langdir}{if !$language_code|in_array:['de', 'es', 'fr', 'it', 'uk', 'us']}uk{else}{$language_code|escape:'htmlall':'UTF-8'}{/if}{/capture}
{capture name=button_lang_var_assign assign=button_lang_var}{if !$language_code|in_array:['de', 'es', 'fr', 'it']}UK{else}{$language_code|escape:'htmlall':'UTF-8'|strtoupper}{/if}{/capture}

{if isset($postSuccess)}
	{foreach from=$postSuccess item=ps}
		<div class="alert alert-success">{$ps|escape:'htmlall':'UTF-8'}</div>
	{/foreach}
{/if}

{if isset($postErrors)}
	{foreach from=$postErrors item=pe}
		<div class="alert alert-danger">{$pe|escape:'htmlall':'UTF-8'}</div>
	{/foreach}
	<div class="alert alert-danger">
		{l s='If you need help, please [1]contact our support[/1]' tags=['<a onclick="jQuery(\'#amztabs a[href=#amzcontactus]\').tab(\'show\');">'] mod='amzpayments'}
	</div>
{/if}

<form method="POST" action="https://payments-eu.amazon.com/register" target="_blank" id="amazonRegForm"> 
	<input type="hidden" value="{$simple_path.ref|escape:'htmlall':'UTF-8'}" name="ref" />
	<input type="hidden" value="{$simple_path.locale|escape:'htmlall':'UTF-8'}" name="locale" />  
	<input type="hidden" value="{$simple_path.spId|escape:'htmlall':'UTF-8'}" name="spId" />  
	<input type="hidden" value="{$ld|escape:'htmlall':'UTF-8'}" name="ld" /> 
	<input type="hidden" value="{$simple_path.uniqueId|escape:'htmlall':'UTF-8'}" name="uniqueId" />  
	<input type="hidden" value="{$simple_path.allowedLoginDomains|escape:'htmlall':'UTF-8'}" name="allowedLoginDomains[]" />
	{foreach from=$simple_path.loginRedirectURLs_1 item=splr}
		<input type="hidden" value="{$splr|escape:'htmlall':'UTF-8'}" name="loginRedirectURLs[]" />
	{/foreach}
	{foreach from=$simple_path.loginRedirectURLs_2 item=splr}
		<input type="hidden" value="{$splr|escape:'htmlall':'UTF-8'}" name="loginRedirectURLs[]" />
	{/foreach}
	<input type="hidden" value="{$simple_path.storeDescription|escape:'htmlall':'UTF-8'}" name="storeDescription" />  
	<input type="hidden" value="{$simple_path.language|escape:'htmlall':'UTF-8'}" name="language" />  
	<input type="hidden" value="{$simple_path.returnMethod|escape:'htmlall':'UTF-8'}" name="returnMethod" />
	<input type="hidden" value="{$simple_path.Source|escape:'htmlall':'UTF-8'}" name="Source" />
	<input type="hidden" value="{$simple_path.sandboxMerchantIPNURL|escape:'htmlall':'UTF-8'}" name="sandboxMerchantIPNURL" />
	<input type="hidden" value="{$simple_path.productionMerchantIPNURL|escape:'htmlall':'UTF-8'}" name="productionMerchantIPNURL" />
</form>

<input type="hidden" name="button_img_dir_base" value="{$imgdir|escape:'htmlall':'UTF-8'}buttons/" />
<input type="hidden" name="button_img_lang_var" value="{$button_lang_var|escape:'htmlall':'UTF-8'}" />

<div id="amzconfigarea">
{$configform} {* no escaping needed, comes from PrestaShop Form Helper!!! *}

<div class="amzconfig">
	<ul class="nav nav-tabs" id="amztabs">
		<li class="active"><a href="#amzregistration" data-toggle="tab">{l s='Registration' mod='amzpayments'}</a></li>
		<li><a href="#amzconnect" data-toggle="tab">{l s='Connection' mod='amzpayments'}</a></li>
		<li><a href="#amzconfiguration" data-toggle="tab">{l s='Configuration' mod='amzpayments'}</a></li>
		<li><a href="#amzpromote" data-toggle="tab">{l s='Promotion' mod='amzpayments'}</a></li>
		<li><a href="#amzcontactus" data-toggle="tab">{l s='Contact us' mod='amzpayments'}</a></li>
		<li><a href="#amzfaq" data-toggle="tab">{l s='FAQ' mod='amzpayments'}</a></li>
	</ul>
	<div class="tab-content panel">
		<div id="amzregistration" class="tab-pane active">
			<div class="row">
				<div class="col-xs-12 col-md-6">
					<p>
						<img src="{$imgdir|escape:'html':'UTF-8'}amazon-payments.jpg" alt="amazon payments" class="img-responsive" />
					</p>					
					<p>
						<strong>{l s='Amazon Pay: Amazon’s Payment and Checkout method for your website' mod='amzpayments'}</strong>
					</p>
					<p>
						{l s='Add Amazon Pay to your website and allow Amazon customers to sign in with their Amazon credentials and easily pay with the address and payment information stored in their Amazon account.' mod='amzpayments'}
						<br />			
					</p>
					<p>
						<strong>{l s='Amazon Pay can help you:' mod='amzpayments'}</strong><br />						
						<ul>
							<li>{l s='Build customer loyalty' mod='amzpayments'}</li>
							<li>{l s='Attract new customers' mod='amzpayments'}</li>
							<li>{l s='Improve your conversion rate' mod='amzpayments'}</li>
							<li>{l s='Reduce fraud' mod='amzpayments'}</li>
						</ul>                        
					</p>
					<p><strong>{l s='Simply follow these 4 steps:' mod='amzpayments'}</strong> &nbsp; 
						<a style="color:#FF9900;" id="showvideoprestashopyoutube" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i>&nbsp;{l s='Watch our video' mod='amzpayments'}</a> <br />
					<ol>
						<li>
							<a id="simplepathRegTrigger" href='#' onclick="jQuery('#amazonRegForm').submit();">{l s='[1]Registration:[/1] Sign up for an Amazon Payments merchant account.' tags=['<span>'] mod='amzpayments'}</a>
						</li>
						<li>
							<a id="showstepsetup" href="#" onclick="jQuery('#amztabs a[href=#amzconnect]').tab('show');">{l s='[1]Connection:[/1] Connect your Amazon Payments account with PrestaShop.' tags=['<span>'] mod='amzpayments'}</a>                                    
						</li>
						<li>
							<a id="showstepconfiguration" href="#" onclick="jQuery('#amztabs a[href=#amzconfiguration]').tab('show');">{l s='[1]Configuration:[/1] Configure and activate the plugin.' tags=['<span>'] mod='amzpayments'}</a>
						</li>
						<li>
							<a id="showstepconfiguration" href="#" onclick="jQuery('#amztabs a[href=#amzpromote]').tab('show');">{l s='[1]Promotion:[/1] Promote Amazon Pay on your website.' tags=['<span>'] mod='amzpayments'}</a>
						</li>
					</ol>
                    <p> 
						<br />
                        <strong>{l s='Important note, before you sign up:' mod='amzpayments'}</strong><br />
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;
                        {l s='Before you start the registration, make sure you sign out of all Amazon accounts you might have.' mod='amzpayments'}
                        <br />
                        {l s='Use an email address that you have never used for any Amazon account.' mod='amzpayments'}
                        <br />
                        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>&nbsp;
                        {l s='If you have an Amazon Seller account (Selling on Amazon), sign out and use a different address to register your Amazon Payments account.' mod='amzpayments'}
					</p>					
				</div>				
				<div class="col-xs-12 col-md-6">
					<div id="video">
                        <table>
                            <tr>
                                <td>
                                	<div class="responsive-video" style="display: none;">
                                    	<iframe id="videoprestashopyoutube" style="vertical-align:top;margin-left:30px;float:left;" width="640" height="360" src="{$youtube_video_embed_link|escape:'html':'UTF-8'}" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe>
                                    </div>
                                    <div id="carrouselAmazonPay" style="vertical-align:top;margin-left:30px;float:left;min-width:205px;max-height:365px;" class="carousel slide" data-ride="carousel">
                                        <ol class="carousel-indicators">
                                            <li data-target="#carrouselAmazonPay" data-slide-to="0" class="active"></li>
                                            <li data-target="#carrouselAmazonPay" data-slide-to="1"></li>
                                            <li data-target="#carrouselAmazonPay" data-slide-to="2"></li>
                                            <li data-target="#carrouselAmazonPay" data-slide-to="3"></li>
                                            <li data-target="#carrouselAmazonPay" data-slide-to="4"></li>
                                        </ol>
                                        <div class="carousel-inner">
                                            <div class="item active">
                                                <img class="img-fluid" src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/carousel/01.jpg" style="min-width:205px;max-height:365px;">
                                            </div>
                                            <div class="item">
                                                <img class="img-fluid" src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/carousel/02.jpg" style="min-width:205px;max-height:365px;">
                                            </div>
                                            <div class="item">
                                                <img class="img-fluid" src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/carousel/03.jpg" style="min-width:205px;max-height:365px;">
                                            </div>
                                            <div class="item">
                                                <img class="img-fluid" src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/carousel/04.jpg" style="min-width:205px;max-height:365px;">
                                            </div>
                                            <div class="item">
                                            	<div class="responsive-video"><iframe id="videoamazonyoutube" width="480" height="288" src="{$videoamazonyoutube|escape:'htmlall':'UTF-8'}" frameborder="0" gesture="media" allowfullscreen></iframe></div>
                                            </div>
                                        </div>
    									<a class="carousel-control left" href="#carrouselAmazonPay" data-slide="prev">
    										<span class="glyphicon glyphicon-chevron-left"></span>
    									</a>
    									<a class="carousel-control right" href="#carrouselAmazonPay" data-slide="next">
    										<span class="glyphicon glyphicon-chevron-right"></span>
    									</a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="margin-left:30px;margin-top:10px;">
                                        <span style="font-size:medium">                                  
                                            <img onclick="jQuery('#amazonRegForm').submit();" src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/subscribe.jpg" style="cursor: pointer; height:30px;" />
                                        </span>&nbsp; <span style="font-weight: bold; text-transform: uppercase">{l s='or' mod='amzpayments'}</span> &nbsp;
                                        <a id="showstepconnect" style="color:#FF9900;cursor: pointer;" aria-hidden="true" onclick="jQuery('#amztabs a[href=#amzconnect]').tab('show');">
                                            {l s='Click here if you already have an Amazon Pay account' mod='amzpayments'}
                                        </a> 
                                    </div> 
                                </td>
                            </tr>
                        </table>
                    </div> 				
				</div>
			</div>
		</div>
		<div id="amzconnect" class="tab-pane">
			<p>
				<a style="color:#FF9900; font-style: italic; font-weight: bold; font-size: 1.1em;" title="{l s='Watch our full integration video here' mod='amzpayments'}" href="{$youtube_video_link|escape:'htmlall':'UTF-8'}" target="_blank"><i class="fa fa-file-video-o" aria-hidden="true"></i>&nbsp;{l s='Watch our full integration video here' mod='amzpayments'}</a>
			</p>
		
			<p>
				<strong>
					1. {l s='Import your access keys' mod='amzpayments'}
				</strong>
				<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="{l s='The access keys are needed to secure the communication between your site and the Amazon servers' mod='amzpayments'}"><i class="fa fa-info-circle" aria-hidden="true" style="color:forestgreen;"></i></span>
			</p>
			
			<div>
				<p style="margin-left: 24px">
					<i style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal1a"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See how to do it' mod='amzpayments'}</i>
                    <a style="color:#FF9900;cursor: pointer;" id="showvideoaccesskeys" title="{l s='Watch it' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch it' mod='amzpayments'}</a>				
				</p>
				<div>
           			<div id="videoaccesskeys" style="width: 80%; clear: both">
            			<video controls="controls" style="max-width: 100%;">
							<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/Keys{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
						</video>
					</div>
				</div>	
				<ol type="a">
					<li>
						<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">{l s='Sign in to your account on Seller Central, in a new browser window' mod='amzpayments'}</a>
					</li>
					<li>
						{l s='Click [1]Integration[/1], and then click [2]MWS Access Key[/2].' tags=['<strong>', '<strong>'] mod='amzpayments'}
					</li>
					<li>
						{l s='Click the [1]Copy your keys[/1] button in the top right corner to generate the keys.' tags=['<strong>'] mod='amzpayments'}
					</li>
					<li>
						{l s='Highlight the text [1]with the curly brackets[/1] in the popup and copy it to the clipboard.' tags=['<i>'] mod='amzpayments'}
						<i style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See how to do it' mod='amzpayments'}</i>
					</li>
					<li>
						{l s='Paste your keys into the box below' mod='amzpayments'}
						<textarea class="form-control form-control-sm" id="jsonMWS" rows="3"></textarea>
					</li>
					<li>
						{l s='Select your region and click [1]Save[/1]' tags=['<strong>'] mod='amzpayments'}
					</li>
				</ol>
			</div>
			
			<div id="connectmessages">
            	<div class="alert alert-warning" id="waitforverification">
            		<div>
                		<strong>{l s='Please wait a few seconds while the access keys are verified.' mod='amzpayments'}</strong>
	               	</div>
	            </div>
	            {if isset($keys_valid)}
	            <div class="alert alert-{if !$keys_valid}danger{else}success{/if}" id="keysverification">
            		<div>
                		<strong>
                			{if !$keys_valid}
                				{l s='The access keys are invalid.' mod='amzpayments'}                			
                			{else}
                				{l s='The access keys are valid.' mod='amzpayments'}
                			{/if}
                		</strong>
	               	</div>
	            </div>
	            {/if}
	        </div>
			
			<div id="amzconnectform"></div>
			
			<div id="connecterrors">
            	<div class="alert alert-warning" id="waitforverification">
            		<div>
                		<strong>{l s='Please wait a few seconds while the access keys are verified.' mod='amzpayments'}</strong>
	               	</div>
	            </div>
            	<div class="alert alert-danger" id="jsonerror">
            		<div>
                		<strong>{l s='There is an error in the JSON string.' mod='amzpayments'}</strong> 
                		{l s='If you need help, please [1]contact our support[/1]' tags=['<a onclick="jQuery(\'#amztabs a[href=#amzcontactus]\').tab(\'show\');">'] mod='amzpayments'}
	               	</div>
	            </div>
            	<div class="alert alert-danger" id="missingerror">
	               	<div>
	               		<strong>{l s='The following information is missing: ' mod='amzpayments'}</strong> <span id="missing_fields"></span><br />
                		{l s='If you need help, please [1]contact our support[/1]' tags=['<a onclick="jQuery(\'#amztabs a[href=#amzcontactus]\').tab(\'show\');">'] mod='amzpayments'}
	               	</div>
				</div>
			</div> 		
			
			<div class="ipnurl">
				<p>
					<strong>
        	        	2. {l s='Add your Instant Payments Notification (IPN) URL to your account in Seller Central' mod='amzpayments'} 
						<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="{l s='The IPN URL enables your store to receive payment notifications from Amazon Pay' mod='amzpayments'}"><i class="fa fa-info-circle" aria-hidden="true" style="color:forestgreen;"></i></span>
					</strong>	
				</p>			
				<p>
					{l s='Your Instant Payment Notification (IPN) URL:' mod='amzpayments'}
					<input readonly type="text" width="60" value="{$simple_path.productionMerchantIPNURL|escape:'htmlall':'UTF-8'}" id="ipnlink">				
				</p>
    	        <div>
    	        	<p style="margin-left: 24px">    	        	
						<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal2"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
	                    <a style="color:#FF9900;cursor: pointer;" id="showvideonotification" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>
    	        	</p>
	          		<div id="videonotification" style="width: 80%; clear: both">
    	        		<video controls="controls" style="max-width: 100%;">
							<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/IPN{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
						</video>
					</div>	
        	        <ol type="a">
            	    	<li>
                			{l s='Copy your IPN URL to the clipboard' mod='amzpayments'}:
							<a class="btn clipper" data-clipboard-target="#ipnlink" style="color:#FF9900;cursor: pointer;" aria-hidden="true"><i class="fa fa-clipboard" aria-hidden="true"></i> {l s='Click here' mod='amzpayments'}</a>                        
						</li>
						<li>
							<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">{l s='Sign in to your account on Seller Central, in a new browser window' mod='amzpayments'}</a>
						</li>
						<li>
            	            {l s='Paste the URL into the [1]Merchant URL[/1] field' tags=['<strong>'] mod='amzpayments'} 
						</li>
        	        </ol>
				</div>		
			</div>
			
			<div class="originandallowed">
				<p>
					<strong>
        	        	3. {l s='Add your Allowed JavaScript Origin to your account in Seller Central' mod='amzpayments'} 
						<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="{l s='Please enter these URLs in your Amazon Pay account so that Login with Amazon SDK for JavaScript can be authorized to run on your website' mod='amzpayments'}"><i class="fa fa-info-circle" aria-hidden="true" style="color:forestgreen;"></i></span>
					</strong>	
				</p>			
				<p>
					{l s='Your Allowed JavaScript Origin:' mod='amzpayments'}
					<input readonly type="text" width="60" value="{$simple_path.allowedLoginDomains|escape:'htmlall':'UTF-8'}" id="whitelisturl">				
				</p>
    	        <div>
    	        	<p style="margin-left: 24px;">
						<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal4"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
	                    <a style="color:#FF9900;cursor: pointer;" id="showvideojavascriptorigins" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>	            	        	
    	        	</p>						                 
				    <div id="videojavascriptorigins" style="width: 80%; clear: both">
    	        		<video controls="controls" style="max-width: 100%;">
							<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/JavaScriptOrigin{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
						</video>
					</div>
        	        <ol type="a">
            	    	<li>
                			{l s='Copy the URL to the clipboard:' mod='amzpayments'}
							<a class="btn clipper" data-clipboard-target="#whitelisturl" style="color:#FF9900;cursor: pointer;" aria-hidden="true"><i class="fa fa-clipboard" aria-hidden="true"></i> {l s='Click here' mod='amzpayments'}</a>                        
						</li>
						<li>
							<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">{l s='Sign in to your account on Seller Central, in a new browser window' mod='amzpayments'}</a>
						</li>
						<li>
            	            {l s='Paste the URL into the [1]Allowed JavaScript Origins[/1] field.' tags=['<strong>'] mod='amzpayments'} 		
						</li>
        	        </ol>
				</div>
				
				{assign var=aru_counter value=1}
				{foreach from=$allowed_return_url_1 item=aru}
					<p>
						{l s='Your %s. allowed return URL' sprintf=$aru_counter mod='amzpayments'}:
						<input readonly type="text" width="60" value="{$aru|escape:'htmlall':'UTF-8'}" id="aru_{$aru_counter|escape:'htmlall':'UTF-8'}">				
					</p>
	    	        <div>
	    	        	<p style="margin-left: 24px">
							<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal5"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
	                		<a style="color:#FF9900;cursor: pointer;" class="showvideoreturnurls" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>	  	    	        	
	    	        	</p>
						<div class="videoreturnurls" style="width: 80%; clear: both">
    	        			<video controls="controls" style="max-width: 100%;">
								<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/ReturnURLs{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
							</video>
						</div>   
    	    	        <ol type="a">
        	    	    	<li>
            	    			{l s='Copy the URL to the clipboard:' mod='amzpayments'}
								<a class="btn clipper" data-clipboard-target="#aru_{$aru_counter|escape:'htmlall':'UTF-8'}" style="color:#FF9900;cursor: pointer;" aria-hidden="true"><i class="fa fa-clipboard" aria-hidden="true"></i> {l s='Click here' mod='amzpayments'}</a>                        
							</li>
							<li>
								<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">{l s='Sign in to your account on Seller Central, in a new browser window' mod='amzpayments'}</a>
							</li>
							<li>
        	    	            {l s='Paste the URL into the [1]Allowed Return URLs[/1] field.' tags=['<strong>'] mod='amzpayments'}
							</li>
        	        	</ol>
					</div>				
					{assign var=aru_counter value=$aru_counter+1}
				{/foreach}
				{foreach from=$allowed_return_url_2 item=aru}
					<p>
						{l s='Your %s. allowed return URL' sprintf=$aru_counter mod='amzpayments'}:
						<input readonly type="text" width="60" value="{$aru|escape:'htmlall':'UTF-8'}" id="aru_{$aru_counter|escape:'htmlall':'UTF-8'}">				
					</p>
	    	        <div>
	    	        	<p style="margin-left: 24px">
							<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal5"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
	                		<a style="color:#FF9900;cursor: pointer;" class="showvideoreturnurls" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>	  	    	        	
	    	        	</p>
						<div class="videoreturnurls" style="width: 80%; clear: both">
    	        			<video controls="controls" style="max-width: 100%;">
								<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/ReturnURLs{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
							</video>
						</div>   
    	    	        <ol type="a">
        	    	    	<li>
            	    			{l s='Copy the URL to the clipboard:' mod='amzpayments'}
								<a class="btn clipper" data-clipboard-target="#aru_{$aru_counter|escape:'htmlall':'UTF-8'}" style="color:#FF9900;cursor: pointer;" aria-hidden="true"><i class="fa fa-clipboard" aria-hidden="true"></i> {l s='Click here' mod='amzpayments'}</a>                        
							</li>
							<li>
								<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">{l s='Sign in to your account on Seller Central, in a new browser window' mod='amzpayments'}</a>
							</li>
							<li>
        	    	            {l s='Paste the URL into the [1]Allowed Return URLs[/1] field.' tags=['<strong>'] mod='amzpayments'}                	    	            
							</li>
        	        	</ol>
					</div>				
					{assign var=aru_counter value=$aru_counter+1}
				{/foreach} 
			</div>        
			
			<div class="validationcheck">
				<p>
					<strong>
        	        	4. {l s='Check if your Amazon Payments account has been verified' mod='amzpayments'} 
					</strong>	
				</p>
				
				{if isset($kyc_passed) && $kyc_passed == 1}
            		<div class="alert alert-success">
                		<strong>{l s='Your account has been validated.' mod='amzpayments'}</strong> 
	                	<a style="cursor: pointer;" onclick="jQuery('#amztabs a[href=#amzconfiguration]').tab('show');">{l s='Configure and activate the Amazon Pay plugin.' mod='amzpayments'}</a>
					</div>
				{else}				
 	   	        	<div>
 	   	        		<p style="margin-left: 24px"> 	   	        			
							<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal6"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
							<a style="color:#FF9900;cursor: pointer;" class="showvalidationnotificationvideo" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>
 	   	        		</p>
 	   	        		<div class="validationnotificationvideo" style="width: 80%; clear: both">
    	        			<video controls="controls" style="max-width: 100%;">
								<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/Notifications{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
							</video>
						</div> 
    	    	        <ul>
        	    	    	<li>
            	    			{l s='After you enter all required information, Amazon Payments will kick off the account verification.' mod='amzpayments'}							                       
							</li>
            	    		<li>
                				{l s='Check your email regularly. If Amazon Payments needs additional information from you to proceed with the verification, you will receive a notification and an email.' mod='amzpayments'}							                       
							</li>
            	    		<li>
                				{l s='Notifications are also stored in your account in Seller Central. To view them in Seller Central, click [1]Performance[/1], and then click [2]Performance Notifications[/2].' tags=['<strong>', '<strong>'] mod='amzpayments'}							                       
							</li>
        	    	    </ul>
					</div>	
					<a class="btn btn-warning btn-sm" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">
    	            	<strong>{l s='Check your Amazon Payments account notifications' mod='amzpayments'}</strong>
					</a>		
				{/if}
			</div>
			
			
		</div>
		<div id="amzconfiguration" class="tab-pane">
			{if $no_ssl}
				<div class="alert alert-warning" role="alert">
					<strong>{l s='Warning!' mod='amzpayments'}</strong> &nbsp;{l s='You don’t have any active SSL certificate installed. To use Amazon Pay on your website you need a SSL certificate installed.' mod='amzpayments'}
				</div>
			{/if}
			{if isset($kyc_passed) && $kyc_passed == 1}
			{else}
				<div class="alert alert-warning" role="alert">
					<strong>{l s='Warning!' mod='amzpayments'}</strong> &nbsp;{l s='Your Amazon Pay account has not been validated. Please check your performance notifications to know what information has to be provided.' mod='amzpayments'}
					<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal6"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
					<a style="color:#FF9900;cursor: pointer;" class="showvalidationnotificationvideo2" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>					
				</div>				
 	   	        <div class="validationnotificationvideo2" style="width: 80%; clear: both">
    	        	<video controls="controls" style="max-width: 100%;">
						<source src="{$videodir|escape:'html':'UTF-8'}{$langdir|escape:'html':'UTF-8'}/Notifications{$langdir|escape:'html':'UTF-8'|strtoupper}.mp4" type="video/webm" />
					</video>
				</div> 
			{/if}
		
			<div id="advancedconfig">
             	<button id="restoreconfig" class="btn btn-warning btn-sm" style="margin:10px;" type="button" aria-expanded="false" aria-controls="collapseExample" onclick="if (confirm('{l s='Really reset to default values?' mod='amzpayments'}')) { location.href='{$reset_link|escape:'htmlall':'UTF-8'}'; }">
					<strong><i class="fa fa-undo" aria-hidden="true"></i>&nbsp;{l s='Restore Default Settings' mod='amzpayments'}</strong>
				</button>
                <a style="cursor: pointer;" target="_blank" href="https://pay.amazon.com/{$langdir|escape:'htmlall':'UTF-8'}/help/202137100">{l s='Click here for more information on advanced configuration' mod='amzpayments'}</a></p>
                
				<div id="returnedurl">
					<a style="cursor: pointer;" target="_blank" href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU">
						{l s='Enter these URLs in your Amazon Pay account' mod='amzpayments'}
					</a>
					<a style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal2"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See instructions' mod='amzpayments'}</a>
					<a style="color:#FF9900;cursor: pointer;" id="showvideonotification" title="{l s='Watch our video' mod='amzpayments'}"><i class="fa fa-file-video-o" aria-hidden="true"></i> {l s='Watch our video' mod='amzpayments'}</a>
					<br />
					{foreach from=$allowed_return_url_1 item=aru}
						{$aru|escape:'htmlall':'UTF-8'}<br />		
					{/foreach}
					{foreach from=$allowed_return_url_2 item=aru}
						{$aru|escape:'htmlall':'UTF-8'}<br />		
					{/foreach}
				</div>
				
				<div id="grouping_states" style="display: none">
					<div id="execution_states" class="grouping">{l s='Payment transaction execution' mod='amzpayments'}</div>
					<div id="payment_states" class="grouping">{l s='Payment transaction status' mod='amzpayments'}</div>
					<div id="email_state" class="grouping">{l s='Automatic email' mod='amzpayments'}</div>
					<div id="amazon_notification" class="grouping">{l s='Payment status updates' mod='amzpayments'}</div>
				</div>
					
			</div>
			<div class="text-right">
				<hr />
				<button type="submit" value="1" name="submitAmzpaymentsModule" class="btn btn-default">
					<i class="process-icon-save"></i> {l s='Save' mod='amzpayments'}
				</button>
			</div>			
		</div>
		<div id="amzpromote" class="tab-pane">
		
			<div id="promobanners">
				{foreach from=$banners item=bannerset key=settyp}
					{foreach from=$bannerset item=banner key=bannertyp}
						<div id="banner_{$settyp|escape:'htmlall':'UTF-8'}_{$bannertyp|escape:'htmlall':'UTF-8'}"><img src="{$banner|escape:'htmlall':'UTF-8'}" /></div>
					{/foreach}				
				{/foreach}
			</div>
		
			<p>
				{l s='You can integrate the "Login with Amazon"-Button at any part of your template. Just use the following HTML-Code, but be aware to always (!) use a unique value for the attribute "id":' mod='amzpayments'}
			</p>
			<code> &lt;div id=&quot;&quot; class=&quot;amazonLoginWr&quot;&gt;&lt;/div&gt; </code>
			
			<div class="text-right">
				<hr />
				<button type="submit" value="1" name="submitAmzpaymentsModule" class="btn btn-default">
					<i class="process-icon-save"></i> {l s='Save' mod='amzpayments'}
				</button>
			</div>
			
		</div>
		<div id="amzcontactus" class="tab-pane">
			<p>
				{l s='Don\'t have an Amazon Pay account?' mod='amzpayments'} <a href="{$new_customer_link|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Click here' mod='amzpayments'}</a><br>
				<br>
				<b>{l s='Already have an Amazon Pay account?' mod='amzpayments'}</b> <a href="https://sellercentral-europe.amazon.com/home?cor=mmd_EU" target="_blank">{l s='Click here' mod='amzpayments'}</a> 
				<i style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal8"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See how to do it' mod='amzpayments'}</i>
				<br>
				{l s='Merchant support might ask you to provide your shop’s log files. To download your log files, ' mod='amzpayments'} <a href="{$log_url|escape:'htmlall':'UTF-8'}" target="_blank">{l s='click here' mod='amzpayments'}</a>. <br> 			
			</p>
			<p class="amz_illustration">
				<img src="https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Prestashop/img/AmazonPayContactUs.jpg" />
			</p>
		</div>
		<div id="amzfaq" class="tab-pane">
			<p>
				<a href="{$faq_link|escape:'htmlall':'UTF-8'}" target="_blank">{l s='Frequently asked questions' mod='amzpayments'}</a>
			</p>
			<p class="amz_illustration">
				<img src="https://m.media-amazon.com/images/G/01/EPSDocumentation/AmazonPay/Prestashop/img/AmazonPayFAQ.jpg" />
			</p>
		</div>
	</div>
</div>

<div id="help-addon-notifications" style="display:none;"><i style="color:#FF9900;cursor: pointer;" aria-hidden="true" data-toggle="modal" data-target="#myModal2"><i class="fa fa-eye" aria-hidden="true"></i> {l s='See how to do it' mod='amzpayments'}</i></a></div>

<div class="modal fade" id="myModal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='How to copy your access keys' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/SelectYourKey.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal1a">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Import your access keys' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/ImportYourKey.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal2">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Enter the link to receive notifications' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/CopyIPN.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal3">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='How to login with a test account' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/Login.png" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal4">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Paste the URL into the [1]Allowed JavaScript Origins[/1] field.' tags=['<strong>'] mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/CopyURLLoginAuthorizedOrigin.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal5">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Paste the URL into the [1]Allowed Return URLs[/1] field.' tags=['<strong>'] mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/CopyURLLoginRedirection.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal6">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Check your Amazon Payments account notifications' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/Notifications.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="myModal8">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color:lightgray;">
				<h4 class="modal-title">{l s='Contact us' mod='amzpayments'}</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				<img src="{$imgdir|escape:'html':'UTF-8'}{$langdir|escape:'htmlall':'UTF-8'}/ContactUs.jpg" />
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">X</button>
			</div>
		</div>
	</div>
</div>

</form>
</div>

<div class="panel" id="amzVersionChecker">
	<div class="panel-heading">
		<i class="icon-cogs"></i>
		{l s='Version-Checker' mod='amzpayments'}
	</div>
	<div class="row">
		<div class="col-xs-12">
			<p style="text-align: center" id="versionCheck">
				<img src="{$smarty.const._PS_BASE_URL_|escape:'htmlall':'UTF-8'}{$smarty.const.__PS_BASE_URI__|escape:'htmlall':'UTF-8'}modules/{$module_name|escape:'htmlall':'UTF-8'}/views/img/loading_indicator.gif" />
				<br /><br />
				{l s='We check if there is a new version of the plugin available.' mod='amzpayments'}
				<br /><br />
			</p>
			<p style="text-align: center" id="versionCheckResult">
				{l s='Your version: ' mod='amzpayments'} <strong>{$current_version|escape:'htmlall':'UTF-8'}</strong>
				<br /><br />
			</p>			
		</div>
	</div>
</div>

<script language="javascript">
	var kyc_passed = '{if isset($kyc_passed)}{$kyc_passed|escape:'htmlall':'UTF-8'}{else}-1{/if}';
	var after_reset = '{if isset($after_reset)}1{else}0{/if}';
	{literal}
	$(document).ready(function() {
		if (after_reset == '1') {
			$('#amztabs a[href=#amzconfiguration]').tab('show');			
		} else if (kyc_passed == '0') {
			$('#amztabs a[href=#amzconnect]').tab('show');
		} else if (kyc_passed == '1') {
			$('#amztabs a[href=#amzconfiguration]').tab('show');	
		}
		$.post("../modules/amzpayments/ajax.php",
		{
			action: "versionCheck",
			asv: "{/literal}{$current_version|escape:'htmlall':'UTF-8'}{literal}",
			psv: "{/literal}{$smarty.const._PS_VERSION_|escape:'htmlall':'UTF-8'}{literal}",
			ref: location.host
		}, 
		function(data) {	
			if (data.newversion == 1) {
				$("#versionCheckResult").append("{/literal}{l s='There is a new version available: ' mod='amzpayments'}{literal}<strong>" + data.newversion_number + "</strong><br /><br /><a href=\"http://www.patworx.de/Amazon-Advanced-Payment-APIs/PrestaShop\" target=\"_blank\">&gt; Download</a>");
			} else {
				$("#versionCheckResult").append("{/literal}{l s='Everything is fine - you are using the latest version' mod='amzpayments'}{literal}");
			}
			$("#versionCheck").hide();
		}, "json"
		);
	});
	{/literal}
</script>

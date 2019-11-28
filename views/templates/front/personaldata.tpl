{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2019 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
    	{$amzpayments->translateHelper('My account', 'identity')|escape:'html':'UTF-8'}
    </a>
    <span class="navigation-pipe">
        {$navigationPipe} {* no escaping! see usage in default-bootstrap theme *}
    </span>
    <span class="navigation_page">
    	{$amzpayments->translateHelper('Your personal information', 'identity')|escape:'html':'UTF-8'}
    </span>
{/capture}
<div class="box">
    <h1 class="page-subheading">
    	{$amzpayments->translateHelper('Your personal information', 'identity')|escape:'html':'UTF-8'}
    </h1>

    {include file="$tpl_dir./errors.tpl"}

    {if isset($confirmation) && $confirmation}
        <p class="alert alert-success">
    		{$amzpayments->translateHelper('Your personal information has been successfully updated.', 'identity')|escape:'html':'UTF-8'}
        </p>
    {else}
        <p class="required">
            <sup>*</sup>{$amzpayments->translateHelper('Required field', 'identity')|escape:'html':'UTF-8'}
        </p>
        <form action="{$link->getModuleLink('amzpayments', 'personaldata')|escape:'html':'UTF-8'}" method="post" class="std">
            <fieldset>
                <div class="required form-group">
                    <label for="firstname" class="required">
                    	{$amzpayments->translateHelper('First name', 'identity')|escape:'html':'UTF-8'}
                    </label>
                    <input class="is_required validate form-control" data-validate="isName" type="text" id="firstname" name="firstname" value="{$smarty.post.firstname|escape:'html':'UTF-8'}" />
                </div>
                <div class="required form-group">
                    <label for="lastname" class="required">
                        {$amzpayments->translateHelper('Last name', 'identity')|escape:'html':'UTF-8'}
                    </label>
                    <input class="is_required validate form-control" data-validate="isName" type="text" name="lastname" id="lastname" value="{$smarty.post.lastname|escape:'html':'UTF-8'}" />
                </div>
                <div class="form-group">
                    <button type="submit" name="submitIdentity" class="btn btn-default button button-medium">                    	
                        <span>{$amzpayments->translateHelper('Save', 'identity')|escape:'html':'UTF-8'}<i class="icon-chevron-right right"></i></span>
                    </button>
                </div>
            </fieldset>
        </form>
    {/if}
</div>


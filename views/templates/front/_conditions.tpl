{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2016 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

  {if $conditions_to_approve|count}
    <form id="conditions-to-approve" method="GET">
      <ul>
        {foreach from=$conditions_to_approve item="condition" key="condition_name"}
          <li>
            <div class="pull-xs-left">
              <span class="custom-checkbox">
                <input  id    = "conditions_to_approve[{$condition_name}]"
                        name  = "conditions_to_approve[{$condition_name}]"
                        class = "conditions_to_approve_checkbox" 
                        required
                        type  = "checkbox"
                        value = "1"
                        class = "ps-shown-by-js"
                >
                <span><i class="material-icons checkbox-checked">&#xE5CA;</i></span>
              </span>
            </div>
            <div class="condition-label">
              <label class="js-terms" for="conditions_to_approve[{$condition_name}]">
                {$condition nofilter}
              </label>
            </div>
          </li>
        {/foreach}
      </ul>
    </form>
  {/if}	
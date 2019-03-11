{*
* Amazon Advanced Payment APIs Modul
* for Support please visit www.patworx.de
*
*  @author patworx multimedia GmbH <service@patworx.de>
*  In collaboration with alkim media
*  @copyright  2013-2015 patworx multimedia GmbH
*  @license    Released under the GNU General Public License
*}

{foreach from=$troubleshooter_results item=tsr}
	
	<div class="row">
		<div class="col-xs-6">
			<div class="row innerrow">
				<div class="col-xs-1">
					{if $tsr.status == '1'}
						<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACIElEQVQ4jY2ST28SURTFL8U0NFIhgV1ZIC5MDAMznRmhTdohIWkXlpqYVINgYGN3xg+giexd2G/Qld2ONdAiNqVALFAGMfxLupHFUE1dSCRxoV0cF1joAJqe5C5e3vm9c999j2hIN2urLqbxQGZrD1WhHgZXDeLWp3vqjXJAthX8rmG/Ru5GUJ5thmCt+KFXboMUHqTw0CsiTMoiHKUV2ArL8ggYbcUMQiOs2quBPvSvshR9mDlcUu2bPoMm+TLweZnzC7BkpV4nbD0kzjZDlwKjrRik48cghYc1I+Ha3rxIztpaylrx/xf0Ha+j9esEkdYLUJYFfeBgyHtgPJhPkdh41L44MHs1ALYZ7K83TrcAAE8/vwTFmd4BCg9dScD0e2+bhHpYk5buKtj4ugVzSYLcSQMA5G/7oDdO0AGr8RqTHhBXHaSxzSDO9f3sBwCgc9aFKeEFvXONXG1qVwQxlbW2XhFBCo9Xp68xrEj5GeitE3SkhXUlHpNxrk3XP95JmZRFkMLDVJGw+WW7D7d+nvRaz7Ij6RM5HvokmyJHcVVwlFYGm0c8IvXn6Pzu4m7hSW9wY17myg4L2nYKRERkKyzLlqJPY3CX7/daz3Gj6fscKMEMvrQvHTXMHC6p5vyC1pyfHQ/HGZVidgMNy5KVZGtGgiHvga4kaAY2kfvb9sXkcbqamWOm03Oycc+rGpMeTO2KmNzhVH3SLVOCYYb9fwA8T9y7cxPBgwAAAABJRU5ErkJggg==" />
					{else}
						<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAACrElEQVQ4jW1T30taYRh+VShjehTsri5MPd+h7OjRVOgiirnBGBtRsfv9KV33J2zQaIEUhMdK7aAnNS1tuCbaBrvr4jiysq0xxlj78eziOPuxvfDywfc+3/O+z8P7Ed2KgweCrzEtyvUZSavPhVCbCeD1wxGtes8t708M+m7jb0Rj2i8fzgZRHe9HecSEkkB6DptQDtlQiQ6hPDko//PwaP6p+XAupNWiTuwKhB1GyDHCNiOonTPH9PtSxIHixID2YsppvtG5FnWiJBDyjJAP2qHwhLTnKtUAhyyv1wtjdhQiDn2St7NS+HA2iN3O4w9ri7hoVJGUbJDdBNlNSEkc2kUFR88XkOmQqGEHVIkLU+PxaKY63o8dRsgHbfj87g1+X16iXasg7rch7rfhbE/F92MNJ7kkEqIVWZ6Q8/ZClSwZajwJN8sjJuQYQeEJWwEbzutV/Pr2FafVEk73C7g8O0arqCDmtSLmImzxhG3BgLTf2qT6XAglQTcq7SEk3KR3rVXw88sFfnxqo7WnYslrxZJTl5T26PhN0QKqzQRQEnS30x4dEJfsaF8n2FWxPMphxXVFoDLChrcPdPBIbJaGTd0JUgE7Pl6T0LomIe7jkOgSGJAQeppUvTuUKYdsyDGCOmbvmnhWq+Cll8PyKIeTjomtXBIpiYPCExTBiCRvytCr+65QJTqEHUbI8oTm2iLO61XERA5LTsKKixD3cTgpKnj/bAGrLkKGJySYCesuChERUXlyUC5FHMgzvZiSbIh19Mpu3diU34pVFyHuJqSYEQk3Xa10fmrKXJwY0ApjduQ7k6Q8ev7dRIXvkDMj4h7S5onMdDsKEYeshh3IeXuxLRi6f0FlBiiCEQlmutn5f5EV74iqZJW3fBZtU7Rgw9uHdaFH22AmOeUi8Tb+Dx+s3LBdcjXYAAAAAElFTkSuQmCC" />
					{/if}
				</div>
				<div class="col-xs-11 troubleshooter_state_{$tsr.status|escape:'htmlall':'UTF-8'}">
					{$tsr.title|escape:'htmlall':'UTF-8'}
				</div>
			</div>			
			{if $tsr.status == '0'}
				<div class="row">
					<div class="col-xs-1">
						&nbsp;
					</div>
					<div class="col-xs-11">
						{$tsr.description nofilter} {* no escaping, previously prepared html content! *}
					</div>
				</div>
			{/if}
		</div>
	</div>

{/foreach}
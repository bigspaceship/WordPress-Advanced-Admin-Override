<div class="wrap">
	<h2>Advanced Admin Override</h2>

	<p>If there are any issues updating or saving custom admin fields, start be disabling attributes here. If there are still issues, go to <a href="<?= get_bloginfo('wpurl') ?>/wp-admin/plugins.php">the Plugins page</a> and deactivate <strong>Advanced Admin Override</strong>. This should fix any issues.</p>
	<p>Contact a developer to any critical fixes beyond that.</p>


	<form method="post">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">Check for Required Fields</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>Check for Required Fields</span></legend>
							<?php foreach($modules as $slug=>$module): ?>
							<label for="bss_admin_require_<?= $slug; ?>">
								<input name="bss_admin_require_<?= $slug; ?>" type="checkbox" id="bss_admin_require_<?= $slug; ?>" <?= $module['is_enabled'] == true ? 'checked="checked"' : '' ?>"> <?= $module['label']; ?>
							</label>
							<br>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>

	</form>
</div>
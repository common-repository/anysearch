<?php

//phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.

?>
<div id="anysearch-plugin-container">
	<div class="anysearch-lower">
		<?php Anysearch_Admin::display_status();?>
		<div class="anysearch-boxes">
			<?php
                Anysearch::view( 'activate' );
			?>
		</div>
	</div>
</div>
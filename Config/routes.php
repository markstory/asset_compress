<?php
Router::connect('/assets/*', array(
	'plugin' => 'asset_compress', 'controller' => 'assets', 
	'action' => 'get'
));

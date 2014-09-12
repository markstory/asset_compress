<?php

if (isset($this->AssetCompress)) {
	$available = true;
}

$this->AssetCompress->addScript('one_file', 'single');
$this->AssetCompress->addScript('no_build');

$this->AssetCompress->addCss('no_build');
$this->AssetCompress->addCss('one_file', 'single');

<?php
$this->AssetCompress->addCss(array('no', 'build'));
$this->AssetCompress->addCss(array('has', 'a_build'), 'array_file');

$this->AssetCompress->addScript(array('no', 'build'));
$this->AssetCompress->addScript(array('one_file', 'two_file'), 'multi_file');

<?php
$this->AssetCompress->css(array('no', 'build'));
$this->AssetCompress->css(array('has', 'a_build'), 'array_file');

$this->AssetCompress->script(array('no', 'build'));
$this->AssetCompress->script(array('one_file', 'two_file'), 'multi_file');
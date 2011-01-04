<?php

$this->AssetCompress->script('one_file', 'single');
$this->AssetCompress->script('no_build');

$this->AssetCompress->css('no_build');
$this->AssetCompress->css('one_file', 'single');

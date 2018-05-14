<?php
use AssetCompress\Middleware\AssetCompressMiddleware;
use Cake\Core\Configure;
use Cake\Event\EventManager;
use Cake\Routing\DispatcherFactory;

$appClass = Configure::read('App.namespace') . '\Application';
if (!class_exists($appClass)) {
    deprecationWarning('You need to upgrade to the new Http Libraries to use AssetCompress.');
}

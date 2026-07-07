<?php
require __DIR__ . '/vendor/autoload.php';

if (class_exists('Livewire\Features\SupportFileUploads\TemporaryUploadedFile')) {
    $r = new ReflectionClass('Livewire\Features\SupportFileUploads\TemporaryUploadedFile');
    echo "Class: " . $r->getName() . "\n";
    foreach ($r->getMethods() as $m) {
        echo " - " . $m->getName() . "\n";
    }
} else {
    echo "TemporaryUploadedFile class not found.\n";
}

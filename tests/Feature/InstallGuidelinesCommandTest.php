<?php

use Illuminate\Support\Facades\File;

// Define variables to store the temporary directory and created files
$tempDir = '';
$createdFiles = [];

beforeEach(function () use (&$tempDir, &$createdFiles) {
    // Create a temporary directory for testing
    $tempDir = sys_get_temp_dir().'/junie_test_'.uniqid();
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Set the output path in the config and ensure all documents are enabled for testing
    config(['junie.output_path' => $tempDir]);

    // Set all documents as enabled for testing
    $documents = config('junie.documents', []);
    foreach ($documents as $key => $document) {
        $documents[$key]['enabled'] = true;
    }
    config(['junie.documents' => $documents]);

    // Mock the File facade for the copy method
    File::shouldReceive('isDirectory')->andReturnUsing(function ($path) use ($tempDir) {
        return $path === $tempDir || is_dir($path);
    });

    File::shouldReceive('makeDirectory')->andReturnUsing(function ($path, $mode, $recursive, $force) {
        if (! is_dir($path)) {
            return mkdir($path, $mode, $recursive);
        }

        return true;
    });

    // Create a variable to track created files
    $createdFiles = [];

    // Allow the put method to work normally and track the created files
    File::shouldReceive('put')->andReturnUsing(function ($path, $content) use (&$createdFiles) {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $result = file_put_contents($path, $content);
        $createdFiles[] = $path;

        return $result;
    });

    // Allow the exists method to work normally
    File::shouldReceive('exists')->andReturnUsing(function ($path) {
        return file_exists($path);
    });

    // Mock the deleteDirectory method
    File::shouldReceive('deleteDirectory')->andReturnUsing(function ($path) {
        if (is_dir($path)) {
            $files = glob($path.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($path);
        }

        return true;
    });

    // Mock the isFile method to check our tracking variable
    File::shouldReceive('isFile')->andReturnUsing(function ($path) use (&$createdFiles) {
        return in_array($path, $createdFiles) || is_file($path);
    });

    // Update the copy method to track created files
    File::shouldReceive('copy')->andReturnUsing(function ($source, $destination) use (&$createdFiles) {
        $content = "# Mock guideline content\n\nThis is mock content for testing.";
        $dir = dirname($destination);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($destination, $content);
        $createdFiles[] = $destination;

        return true;
    });
});

afterEach(function () use (&$tempDir, &$createdFiles) {
    // Clean up the temporary directory
    if (is_dir($tempDir)) {
        $files = glob($tempDir.'/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($tempDir);
    }
});

it('installs all guidelines when --all flag is used', function () use (&$tempDir, &$createdFiles) {
    test()->artisan('junie:install', ['--all' => true])
        ->assertSuccessful()
        ->expectsOutput('Guidelines installed successfully!');

    // Check that all files were created
    $generalPath = null;
    foreach ($createdFiles as $file) {
        if (strpos($file, '/general.md') !== false) {
            $generalPath = $file;
            break;
        }
    }
    expect($generalPath)->not->toBeNull();
    expect(File::isFile($generalPath))->toBeTrue();

    $apiPath = null;
    $livewirePath = null;
    $testingPath = null;
    $frontendPath = null;
    $modulesPath = null;
    $indexPath = null;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/api.md') !== false) {
            $apiPath = $file;
        } elseif (strpos($file, '/livewire.md') !== false) {
            $livewirePath = $file;
        } elseif (strpos($file, '/testing.md') !== false) {
            $testingPath = $file;
        } elseif (strpos($file, '/frontend.md') !== false) {
            $frontendPath = $file;
        } elseif (strpos($file, '/modules.md') !== false) {
            $modulesPath = $file;
        } elseif (strpos($file, '/index.md') !== false) {
            $indexPath = $file;
        }
    }

    expect($apiPath)->not->toBeNull();
    expect($livewirePath)->not->toBeNull();
    expect($testingPath)->not->toBeNull();
    expect($frontendPath)->not->toBeNull();
    expect($modulesPath)->not->toBeNull();
    expect($indexPath)->not->toBeNull();

    expect(File::isFile($apiPath))->toBeTrue();
    expect(File::isFile($livewirePath))->toBeTrue();
    expect(File::isFile($testingPath))->toBeTrue();
    expect(File::isFile($frontendPath))->toBeTrue();
    expect(File::isFile($modulesPath))->toBeTrue();
    expect(File::isFile($indexPath))->toBeTrue();
});

it('installs specific guidelines when individual flags are used', function () use (&$tempDir, &$createdFiles) {
    test()->artisan('junie:install', ['--general' => true, '--api' => true])
        ->assertSuccessful()
        ->expectsOutput('Guidelines installed successfully!');

    // Check that only specified files were created
    $generalPath = null;
    $apiPath = null;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/general.md') !== false) {
            $generalPath = $file;
        } elseif (strpos($file, '/api.md') !== false) {
            $apiPath = $file;
        }
    }

    expect($generalPath)->not->toBeNull();
    expect($apiPath)->not->toBeNull();

    expect(File::isFile($generalPath))->toBeTrue();
    expect(File::isFile($apiPath))->toBeTrue();

    // Check that other files were not created
    $livewireExists = false;
    $testingExists = false;
    $frontendExists = false;
    $modulesExists = false;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/livewire.md') !== false) {
            $livewireExists = true;
        } elseif (strpos($file, '/testing.md') !== false) {
            $testingExists = true;
        } elseif (strpos($file, '/frontend.md') !== false) {
            $frontendExists = true;
        } elseif (strpos($file, '/modules.md') !== false) {
            $modulesExists = true;
        }
    }

    expect($livewireExists)->toBeFalse();
    expect($testingExists)->toBeFalse();
    expect($frontendExists)->toBeFalse();
    expect($modulesExists)->toBeFalse();

    // Check for the index file in the createdFiles array
    $indexPath = null;
    foreach ($createdFiles as $file) {
        if (strpos($file, '/index.md') !== false) {
            $indexPath = $file;
            break;
        }
    }
    expect($indexPath)->not->toBeNull();
    expect(File::isFile($indexPath))->toBeTrue();
});

it('prompts for guidelines when no flags are provided', function () use (&$tempDir, &$createdFiles) {
    test()->artisan('junie:install')
        ->expectsQuestion('Which guidelines would you like to install?', ['general', 'api'])
        ->assertSuccessful()
        ->expectsOutput('Guidelines installed successfully!');

    // Check that only selected files were created
    $generalPath = null;
    $apiPath = null;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/general.md') !== false) {
            $generalPath = $file;
        } elseif (strpos($file, '/api.md') !== false) {
            $apiPath = $file;
        }
    }

    expect($generalPath)->not->toBeNull();
    expect($apiPath)->not->toBeNull();

    expect(File::isFile($generalPath))->toBeTrue();
    expect(File::isFile($apiPath))->toBeTrue();

    // Check that other files were not created
    $livewireExists = false;
    $testingExists = false;
    $frontendExists = false;
    $modulesExists = false;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/livewire.md') !== false) {
            $livewireExists = true;
        } elseif (strpos($file, '/testing.md') !== false) {
            $testingExists = true;
        } elseif (strpos($file, '/frontend.md') !== false) {
            $frontendExists = true;
        } elseif (strpos($file, '/modules.md') !== false) {
            $modulesExists = true;
        }
    }

    expect($livewireExists)->toBeFalse();
    expect($testingExists)->toBeFalse();
    expect($frontendExists)->toBeFalse();
    expect($modulesExists)->toBeFalse();

    // Check for the index file in the createdFiles array
    $indexPath = null;
    foreach ($createdFiles as $file) {
        if (strpos($file, '/index.md') !== false) {
            $indexPath = $file;
            break;
        }
    }
    expect($indexPath)->not->toBeNull();
    expect(File::isFile($indexPath))->toBeTrue();
});

it('installs all guidelines when "all" is selected in prompt', function () use (&$tempDir, &$createdFiles) {
    test()->artisan('junie:install')
        ->expectsQuestion('Which guidelines would you like to install?', ['all'])
        ->assertSuccessful()
        ->expectsOutput('Guidelines installed successfully!');

    // Check that all files were created
    $generalPath = null;
    $apiPath = null;
    $livewirePath = null;
    $testingPath = null;
    $frontendPath = null;
    $modulesPath = null;
    $indexPath = null;

    foreach ($createdFiles as $file) {
        if (strpos($file, '/general.md') !== false) {
            $generalPath = $file;
        } elseif (strpos($file, '/api.md') !== false) {
            $apiPath = $file;
        } elseif (strpos($file, '/livewire.md') !== false) {
            $livewirePath = $file;
        } elseif (strpos($file, '/testing.md') !== false) {
            $testingPath = $file;
        } elseif (strpos($file, '/frontend.md') !== false) {
            $frontendPath = $file;
        } elseif (strpos($file, '/modules.md') !== false) {
            $modulesPath = $file;
        } elseif (strpos($file, '/index.md') !== false) {
            $indexPath = $file;
        }
    }

    expect($generalPath)->not->toBeNull();
    expect($apiPath)->not->toBeNull();
    expect($livewirePath)->not->toBeNull();
    expect($testingPath)->not->toBeNull();
    expect($frontendPath)->not->toBeNull();
    expect($modulesPath)->not->toBeNull();
    expect($indexPath)->not->toBeNull();

    expect(File::isFile($generalPath))->toBeTrue();
    expect(File::isFile($apiPath))->toBeTrue();
    expect(File::isFile($livewirePath))->toBeTrue();
    expect(File::isFile($testingPath))->toBeTrue();
    expect(File::isFile($frontendPath))->toBeTrue();
    expect(File::isFile($modulesPath))->toBeTrue();
    expect(File::isFile($indexPath))->toBeTrue();
});

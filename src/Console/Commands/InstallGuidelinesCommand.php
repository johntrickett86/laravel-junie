<?php

namespace Dcblogdev\Junie\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallGuidelinesCommand extends Command
{
    protected $signature = 'junie:install 
                            {--all : Install all guideline documents}
                            {--general : Install general guidelines}
                            {--api : Install API guidelines}
                            {--livewire : Install Livewire guidelines}
                            {--testing : Install testing guidelines}
                            {--frontend : Install frontend guidelines}
                            {--modules : Install modular architecture guidelines}';

    protected $description = 'Install selected guideline documents';

    public function handle(): void
    {
        // Determine which documents to install
        $installAll = $this->option('all');

        $documents = [
            'general' => $installAll || $this->option('general'),
            'api' => $installAll || $this->option('api'),
            'livewire' => $installAll || $this->option('livewire'),
            'testing' => $installAll || $this->option('testing'),
            'frontend' => $installAll || $this->option('frontend'),
            'modules' => $installAll || $this->option('modules'),
        ];

        // If no specific documents were selected, ask the user
        if (! $installAll && ! array_filter($documents)) {
            $documents = $this->promptForDocuments();
        }

        // Install selected documents
        $this->installDocuments($documents);

        $this->info('Guidelines installed successfully!');
    }

    protected function promptForDocuments(): array
    {
        return [
            'general' => $this->confirm('Install general guidelines?', true),
            'api' => $this->confirm('Install API guidelines?', false),
            'livewire' => $this->confirm('Install Livewire guidelines?', false),
            'testing' => $this->confirm('Install testing guidelines?', false),
            'frontend' => $this->confirm('Install frontend guidelines?', false),
            'modules' => $this->confirm('Install modular architecture guidelines?', false),
        ];
    }

    protected function installDocuments(array $documents): void
    {
        $outputPath = config('junie.output_path', '.junie');

        // Create an output directory if it doesn't exist
        if (! File::isDirectory($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
        }

        // Copy selected documents
        foreach ($documents as $document => $install) {
            if ($install) {
                $source = __DIR__.'/../../resources/docs/'.$document.'.md';
                $destination = base_path($outputPath.'/'.$document.'.md');

                File::copy($source, $destination);
                $this->line("Installed <info>{$document}</info>");
            }
        }

        // Create an index file with links to all installed documents
        $this->createIndexFile($outputPath, $documents);
    }

    protected function createIndexFile(string $outputPath, array $documents): void
    {
        $content = "# Laravel Guidelines\n\n";
        $content .= "## Available Guidelines\n\n";

        foreach ($documents as $document => $installed) {
            if ($installed) {
                $title = ucfirst($document);
                $content .= "- [{$title} Guidelines]({$document}.md)\n";
            }
        }

        File::put(base_path($outputPath.'/index.md'), $content);
    }
}

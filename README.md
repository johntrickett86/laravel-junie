# Laravel Junie

## Usage

After installing the package, users can:

1. Publish the configuration:
   ```bash
   php artisan vendor:publish --tag=guidelines-config
   ```

2. Install specific guideline documents:
   ```bash
   # Install all guidelines
   php artisan guidelines:install --all
   
   # Install only specific guidelines
   php artisan guidelines:install --general --testing
   
   # Interactive installation
   php artisan guidelines:install
   ```



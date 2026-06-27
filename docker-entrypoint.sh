#!/bin/bash

# Jika composer.json tidak ada, berarti proyek Laravel belum diinisialisasi
if [ ! -f "composer.json" ]; then
    echo "=================================================="
    echo "Initializing fresh Laravel project..."
    echo "=================================================="
    
    # Create Laravel project in a temporary directory to avoid "directory not empty" error
    composer create-project laravel/laravel tmp_laravel --no-interaction
    
    # Copy all files (including hidden ones) to the current working directory
    cp -rp tmp_laravel/. .
    
    # Clean up the temporary directory
    rm -rf tmp_laravel
    
    # Konfigurasi database di .env
    if [ -f ".env" ]; then
        echo "Configuring database credentials in .env..."
        sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env
        sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=db/g' .env
        sed -i 's/# DB_PORT=3306/DB_PORT=3306/g' .env
        sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=absensi_operator/g' .env
        sed -i 's/# DB_USERNAME=root/DB_USERNAME=sail/g' .env
        sed -i 's/# DB_PASSWORD=/DB_PASSWORD=password/g' .env
        
        # Juga untuk .env.example
        sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/g' .env.example
        sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=db/g' .env.example
        sed -i 's/# DB_PORT=3306/DB_PORT=3306/g' .env.example
        sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=absensi_operator/g' .env.example
        sed -i 's/# DB_USERNAME=root/DB_USERNAME=sail/g' .env.example
        sed -i 's/# DB_PASSWORD=/DB_PASSWORD=password/g' .env.example
    fi
    
    echo "=================================================="
    echo "Installing Laravel Breeze (Inertia + Vue + Tailwind)..."
    echo "=================================================="
    composer require laravel/breeze --dev --no-interaction
    php artisan breeze:install vue --inertia --dark --no-interaction
fi

# Pastikan folder vendor terinstal (cek autoload.php karena volume bisa kosong)
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Pastikan berkas .env ada
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Tunggu MySQL siap sebelum menjalankan migrasi
echo "Waiting for MySQL database to be ready..."
until mysqladmin ping -h db -u root -proot --skip-ssl --silent; do
    sleep 1
done

# Jalankan migrasi
echo "Running migrations..."
php artisan migrate --force

# Jalankan server Laravel
echo "Starting Laravel Artisan serve..."
php artisan serve --host=0.0.0.0 --port=8000 &

# Jaga agar container tetap berjalan
wait

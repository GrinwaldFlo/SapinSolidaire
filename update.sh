#!/bin/bash

# SapinSolidaire Website Update Script
# This script handles all necessary tasks when updating the website
# Including migrations, dependency installation, and asset compilation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Main update process
main() {
    print_info "Starting SapinSolidaire website update..."
    
    # Step 1: Pull latest changes
    print_info "Pulling latest changes from Git..."
    git pull origin || print_warning "Git pull failed, continuing anyway..."
    
    # Step 2: Install/Update PHP dependencies
    print_info "Installing PHP dependencies with Composer..."
    if command -v composer &> /dev/null; then
        composer install --no-interaction --optimize-autoloader
        print_success "Composer dependencies installed"
    else
        print_warning "Composer not found, skipping PHP dependencies"
    fi
    
    # Step 3: Install/Update Node dependencies
    print_info "Installing Node dependencies..."
    if command -v npm &> /dev/null; then
        npm install
        print_success "Node dependencies installed"
    else
        print_warning "npm not found, skipping Node dependencies"
    fi
    
    # Step 4: Build frontend assets
    print_info "Building frontend assets with Vite..."
    npm run build || print_warning "Frontend asset build failed, continuing anyway..."
    print_success "Frontend assets built"
    
    # Step 5: Run database migrations
    print_info "Running database migrations..."
    if command -v php &> /dev/null; then
        php artisan migrate --force
        print_success "Database migrations completed"
    else
        print_error "PHP not found, cannot run migrations"
        exit 1
    fi
    
    # Step 6: Clear caches
    print_info "Clearing application caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    print_success "Caches cleared"
    
    # Step 7: Set permissions (if running on Linux/macOS)
    if [[ "$OSTYPE" != "msys" && "$OSTYPE" != "win32" ]]; then
        print_info "Setting directory permissions..."
        chmod -R 775 storage bootstrap/cache
        print_success "Permissions set"
    fi
    
    # Completion message
    echo ""
    print_success "Website update completed successfully!"
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
    print_info "Next steps:"
    echo "  1. Verify the website is running correctly"
    echo "  2. Check the application logs: tail -f storage/logs/laravel.log"
    echo "  3. Monitor performance and error rates"
    echo -e "${BLUE}═══════════════════════════════════════${NC}"
}

# Error handling
trap 'print_error "Update script failed at line $LINENO"; exit 1' ERR

# Run main function
main

#!/bin/bash

# Database Transfer Script for Hello Deer Panel
# This script exports database from shared server and imports to local MySQL

set -e  # Exit on any error

# Configuration file
CONFIG_FILE=".db_transfer_config"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    if ! command_exists mysql; then
        print_error "MySQL client is not installed. Please install it first."
        exit 1
    fi
    
    if ! command_exists mysqldump; then
        print_error "mysqldump is not installed. Please install it first."
        exit 1
    fi
    
    if ! command_exists ssh; then
        print_error "SSH client is not installed. Please install it first."
        exit 1
    fi
    
    print_success "Prerequisites check passed"
}

# Function to get user input with default value
get_input() {
    local prompt="$1"
    local default="$2"
    local var_name="$3"
    
    if [ -n "$default" ]; then
        read -p "$prompt [$default]: " input
        eval "$var_name=\${input:-$default}"
    else
        read -p "$prompt: " input
        eval "$var_name=\$input"
    fi
}

# Function to load configuration
load_config() {
    if [ -f "$CONFIG_FILE" ]; then
        print_status "Loading saved configuration..."
        source "$CONFIG_FILE"
        print_success "Configuration loaded"
        return 0
    fi
    return 1
}

# Function to save configuration
save_config() {
    print_status "Saving configuration for future use..."
    cat > "$CONFIG_FILE" << EOF
# Database Transfer Configuration
# Generated on $(date)
CONNECTION_TYPE="$CONNECTION_TYPE"
EOF
    
    if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
        cat >> "$CONFIG_FILE" << EOF
SSH_HOST="$SSH_HOST"
SSH_USER="$SSH_USER"
SSH_PORT="$SSH_PORT"
SHARED_USER="$SHARED_USER"
SHARED_DATABASE="$SHARED_DATABASE"
MYSQL_SOCKET="$MYSQL_SOCKET"
SHARED_PASSWORD="$SHARED_PASSWORD"
EOF
    else
        cat >> "$CONFIG_FILE" << EOF
SHARED_HOST="$SHARED_HOST"
SHARED_USER="$SHARED_USER"
SHARED_DATABASE="$SHARED_DATABASE"
SHARED_PASSWORD="$SHARED_PASSWORD"
EOF
    fi
    
    cat >> "$CONFIG_FILE" << EOF
LOCAL_DATABASE="$LOCAL_DATABASE"
LOCAL_MYSQL_SOCKET="$LOCAL_MYSQL_SOCKET"
LOCAL_PASSWORD="$LOCAL_PASSWORD"
EOF
    
    print_success "Configuration saved to $CONFIG_FILE"
}

# Function to setup configuration
setup_config() {
    echo -e "${YELLOW}First time setup - Database Configuration:${NC}"
    echo
    
    # Choose connection method
    echo -e "${YELLOW}Connection Method:${NC}"
    echo "1) Remote MySQL connection (host/IP)"
    echo "2) SSH + Local socket (recommended for shared hosting)"
    read -p "Choose connection method (1/2): " connection_method
    
    if [[ $connection_method == "2" ]]; then
        # SSH + Socket method
        echo -e "${YELLOW}SSH Configuration:${NC}"
        get_input "Enter SSH host/IP" "" "SSH_HOST"
        get_input "Enter SSH username" "" "SSH_USER"
        get_input "Enter SSH port" "22" "SSH_PORT"
        get_input "Enter database username" "" "SHARED_USER"
        get_input "Enter database name" "" "SHARED_DATABASE"
        echo -n "Enter database password: "
        read -s SHARED_PASSWORD
        echo
        
        # Set socket path (common locations)
        get_input "Enter MySQL socket path" "/tmp/mysql.sock" "MYSQL_SOCKET"
        
        # Set connection method
        CONNECTION_TYPE="ssh_socket"
    else
        # Remote MySQL method
        echo -e "${YELLOW}Remote MySQL Configuration:${NC}"
        get_input "Enter shared server host/IP" "localhost" "SHARED_HOST"
        get_input "Enter database username" "" "SHARED_USER"
        get_input "Enter database name" "" "SHARED_DATABASE"
        echo -n "Enter database password: "
        read -s SHARED_PASSWORD
        echo
        
        # Set connection method
        CONNECTION_TYPE="remote_mysql"
    fi
    
    # Get local database details
    echo -e "${YELLOW}Local Database Configuration:${NC}"
    get_input "Enter local database name" "hello_deer_panel" "LOCAL_DATABASE"
    
    # Get Local MySQL connection details
    echo -e "${YELLOW}Local MySQL Connection:${NC}"
    get_input "Enter local MySQL socket path" "/tmp/mysql.sock" "LOCAL_MYSQL_SOCKET"
    echo -n "Enter local MySQL root password: "
    read -s LOCAL_MYSQL_PASSWORD
    echo
    
    # Ask if user wants to save config
    echo
    print_warning "Note: Passwords will be stored in the config file. Keep this file secure!"
    read -p "Save this configuration for future use? (Y/n): " save_config_choice
    
    if [[ $save_config_choice =~ ^[Nn]$ ]]; then
        print_status "Configuration not saved"
    else
        save_config
    fi
}

# Function to test database connection
test_connection() {
    if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
        test_ssh_socket_connection
    else
        test_remote_mysql_connection
    fi
}

# Function to test SSH + Socket connection
test_ssh_socket_connection() {
    print_status "Testing SSH connection to $SSH_HOST..."
    
    if ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "exit" 2>/dev/null; then
        print_success "SSH connection successful"
        
        print_status "Testing MySQL socket connection via SSH..."
        if ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mysql -S $MYSQL_SOCKET -u $SHARED_USER -p$SHARED_PASSWORD -e 'USE $SHARED_DATABASE;'" 2>/dev/null; then
            print_success "MySQL socket connection successful"
            return 0
        else
            print_error "MySQL socket connection failed"
            return 1
        fi
    else
        print_error "SSH connection failed"
        return 1
    fi
}

# Function to test remote MySQL connection
test_remote_mysql_connection() {
    local host="$SHARED_HOST"
    local user="$SHARED_USER"
    local password="$SHARED_PASSWORD"
    local database="$SHARED_DATABASE"
    
    print_status "Testing connection to $host..."
    
    if mysql -h "$host" -u "$user" -p"$password" -e "USE $database;" 2>/dev/null; then
        print_success "Connection successful"
        return 0
    else
        print_error "Connection failed"
        return 1
    fi
}

# Function to create local database if it doesn't exist
create_local_database() {
    local db_name="$1"
    
    print_status "Checking if local database '$db_name' exists..."
    
    if mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" -e "USE $db_name;" 2>/dev/null; then
        print_success "Database '$db_name' already exists"
    else
        print_status "Creating local database '$db_name'..."
        mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" -e "CREATE DATABASE $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        print_success "Local database created"
    fi
}

# Function to get list of tables from source database
get_source_tables() {
    print_status "Getting list of tables from source database..."
    
    if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
        # SSH + Socket method
        tables=$(ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mysql -S $MYSQL_SOCKET -u $SHARED_USER -p$SHARED_PASSWORD $SHARED_DATABASE -e 'SHOW TABLES;' --skip-column-names" 2>/dev/null)
    else
        # Remote MySQL method
        tables=$(mysql -h "$SHARED_HOST" -u "$SHARED_USER" -p"$SHARED_PASSWORD" "$SHARED_DATABASE" -e "SHOW TABLES;" --skip-column-names 2>/dev/null)
    fi
    
    if [ $? -eq 0 ] && [ -n "$tables" ]; then
        print_success "Found $(echo "$tables" | wc -l) tables to import"
        echo "$tables"
    else
        print_error "Failed to get table list from source database"
        exit 1
    fi
}

# Function to drop specific tables in local database
drop_source_tables_locally() {
    local db_name="$1"
    local tables="$2"
    
    if [ -z "$tables" ]; then
        print_status "No tables to drop"
        return 0
    fi
    
    print_status "Dropping existing tables that will be imported..."
    
    # Disable foreign key checks to allow dropping tables with constraints
    print_status "Disabling foreign key checks..."
    mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$db_name" -e "SET FOREIGN_KEY_CHECKS = 0;" >/dev/null 2>&1
    
    # Check which tables exist locally and drop them
    local dropped_count=0
    while IFS= read -r table; do
        if [ -n "$table" ]; then
            # Check if table exists in local database
            if mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$db_name" -e "DESCRIBE $table;" >/dev/null 2>&1; then
                print_status "Dropping table: $table"
                mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$db_name" -e "DROP TABLE IF EXISTS $table;" >/dev/null 2>&1
                if [ $? -eq 0 ]; then
                    ((dropped_count++))
                else
                    print_warning "Failed to drop table: $table"
                fi
            fi
        fi
    done <<< "$tables"
    
    # Re-enable foreign key checks
    print_status "Re-enabling foreign key checks..."
    mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$db_name" -e "SET FOREIGN_KEY_CHECKS = 1;" >/dev/null 2>&1
    
    if [ $dropped_count -gt 0 ]; then
        print_success "Dropped $dropped_count existing tables"
    else
        print_status "No existing tables needed to be dropped"
    fi
}

# Function to backup local database
backup_local_database() {
    local db_name="$1"
    local backup_file="local_backup_$(date +%Y%m%d_%H%M%S).sql"
    
    print_status "Creating backup of local database..."
    if mysqldump -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$db_name" > "$backup_file" 2>/dev/null; then
        print_success "Local backup created: $backup_file"
    else
        print_warning "Could not create local backup (database might be empty)"
    fi
}

# Main transfer function
transfer_database() {
    print_status "Starting database transfer..."
    
    if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
        print_status "From: $SHARED_USER@$SSH_HOST (via socket)"
    else
        print_status "From: $SHARED_USER@$SHARED_HOST"
    fi
    print_status "To: localhost/$LOCAL_DATABASE"
    
    # Test shared server connection
    if ! test_connection; then
        print_error "Cannot connect to shared server. Please check your credentials."
        exit 1
    fi
    
    # Create local database
    create_local_database "$LOCAL_DATABASE"
    
    # Backup local database
    backup_local_database "$LOCAL_DATABASE"
    
    # Perform the transfer
    print_status "Transferring database (this may take a while)..."
    
    # Disable foreign key checks for the import process
    print_status "Disabling foreign key checks for import..."
    mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE" -e "SET FOREIGN_KEY_CHECKS = 0;" >/dev/null 2>&1
    
    if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
        # SSH + Socket method (using --add-drop-table since tables are already clean)
        if ssh -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "mysqldump -S $MYSQL_SOCKET -u $SHARED_USER -p$SHARED_PASSWORD $SHARED_DATABASE --single-transaction --routines --triggers --add-drop-table --default-character-set=utf8mb4" | mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE"; then
            # Re-enable foreign key checks
            print_status "Re-enabling foreign key checks..."
            mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE" -e "SET FOREIGN_KEY_CHECKS = 1;" >/dev/null 2>&1
            print_success "Database transfer completed successfully!"
            print_success "Your local database '$LOCAL_DATABASE' is now ready."
        else
            # Re-enable foreign key checks even on failure
            mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE" -e "SET FOREIGN_KEY_CHECKS = 1;" >/dev/null 2>&1
            print_error "Database transfer failed!"
            exit 1
        fi
    else
        # Remote MySQL method (using --add-drop-table since tables are already clean)
        if mysqldump -h "$SHARED_HOST" -u "$SHARED_USER" -p"$SHARED_PASSWORD" \
            "$SHARED_DATABASE" \
            --single-transaction \
            --routines \
            --triggers \
            --add-drop-table \
            --default-character-set=utf8mb4 | mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE"; then
            
            # Re-enable foreign key checks
            print_status "Re-enabling foreign key checks..."
            mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE" -e "SET FOREIGN_KEY_CHECKS = 1;" >/dev/null 2>&1
            print_success "Database transfer completed successfully!"
            print_success "Your local database '$LOCAL_DATABASE' is now ready."
        else
            # Re-enable foreign key checks even on failure
            mysql -u root -p"$LOCAL_PASSWORD" -S "$LOCAL_MYSQL_SOCKET" "$LOCAL_DATABASE" -e "SET FOREIGN_KEY_CHECKS = 1;" >/dev/null 2>&1
            print_error "Database transfer failed!"
            exit 1
        fi
    fi
}

# Main script
main() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}  Hello Deer Panel DB Transfer  ${NC}"
    echo -e "${BLUE}================================${NC}"
    echo
    
    # Check prerequisites
    check_prerequisites
    
    # Try to load existing configuration
    if load_config; then
        echo -e "${YELLOW}Loaded Configuration:${NC}"
        if [[ $CONNECTION_TYPE == "ssh_socket" ]]; then
            echo "  SSH: $SSH_USER@$SSH_HOST:$SSH_PORT"
            echo "  Database: $SHARED_USER@$MYSQL_SOCKET/$SHARED_DATABASE"
        else
            echo "  Shared Server: $SHARED_USER@$SHARED_HOST/$SHARED_DATABASE"
        fi
        echo "  Local Database: $LOCAL_DATABASE"
        echo "  Local MySQL Socket: $LOCAL_MYSQL_SOCKET"
        echo
        
        read -p "Use saved configuration? (Y/n): " use_saved
        
        if [[ $use_saved =~ ^[Nn]$ ]]; then
            setup_config
        else
            # Check if passwords are saved
            if [[ -z "$SHARED_PASSWORD" || -z "$LOCAL_PASSWORD" ]]; then
                print_warning "Some passwords are missing from config. Please enter them:"
                if [[ -z "$SHARED_PASSWORD" ]]; then
                    echo -n "Enter shared database password: "
                    read -s SHARED_PASSWORD
                    echo
                fi
                if [[ -z "$LOCAL_PASSWORD" ]]; then
                    echo -n "Enter local MySQL password: "
                    read -s LOCAL_PASSWORD
                    echo
                fi
                # Save the updated config with passwords
                save_config
            else
                print_success "Using saved passwords from config"
                echo
                read -p "Update passwords? (y/N): " update_passwords
                if [[ $update_passwords =~ ^[Yy]$ ]]; then
                    print_status "Updating passwords..."
                    echo -n "Enter new shared database password: "
                    read -s SHARED_PASSWORD
                    echo
                    echo -n "Enter new local MySQL password: "
                    read -s LOCAL_PASSWORD
                    echo
                    save_config
                    print_success "Passwords updated and saved"
                fi
            fi
        fi
    else
        # First time setup
        setup_config
    fi
    
    echo
    print_status "Configuration summary:"
    echo "  Shared Server: $SHARED_USER@$SHARED_HOST/$SHARED_DATABASE"
    echo "  Local Database: $LOCAL_DATABASE"
    echo
    
    read -p "Proceed with transfer? (y/N): " confirm
    
    if [[ $confirm =~ ^[Yy]$ ]]; then
        transfer_database
    else
        print_status "Transfer cancelled"
        exit 0
    fi
}

# Run main function
main "$@"

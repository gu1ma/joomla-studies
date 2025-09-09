#!/bin/bash

# Start MySQL Docker container with Joomla database
echo "Starting MySQL 8.0 with Joomla database..."

# Stop any existing containers
docker compose -f docker-compose-mysql.yml down

# Remove existing volumes to ensure fresh database
echo "Removing existing database volumes..."
docker volume rm joomla-studies_mysql_data 2>/dev/null || true

# Start the containers
docker compose -f docker-compose-mysql.yml up -d

echo ""
echo "MySQL container is starting up..."
echo "Database credentials:"
echo "  Host: localhost:3306"
echo "  Database: joomla_db"
echo "  Username: joomla"
echo "  Password: joomla_password"
echo "  Root password: root_password"
echo ""
echo "phpMyAdmin: http://localhost:8081"
echo ""
echo "Waiting for MySQL to be ready..."

# Wait for MySQL to be healthy
while ! docker compose -f docker-compose-mysql.yml exec mysql mysqladmin ping -h localhost -u root -proot_password --silent; do
    echo -n "."
    sleep 2
done

echo ""
echo "MySQL is ready! Database has been initialized with your Joomla export."
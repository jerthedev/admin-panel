#!/bin/bash

# Admin Panel Development Symlink Setup Script
#
# This script adds the local admin panel package to composer.json
# for development purposes. Run this before installing the package
# when you want to use the local development version.
#
# Usage: ./setup-dev-symlink.sh
#
# @author Jeremy Fall <jerthedev@gmail.com>
# @version 1.0.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script info
echo -e "${BLUE}🔗 Admin Panel Development Symlink Setup${NC}"
echo -e "${BLUE}===========================================${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Error: composer.json not found in current directory${NC}"
    echo -e "${YELLOW}💡 Please run this script from your Laravel application root directory${NC}"
    exit 1
fi

# Check if the package directory exists
PACKAGE_PATH="packages/jerthedev/admin-panel"
if [ ! -d "$PACKAGE_PATH" ]; then
    echo -e "${RED}❌ Error: Package directory not found at $PACKAGE_PATH${NC}"
    echo -e "${YELLOW}💡 Please ensure the admin panel package is in the correct location${NC}"
    exit 1
fi

# Check if package composer.json exists
if [ ! -f "$PACKAGE_PATH/composer.json" ]; then
    echo -e "${RED}❌ Error: Package composer.json not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Found Laravel application composer.json${NC}"
echo -e "${GREEN}✅ Found admin panel package at $PACKAGE_PATH${NC}"
echo ""

# Backup composer.json
echo -e "${BLUE}📋 Creating backup of composer.json...${NC}"
cp composer.json composer.json.backup
echo -e "${GREEN}✅ Backup created: composer.json.backup${NC}"

# Check if repositories section already exists
if grep -q '"repositories"' composer.json; then
    echo -e "${YELLOW}⚠️  Repositories section already exists in composer.json${NC}"
    
    # Check if our package is already configured
    if grep -q "jerthedev/admin-panel" composer.json; then
        echo -e "${YELLOW}⚠️  Admin panel package already configured in composer.json${NC}"
        echo -e "${BLUE}ℹ️  Current configuration:${NC}"
        grep -A 10 -B 2 "jerthedev/admin-panel" composer.json || true
        echo ""
        read -p "Do you want to update the configuration? (y/N): " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}⏭️  Skipping configuration update${NC}"
            exit 0
        fi
    fi
else
    echo -e "${BLUE}📝 Adding repositories section to composer.json...${NC}"
fi

# Create temporary file with updated composer.json
echo -e "${BLUE}🔧 Updating composer.json...${NC}"

# Use jq if available, otherwise use sed
if command -v jq &> /dev/null; then
    echo -e "${GREEN}✅ Using jq for JSON manipulation${NC}"
    
    # Add repository configuration using jq
    jq --arg path "$PACKAGE_PATH" '
        .repositories = (.repositories // []) + [{
            "type": "path",
            "url": $path,
            "options": {
                "symlink": true
            }
        }]
    ' composer.json > composer.json.tmp && mv composer.json.tmp composer.json
    
else
    echo -e "${YELLOW}⚠️  jq not found, using sed (less reliable)${NC}"
    
    # Fallback to sed-based approach
    if grep -q '"repositories"' composer.json; then
        # Add to existing repositories array
        sed -i.tmp 's/"repositories": \[/"repositories": [\
        {\
            "type": "path",\
            "url": "'$PACKAGE_PATH'",\
            "options": {\
                "symlink": true\
            }\
        },/' composer.json
    else
        # Add new repositories section after name
        sed -i.tmp '/"name":/a\
    "repositories": [\
        {\
            "type": "path",\
            "url": "'$PACKAGE_PATH'",\
            "options": {\
                "symlink": true\
            }\
        }\
    ],' composer.json
    fi
    rm -f composer.json.tmp
fi

echo -e "${GREEN}✅ composer.json updated with local package repository${NC}"
echo ""

# Show the configuration
echo -e "${BLUE}📋 Repository configuration added:${NC}"
echo -e "${YELLOW}"
cat << EOF
{
    "type": "path",
    "url": "$PACKAGE_PATH",
    "options": {
        "symlink": true
    }
}
EOF
echo -e "${NC}"

echo ""
echo -e "${GREEN}🎉 Setup complete!${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "1. Run: ${YELLOW}composer require jerthedev/admin-panel${NC}"
echo -e "2. Run: ${YELLOW}php artisan vendor:publish --tag=admin-panel-config${NC} (optional)"
echo -e "3. Run: ${YELLOW}php artisan migrate${NC} (if any migrations)"
echo -e "4. Run: ${YELLOW}php artisan admin-panel:user${NC}"
echo -e "5. Visit: ${YELLOW}/admin${NC}"
echo ""
echo -e "${BLUE}💡 To restore original composer.json: ${YELLOW}mv composer.json.backup composer.json${NC}"

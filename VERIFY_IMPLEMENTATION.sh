#!/bin/bash

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}=======================================${NC}"
echo -e "${BLUE}Department & Role Implementation Verify${NC}"
echo -e "${BLUE}=======================================${NC}"
echo ""

# Check if artisan command exists
echo -e "${YELLOW}1. Checking Artisan Commands...${NC}"
if php artisan list | grep -q "users:check-departments"; then
    echo -e "${GREEN}✓ CheckUserDepartmentRequirements command found${NC}"
else
    echo -e "${RED}✗ CheckUserDepartmentRequirements command NOT found${NC}"
fi

if php artisan list | grep -q "users:fix-departments"; then
    echo -e "${GREEN}✓ FixUserDepartmentAssignments command found${NC}"
else
    echo -e "${RED}✗ FixUserDepartmentAssignments command NOT found${NC}"
fi
echo ""

# Check if files exist
echo -e "${YELLOW}2. Checking Modified Files...${NC}"

FILES=(
    "app/Services/RolePermissionService.php"
    "app/Models/User.php"
    "app/Livewire/BranchDashboard/EmployeeModule/Create.php"
    "app/Livewire/BranchDashboard/EmployeeModule/Edit.php"
    "app/Console/Commands/CheckUserDepartmentRequirements.php"
    "app/Console/Commands/FixUserDepartmentAssignments.php"
    "database/migrations/2026_01_08_152030_add_department_requirement_to_users_table.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file NOT FOUND${NC}"
    fi
done
echo ""

# Check for specific code additions
echo -e "${YELLOW}3. Checking Code Additions...${NC}"

if grep -q "validateRoleForDepartment" app/Services/RolePermissionService.php; then
    echo -e "${GREEN}✓ validateRoleForDepartment found in RolePermissionService${NC}"
else
    echo -e "${RED}✗ validateRoleForDepartment NOT found${NC}"
fi

if grep -q "ensureAllUsersHaveDepartment" app/Services/RolePermissionService.php; then
    echo -e "${GREEN}✓ ensureAllUsersHaveDepartment found in RolePermissionService${NC}"
else
    echo -e "${RED}✗ ensureAllUsersHaveDepartment NOT found${NC}"
fi

if grep -q "requiresDepartment" app/Models/User.php; then
    echo -e "${GREEN}✓ requiresDepartment method found in User model${NC}"
else
    echo -e "${RED}✗ requiresDepartment method NOT found${NC}"
fi

if grep -q "hasDepartment" app/Models/User.php; then
    echo -e "${GREEN}✓ hasDepartment method found in User model${NC}"
else
    echo -e "${RED}✗ hasDepartment method NOT found${NC}"
fi
echo ""

# Check documentation files
echo -e "${YELLOW}4. Checking Documentation...${NC}"

DOCS=(
    "DEPARTMENT_ROLE_REQUIREMENTS.md"
    "IMPLEMENTATION_SUMMARY_DEPARTMENT_ROLES.md"
    "QUICK_REFERENCE_DEPARTMENT_ROLES.md"
    "CHANGES_SUMMARY.txt"
)

for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✓ $doc${NC}"
    else
        echo -e "${RED}✗ $doc NOT FOUND${NC}"
    fi
done
echo ""

# Run the check command
echo -e "${YELLOW}5. Running Compliance Check...${NC}"
php artisan users:check-departments 2>/dev/null
echo ""

# Check for syntax errors
echo -e "${YELLOW}6. Checking PHP Syntax...${NC}"

SYNTAX_FILES=(
    "app/Services/RolePermissionService.php"
    "app/Models/User.php"
    "app/Livewire/BranchDashboard/EmployeeModule/Create.php"
    "app/Livewire/BranchDashboard/EmployeeModule/Edit.php"
    "app/Console/Commands/CheckUserDepartmentRequirements.php"
    "app/Console/Commands/FixUserDepartmentAssignments.php"
)

SYNTAX_OK=true
for file in "${SYNTAX_FILES[@]}"; do
    if php -l "$file" 2>&1 | grep -q "No syntax errors"; then
        echo -e "${GREEN}✓ $file - syntax OK${NC}"
    else
        echo -e "${RED}✗ $file - SYNTAX ERROR${NC}"
        php -l "$file"
        SYNTAX_OK=false
    fi
done
echo ""

# Summary
echo -e "${BLUE}=======================================${NC}"
if [ "$SYNTAX_OK" = true ]; then
    echo -e "${GREEN}✓ Implementation verification PASSED${NC}"
    echo -e "${GREEN}Ready for: php artisan migrate${NC}"
else
    echo -e "${RED}✗ Implementation verification FAILED${NC}"
    echo -e "${YELLOW}Fix syntax errors before proceeding${NC}"
fi
echo -e "${BLUE}=======================================${NC}"

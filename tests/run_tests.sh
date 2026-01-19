#!/bin/bash

# ===================================================
# RBAC Test Suite Runner
# ===================================================
# Tum RBAC unit testlerini calistirir.
#
# Kullanim:
#   ./tests/run_tests.sh          # Docker container icinde
#   php tests/run_tests.php       # Dogrudan PHP ile
# ===================================================

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║          NBT RBAC Test Suite                    ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
FAILED=0

# Test listesi
TESTS=(
    "AuthorizationServiceTest.php"
    "RoleRepositoryTest.php"
    "PermissionMiddlewareTest.php"
    "RedisCacheTest.php"
    "RbacIntegrationTest.php"
)

# Her testi calistir
for TEST in "${TESTS[@]}"; do
    echo "───────────────────────────────────────────────────"
    echo "Running: $TEST"
    echo "───────────────────────────────────────────────────"
    
    php "$SCRIPT_DIR/$TEST"
    
    if [ $? -ne 0 ]; then
        FAILED=$((FAILED + 1))
    fi
    
    echo ""
done

# Sonuc
echo "═══════════════════════════════════════════════════"
if [ $FAILED -eq 0 ]; then
    echo "✅ All tests passed!"
else
    echo "❌ $FAILED test suite(s) failed"
fi
echo "═══════════════════════════════════════════════════"

exit $FAILED

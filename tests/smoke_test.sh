#!/bin/bash
# ============================================================================
# NBT Project - Smoke Test Script
# Tüm API endpoint'lerini test eder
# 
# Kullanım: ./tests/smoke_test.sh [BASE_URL]
# Örnek: ./tests/smoke_test.sh http://localhost:8000
# ============================================================================

set -e

# Renk kodları
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Varsayılan ayarlar
BASE_URL="${1:-http://localhost:8000}"
COOKIE_FILE="/tmp/nbt_smoke_cookies.txt"
PASS_COUNT=0
FAIL_COUNT=0
SKIP_COUNT=0

# Temizlik
rm -f "$COOKIE_FILE"

echo "========================================"
echo " NBT Project Smoke Test"
echo " Base URL: $BASE_URL"
echo "========================================"
echo ""

# Test fonksiyonu
test_endpoint() {
    local METHOD="$1"
    local ENDPOINT="$2"
    local EXPECTED_STATUS="$3"
    local DESCRIPTION="$4"
    local AUTH_REQUIRED="${5:-false}"
    local DATA="${6:-}"
    
    local CURL_OPTS="-s -o /dev/null -w %{http_code}"
    
    if [ "$AUTH_REQUIRED" = "true" ]; then
        CURL_OPTS="$CURL_OPTS -b $COOKIE_FILE"
    fi
    
    if [ -n "$DATA" ]; then
        CURL_OPTS="$CURL_OPTS -H 'Content-Type: application/json' -d '$DATA'"
    fi
    
    local ACTUAL_STATUS
    if [ "$METHOD" = "GET" ]; then
        ACTUAL_STATUS=$(curl $CURL_OPTS "$BASE_URL$ENDPOINT" 2>/dev/null)
    else
        ACTUAL_STATUS=$(curl $CURL_OPTS -X "$METHOD" "$BASE_URL$ENDPOINT" 2>/dev/null)
    fi
    
    if [ "$ACTUAL_STATUS" = "$EXPECTED_STATUS" ]; then
        echo -e "${GREEN}✓${NC} [$METHOD] $ENDPOINT -> $ACTUAL_STATUS ($DESCRIPTION)"
        ((PASS_COUNT++))
    else
        echo -e "${RED}✗${NC} [$METHOD] $ENDPOINT -> $ACTUAL_STATUS (expected: $EXPECTED_STATUS) ($DESCRIPTION)"
        ((FAIL_COUNT++))
    fi
}

# Login testi (session alıp cookie'ye kaydet)
test_login() {
    echo "--- AUTH TESTS ---"
    
    # Login sayfası erişimi
    test_endpoint "GET" "/login" "200" "Login sayfası"
    
    # Login API (hatalı credentials)
    local LOGIN_RESPONSE=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" \
        -H "Content-Type: application/json" \
        -d '{"username":"test_invalid","password":"wrong"}' \
        "$BASE_URL/api/auth/login")
    
    if echo "$LOGIN_RESPONSE" | grep -q "error\|hatali\|invalid"; then
        echo -e "${GREEN}✓${NC} [POST] /api/auth/login (invalid) -> 401/error (Hatalı giriş reddedildi)"
        ((PASS_COUNT++))
    else
        echo -e "${YELLOW}?${NC} [POST] /api/auth/login (invalid) -> Beklenmedik yanıt"
        ((SKIP_COUNT++))
    fi
    
    # Login API (geçerli credentials - superadmin)
    local LOGIN_SUCCESS=$(curl -s -c "$COOKIE_FILE" -b "$COOKIE_FILE" \
        -H "Content-Type: application/json" \
        -d '{"username":"superadmin","password":"Super123!"}' \
        "$BASE_URL/api/auth/login")
    
    if echo "$LOGIN_SUCCESS" | grep -q "kullanici\|user\|token\|success"; then
        echo -e "${GREEN}✓${NC} [POST] /api/auth/login (valid) -> 200/success (Başarılı giriş)"
        ((PASS_COUNT++))
    else
        echo -e "${RED}✗${NC} [POST] /api/auth/login (valid) -> Giriş başarısız"
        ((FAIL_COUNT++))
        echo -e "${YELLOW}!${NC} Auth gerektiren testler atlanacak..."
        return 1
    fi
    
    echo ""
    return 0
}

# Public endpoint testleri (auth gerektirmeyen)
test_public_endpoints() {
    echo "--- PUBLIC ENDPOINTS ---"
    test_endpoint "GET" "/health" "200" "Health check"
    test_endpoint "GET" "/" "302" "Root redirect"
    test_endpoint "GET" "/404-test-nonexistent" "404" "404 sayfası"
    echo ""
}

# API endpoint testleri (auth gerektiren)
test_api_endpoints() {
    echo "--- API ENDPOINTS (AUTH REQUIRED) ---"
    
    # Dashboard
    test_endpoint "GET" "/api/dashboard" "200" "Dashboard API" true
    
    # Customers
    test_endpoint "GET" "/api/customers" "200" "Müşteri listesi" true
    
    # Projects
    test_endpoint "GET" "/api/projects" "200" "Proje listesi" true
    
    # Invoices
    test_endpoint "GET" "/api/invoices" "200" "Fatura listesi" true
    
    # Payments
    test_endpoint "GET" "/api/payments" "200" "Ödeme listesi" true
    
    # Contracts
    test_endpoint "GET" "/api/contracts" "200" "Sözleşme listesi" true
    
    # Offers
    test_endpoint "GET" "/api/offers" "200" "Teklif listesi" true
    
    # Guarantees
    test_endpoint "GET" "/api/guarantees" "200" "Teminat listesi" true
    
    # Meetings
    test_endpoint "GET" "/api/meetings" "200" "Görüşme listesi" true
    
    # Contacts
    test_endpoint "GET" "/api/contacts" "200" "Kişi listesi" true
    
    # Files
    test_endpoint "GET" "/api/files" "200" "Dosya listesi" true
    
    # Stamp Taxes
    test_endpoint "GET" "/api/stamp-taxes" "200" "Damga vergisi listesi" true
    
    # Calendar
    test_endpoint "GET" "/api/takvim" "200" "Takvim listesi" true
    
    # Alarms
    test_endpoint "GET" "/api/alarms" "200" "Alarm listesi" true
    
    # Users
    test_endpoint "GET" "/api/users" "200" "Kullanıcı listesi" true
    
    # Roles
    test_endpoint "GET" "/api/roles" "200" "Rol listesi" true
    
    # Parameters
    test_endpoint "GET" "/api/parameters" "200" "Parametre listesi" true
    
    # Cities
    test_endpoint "GET" "/api/cities" "200" "İl listesi" true
    
    # Districts
    test_endpoint "GET" "/api/districts" "200" "İlçe listesi" true
    
    # Logs
    test_endpoint "GET" "/api/logs" "200" "Log listesi" true
    
    echo ""
}

# Web sayfası testleri
test_web_pages() {
    echo "--- WEB PAGES ---"
    test_endpoint "GET" "/dashboard" "200" "Dashboard sayfası" true
    test_endpoint "GET" "/customers" "200" "Müşteriler sayfası" true
    test_endpoint "GET" "/users" "200" "Kullanıcılar sayfası" true
    test_endpoint "GET" "/roles" "200" "Roller sayfası" true
    test_endpoint "GET" "/parameters" "200" "Parametreler sayfası" true
    test_endpoint "GET" "/logs" "200" "Loglar sayfası" true
    test_endpoint "GET" "/my-account" "200" "Hesabım sayfası" true
    echo ""
}

# Logout testi
test_logout() {
    echo "--- LOGOUT TEST ---"
    local LOGOUT_RESPONSE=$(curl -s -b "$COOKIE_FILE" \
        -X POST "$BASE_URL/api/auth/logout")
    
    if echo "$LOGOUT_RESPONSE" | grep -q "success\|ok\|cikis"; then
        echo -e "${GREEN}✓${NC} [POST] /api/auth/logout -> Başarılı çıkış"
        ((PASS_COUNT++))
    else
        echo -e "${YELLOW}?${NC} [POST] /api/auth/logout -> Yanıt kontrol edilemedi"
        ((SKIP_COUNT++))
    fi
    echo ""
}

# Ana test akışı
main() {
    test_public_endpoints
    
    if test_login; then
        test_api_endpoints
        test_web_pages
        test_logout
    fi
    
    # Temizlik
    rm -f "$COOKIE_FILE"
    
    # Sonuç özeti
    echo "========================================"
    echo " TEST SONUÇLARI"
    echo "========================================"
    echo -e " ${GREEN}Başarılı:${NC} $PASS_COUNT"
    echo -e " ${RED}Başarısız:${NC} $FAIL_COUNT"
    echo -e " ${YELLOW}Atlandı:${NC} $SKIP_COUNT"
    echo "========================================"
    
    if [ "$FAIL_COUNT" -gt 0 ]; then
        echo -e "${RED}SMOKE TEST BAŞARISIZ${NC}"
        exit 1
    else
        echo -e "${GREEN}SMOKE TEST BAŞARILI${NC}"
        exit 0
    fi
}

main

#!/bin/bash

# Script to clean sensitive files from git cache
# Run before pushing to GitHub

echo "🧹 Cleaning sensitive files from git cache..."

# Remove sensitive files from git cache if they are tracked
echo "Removing sensitive configuration files..."
git rm --cached www/config_security.php 2>/dev/null || echo "  ✓ config_security.php not tracked"
git rm --cached www/.env 2>/dev/null || echo "  ✓ .env not tracked"
git rm --cached www/.env.local 2>/dev/null || echo "  ✓ .env.local not tracked"

echo "Removing SSL certificates..."
git rm --cached ssl/*.crt 2>/dev/null || echo "  ✓ SSL certificates not tracked"
git rm --cached ssl/*.key 2>/dev/null || echo "  ✓ SSL keys not tracked"
git rm --cached ssl/*.pem 2>/dev/null || echo "  ✓ PEM files not tracked"

echo "Removing sensitive test files..."
git rm --cached www/test_security.php 2>/dev/null || echo "  ✓ test_security.php not tracked"

echo "Removing logs..."
git rm --cached *.log 2>/dev/null || echo "  ✓ Logs not tracked"
git rm --cached logs/*.log 2>/dev/null || echo "  ✓ Logs in logs/ not tracked"

echo "Removing database files..."
git rm --cached *.sql 2>/dev/null || echo "  ✓ SQL files not tracked"
git rm --cached *.sqlite 2>/dev/null || echo "  ✓ SQLite files not tracked"

echo ""
echo "✅ Cleanup completed!"
echo ""
echo "📋 Files to verify before committing:"
echo "  - www/config_security.php (should be ignored)"
echo "  - www/.env (should be ignored)"
echo "  - ssl/ (should be ignored)"
echo "  - www/test_security.php (should be ignored)"
echo ""
echo "🔍 Check with: git status"
echo "📤 Then commit with: git add . && git commit -m 'your message'"

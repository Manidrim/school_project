#!/bin/bash

# Script pour corriger automatiquement les erreurs PHPStan les plus fréquentes

# Répertoire des tests
TEST_DIR="api/tests"

echo "🔧 Correction automatique des erreurs PHPStan..."

# 1. Corriger les json_decode sans validation
echo "📝 Correction des json_decode..."
find $TEST_DIR -name "*.php" -exec sed -i '' 's/\$response = \\json_decode(\$client->getResponse()->getContent(), true);/\$content = \$client->getResponse()->getContent();\
        self::assertNotFalse(\$content);\
        \$response = \\json_decode(\$content, true);\
        self::assertIsArray(\$response);/g' {} \;

# 2. Corriger les json_encode dans les requêtes
echo "📝 Correction des json_encode..."
find $TEST_DIR -name "*.php" -exec sed -i '' 's/], \\json_encode(\[/], \$this->encodeJson(\[/g' {} \;

# 3. Corriger les getResponseData() -> decodeJsonResponse()
echo "📝 Remplacement de getResponseData par decodeJsonResponse..."
find $TEST_DIR -name "*.php" -exec sed -i '' 's/\$this->getResponseData()/\$this->decodeJsonResponse()/g' {} \;

echo "✅ Corrections automatiques terminées !"
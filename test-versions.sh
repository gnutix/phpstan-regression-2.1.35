#!/bin/bash

set -e

VERSIONS=("2.1.33" "2.1.34" "2.1.35" "2.1.x-dev")
OUTPUT_DIR="results"

mkdir -p "$OUTPUT_DIR"

echo "=========================================="
echo "PHPStan Regression Test"
echo "=========================================="
echo ""

for VERSION in "${VERSIONS[@]}"; do
    echo "----------------------------------------"
    echo "Testing PHPStan $VERSION"
    echo "----------------------------------------"

    # Install specific version
    composer require --dev phpstan/phpstan:"$VERSION" --quiet 2>/dev/null || {
        echo "Failed to install PHPStan $VERSION"
        continue
    }

    # Get actual installed version
    ACTUAL_VERSION=$(composer show phpstan/phpstan | grep versions | awk '{print $NF}')
    echo "Installed: $ACTUAL_VERSION"

    # Run PHPStan and capture output
    OUTPUT_FILE="$OUTPUT_DIR/phpstan-$VERSION.txt"

    echo "Running analysis..."
    vendor/bin/phpstan analyse --no-progress 2>&1 | tee "$OUTPUT_FILE"

    echo ""
done

echo "=========================================="
echo "Summary"
echo "=========================================="
echo ""

for VERSION in "${VERSIONS[@]}"; do
    OUTPUT_FILE="$OUTPUT_DIR/phpstan-$VERSION.txt"
    if [[ -f "$OUTPUT_FILE" ]]; then
        ERROR_COUNT=$(grep -c "ERROR" "$OUTPUT_FILE" 2>/dev/null || echo "0")
        if grep -q "No errors" "$OUTPUT_FILE"; then
            echo "PHPStan $VERSION: ✅ No errors"
        else
            ERRORS=$(grep -oP '\[ERROR\] Found \K\d+' "$OUTPUT_FILE" 2>/dev/null || echo "?")
            echo "PHPStan $VERSION: ❌ $ERRORS errors"
        fi
    fi
done

echo ""
echo "Detailed results saved in $OUTPUT_DIR/"

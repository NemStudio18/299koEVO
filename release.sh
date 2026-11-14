#!/usr/bin/env bash
set -e

if [ -z "$1" ]; then
    echo "Usage: ./release.sh <version> (ex: ./release.sh 0.2.2)"
    exit 1
fi

VERSION="${1#v}"
TAG="v${VERSION}"

echo "🚀 Préparation de la release ${TAG}..."

sed -i -E "s/const VERSION = '[^']+';/const VERSION = '${VERSION}';/" common/config.php
echo "✅ common/config.php mis à jour vers VERSION = '${VERSION}'"

git add common/config.php
git commit -m "chore(release): bump VERSION to ${VERSION} in common/config.php"

git tag -a "${TAG}" -m "Release ${TAG}"
echo "✅ Tag ${TAG} créé"

git push origin main
git push origin "${TAG}"

echo "🎉 Release ${TAG} publiée sur GitHub !"


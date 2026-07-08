#!/usr/bin/env bash
#
# scan-property-bag-consumers.sh
#
# Finds every repo in the AgentSoftware org that consumes
# agentsoftware/laravel-property-bag (or the old zachleigh/laravel-property-bag,
# or the LaravelPropertyBag\ PHP namespace).
#
# It does NOT trust the GitHub code-search index alone (private-repo indexing can
# lag or miss). It enumerates every repo and reads composer.json / composer.lock
# directly via the API, then cross-checks with code search for namespace usage.
#
# Requirements: gh (authenticated: `gh auth login`), jq
# Usage:        ./scan-property-bag-consumers.sh [ORG]
#               ORG defaults to AgentSoftware
#
set -uo pipefail

ORG="${1:-AgentSoftware}"
PKG="laravel-property-bag"          # matches agentsoftware/ and zachleigh/ names
NS="LaravelPropertyBag"             # PHP namespace
OUT="property-bag-scan-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"

command -v gh >/dev/null || { echo "ERROR: gh not installed"; exit 1; }
command -v jq >/dev/null || { echo "ERROR: jq not installed"; exit 1; }
gh auth status >/dev/null 2>&1 || { echo "ERROR: run 'gh auth login' first"; exit 1; }

echo "== Scanning org: $ORG =="
echo "   package match: */$PKG   namespace match: $NS"
echo "   output dir:    $OUT"
echo

########################################################################
# 0. Fast first pass: GitHub code search (whole-org index).
#    Convenient but can miss un-indexed private repos, hence steps 1-2.
########################################################################
echo "== [0] GitHub code-search pass (indicative, not authoritative) =="
for q in \
  "$PKG org:$ORG filename:composer.json" \
  "$PKG org:$ORG filename:composer.lock" \
  "$NS org:$ORG" \
  "dev-ddd-namespace org:$ORG" ; do
  echo "--- gh search code '$q'"
  gh search code "$q" --limit 100 --json repository,path \
    --jq '.[] | "  " + .repository.nameWithOwner + " :: " + .path' 2>/dev/null \
    | sort -u | tee -a "$OUT/00-code-search.txt"
  echo
done

########################################################################
# 1. Enumerate EVERY repo in the org (authoritative list).
########################################################################
echo "== [1] Listing all repos in $ORG =="
gh repo list "$ORG" --limit 1000 --json name,isArchived,primaryLanguage,defaultBranchRef \
  > "$OUT/01-all-repos.json"
TOTAL=$(jq 'length' "$OUT/01-all-repos.json")
echo "   found $TOTAL repos"
echo

########################################################################
# 2. For each repo, read composer.json AND composer.lock from the default
#    branch via the contents API and grep for the package / namespace.
#    A direct requirer must name the package in its composer.json; the lock
#    catches transitive/resolved pins and records the exact branch+commit.
########################################################################
echo "== [2] Reading composer.json + composer.lock from each repo's default branch =="
HITS="$OUT/02-consumers.txt"
: > "$HITS"

# helper: fetch a file from a repo's default branch, base64-decode, or empty
fetch() { # $1=repo  $2=path
  gh api "repos/$ORG/$1/contents/$2" --jq '.content' 2>/dev/null \
    | tr -d '\n' | base64 --decode 2>/dev/null
}

jq -r '.[] | .name + "\t" + ((.defaultBranchRef.name)//"HEAD") + "\t" + (if .isArchived then "archived" else "active" end)' \
  "$OUT/01-all-repos.json" \
| while IFS=$'\t' read -r repo branch state; do
    found=""
    cj="$(fetch "$repo" composer.json)"
    if grep -Eq "$PKG|$NS" <<<"$cj"; then
      found="composer.json"
      {
        echo "### $ORG/$repo  (branch: $branch, $state)  [composer.json]"
        grep -nE "$PKG|$NS" <<<"$cj" | sed 's/^/    /'
        # show the repositories{} VCS block too, if present
        echo "$cj" | jq -r '
          if (.repositories!=null) then
            (.repositories | if type=="array" then .[] else to_entries[].value end)
            | select((.url//""|test("'"$PKG"'")) or ((.name//"")|test("'"$PKG"'")))
            | "    repo-source: " + (.type//"?") + " " + (.url//.name//"?")
          else empty end' 2>/dev/null
      } >> "$HITS"
    fi

    cl="$(fetch "$repo" composer.lock)"
    if grep -Eq "\"$ORG/$PKG\"|zachleigh/$PKG" <<<"$cl"; then
      found="${found:+$found+}composer.lock"
      # pull the locked stanza: name, version, source.reference (commit), source.url
      echo "$cl" | jq -r --arg pkg "$PKG" '
        ((.packages // []) + (."packages-dev" // []))[]
        | select(.name|test($pkg))
        | "    LOCKED: " + .name + " @ " + (.version//"?")
          + "  ref=" + ((.source.reference)//(.dist.reference)//"?")
          + "  url=" + ((.source.url)//"?")' 2>/dev/null >> "$HITS"
    fi

    if [ -n "$found" ]; then
      echo "  HIT  $repo  ($found)"
      echo "" >> "$HITS"
    fi
  done

echo
echo "== DONE =="
echo "Consumers (composer.json / composer.lock):  $HITS"
echo "Code-search cross-check:                     $OUT/00-code-search.txt"
echo
echo "----- consumer summary -----"
if [ -s "$HITS" ]; then cat "$HITS"; else echo "(no direct consumers found in any repo's composer manifests)"; fi

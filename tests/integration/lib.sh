#!/usr/bin/env bash
#
# Shell helpers for the Panopticon Connector integration test harness.
#
# Sourced by run-tests.sh. Ported (mostly verbatim) from
# ~/Projects/j5-akeeba/admintools/tests/integration/docker/run.sh, trimmed to
# what this repository's simpler single-`web`-container stack needs.
#
# Functions provided:
#   log/ok/warn/die              - pretty logging
#   version_lt A B                - dotted-numeric version comparison
#   acquire_joomla WANT             - buildfiles' joomla_try_acquire; sets
#                                     JOOMLA_ZIP and JOOMLA_RESOLVED
#   extract_joomla                  - unzip the acquired package into www/
#   install_joomla                  - run the Joomla CLI installer inside the
#                                     `web` container
#
# Requires the caller to have already defined: INBOX_DIR, WWW_DIR, DC (a
# function wrapping `docker compose`), ADMIN_USER, ADMIN_PASSWORD,
# ADMIN_EMAIL, SITE_NAME, DB_USER, DB_PASSWORD, DB_NAME, DB_PREFIX.

# ---------------------------------------------------------------------------
# Pretty logging
# ---------------------------------------------------------------------------
if [ -t 1 ]; then C_B='\033[0;34m'; C_G='\033[0;32m'; C_R='\033[0;31m'; C_Y='\033[0;33m'; C_0='\033[0m'; else C_B=; C_G=; C_R=; C_Y=; C_0=; fi
log()  { printf "${C_B}==>${C_0} %s\n" "$*"; }
ok()   { printf "${C_G}  ok${C_0} %s\n" "$*"; }
warn() { printf "${C_Y}  !!${C_0} %s\n" "$*"; }
die()  { printf "${C_R}ERROR:${C_0} %s\n" "$*" >&2; exit 1; }

# True (0) when $1 is strictly lower than $2, comparing dotted numeric versions.
version_lt() {
	[ "$1" = "$2" ] && return 1
	[ "$(printf '%s\n%s\n' "$1" "$2" | sort -V | head -1)" = "$1" ]
}

JOOMLA_ZIP=""
JOOMLA_RESOLVED=""

# Acquire a Joomla package for the given (possibly partial) version: look up the
# release, then download it unless inbox/ already has it. Sets
# JOOMLA_ZIP and JOOMLA_RESOLVED. Returns non-zero (without dying) if the
# requested major has no resolvable stable release, so callers can skip it.
acquire_joomla() {
	local want="$1"
	log "Acquiring Joomla ${want}"

	local library="${REPO_ROOT}/../buildfiles/docker/e2e/joomla/acquire.sh"
	[ -f "${library}" ] || die "${library} not found. Update the akeeba/buildfiles checkout next to this repository."
	# shellcheck source=/dev/null
	. "${library}"
	joomla_try_acquire "${want}" "${INBOX_DIR}" || { warn "${JOOMLA_ACQUIRE_ERROR}"; return 1; }
}

extract_joomla() {
	log "Extracting Joomla ${JOOMLA_RESOLVED} into web root"
	command -v unzip >/dev/null 2>&1 || die "unzip is required to extract the Joomla package."
	unzip -q -o "${JOOMLA_ZIP}" -d "${WWW_DIR}"
	ok "Joomla ${JOOMLA_RESOLVED} extracted"
}

# Run the Joomla CLI installer inside the `web` container, then remove the
# installation/ directory (Joomla refuses to run while it's present).
install_joomla() {
	log "Installing Joomla ${JOOMLA_RESOLVED} via installation/joomla.php CLI"

	local -a opts=(
		installation/joomla.php install
		--site-name "${SITE_NAME}"
		--admin-user "${ADMIN_NAME}"
		--admin-username "${ADMIN_USER}"
		--admin-password "${ADMIN_PASSWORD}"
		--admin-email "${ADMIN_EMAIL}"
		--db-type mysqli
		--db-host db
		--db-user "${DB_USER}"
		--db-pass "${DB_PASSWORD}"
		--db-name "${DB_NAME}"
		--db-prefix "${DB_PREFIX}"
		--db-encryption 0
	)

	# The --public-folder option only exists from Joomla 5.0 onwards; passing it to
	# a Joomla 4.x installer is an error.
	if ! version_lt "${JOOMLA_RESOLVED}" "5.0.0"; then
		opts+=(--public-folder "")
	fi

	DC exec -T web php "${opts[@]}" || die "Joomla CLI installation failed."

	rm -rf "${WWW_DIR}/installation"
	ok "Joomla ${JOOMLA_RESOLVED} installed"
}

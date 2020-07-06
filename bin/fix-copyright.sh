#!/usr/bin/env zsh

ROOT=${PWD}
GREP_PATTERN="(Copyright )?\(C\) .* Open Source Matters.*All rights reserved\.?"
SED_PATTERN="\(Copyright \)\?(C) .* Open Source Matters.*All rights reserved\.\?"
OWNER="Open Source Matters, Inc."
CONTACT="https://www.joomla.org"

main() {
  echo "Processing files in ${ROOT}"

  COUNT=0

  for FILE in $(grep -rilP "${GREP_PATTERN}" --exclude-dir="vendor" "${ROOT}"); do
    YEAR=$(php fix-copyright.php ${FILE})

    if [[ ${FILE} == *.xml ]]; then
      REPLACEMENT="(C) ${YEAR:-2005} ${OWNER}"
    else
      REPLACEMENT="(C) ${YEAR:-2005} ${OWNER} <${CONTACT}>"
    fi

    COUNT=$((COUNT + 1))

    printf '%5.5s| %7.7s => %s\n' "${COUNT}" "${YEAR:-default}" "${FILE}"

    sed -i -e "s|${SED_PATTERN}|${REPLACEMENT}|" "${FILE}"

  done

  echo "Fixed ${COUNT} copyright notices."
}

main

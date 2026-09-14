"use strict"

const {
  diffAgainstSource,
  flattenToDotPaths,
  listLocaleFiles,
  readLocaleFile,
  sortObjectDeep,
  SOURCE_LOCALE,
} = require("./lib.js")

function checkLocaleFiles() {
  const sourceObject = readLocaleFile(SOURCE_LOCALE)
  const sourceFlattened = flattenToDotPaths(sourceObject)
  const failures = []

  for (const fileName of listLocaleFiles()) {
    const object = readLocaleFile(fileName)
    const isSorted =
      JSON.stringify(object) === JSON.stringify(sortObjectDeep(object))
    const { missingKeys, extraKeys } =
      fileName === SOURCE_LOCALE
        ? { missingKeys: [], extraKeys: [] }
        : diffAgainstSource(sourceFlattened, flattenToDotPaths(object))

    if (!isSorted || missingKeys.length > 0 || extraKeys.length > 0) {
      failures.push({ fileName, isSorted, missingKeys, extraKeys })
    }
  }

  return failures
}

function printFailure(failure) {
  console.log(`${failure.fileName}:`)

  if (!failure.isSorted) {
    console.log("  keys are not sorted (run `npm run i18n:fix-json`)")
  }

  if (failure.missingKeys.length > 0) {
    console.log(`  missing keys (present in ${SOURCE_LOCALE}, absent here):`)
    for (const dotPath of failure.missingKeys) {
      console.log(`    - ${dotPath}`)
    }
  }

  if (failure.extraKeys.length > 0) {
    console.log(`  extra keys (present here, absent in ${SOURCE_LOCALE}):`)
    for (const dotPath of failure.extraKeys) {
      console.log(`    - ${dotPath}`)
    }
  }
}

const failures = checkLocaleFiles()

if (failures.length > 0) {
  failures.forEach(printFailure)
  console.error(`\n${failures.length} locale file(s) failed validation.`)
  process.exit(1)
}

console.log("All locale JSON files are sorted and key-complete.")

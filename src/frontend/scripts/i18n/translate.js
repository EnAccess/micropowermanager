"use strict"

const {
  diffAgainstSource,
  flattenToDotPaths,
  listLocaleFiles,
  readLocaleFile,
  setValueAtDotPath,
  SOURCE_LOCALE,
  writeLocaleFile,
} = require("./lib.js")

// The locale filename convention doesn't always match the ISO 639-1 code Google Translate expects.
const LOCALE_FILE_TO_ISO_CODE = {
  bu: "my",
}

const GOOGLE_TRANSLATE_API_URL =
  "https://translation.googleapis.com/language/translate/v2"
const GOOGLE_TRANSLATE_API_KEY = process.env.GOOGLE_TRANSLATE_API_KEY || ""
const DRY_RUN =
  process.argv.includes("--dry-run") || process.env.TRANSLATE_DRY_RUN === "1"

// Translate in small parallel batches instead of one request at a time — a new locale
// can have 700+ missing keys, and sequential round-trips make that feel stuck.
const TRANSLATION_BATCH_SIZE = 10

function isoCodeForLocaleFile(fileName) {
  const stem = fileName.replace(/\.json$/, "")
  return LOCALE_FILE_TO_ISO_CODE[stem] || stem
}

function targetFileNames() {
  const requestedLocale =
    process.argv[2] && !process.argv[2].startsWith("--")
      ? process.argv[2]
      : null
  const allTargets = listLocaleFiles().filter(
    (fileName) => fileName !== SOURCE_LOCALE,
  )

  if (!requestedLocale) {
    return allTargets
  }

  const fileName = requestedLocale.endsWith(".json")
    ? requestedLocale
    : `${requestedLocale}.json`
  if (!allTargets.includes(fileName)) {
    throw new Error(
      `Unknown locale "${requestedLocale}". Available: ${allTargets.join(", ")}`,
    )
  }
  return [fileName]
}

async function fetchSupportedLanguageCodes() {
  try {
    const response = await fetch(
      `${GOOGLE_TRANSLATE_API_URL}/languages?key=${GOOGLE_TRANSLATE_API_KEY}`,
    )
    if (!response.ok) {
      return null
    }
    const body = await response.json()
    return new Set(body.data.languages.map((language) => language.language))
  } catch (error) {
    return null
  }
}

async function translateText(text, targetIsoCode) {
  const response = await fetch(
    `${GOOGLE_TRANSLATE_API_URL}?key=${GOOGLE_TRANSLATE_API_KEY}`,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        q: text,
        source: "en",
        target: targetIsoCode,
        format: "text",
      }),
    },
  )

  if (!response.ok) {
    throw new Error(`request failed with status ${response.status}`)
  }

  const body = await response.json()
  const translatedText = body?.data?.translations?.[0]?.translatedText
  if (typeof translatedText !== "string") {
    throw new Error("response did not include a translation")
  }
  return translatedText
}

async function translateLocaleFile(
  fileName,
  sourceFlattened,
  supportedLanguageCodes,
) {
  const targetIsoCode = isoCodeForLocaleFile(fileName)
  const object = readLocaleFile(fileName)
  const { missingKeys } = diffAgainstSource(
    sourceFlattened,
    flattenToDotPaths(object),
  )

  if (missingKeys.length === 0) {
    console.log(`${fileName}: no missing keys`)
    return
  }

  console.log(
    `${fileName}: ${missingKeys.length} missing key(s), target language "${targetIsoCode}"`,
  )

  if (DRY_RUN) {
    missingKeys.forEach((dotPath) => console.log(`  - ${dotPath}`))
    return
  }

  if (supportedLanguageCodes && !supportedLanguageCodes.has(targetIsoCode)) {
    console.warn(
      `  skipping: Google Translate does not support target language "${targetIsoCode}"`,
    )
    return
  }

  let translatedCount = 0
  for (
    let batchStart = 0;
    batchStart < missingKeys.length;
    batchStart += TRANSLATION_BATCH_SIZE
  ) {
    const batch = missingKeys.slice(
      batchStart,
      batchStart + TRANSLATION_BATCH_SIZE,
    )

    await Promise.all(
      batch.map(async (dotPath) => {
        try {
          const translatedText = await translateText(
            sourceFlattened.get(dotPath),
            targetIsoCode,
          )
          setValueAtDotPath(object, dotPath, translatedText)
          translatedCount += 1
        } catch (error) {
          console.warn(`  skipping "${dotPath}": ${error.message}`)
        }
      }),
    )

    // Persist after every batch so an interrupted run keeps whatever finished —
    // a re-run only has to translate what's still missing.
    writeLocaleFile(fileName, object)
    console.log(
      `  ${fileName}: ${Math.min(batchStart + TRANSLATION_BATCH_SIZE, missingKeys.length)}/${missingKeys.length} processed`,
    )
  }

  console.log(
    `${fileName}: translated ${translatedCount}/${missingKeys.length} missing key(s)`,
  )
}

async function main() {
  if (!DRY_RUN && !GOOGLE_TRANSLATE_API_KEY) {
    throw new Error(
      "GOOGLE_TRANSLATE_API_KEY is required (or pass --dry-run to preview without translating).",
    )
  }

  const sourceFlattened = flattenToDotPaths(readLocaleFile(SOURCE_LOCALE))
  const supportedLanguageCodes = DRY_RUN
    ? null
    : await fetchSupportedLanguageCodes()

  for (const fileName of targetFileNames()) {
    await translateLocaleFile(fileName, sourceFlattened, supportedLanguageCodes)
  }
}

main().catch((error) => {
  console.error(error.message)
  process.exit(1)
})

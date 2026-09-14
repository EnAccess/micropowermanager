"use strict"

const fs = require("fs")
const path = require("path")

const LOCALES_DIRECTORY = path.join(
  __dirname,
  "..",
  "..",
  "src",
  "assets",
  "locales",
)
const SOURCE_LOCALE = "en.json"

function isPlainObject(value) {
  return typeof value === "object" && value !== null && !Array.isArray(value)
}

function listLocaleFiles() {
  return fs
    .readdirSync(LOCALES_DIRECTORY)
    .filter((fileName) => fileName.endsWith(".json"))
    .sort()
}

function readLocaleFile(fileName) {
  const filePath = path.join(LOCALES_DIRECTORY, fileName)
  const rawContent = fs.readFileSync(filePath, "utf8")
  try {
    return JSON.parse(rawContent)
  } catch (error) {
    throw new Error(`Failed to parse ${fileName}: ${error.message}`)
  }
}

function flattenToDotPaths(object, prefix = "") {
  const flattened = new Map()
  for (const [key, value] of Object.entries(object)) {
    const dotPath = prefix ? `${prefix}.${key}` : key
    if (isPlainObject(value)) {
      for (const [childPath, childValue] of flattenToDotPaths(value, dotPath)) {
        flattened.set(childPath, childValue)
      }
    } else {
      flattened.set(dotPath, value)
    }
  }
  return flattened
}

// Matches `jq -S`'s sort order, which the existing locale files were sorted with.
function sortObjectDeep(object) {
  if (!isPlainObject(object)) {
    return object
  }
  const sorted = {}
  for (const key of Object.keys(object).sort()) {
    sorted[key] = sortObjectDeep(object[key])
  }
  return sorted
}

function diffAgainstSource(sourceFlattened, targetFlattened) {
  const missingKeys = []
  const extraKeys = []

  for (const dotPath of sourceFlattened.keys()) {
    if (!targetFlattened.has(dotPath)) {
      missingKeys.push(dotPath)
    }
  }

  for (const dotPath of targetFlattened.keys()) {
    if (!sourceFlattened.has(dotPath)) {
      extraKeys.push(dotPath)
    }
  }

  return { missingKeys: missingKeys.sort(), extraKeys: extraKeys.sort() }
}

function setValueAtDotPath(object, dotPath, value) {
  const keys = dotPath.split(".")
  let cursor = object

  for (let index = 0; index < keys.length - 1; index += 1) {
    const key = keys[index]
    if (!isPlainObject(cursor[key])) {
      cursor[key] = {}
    }
    cursor = cursor[key]
  }

  cursor[keys[keys.length - 1]] = value
}

function formatJsonForFile(object) {
  return `${JSON.stringify(object, null, 2)}\n`
}

function writeLocaleFile(fileName, object) {
  const filePath = path.join(LOCALES_DIRECTORY, fileName)
  fs.writeFileSync(filePath, formatJsonForFile(sortObjectDeep(object)))
}

module.exports = {
  diffAgainstSource,
  flattenToDotPaths,
  formatJsonForFile,
  listLocaleFiles,
  LOCALES_DIRECTORY,
  readLocaleFile,
  setValueAtDotPath,
  sortObjectDeep,
  SOURCE_LOCALE,
  writeLocaleFile,
}

"use strict"

const { Encoder, TokenTypes } = require("openpaygo")

/**
 * Transport handlers may expose input errors, while internal failures can contain
 * device keys. A distinct type keeps those internal details private.
 */
class InputValidationError extends TypeError {}

const allowedFields = new Set([
  "secretKeyHex",
  "startingCode",
  "counter",
  "tokenType",
  "value",
  "restrictedDigitSet",
])
const operations = new Map([
  ["ADD_TIME", TokenTypes.ADD_TIME],
  ["SET_TIME", TokenTypes.SET_TIME],
  ["DISABLE_PAYG", TokenTypes.DISABLE_PAYG],
])

// The reference encoder rebuilds the hash chain from the start on each call.
// This workload limit applies to the input counter, not the protocol itself.
const maximumCounter = 100000

function requireInteger(value, field, maximum) {
  if (!Number.isSafeInteger(value) || value < 0 || value > maximum) {
    throw new InputValidationError(`${field} must be an integer between 0 and ${maximum}.`)
  }
}

function generateToken(input) {
  if (input === null || typeof input !== "object" || Array.isArray(input)) {
    throw new InputValidationError("Input must be a JSON object.")
  }

  if (Object.keys(input).some((field) => !allowedFields.has(field))) {
    throw new InputValidationError("Input contains an unsupported field.")
  }

  const {
    secretKeyHex,
    startingCode,
    counter,
    tokenType = "ADD_TIME",
    value,
    restrictedDigitSet = false,
  } = input

  if (typeof secretKeyHex !== "string" || !/^[a-fA-F0-9]{32}$/.test(secretKeyHex)) {
    throw new InputValidationError("secretKeyHex must contain exactly 32 hexadecimal characters.")
  }

  requireInteger(startingCode, "startingCode", 999999999)
  requireInteger(counter, "counter", maximumCounter)

  if (!operations.has(tokenType)) {
    throw new InputValidationError("tokenType must be ADD_TIME, SET_TIME, or DISABLE_PAYG.")
  }

  if (typeof restrictedDigitSet !== "boolean") {
    throw new InputValidationError("restrictedDigitSet must be a boolean.")
  }

  if (tokenType === "DISABLE_PAYG") {
    if (value !== undefined) {
      throw new InputValidationError("DISABLE_PAYG must not include a value.")
    }
  } else {
    requireInteger(value, "value", 995)
  }

  const { finalToken, newCount } = new Encoder().generateToken({
    secretKeyHex,
    startingCode,
    count: counter,
    tokenType: operations.get(tokenType),
    value,
    restrictDigitSet: restrictedDigitSet,
    extendToken: false,
  })

  return { token: finalToken, nextCounter: newCount }
}

module.exports = { generateToken, InputValidationError }

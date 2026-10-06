"use strict"

const assert = require("node:assert/strict")
const { spawnSync } = require("node:child_process")
const path = require("node:path")
const { test } = require("node:test")

const vectors = require("openpaygo/test/sample_tokens.json")
const { generateToken } = require("../index.js")

const request = {
  secretKeyHex: "bc41ec9530f6dac86b1a29ab82edc5fb",
  startingCode: 516959010,
  counter: 1,
  tokenType: "ADD_TIME",
  value: 1,
}

for (const vector of vectors.filter(
  (vector) =>
    !vector.extended_token &&
    ["ADD_TIME", "SET_TIME", "DISABLE_PAYG"].includes(vector.token_type),
)) {
  test(`matches published vector ${vector.token}`, () => {
    const input = {
      secretKeyHex: vector.key,
      startingCode: vector.starting_code,
      counter: vector.count,
      tokenType: vector.token_type,
      restrictedDigitSet: vector.restricted_digit_set,
    }
    if (vector.token_type !== "DISABLE_PAYG") {
      input.value = vector.value_raw
    }

    assert.deepEqual(generateToken(input), {
      token: vector.token,
      nextCounter: vector.new_count,
    })
  })
}

test("successive credit tokens use the returned counter without mutating input", () => {
  const original = structuredClone(request)
  const first = generateToken(request)
  const second = generateToken({ ...request, counter: first.nextCounter })

  assert.deepEqual(request, original)
  assert.equal(first.nextCounter, 2)
  assert.equal(second.nextCounter, 4)
  assert.notEqual(first.token, second.token)
  assert.deepEqual(generateToken(request), first)
})

test("different device keys produce different tokens", () => {
  const otherDevice = { ...request, secretKeyHex: "ac41ec9530f6dac86b1a29ab82edc5fb" }
  assert.notEqual(generateToken(request).token, generateToken(otherDevice).token)
})

for (const value of [-1, 996, 1.5, NaN, Infinity, "1", null, undefined]) {
  test(`rejects invalid credit value ${String(value)}`, () => {
    assert.throws(() => generateToken({ ...request, value }), /value must be an integer/)
  })
}

for (const counter of [-1, 100001, 1.5, NaN, Infinity, "1", null, undefined]) {
  test(`rejects invalid counter ${String(counter)}`, () => {
    assert.throws(() => generateToken({ ...request, counter }), /counter must be an integer/)
  })
}

test("rejects malformed configuration and unsupported operations", () => {
  for (const input of [null, [], "input"]) {
    assert.throws(() => generateToken(input), /Input must be a JSON object/)
  }
  for (const secretKeyHex of ["", "1234", "z".repeat(32), null]) {
    assert.throws(() => generateToken({ ...request, secretKeyHex }), /secretKeyHex must/)
  }
  for (const startingCode of [-1, 1000000000, 1.5, "516959010", null, undefined]) {
    assert.throws(() => generateToken({ ...request, startingCode }), /startingCode must/)
  }
  assert.throws(() => generateToken({ ...request, tokenType: "COUNTER_SYNC" }), /tokenType must/)
  assert.throws(() => generateToken({ ...request, restrictedDigitSet: "false" }), /must be a boolean/)
  assert.throws(() => generateToken({ ...request, extendToken: true }), /unsupported field/)
  assert.throws(() => generateToken({ ...request, tokenType: "DISABLE_PAYG" }), /must not include a value/)
})

test("CLI prints only its JSON result on stdout", () => {
  const result = runCli(JSON.stringify(request))
  assert.equal(result.status, 0)
  assert.equal(result.stderr, "")
  assert.deepEqual(JSON.parse(result.stdout), { token: "588224011", nextCounter: 2 })
})

test("CLI rejects bad input without exposing the supplied secret", () => {
  for (const input of ["{", JSON.stringify({ ...request, value: -1 }), " ".repeat(4097)]) {
    const result = runCli(input)
    assert.equal(result.status, 1)
    assert.equal(result.stdout, "")
    assert.equal(typeof JSON.parse(result.stderr).error, "string")
    assert.ok(!result.stderr.includes(request.secretKeyHex))
  }
})

function runCli(input) {
  return spawnSync(process.execPath, [path.join(__dirname, "../cli.js")], {
    input,
    encoding: "utf8",
    timeout: 5000,
  })
}

"use strict"

const assert = require("node:assert/strict")
const { once } = require("node:events")
const { request: httpRequest } = require("node:http")
const { test } = require("node:test")

const { createServer } = require("../server.js")

const apiKey = "test-only-openpaygo-service-key-123456789"
const tokenRequest = {
  secretKeyHex: "bc41ec9530f6dac86b1a29ab82edc5fb",
  startingCode: 516959010,
  counter: 1,
  tokenType: "ADD_TIME",
  value: 1,
}

async function startServer(context, generate) {
  const server = createServer({ apiKey, generate })
  server.listen(0, "127.0.0.1")
  await once(server, "listening")
  context.after(
    () =>
      new Promise((resolve) => {
        server.close(resolve)
        server.closeAllConnections()
      }),
  )
  return `http://127.0.0.1:${server.address().port}`
}

function post(url, body = tokenRequest, headers = {}) {
  return fetch(`${url}/generate`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
      ...headers,
    },
    body: JSON.stringify(body),
  })
}

test("health responds without authentication", async (context) => {
  const url = await startServer(context)
  const response = await fetch(`${url}/health`)
  assert.equal(response.status, 200)
  assert.deepEqual(await response.json(), { status: "ok" })
})

test("authenticated HTTP requests generate reference tokens and successive counters", async (context) => {
  const url = await startServer(context)
  const first = await post(url)
  assert.equal(first.status, 200)
  assert.equal(first.headers.get("cache-control"), "no-store")
  const firstToken = await first.json()
  assert.deepEqual(firstToken, { token: "588224011", nextCounter: 2 })

  const second = await post(url, { ...tokenRequest, counter: firstToken.nextCounter })
  const secondToken = await second.json()
  assert.equal(second.status, 200)
  assert.equal(secondToken.nextCounter, 4)
  assert.notEqual(secondToken.token, firstToken.token)

  const repeated = await post(url)
  assert.deepEqual(await repeated.json(), firstToken)
})

test("missing, wrong, and malformed authentication is rejected without echoing keys", async (context) => {
  const url = await startServer(context)
  for (const authorization of ["", `Bearer ${"x".repeat(apiKey.length)}`, apiKey]) {
    const response = await post(url, tokenRequest, { Authorization: authorization })
    assert.equal(response.status, 401)
    assert.deepEqual(await response.json(), { error: "Unauthorized." })
  }
})

test("JSON charset and case-insensitive Bearer scheme are accepted", async (context) => {
  const url = await startServer(context)
  const response = await post(url, tokenRequest, {
    Authorization: `bearer ${apiKey}`,
    "Content-Type": "application/json; charset=utf-8",
  })
  assert.equal(response.status, 200)
})

test("unsupported media types and compressed bodies are rejected", async (context) => {
  const url = await startServer(context)
  for (const headers of [
    { "Content-Type": "" },
    { "Content-Type": "text/plain" },
    { "Content-Encoding": "gzip" },
  ]) {
    const response = await post(url, tokenRequest, headers)
    assert.equal(response.status, 415)
    assert.deepEqual(await response.json(), { error: "Use uncompressed application/json." })
  }
})

test("malformed JSON is a bad request", async (context) => {
  const url = await startServer(context)
  const response = await fetch(`${url}/generate`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: "{",
  })
  assert.equal(response.status, 400)
  assert.deepEqual(await response.json(), { error: "Input must be valid JSON." })
})

test("invalid token inputs return actionable validation errors", async (context) => {
  const url = await startServer(context)
  for (const body of [null, [], { ...tokenRequest, value: -1 }, { ...tokenRequest, extra: true }]) {
    const response = await post(url, body)
    assert.equal(response.status, 422)
    const error = await response.json()
    assert.equal(typeof error.error, "string")
    assert.ok(!error.error.includes(tokenRequest.secretKeyHex))
  }
})

test("oversized declared bodies are rejected", async (context) => {
  const url = await startServer(context)
  const response = await post(url, { ...tokenRequest, extra: "x".repeat(4096) })
  assert.equal(response.status, 413)
})

test("oversized chunked bodies are rejected after draining without retaining them", async (context) => {
  const url = await startServer(context)
  const result = await new Promise((resolve, reject) => {
    const request = httpRequest(
      `${url}/generate`,
      {
        method: "POST",
        headers: {
          Authorization: `Bearer ${apiKey}`,
          "Content-Type": "application/json",
        },
      },
      (response) => {
        let body = ""
        response.on("data", (chunk) => { body += chunk.toString() })
        response.on("end", () => resolve({ status: response.statusCode, body }))
      },
    )
    request.on("error", reject)
    request.write(" ".repeat(2048))
    request.write(" ".repeat(2048))
    request.end(" ")
  })
  assert.equal(result.status, 413)
  assert.deepEqual(JSON.parse(result.body), { error: "Input must not exceed 4096 bytes." })
})

test("unknown routes and unsupported methods are explicit", async (context) => {
  const url = await startServer(context)
  const missing = await fetch(`${url}/missing`)
  assert.equal(missing.status, 404)
  const wrongMethod = await fetch(`${url}/generate`)
  assert.equal(wrongMethod.status, 405)
  assert.equal(wrongMethod.headers.get("allow"), "POST")
})

test("unexpected failures do not expose device secrets or internal details", async (context) => {
  const url = await startServer(context, () => {
    throw new Error(`internal failure containing ${tokenRequest.secretKeyHex}`)
  })
  const response = await post(url)
  assert.equal(response.status, 500)
  assert.deepEqual(await response.json(), { error: "Token generation failed." })
})

test("missing or weak service keys fail before opening a listener", () => {
  for (const key of [undefined, "", "short", "x".repeat(257), " ".repeat(32)]) {
    assert.throws(() => createServer({ apiKey: key }), /OPENPAYGO_GENERATOR_API_KEY/)
  }
})

test("internal TypeErrors are not mistaken for public validation errors", async (context) => {
  const url = await startServer(context, () => {
    throw new TypeError(`internal failure containing ${tokenRequest.secretKeyHex}`)
  })
  const response = await post(url)
  assert.equal(response.status, 500)
  assert.deepEqual(await response.json(), { error: "Token generation failed." })
})

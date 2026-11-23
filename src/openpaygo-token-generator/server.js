"use strict"

const { timingSafeEqual } = require("node:crypto")
const { createServer: createHttpServer } = require("node:http")

const { generateToken, InputValidationError } = require("./index.js")

class HttpError extends Error {
  constructor(status, message) {
    super(message)
    this.status = status
  }
}

function sendJson(response, status, body) {
  response.writeHead(status, {
    "Content-Type": "application/json; charset=utf-8",
    "Cache-Control": "no-store",
  })
  response.end(JSON.stringify(body))
}

async function readJson(request) {
  const maximumBytes = 4096
  let receivedBytes = 0
  const chunks = []

  for await (const chunk of request) {
    receivedBytes += chunk.length
    if (receivedBytes <= maximumBytes) {
      chunks.push(chunk)
    }
  }

  if (receivedBytes > maximumBytes) {
    throw new HttpError(413, "Input must not exceed 4096 bytes.")
  }

  try {
    return JSON.parse(Buffer.concat(chunks).toString("utf8"))
  } catch {
    throw new HttpError(400, "Input must be valid JSON.")
  }
}

function createServer({ apiKey, generate = generateToken }) {
  if (typeof apiKey !== "string" || !/^[A-Za-z0-9_-]{32,256}$/.test(apiKey)) {
    throw new TypeError(
      "OPENPAYGO_GENERATOR_API_KEY must contain 32-256 letters, digits, hyphens, or underscores.",
    )
  }
  const expectedKey = Buffer.from(apiKey)

  return createHttpServer(
    { requestTimeout: 10000, headersTimeout: 5000 },
    async (request, response) => {
      if (request.url === "/health" && request.method === "GET") {
        request.resume()
        sendJson(response, 200, { status: "ok" })
        return
      }

      if (request.url !== "/generate") {
        request.resume()
        sendJson(response, 404, { error: "Route not found." })
        return
      }

      if (request.method !== "POST") {
        request.resume()
        response.setHeader("Allow", "POST")
        sendJson(response, 405, { error: "Use POST /generate." })
        return
      }

      const authorization = request.headers.authorization || ""
      const match = /^Bearer ([A-Za-z0-9_-]{32,256})$/i.exec(authorization)
      const suppliedKey = Buffer.from(match ? match[1] : "")
      if (
        suppliedKey.length !== expectedKey.length ||
        !timingSafeEqual(suppliedKey, expectedKey)
      ) {
        request.resume()
        sendJson(response, 401, { error: "Unauthorized." })
        return
      }

      const contentType = (request.headers["content-type"] || "")
        .split(";")[0]
        .trim()
        .toLowerCase()
      if (
        contentType !== "application/json" ||
        (request.headers["content-encoding"] &&
          request.headers["content-encoding"] !== "identity")
      ) {
        request.resume()
        sendJson(response, 415, { error: "Use uncompressed application/json." })
        return
      }

      if (Number(request.headers["content-length"]) > 4096) {
        request.resume()
        sendJson(response, 413, { error: "Input must not exceed 4096 bytes." })
        return
      }

      try {
        const input = await readJson(request)
        sendJson(response, 200, generate(input))
      } catch (error) {
        if (response.destroyed) {
          return
        }
        if (error instanceof HttpError) {
          sendJson(response, error.status, { error: error.message })
          return
        }
        if (error instanceof InputValidationError) {
          sendJson(response, 422, { error: error.message })
          return
        }
        sendJson(response, 500, { error: "Token generation failed." })
      }
    },
  )
}

if (require.main === module) {
  try {
    const port = Number(process.env.PORT || 3000)
    if (!Number.isInteger(port) || port < 1 || port > 65535) {
      throw new TypeError("PORT must be an integer between 1 and 65535.")
    }
    const server = createServer({ apiKey: process.env.OPENPAYGO_GENERATOR_API_KEY })
    server.on("error", () => {
      console.error("OpenPAYGO HTTP service could not start.")
      process.exitCode = 1
    })
    server.listen(port, "0.0.0.0", () => {
      console.log(`OpenPAYGO generator listening on port ${port}.`)
    })
    process.on("SIGTERM", () => server.close())
    process.on("SIGINT", () => server.close())
  } catch (error) {
    console.error(error instanceof TypeError ? error.message : "HTTP service could not start.")
    process.exitCode = 1
  }
}

module.exports = { createServer }

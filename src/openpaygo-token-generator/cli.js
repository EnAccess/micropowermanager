"use strict"

async function main() {
  let input = ""

  try {
    const { generateToken } = require("./index.js")

    for await (const chunk of process.stdin) {
      input += chunk.toString("utf8")
      if (Buffer.byteLength(input, "utf8") > 4096) {
        throw new TypeError("Input must not exceed 4096 bytes.")
      }
    }

    let request
    try {
      request = JSON.parse(input)
    } catch {
      throw new TypeError("Input must be valid JSON.")
    }

    process.stdout.write(`${JSON.stringify(generateToken(request))}\n`)
  } catch (error) {
    const message = error instanceof TypeError ? error.message : "Token generation failed."
    process.stderr.write(`${JSON.stringify({ error: message })}\n`)
    process.exitCode = 1
  }
}

main()

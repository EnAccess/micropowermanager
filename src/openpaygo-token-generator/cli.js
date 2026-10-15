"use strict"

async function main() {
  let input = ""
  let generator

  try {
    generator = require("./index.js")

    for await (const chunk of process.stdin) {
      input += chunk.toString("utf8")
      if (Buffer.byteLength(input, "utf8") > 4096) {
        throw new generator.InputValidationError("Input must not exceed 4096 bytes.")
      }
    }

    let request
    try {
      request = JSON.parse(input)
    } catch {
      throw new generator.InputValidationError("Input must be valid JSON.")
    }

    process.stdout.write(`${JSON.stringify(generator.generateToken(request))}\n`)
  } catch (error) {
    const message =
      generator && error instanceof generator.InputValidationError
        ? error.message
        : "Token generation failed."
    process.stderr.write(`${JSON.stringify({ error: message })}\n`)
    process.exitCode = 1
  }
}

main()

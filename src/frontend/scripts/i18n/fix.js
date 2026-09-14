"use strict"

const { listLocaleFiles, readLocaleFile, writeLocaleFile } = require("./lib.js")

for (const fileName of listLocaleFiles()) {
  writeLocaleFile(fileName, readLocaleFile(fileName))
  console.log(`${fileName}: sorted and formatted`)
}

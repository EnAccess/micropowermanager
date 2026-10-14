const fs = require('fs');
const path = require('path');
const assert = require("node:assert/strict");
const {Encoder} = require("openpaygo");

const encoder = new Encoder()


const ADD_TIME = 1
const SET_TIME = 2
const DISABLE_PAYG = 3
const COUNTER_SYNC = 4

const KEYS = ["bc41ec9530f6dac86b1a29ab82edc5fb",
  "00112233445566778899aabbccddeeff"]

const STARTING_CODES = [516959010, null]
const COUNTS = [0,1,2,3,4,7,10, 100]

const STANDARD_VALUES = [1, 30, 995]
const EXTENDED_VALUE = [1, 30, 999999]

function casesFor(extendToken) {
    const values = extendToken ? EXTENDED_VALUE : STANDARD_VALUES
    return [
        ...values.map((v) => [ADD_TIME, v]),
        ...values.map((v) => [SET_TIME, v]),
        [DISABLE_PAYG, undefined],
        [COUNTER_SYNC, undefined],
    ]
}

const vectors = []
const skipped = []

for(const secretKeyHex of KEYS) {
    for(const startingCode of STARTING_CODES) {
        for(const count of COUNTS) {
            for(const extendToken of [false, true]) {
                for(const restrictDigitSet of [false, true]) {
                    for(const [tokenType, value] of casesFor(extendToken)) {
                        const input = { tokenType, secretKeyHex, count, restrictDigitSet, extendToken}
                        if(startingCode !== null) input.startingCode = startingCode
                        if(value !== undefined) input.value = value

                        let result
                        try {
                            result = encoder.generateToken(input)
                        } catch(e) {
                            skipped.push({input: {...input, startingCode}, libraryMessage: e.message })
                            continue
                        }

                        vectors.push({
                            input: { ...input, startingCode: startingCode},
                            expected: {token: result.finalToken, newCount: result.newCount}
                        })
                    }
                }
            }
        }
    }
}

const goodKey = KEYS[0]
const errorInputs = [
    ["value too high (standard)", {tokenType: ADD_TIME, secretKeyHex:goodKey, count: 3, startingCode: 516959010, value: 996}],
    ["value too high (extended)", { tokenType: ADD_TIME, secretKeyHex: goodKey, count: 3, startingCode: 516959010, value: 1000000, extendToken: true }],
    ["add-time without a value", { tokenType: ADD_TIME, secretKeyHex: goodKey, count: 3, startingCode: 516959010 }],
    ["value given to a disable token", { tokenType: DISABLE_PAYG, secretKeyHex: goodKey, count: 3, startingCode: 516959010, value: 1 }],
    ["unsupported token type", { tokenType: 9, secretKeyHex: goodKey, count: 3, startingCode: 516959010 }],
    ["key too short", { tokenType: ADD_TIME, secretKeyHex: "abcd", count: 3, startingCode: 516959010, value: 1 }],
    ["key is not hex", { tokenType: ADD_TIME, secretKeyHex: "zz41ec9530f6dac86b1a29ab82edc5fb", count: 3, startingCode: 516959010, value: 1 }],
]

const errors = errorInputs.map(([name, input]) => {
    try {
        encoder.generateToken(input)
    } catch(e) {
        return { name, input, expectedError: true, libraryMessage: e.message }
    }
    throw new Error(`Expected "${name}" to fail, but it produced a token`)
})

const output = {
    meta: {
        generatedWith: "openpaygo (npm) " + require("openpaygo/package.json").version,
        tokenTypes: {ADD_TIME, SET_TIME, DISABLE_PAYG, COUNTER_SYNC},
        notes: [
            "token is a STRING; it can start with 0 and must never be stored as an integer.",
            "input.count is the last count; expected.newCount is the count the token uses and the value to store next.",
            "startingCode null = omit it and derive it from the key.",
            "Test keys only.",
        ],
    },

    vectors,
    errors,
    skipped,
}

const file = path.join(__dirname, "vectors.json")
if(process.argv.includes("--check")) {
    const checkedInOutput = JSON.parse(fs.readFileSync(file, "utf8"))
    assert.deepEqual(output, checkedInOutput, "Generated vectors differ from vectors.json; regenerate and review the fixture.")
    console.log(`Verified ${vectors.length} vectors, ${errors.length} error cases, and ${skipped.length} skipped library cases.`)
} else {
    fs.writeFileSync(file, JSON.stringify(output, null, 1))
    console.log(`Wrote ${vectors.length} vectors, ${errors.length} error cases, and ${skipped.length} skipped library cases to vectors.json.`)
}
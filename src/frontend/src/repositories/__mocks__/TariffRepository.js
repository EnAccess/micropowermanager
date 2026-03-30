const tariffCreateResponse = require("./responses/tariff/tariffCreate.json")
const tariffGetResponse = require("./responses/tariff/tariffGet.json")
const tariffListResponse = require("./responses/tariff/tariffList.json")
export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(tariffListResponse))
    })
  },
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(tariffCreateResponse))
    })
  },
  get() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(tariffGetResponse))
    })
  },
  update() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(tariffGetResponse))
    })
  },
}

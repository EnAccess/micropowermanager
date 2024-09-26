const cityListResponse = require("./responses/city/cityList.json")
const cityCreateResponse = require("./responses/city/cityCreate.json")

export default {
  list() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(cityListResponse))
    })
  },
  create() {
    return new Promise((resolve) => {
      process.nextTick(() => resolve(cityCreateResponse))
    })
  },
}
